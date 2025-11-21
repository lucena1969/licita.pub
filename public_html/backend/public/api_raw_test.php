<?php
/**
 * Teste RAW - SQL direto, sem passar pelo controller
 */

require_once __DIR__ . '/../src/Config/Config.php';
require_once __DIR__ . '/../src/Config/Database.php';

use App\Config\Config;
use App\Config\Database;

Config::load();

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getConnection();

    // Query EXATAMENTE igual ao controller
    $sql = "
        SELECT
            pncp_id,
            numero,
            modalidade,
            objeto,
            situacao,
            data_publicacao,
            valor_estimado,
            cnpj_orgao,
            municipio,
            uf
        FROM licitacoes
        ORDER BY sincronizado_em DESC
        LIMIT 3
    ";

    $stmt = $db->query($sql);
    $licitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'total' => count($licitacoes),
        'data' => $licitacoes,
        'debug' => []
    ];

    // Debug de cada registro
    foreach ($licitacoes as $lic) {
        $response['debug'][] = [
            'pncp_id' => $lic['pncp_id'],
            'tem_objeto' => isset($lic['objeto']),
            'objeto_vazio' => empty($lic['objeto']),
            'objeto_length' => strlen($lic['objeto'] ?? ''),
            'objeto_preview' => substr($lic['objeto'] ?? '', 0, 50)
        ];
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
