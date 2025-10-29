<?php
/**
 * Teste do endpoint /api/licitacoes/buscar.php
 * Simula uma requisição GET com filtro UF=SC
 */

// Simular parâmetros GET
$_GET['uf'] = 'SC';
$_GET['pagina'] = 1;
$_GET['limite'] = 20;

echo "=== TESTE DO ENDPOINT /api/licitacoes/buscar.php ===\n\n";
echo "Parâmetros enviados:\n";
print_r($_GET);
echo "\n";

// Capturar o output do endpoint
ob_start();

try {
    require_once __DIR__ . '/api/licitacoes/buscar.php';
} catch (Exception $e) {
    ob_end_clean();
    echo "ERRO CAPTURADO:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit;
}

$output = ob_get_clean();

echo "Resposta do endpoint:\n";
echo $output;
echo "\n";

// Decodificar JSON para análise
$response = json_decode($output, true);

if ($response) {
    echo "\n=== ANÁLISE DA RESPOSTA ===\n";
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";

    if ($response['success']) {
        echo "Total de resultados: " . count($response['data']) . "\n";
        echo "Total geral: " . $response['paginacao']['total'] . "\n";
        echo "\n✅ ENDPOINT FUNCIONANDO!\n";

        if (!empty($response['data'])) {
            echo "\nPrimeiro resultado:\n";
            print_r($response['data'][0]);
        }
    } else {
        echo "Erro: " . $response['error'] . "\n";
        echo "Mensagem: " . $response['message'] . "\n";
        echo "\n❌ ENDPOINT COM ERRO!\n";
    }
} else {
    echo "\n❌ RESPOSTA NÃO É JSON VÁLIDO!\n";
}
