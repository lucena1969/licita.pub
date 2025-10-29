<?php
/**
 * Teste do endpoint /api/licitacoes/detalhes.php completo
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste Endpoint Completo: /api/licitacoes/detalhes.php</h1>";

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    // Pegar um ID válido do banco
    $db = Database::getConnection();
    $stmt = $db->query("SELECT pncp_id FROM licitacoes LIMIT 1");
    $primeira = $stmt->fetch();
    $pncpId = $primeira['pncp_id'];

    echo "<h2>ID de teste: $pncpId</h2>";

    // Simular requisição GET
    $_GET['id'] = $pncpId;

    echo "<h2>Simulando requisição ao endpoint...</h2>";
    echo "<p>GET['id'] = $pncpId</p>";

    // Capturar output
    ob_start();

    try {
        require __DIR__ . '/api/licitacoes/detalhes.php';
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "<h2 style='color: red;'>❌ ERRO CAPTURADO:</h2>";
        echo "<pre>";
        echo "Tipo: " . get_class($e) . "\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString();
        echo "</pre>";
        exit;
    }

    $output = ob_get_clean();

    echo "<h2>Resposta do endpoint:</h2>";
    echo "<pre>$output</pre>";

    // Tentar decodificar JSON
    $response = json_decode($output, true);

    if ($response) {
        echo "<h2>JSON decodificado:</h2>";
        echo "<pre>";
        print_r($response);
        echo "</pre>";

        if ($response['success']) {
            echo "<h3 style='color: green;'>✅ ENDPOINT FUNCIONANDO!</h3>";
        } else {
            echo "<h3 style='color: orange;'>⚠️ Endpoint retornou erro</h3>";
            echo "<p>Erro: " . ($response['error'] ?? 'N/A') . "</p>";
            echo "<p>Mensagem: " . ($response['message'] ?? 'N/A') . "</p>";
        }
    } else {
        echo "<h3 style='color: red;'>❌ Resposta não é JSON válido!</h3>";
        echo "<p>Erro JSON: " . json_last_error_msg() . "</p>";
    }

} catch (\Throwable $e) {
    echo "<h2 style='color: red;'>❌ ERRO GERAL:</h2>";
    echo "<pre>";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
