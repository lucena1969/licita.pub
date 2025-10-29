<?php
/**
 * Teste do endpoint detalhes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste Endpoint Detalhes</h1>";

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Controllers\LicitacaoController;
use Dotenv\Dotenv;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    echo "<h2>✓ .env carregado</h2>";

    // ID de teste (pegar o primeiro da tabela)
    $db = Database::getConnection();
    $stmt = $db->query("SELECT pncp_id FROM licitacoes LIMIT 1");
    $primeira = $stmt->fetch();
    $pncpId = $primeira['pncp_id'];

    echo "<h2>ID de teste: $pncpId</h2>";

    // Testar controller direto (sem middleware)
    echo "<h2>Testando LicitacaoController->detalhes()...</h2>";

    $controller = new LicitacaoController();

    // Criar objeto request vazio
    $request = new stdClass();
    $request->usuario = null;
    $request->ip = '127.0.0.1';

    $resultado = $controller->detalhes($pncpId, $request);

    echo "<h3>Resultado:</h3>";
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";

    if ($resultado['success']) {
        echo "<h3 style='color: green;'>✅ CONTROLLER FUNCIONANDO!</h3>";

        echo "<h4>Dados da licitação:</h4>";
        echo "<table border='1' cellpadding='5'>";
        foreach ($resultado['data'] as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                echo "<tr>";
                echo "<td><strong>$key</strong></td>";
                echo "<td>" . substr($value, 0, 100) . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<h3 style='color: red;'>❌ ERRO NO CONTROLLER</h3>";
        echo "<p>Erro: " . $resultado['error'] . "</p>";
        echo "<p>Mensagem: " . $resultado['message'] . "</p>";
    }

} catch (\Throwable $e) {
    echo "<h2 style='color: red;'>❌ ERRO:</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
