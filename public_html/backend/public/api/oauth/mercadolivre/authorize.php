<?php
/**
 * Endpoint: Iniciar autorização OAuth Mercado Livre
 *
 * Redireciona usuário para autorizar a aplicação no Mercado Livre
 *
 * Método: GET
 * Autenticação: Requerida
 */

// NÃO usar bootstrap.php para evitar conflito com Content-Type: application/json
// Carregar apenas o necessário

// Autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Carregar .env
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../../');
$dotenv->load();

use App\Services\MercadoLivreOAuthService;
use App\Middleware\AuthMiddleware;

// Logging detalhado
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

    // Mostrar erro detalhado se APP_DEBUG = true
    if (getenv('APP_DEBUG') === 'true' || getenv('APP_ENV') === 'development') {
        echo "<h1>Erro ao iniciar OAuth</h1>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "<p><a href='/frontend/inteligencia-precos.html'>Voltar</a></p>";
    } else {
        header('Location: /frontend/inteligencia-precos.html?error=oauth_init_failed&msg=' . urlencode($e->getMessage()));
    }
    exit;
}
