#!/usr/bin/env php
<?php
/**
 * Script para verificar duplicatas de pncp_id
 * Executar antes de adicionar índice UNIQUE
 */

require_once __DIR__ . '/../src/Config/Config.php';
require_once __DIR__ . '/../src/Config/Database.php';

use App\Config\Config;
use App\Config\Database;

Config::load();

echo "\n🔍 VERIFICANDO DUPLICATAS DE PNCP_ID\n";
echo str_repeat('=', 60) . "\n\n";

try {
    $db = Database::getConnection();

    // Verificar duplicatas
    $sql = "SELECT pncp_id, COUNT(*) as duplicatas,
            GROUP_CONCAT(id) as ids,
            GROUP_CONCAT(created_at) as datas_criacao
            FROM licitacoes
            GROUP BY pncp_id
            HAVING COUNT(*) > 1
            ORDER BY duplicatas DESC";

    $stmt = $db->query($sql);
    $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicatas)) {
        echo "✅ NENHUMA DUPLICATA ENCONTRADA!\n";
        echo "   O banco está limpo e pronto para receber o índice UNIQUE.\n\n";
        exit(0);
    }

    echo "⚠️  DUPLICATAS ENCONTRADAS!\n\n";
    echo "Total de pncp_ids duplicados: " . count($duplicatas) . "\n\n";

    $totalRegistrosDuplicados = 0;

    foreach ($duplicatas as $dup) {
        $totalRegistrosDuplicados += ($dup['duplicatas'] - 1); // -1 porque vamos manter 1

        echo "PNCP ID: {$dup['pncp_id']}\n";
        echo "  Quantidade: {$dup['duplicatas']} registros\n";
        echo "  IDs: {$dup['ids']}\n";
        echo "  Datas: {$dup['datas_criacao']}\n";
        echo "\n";
    }

    echo str_repeat('-', 60) . "\n";
    echo "📊 RESUMO:\n";
    echo "  • PNCP IDs duplicados: " . count($duplicatas) . "\n";
    echo "  • Registros que serão removidos: {$totalRegistrosDuplicados}\n";
    echo str_repeat('=', 60) . "\n\n";

    echo "💡 PRÓXIMO PASSO:\n";
    echo "   Execute o script de limpeza para remover duplicatas:\n";
    echo "   php backend/database/limpar_duplicatas.php\n\n";

    exit(1); // Exit com erro para indicar que há duplicatas

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
