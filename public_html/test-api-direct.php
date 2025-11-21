<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DIRETO DA API ===\n\n";

// Simular requisição GET
$_GET['q'] = 'notebook';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Parâmetros: q=notebook\n\n";
echo "--- Iniciando inclusão do arquivo ---\n\n";

// Capturar output
ob_start();

try {
    chdir('public_html');
    include 'backend/public/api/inteligencia/buscar-precos-governo.php';
    $output = ob_get_clean();

    echo "--- OUTPUT DA API ---\n";
    echo $output;
    echo "\n\n--- FIM OUTPUT ---\n";

    // Tentar decodificar JSON
    $json = json_decode($output, true);
    if ($json) {
        echo "\n✅ JSON válido!\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['error'])) {
            echo "Erro: " . $json['error'] . "\n";
        }
        if (isset($json['catmat'])) {
            echo "CATMAT encontrado: " . $json['catmat']['codigo'] . "\n";
        }
    } else {
        echo "\n❌ JSON inválido\n";
        echo "Erro decode: " . json_last_error_msg() . "\n";
    }

} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
