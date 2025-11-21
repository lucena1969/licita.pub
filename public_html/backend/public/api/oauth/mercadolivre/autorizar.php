<?php
/**
 * Endpoint OAuth Mercado Livre - Autorização
 */

// Carregar .env MANUALMENTE
$envFile = __DIR__ . '/../../../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
            putenv(trim($key) . "=" . trim($value, '"\''));
        }
    }
}

// Autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

try {
    $usuario = \App\Middleware\AuthMiddleware::verificar();

    if (!$usuario) {
        header('Location: /frontend/login.html?redirect=' . urlencode('/frontend/inteligencia-precos.html'));
        exit;
    }

    $oauthService = new \App\Services\MercadoLivreOAuthService();
    $authUrl = $oauthService->getAuthorizationUrl($usuario->id);

    header('Location: ' . $authUrl);
    exit;

} catch (\Exception $e) {
    error_log("[OAuth ML] Erro: " . $e->getMessage());
    header('Location: /frontend/inteligencia-precos.html?error=oauth_failed&msg=' . urlencode($e->getMessage()));
    exit;
}
