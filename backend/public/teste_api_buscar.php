<?php
/**
 * Teste direto do endpoint buscar.php
 */

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste do Endpoint /api/licitacoes/buscar.php</h1>";

// Simular requisição GET com filtro SC
$_GET['uf'] = 'SC';

echo "<h2>Parâmetros:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>Resultado:</h2>";

try {
    // Incluir o endpoint
    require_once __DIR__ . '/api/licitacoes/buscar.php';
} catch (Exception $e) {
    echo "<h3 style='color: red;'>ERRO CAPTURADO:</h3>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
