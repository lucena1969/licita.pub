<?php

/**
 * Endpoint: POST /api/auth/reenviar-verificacao.php
 * Descrição: Reenviar email de verificação
 * Body: { "email": "usuario@example.com" }
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Middleware\CorsMiddleware;
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

// Validar campo email
if (empty($data['email'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Email é obrigatório'],
    ]);
    exit;
}

// Reenviar email de verificação
try {
    $authService = new AuthService();
    $result = $authService->reenviarEmailVerificacao($data['email']);

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
