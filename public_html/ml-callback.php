<?php
/**
 * Endpoint OAuth Mercado Livre - Callback
 * Localização: /ml-callback.php (raiz do site)
 */

// IMPORTANTE: Iniciar output buffering ANTES de qualquer coisa
ob_start();

// 1. Carregar .env PRIMEIRO
$envFile = __DIR__ . '/backend/.env';
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

// 2. Autoloader
require_once __DIR__ . '/backend/vendor/autoload.php';

// 3. Processar callback
try {
    $usuario = \App\Middleware\AuthMiddleware::verificar();

    if (!$usuario) {
        ob_end_clean();
        header('Location: /frontend/login.html?error=session_expired');
        exit;
    }

    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    $error = $_GET['error'] ?? null;

    if ($error) {
        error_log("[OAuth ML] Erro na autorização: {$error}");
        ob_end_clean();
        header('Location: /frontend/inteligencia-precos.html?oauth=denied');
        exit;
    }

    if (!$code || !$state) {
        error_log("[OAuth ML] Código ou state ausente");
        ob_end_clean();
        header('Location: /frontend/inteligencia-precos.html?oauth=invalid');
        exit;
    }

    $oauthService = new \App\Services\MercadoLivreOAuthService();
    $result = $oauthService->exchangeCodeForToken($code, $state, $usuario->id);

    if (!$result['success']) {
        error_log("[OAuth ML] Erro ao trocar código: " . json_encode($result));
        ob_end_clean();
        header('Location: /frontend/inteligencia-precos.html?oauth=token_error');
        exit;
    }

    ob_end_clean();
    header('Location: /frontend/inteligencia-precos.html?oauth=success');
    exit;

} catch (\Exception $e) {
    error_log("[OAuth ML] Exception no callback: " . $e->getMessage());
    ob_end_clean();
    header('Location: /frontend/inteligencia-precos.html?oauth=error&msg=' . urlencode($e->getMessage()));
    exit;
}
