<?php
/**
 * Endpoint: Callback OAuth Mercado Livre
 *
 * Recebe código de autorização e troca por access token
 *
 * Método: GET
 * Parâmetros:
 *   - code: Código de autorização
 *   - state: State para validação CSRF
 */

// NÃO usar bootstrap.php para evitar conflito com Content-Type
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Carregar .env
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../../');
$dotenv->load();

use App\Services\MercadoLivreOAuthService;
use App\Middleware\AuthMiddleware;

try {
    // Verificar autenticação
    $usuario = AuthMiddleware::verificar();

    if (!$usuario) {
        header('Location: /frontend/login.html?error=session_expired');
        exit;
    }

    // Obter parâmetros
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    $error = $_GET['error'] ?? null;

    // Verificar se houve erro na autorização
    if ($error) {
        error_log("[OAuth ML] Erro na autorização: {$error}");
        header('Location: /frontend/inteligencia-precos.html?oauth=denied');
        exit;
    }

    // Validar parâmetros
    if (!$code || !$state) {
        error_log("[OAuth ML] Código ou state ausente");
        header('Location: /frontend/inteligencia-precos.html?oauth=invalid');
        exit;
    }

    // Trocar código por token
    $oauthService = new MercadoLivreOAuthService();
    $result = $oauthService->exchangeCodeForToken($code, $state, $usuario->id);

    if (!$result['success']) {
        error_log("[OAuth ML] Erro ao trocar código: " . json_encode($result));
        header('Location: /frontend/inteligencia-precos.html?oauth=token_error');
        exit;
    }

    // Sucesso! Redirecionar para página de inteligência de preços
    header('Location: /frontend/inteligencia-precos.html?oauth=success');
    exit;

} catch (\Exception $e) {
    error_log("[OAuth ML] Exception no callback: " . $e->getMessage());
    header('Location: /frontend/inteligencia-precos.html?oauth=error');
    exit;
}
