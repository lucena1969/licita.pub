<?php
/**
 * Teste passo a passo - para identificar qual linha causa erro
 */

// Desabilitar output buffering
ob_implicit_flush(true);
ob_end_flush();

echo "<!DOCTYPE html><html><head><title>Teste Passo a Passo</title></head><body>";
echo "<h1>Teste Passo a Passo</h1>";
echo "<pre>";

try {
    echo "1. Iniciando...\n";
    flush();

    echo "2. Verificando autoloader...\n";
    flush();

    $autoloadPath = __DIR__ . '/../../../../vendor/autoload.php';
    echo "   Path: $autoloadPath\n";
    flush();

    if (!file_exists($autoloadPath)) {
        die("   ❌ Autoload NÃO encontrado!\n");
    }
    echo "   ✓ Autoload existe\n";
    flush();

    echo "3. Carregando autoloader...\n";
    flush();

    require_once $autoloadPath;
    echo "   ✓ Autoload carregado\n";
    flush();

    echo "4. Carregando Dotenv...\n";
    flush();

    use Dotenv\Dotenv;
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../../');
    echo "   ✓ Dotenv instanciado\n";
    flush();

    echo "5. Carregando .env...\n";
    flush();

    $dotenv->load();
    echo "   ✓ .env carregado\n";
    flush();

    echo "6. Verificando variáveis...\n";
    $mlId = $_ENV['ML_CLIENT_ID'] ?? 'NÃO ENCONTRADO';
    echo "   ML_CLIENT_ID: $mlId\n";
    flush();

    echo "7. Importando AuthMiddleware...\n";
    flush();

    use App\Middleware\AuthMiddleware;
    echo "   ✓ AuthMiddleware importado\n";
    flush();

    echo "8. Chamando AuthMiddleware::verificar()...\n";
    flush();

    $usuario = AuthMiddleware::verificar();
    echo "   ✓ Método executado\n";
    flush();

    if ($usuario) {
        echo "   ✓ Usuário autenticado: ID = {$usuario->id}\n";
    } else {
        echo "   ⚠ Usuário NÃO autenticado (esperado se não fez login)\n";
    }
    flush();

    echo "9. Importando MercadoLivreOAuthService...\n";
    flush();

    use App\Services\MercadoLivreOAuthService;
    echo "   ✓ MercadoLivreOAuthService importado\n";
    flush();

    echo "10. Instanciando MercadoLivreOAuthService...\n";
    flush();

    $oauthService = new MercadoLivreOAuthService();
    echo "   ✓ MercadoLivreOAuthService instanciado\n";
    flush();

    if ($usuario) {
        echo "11. Gerando URL de autorização...\n";
        flush();

        $authUrl = $oauthService->getAuthorizationUrl($usuario->id);
        echo "   ✓ URL gerada!\n";
        echo "   URL: $authUrl\n";
    } else {
        echo "11. Gerando URL com user_id fake (1)...\n";
        flush();

        $authUrl = $oauthService->getAuthorizationUrl(1);
        echo "   ✓ URL gerada!\n";
        echo "   URL: $authUrl\n";
    }
    flush();

    echo "\n✅ TODOS OS PASSOS EXECUTADOS COM SUCESSO!\n";
    echo "\nO código está funcionando. Problema pode ser no tratamento de headers/redirects.\n";

} catch (Exception $e) {
    echo "\n❌ ERRO CAPTURADO!\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
} catch (Error $e) {
    echo "\n❌ FATAL ERROR CAPTURADO!\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
echo "</body></html>";
