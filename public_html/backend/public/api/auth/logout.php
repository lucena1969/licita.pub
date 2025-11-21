<?php

/**
 * Endpoint: POST /api/auth/logout.php
 * Descrição: Fazer logout
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

// Obter session_id
$sessionId = null;

// Tentar obter do header Authorization
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
    $sessionId = $matches[1];
}

// Tentar obter do cookie
if (!$sessionId && isset($_COOKIE['session_id'])) {
    $sessionId = $_COOKIE['session_id'];
}

if (!$sessionId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Session ID não fornecido'],
    ]);
    exit;
}

// Fazer logout
try {
    $authService = new AuthService();
    $result = $authService->logout($sessionId);

    // Remover cookie
    setcookie(
        'session_id',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    http_response_code(200);
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
