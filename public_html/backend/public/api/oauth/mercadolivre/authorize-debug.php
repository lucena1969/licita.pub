<?php
/**
 * Versão DEBUG do authorize.php
 * Salva logs em arquivo acessível
 */

// Arquivo de log customizado
$logFile = __DIR__ . '/../../../../../../debug-oauth.log';

function debugLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

debugLog("=== INÍCIO DO AUTHORIZE ===");

try {
    debugLog("Passo 1: Verificando autoloader");

    $autoloadPath = __DIR__ . '/../../../../vendor/autoload.php';
    debugLog("Autoload path: $autoloadPath");

    if (!file_exists($autoloadPath)) {
        debugLog("ERRO: Autoload não encontrado!");
        throw new Exception("Autoload não encontrado");
    }

    require_once $autoloadPath;
    debugLog("✓ Autoload carregado");

    debugLog("Passo 2: Carregando Dotenv");

    use Dotenv\Dotenv;
    $envPath = __DIR__ . '/../../../../';
    debugLog("ENV path: $envPath");

    $dotenv = Dotenv::createImmutable($envPath);
    $dotenv->load();
    debugLog("✓ Dotenv carregado");

    $mlClientId = $_ENV['ML_CLIENT_ID'] ?? 'NÃO ENCONTRADO';
    debugLog("ML_CLIENT_ID: $mlClientId");

    debugLog("Passo 3: Carregando AuthMiddleware");

    use App\Middleware\AuthMiddleware;
    debugLog("✓ AuthMiddleware carregado");

    debugLog("Passo 4: Verificando sessão");

    $usuario = AuthMiddleware::verificar();

    if (!$usuario) {
        debugLog("Usuário NÃO autenticado - redirecionando para login");
        header('Location: /frontend/login.html?redirect=' . urlencode('/frontend/inteligencia-precos.html'));
        exit;
    }

    debugLog("✓ Usuário autenticado: ID = " . $usuario->id);

    debugLog("Passo 5: Carregando MercadoLivreOAuthService");

    use App\Services\MercadoLivreOAuthService;
    $oauthService = new MercadoLivreOAuthService();
    debugLog("✓ MercadoLivreOAuthService instanciado");

    debugLog("Passo 6: Gerando URL de autorização");

    $authUrl = $oauthService->getAuthorizationUrl($usuario->id);
    debugLog("✓ URL gerada: $authUrl");

    debugLog("Passo 7: Redirecionando para Mercado Livre");

    header('Location: ' . $authUrl);
    debugLog("✓ Redirect enviado");
    exit;

} catch (\Exception $e) {
    debugLog("❌ ERRO: " . $e->getMessage());
    debugLog("Arquivo: " . $e->getFile());
    debugLog("Linha: " . $e->getLine());
    debugLog("Stack: " . $e->getTraceAsString());

    // Mostrar erro na tela
    echo "<h1>Erro OAuth</h1>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<hr>";
    echo "<p>Log salvo em: debug-oauth.log</p>";
    echo "<p><a href='/view-debug-log.php'>Ver Log Completo</a></p>";
    exit;
}

debugLog("=== FIM DO AUTHORIZE (SUCESSO) ===");
