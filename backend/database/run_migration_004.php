<?php
/**
 * Script para executar Migration 004 - Adicionar UNIQUE constraint em pncp_id
 * Uso: php run_migration_004.php
 */

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("Arquivo .env não encontrado!\n");
}

// Função simples para ler .env
function loadEnv($file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Comentário
        if (strpos($line, '=') === false) continue; // Linha inválida
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

$envVars = loadEnv($envFile);

try {
    echo "=== Executando Migration 004 - Adicionar UNIQUE pncp_id ===\n\n";

    // Conectar ao banco
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        $envVars['DB_HOST'] ?? 'localhost',
        $envVars['DB_PORT'] ?? '3306',
        $envVars['DB_DATABASE']
    );

    $db = new PDO(
        $dsn,
        $envVars['DB_USERNAME'],
        $envVars['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $db->exec("SET NAMES utf8mb4");

    echo "✓ Conectado ao banco: {$envVars['DB_DATABASE']}\n\n";

    // Verificar quantas duplicatas existem ANTES
    echo "=== Verificando duplicatas ANTES da migration ===\n";
    $stmt = $db->query("
        SELECT pncp_id, COUNT(*) as total
        FROM licitacoes
        WHERE pncp_id IS NOT NULL
        GROUP BY pncp_id
        HAVING COUNT(*) > 1
        ORDER BY total DESC
        LIMIT 10
    ");
    $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($duplicatas) > 0) {
        echo "⚠️  Encontradas " . count($duplicatas) . " pncp_id duplicados:\n";
        foreach ($duplicatas as $dup) {
            echo "  - {$dup['pncp_id']}: {$dup['total']} ocorrências\n";
        }
        echo "\n";
    } else {
        echo "✓ Nenhuma duplicata encontrada!\n\n";
    }

    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/migrations/004_adicionar_unique_pncp_id.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo de migration não encontrado: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Remover comentários e linhas vazias
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) &&
                   !preg_match('/^--/', $stmt) &&
                   !preg_match('/^\/\*/', $stmt);
        }
    );

    // Executar cada statement
    echo "=== Executando migration ===\n";
    $db->beginTransaction();

    $executedCount = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        // Pular comandos SELECT de verificação (não modificam dados)
        if (preg_match('/^SELECT/i', $statement)) {
            echo "⏩ Pulando SELECT de verificação\n";
            continue;
        }

        echo "Executando: " . substr($statement, 0, 80) . "...\n";

        try {
            $db->exec($statement);
            $executedCount++;
            echo "✅ Sucesso\n\n";
        } catch (PDOException $e) {
            // Se erro for "Duplicate key name", apenas avisar
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠️  UNIQUE index já existe (pulando): " . $e->getMessage() . "\n\n";
                continue;
            }
            throw $e;
        }
    }

    $db->commit();

    echo "\n=== Migration 004 concluída com sucesso! ===\n";
    echo "Total de comandos executados: $executedCount\n\n";

    // Verificar se o índice foi criado
    echo "=== Verificando índice UNIQUE ===\n";
    $stmt = $db->query("
        SHOW INDEX FROM licitacoes
        WHERE Key_name = 'idx_pncp_id_unique'
    ");

    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($indices) > 0) {
        echo "✓ Índice UNIQUE 'idx_pncp_id_unique' criado com sucesso!\n";
        foreach ($indices as $idx) {
            echo "  - Campo: {$idx['Column_name']}, Não-único: {$idx['Non_unique']}\n";
        }
    } else {
        echo "⚠️  Índice UNIQUE não encontrado (pode já existir com outro nome)\n";
    }

    // Verificar duplicatas DEPOIS
    echo "\n=== Verificando duplicatas DEPOIS da migration ===\n";
    $stmt = $db->query("
        SELECT pncp_id, COUNT(*) as total
        FROM licitacoes
        WHERE pncp_id IS NOT NULL
        GROUP BY pncp_id
        HAVING COUNT(*) > 1
        ORDER BY total DESC
        LIMIT 10
    ");
    $duplicatasDepois = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($duplicatasDepois) > 0) {
        echo "❌ ERRO: Ainda existem duplicatas!\n";
        foreach ($duplicatasDepois as $dup) {
            echo "  - {$dup['pncp_id']}: {$dup['total']} ocorrências\n";
        }
    } else {
        echo "✅ Nenhuma duplicata encontrada! UPSERT pode ser usado com segurança.\n";
    }

    // Estatísticas finais
    echo "\n=== Estatísticas da tabela licitacoes ===\n";
    $stmt = $db->query("
        SELECT
            COUNT(*) as total,
            COUNT(DISTINCT pncp_id) as pncp_unicos,
            COUNT(*) - COUNT(DISTINCT pncp_id) as duplicatas_removidas
        FROM licitacoes
        WHERE pncp_id IS NOT NULL
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Total de registros: {$stats['total']}\n";
    echo "PNCP_IDs únicos: {$stats['pncp_unicos']}\n";
    echo "Duplicatas removidas: {$stats['duplicatas_removidas']}\n";

    echo "\n✅ Migration 004 executada com sucesso!\n";
    echo "✅ Sistema pronto para usar UPSERT na sincronização PNCP!\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
