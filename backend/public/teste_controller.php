<?php
/**
 * Teste direto do LicitacaoController
 */

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste do LicitacaoController->buscar()</h1>";

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\LicitacaoController;
use Dotenv\Dotenv;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    echo "<h2>✓ .env carregado</h2>";

    // Simular parâmetros GET
    $_GET['uf'] = 'SC';
    $_GET['pagina'] = 1;
    $_GET['limite'] = 20;

    echo "<h2>Parâmetros:</h2>";
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";

    // Instanciar controller
    $controller = new LicitacaoController();

    echo "<h2>✓ Controller instanciado</h2>";

    // Executar busca
    $resultado = $controller->buscar();

    echo "<h2>Resultado:</h2>";
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";

    if ($resultado['success']) {
        echo "<h3 style='color: green;'>✓ Busca executada com sucesso!</h3>";
        echo "<p>Total de resultados: " . count($resultado['data']) . "</p>";
        echo "<p>Total geral: " . $resultado['paginacao']['total'] . "</p>";
    } else {
        echo "<h3 style='color: red;'>✗ Erro na busca</h3>";
        echo "<p>Mensagem: " . ($resultado['message'] ?? 'N/A') . "</p>";
        echo "<p>Erro: " . ($resultado['error'] ?? 'N/A') . "</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERRO CAPTURADO:</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
