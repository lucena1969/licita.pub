<?php
/**
 * Teste do LimiteConsultaMiddleware
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste LimiteConsultaMiddleware</h1>";

require_once __DIR__ . '/../vendor/autoload.php';

use App\Middleware\LimiteConsultaMiddleware;
use App\Config\Database;
use Dotenv\Dotenv;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    echo "<h2>✓ .env carregado</h2>";

    // Testar instanciação do middleware
    echo "<h2>Testando instanciação do middleware...</h2>";

    $middleware = new LimiteConsultaMiddleware();

    echo "<h3 style='color: green;'>✅ Middleware instanciado com sucesso!</h3>";

    // Testar execução do middleware
    echo "<h2>Testando execução do handle()...</h2>";

    $request = new stdClass();

    $executou = false;
    $middleware->handle($request, function($req) use (&$executou) {
        $executou = true;
        echo "<h3 style='color: green;'>✅ Callback executado com sucesso!</h3>";
        echo "<h4>Dados do request:</h4>";
        echo "<pre>";
        print_r($req);
        echo "</pre>";
        return $req;
    });

    if ($executou) {
        echo "<h3 style='color: green;'>✅ MIDDLEWARE FUNCIONANDO PERFEITAMENTE!</h3>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Middleware bloqueou (limite atingido ou erro)</h3>";
    }

} catch (\Throwable $e) {
    echo "<h2 style='color: red;'>❌ ERRO:</h2>";
    echo "<pre>";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
