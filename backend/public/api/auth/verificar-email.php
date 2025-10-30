<?php

/**
 * Endpoint: GET /api/auth/verificar-email.php?token=xxx
 * Descrição: Verificar email do usuário com token
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Middleware\CorsMiddleware;
use App\Services\AuthService;

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

// Aplicar CORS
CorsMiddleware::handle();

// Aceitar apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Método não permitido. Use GET.'],
    ]);
    exit;
}

// Validar parâmetro token
if (empty($_GET['token'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Token é obrigatório'],
    ]);
    exit;
}

// Verificar email
try {
    $authService = new AuthService();
    $result = $authService->verificarEmail($_GET['token']);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
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
