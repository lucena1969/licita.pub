<?php
/**
 * Teste rápido da API - Verifica estrutura básica
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE RÁPIDO DA API ===\n\n";

// Testar apenas a estrutura básica
$_GET['q'] = 'ab'; // Menos de 3 caracteres para retornar erro rápido
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "1. Teste com parâmetro inválido (deve retornar erro 400)\n";

ob_start();
chdir('public_html');
include 'backend/public/api/inteligencia/buscar-precos-governo.php';
$output = ob_get_clean();

echo "Resposta:\n";
echo $output;
echo "\n\n";

$json = json_decode($output, true);
if ($json) {
    if (!$json['success'] && isset($json['error'])) {
        echo "✅ API funcionando! Retornou erro esperado: " . $json['error'] . "\n";
    } else {
        echo "⚠️ API retornou resposta inesperada\n";
    }
} else {
    echo "❌ Resposta não é JSON válido\n";
    echo "JSON error: " . json_last_error_msg() . "\n";
}
