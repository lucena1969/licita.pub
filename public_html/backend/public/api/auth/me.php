<?php

/**
 * Endpoint: GET /api/auth/me.php
 * Descrição: Obter dados do usuário autenticado
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;

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

// Exigir autenticação
try {
    $usuario = AuthMiddleware::exigir();

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'usuario' => $usuario->toArray(),
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Erro interno do servidor: ' . $e->getMessage()],
    ]);
}
