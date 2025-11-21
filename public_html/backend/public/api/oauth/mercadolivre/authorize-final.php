<?php
/**
 * Endpoint: Iniciar autorização OAuth Mercado Livre
 *
 * Redireciona usuário para autorizar a aplicação no Mercado Livre
 *
 * Método: GET
 * Autenticação: Requerida
 */

// Carregar .env MANUALMENTE (antes de tudo)
$envFile = __DIR__ . '/../../../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

use App\Services\MercadoLivreOAuthService;
use App\Middleware\AuthMiddleware;

error_log("[OAuth ML Authorize] Iniciando endpoint");

try {
    // Verificar autenticação
    error_log("[OAuth ML Authorize] Verificando autenticação");

    $usuario = AuthMiddleware::verificar();

    if (!$usuario) {
        error_log("[OAuth ML Authorize] Usuário não autenticado, redirecionando para login");
        header('Location: /frontend/login.html?redirect=' . urlencode('/frontend/inteligencia-precos.html'));
        exit;
    }

    error_log("[OAuth ML Authorize] Usuário autenticado: ID = {$usuario->id}");

    // Gerar URL de autorização
    error_log("[OAuth ML Authorize] Gerando URL de autorização");

    $oauthService = new MercadoLivreOAuthService();
    $authUrl = $oauthService->getAuthorizationUrl($usuario->id);

    error_log("[OAuth ML Authorize] URL gerada com sucesso");
    error_log("[OAuth ML Authorize] Redirecionando para: $authUrl");

    // Redirecionar para Mercado Livre
    header('Location: ' . $authUrl);
    exit;

} catch (\Exception $e) {
    error_log("[OAuth ML Authorize] ERRO: " . $e->getMessage());
    error_log("[OAuth ML Authorize] Stack: " . $e->getTraceAsString());

    // Mostrar erro detalhado
    echo "<h1>Erro ao iniciar OAuth</h1>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p><a href='/frontend/inteligencia-precos.html'>Voltar</a></p>";
    exit;
}
