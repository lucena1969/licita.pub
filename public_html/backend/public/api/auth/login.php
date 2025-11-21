<?php

/**
 * Endpoint: POST /api/auth/login.php
 * Descrição: Fazer login
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

// Aplicar CORS
CorsMiddleware::handle();

// Aceitar apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Método não permitido. Use POST.'],
    ]);
    exit;
}

// Obter dados do corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Dados inválidos. Envie JSON válido.'],
    ]);
    exit;
}

// Validar campos obrigatórios
if (empty($data['email']) || empty($data['senha'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Email e senha são obrigatórios'],
    ]);
    exit;
}

// Fazer login
try {
    $authService = new AuthService();
    $ip = AuthMiddleware::getClientIp();
    $userAgent = AuthMiddleware::getUserAgent();

    $result = $authService->login($data['email'], $data['senha'], $ip, $userAgent);

    if ($result['success']) {
        // Definir cookie com session_id (httpOnly e secure)
        $expiresIn = $result['expires_in'];
        setcookie(
            'session_id',
            $result['session_id'],
            [
                'expires' => time() + $expiresIn,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        http_response_code(200);
    } else {
        http_response_code(401);
    }

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Erro interno do servidor: ' . $e->getMessage()],
    ]);
}
