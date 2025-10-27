<?php

/**
 * Endpoint: POST /api/auth/register.php
 * Descrição: Registrar novo usuário
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

// Validar campos obrigatórios
$requiredFields = ['email', 'senha', 'nome'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Campos obrigatórios faltando: ' . implode(', ', $missingFields)],
    ]);
    exit;
}

// Registrar usuário
try {
    $authService = new AuthService();
    $result = $authService->register($data);

    if ($result['success']) {
        http_response_code(201);
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
