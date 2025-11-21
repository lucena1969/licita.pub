#!/usr/bin/env php
<?php
/**
 * Script para limpar duplicatas de pncp_id
 * Mantém apenas o registro mais recente de cada pncp_id duplicado
 *
 * ⚠️  ATENÇÃO: Este script irá DELETAR dados!
 *     Execute primeiro verificar_duplicatas.php para ver o que será removido
 *
 * Uso:
 *   php backend/database/limpar_duplicatas.php
 *   php backend/database/limpar_duplicatas.php --dry-run (apenas simular)
 */

require_once __DIR__ . '/../src/Config/Config.php';
require_once __DIR__ . '/../src/Config/Database.php';

use App\Config\Config;
use App\Config\Database;

Config::load();

// Verificar modo dry-run
$dryRun = in_array('--dry-run', $argv);

echo "\n🧹 LIMPEZA DE DUPLICATAS DE PNCP_ID\n";
echo str_repeat('=', 60) . "\n\n";

if ($dryRun) {
    echo "⚠️  MODO DRY-RUN ATIVADO (nenhum dado será deletado)\n\n";
}

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    // 1. Identificar duplicatas
    echo "🔍 Identificando duplicatas...\n\n";

    $sql = "SELECT pncp_id, COUNT(*) as duplicatas,
            GROUP_CONCAT(id ORDER BY created_at ASC) as todos_ids,
            MAX(id) as id_manter
            FROM licitacoes
            GROUP BY pncp_id
            HAVING COUNT(*) > 1
            ORDER BY duplicatas DESC";

    $stmt = $db->query($sql);
    $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicatas)) {
        echo "✅ NENHUMA DUPLICATA ENCONTRADA!\n";
        echo "   Nada a fazer.\n\n";
        $db->rollBack();
        exit(0);
    }

    echo "⚠️  Encontradas " . count($duplicatas) . " pncp_ids com duplicatas\n\n";

    $totalRemover = 0;
    $idsParaRemover = [];

    foreach ($duplicatas as $dup) {
        $qtdRemover = $dup['duplicatas'] - 1;
        $totalRemover += $qtdRemover;

        $todosIds = explode(',', $dup['todos_ids']);
        $idManter = $dup['id_manter'];

        echo "PNCP ID: {$dup['pncp_id']}\n";
        echo "  Total de registros: {$dup['duplicatas']}\n";
        echo "  ID que será mantido: {$idManter} (mais recente)\n";
        echo "  IDs que serão removidos: ";

        $idsRemover = array_filter($todosIds, function($id) use ($idManter) {
            return $id !== $idManter;
        });

        echo implode(', ', $idsRemover) . "\n";
        $idsParaRemover = array_merge($idsParaRemover, $idsRemover);
        echo "\n";
    }

    echo str_repeat('-', 60) . "\n";
    echo "📊 RESUMO:\n";
    echo "  • PNCP IDs duplicados: " . count($duplicatas) . "\n";
    echo "  • Registros que serão MANTIDOS: " . count($duplicatas) . "\n";
    echo "  • Registros que serão REMOVIDOS: {$totalRemover}\n";
    echo str_repeat('=', 60) . "\n\n";

    if ($dryRun) {
        echo "⚠️  DRY-RUN: Nenhum dado foi deletado.\n";
        echo "   Execute sem --dry-run para confirmar a remoção:\n";
        echo "   php backend/database/limpar_duplicatas.php\n\n";
        $db->rollBack();
        exit(0);
    }

    // Confirmar antes de deletar
    echo "⚠️  ATENÇÃO: Esta ação NÃO PODE SER DESFEITA!\n";
    echo "   Deseja continuar? (digite 'SIM' para confirmar): ";

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if ($line !== 'SIM') {
        echo "\n❌ Operação cancelada pelo usuário.\n\n";
        $db->rollBack();
        exit(1);
    }

    // 2. Executar remoção
    echo "\n🗑️  Removendo duplicatas...\n";

    if (!empty($idsParaRemover)) {
        $placeholders = str_repeat('?,', count($idsParaRemover) - 1) . '?';
        $sql = "DELETE FROM licitacoes WHERE id IN ($placeholders)";

        $stmt = $db->prepare($sql);
        $stmt->execute($idsParaRemover);

        $removidos = $stmt->rowCount();

        echo "✅ {$removidos} registros removidos com sucesso!\n\n";
    }

    // 3. Verificar resultado
    echo "🔍 Verificando resultado...\n";

    $sql = "SELECT pncp_id, COUNT(*) as duplicatas
            FROM licitacoes
            GROUP BY pncp_id
            HAVING COUNT(*) > 1";

    $stmt = $db->query($sql);
    $duplicatasRestantes = $stmt->fetchAll();

    if (empty($duplicatasRestantes)) {
        echo "✅ NENHUMA DUPLICATA RESTANTE!\n";
        echo "   Banco está limpo e pronto para o índice UNIQUE.\n\n";

        $db->commit();

        echo str_repeat('=', 60) . "\n";
        echo "✅ LIMPEZA CONCLUÍDA COM SUCESSO!\n";
        echo str_repeat('=', 60) . "\n\n";

        echo "📝 Próximo passo:\n";
        echo "   Execute a migration para adicionar o índice UNIQUE:\n";
        echo "   mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/004_adicionar_unique_pncp_id.sql\n\n";

        exit(0);

    } else {
        echo "⚠️  AINDA HÁ DUPLICATAS! (" . count($duplicatasRestantes) . ")\n";
        echo "   Execute o script novamente.\n\n";

        $db->rollBack();
        exit(1);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Nenhum dado foi alterado (rollback executado).\n\n";
    exit(1);
}
