<?php
/**
 * Teste com ID específico que está dando erro no frontend
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$idTeste = '13323274000163-2-000448/2025';

echo "<h1>Teste com ID Específico</h1>";
echo "<h2>ID: $idTeste</h2>";

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Controllers\LicitacaoController;
use App\Middleware\LimiteConsultaMiddleware;
use Dotenv\Dotenv;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    echo "<h2>✓ .env carregado</h2>";

    // 1. Verificar se o ID existe no banco
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT pncp_id FROM licitacoes WHERE pncp_id = ?");
    $stmt->execute([$idTeste]);
    $existe = $stmt->fetch();

    if ($existe) {
        echo "<h3 style='color: green;'>✅ ID existe no banco</h3>";
    } else {
        echo "<h3 style='color: red;'>❌ ID NÃO existe no banco</h3>";
        echo "<p>Tentando buscar IDs similares...</p>";

        $stmt = $db->prepare("SELECT pncp_id FROM licitacoes WHERE pncp_id LIKE ? LIMIT 5");
        $stmt->execute(['%13323274000163%']);
        $similares = $stmt->fetchAll();

        if ($similares) {
            echo "<p>IDs similares encontrados:</p><ul>";
            foreach ($similares as $sim) {
                echo "<li>{$sim['pncp_id']}</li>";
            }
            echo "</ul>";
        }
    }

    // 2. Testar o controller diretamente
    echo "<h2>Testando LicitacaoController->detalhes()...</h2>";

    $controller = new LicitacaoController();
    $request = new stdClass();
    $request->usuario = null;
    $request->ip = '127.0.0.1';

    $resultado = $controller->detalhes($idTeste, $request);

    echo "<h3>Resultado do controller:</h3>";
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";

    // 3. Testar o middleware + controller juntos
    echo "<h2>Testando Middleware + Controller...</h2>";

    $_GET['id'] = $idTeste;

    ob_start();
    try {
        require __DIR__ . '/api/licitacoes/detalhes.php';
        $output = ob_get_clean();

        echo "<h3 style='color: green;'>✅ Endpoint executado sem exceção</h3>";
        echo "<h4>Resposta:</h4>";
        echo "<pre>$output</pre>";

        $json = json_decode($output, true);
        if ($json) {
            echo "<h4>JSON decodificado:</h4>";
            echo "<pre>";
            print_r($json);
            echo "</pre>";
        }

    } catch (\Throwable $e) {
        ob_end_clean();
        echo "<h3 style='color: red;'>❌ ERRO no endpoint:</h3>";
        echo "<pre>";
        echo "Tipo: " . get_class($e) . "\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString();
        echo "</pre>";
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
