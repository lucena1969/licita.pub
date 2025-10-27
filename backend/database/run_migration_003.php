<?php
/**
 * Script para executar Migration 003
 * Uso: php run_migration_003.php
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
    echo "=== Executando Migration 003 ===\n\n";

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

    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/migrations/003_atualizar_usuarios_limites.sql';
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

        // Pular comandos DESCRIBE
        if (preg_match('/^DESCRIBE/i', $statement)) {
            echo "⏩ Pulando DESCRIBE\n";
            continue;
        }

        echo "Executando: " . substr($statement, 0, 100) . "...\n";

        try {
            $db->exec($statement);
            $executedCount++;
            echo "✅ Sucesso\n\n";
        } catch (PDOException $e) {
            // Se erro for "Duplicate column" ou "Table already exists", apenas avisar
            if (
                strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'already exists') !== false
            ) {
                echo "⚠️  Já existe (pulando): " . $e->getMessage() . "\n\n";
                continue;
            }
            throw $e;
        }
    }

    $db->commit();

    echo "\n=== Migration 003 concluída com sucesso! ===\n";
    echo "Total de comandos executados: $executedCount\n\n";

    // Verificar tabelas criadas
    echo "=== Verificando tabelas ===\n";
    $stmt = $db->query("
        SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ('usuarios', 'limites_ip', 'historico_consultas', 'sessoes')
        ORDER BY TABLE_NAME
    ");

    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tables as $table) {
        echo "✓ {$table['TABLE_NAME']}: {$table['TABLE_ROWS']} registros (criada em {$table['CREATE_TIME']})\n";
    }

    echo "\n=== Verificando campos da tabela usuarios ===\n";
    $stmt = $db->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $newColumns = ['consultas_hoje', 'primeira_consulta_em', 'limite_diario'];
    foreach ($columns as $column) {
        if (in_array($column['Field'], $newColumns)) {
            echo "✓ {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
        }
    }

    echo "\n✅ Migration 003 executada com sucesso!\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
