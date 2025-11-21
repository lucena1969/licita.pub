<?php
/**
 * API: Feedback de Palavras-Chave
 * Endpoint para registrar se uma palavra-chave foi útil ou não (aprendizado)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar .env
$envFile = __DIR__ . '/../../../.env';
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

// Autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Verificar autenticação
$usuario = \App\Middleware\AuthMiddleware::verificar();

if (!$usuario) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Não autenticado'
    ]);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
    exit;
}

try {
    // Obter payload
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Dados inválidos');
    }

    // Validar campos obrigatórios
    if (empty($data['palavra'])) {
        throw new Exception('Campo obrigatório: palavra');
    }

    if (!isset($data['tipo']) || !in_array($data['tipo'], ['positivo', 'negativo'])) {
        throw new Exception('Campo tipo deve ser "positivo" ou "negativo"');
    }

    $palavra = trim(mb_strtolower($data['palavra']));
    $tipo = $data['tipo'];

    // Criar serviço de extração
    $service = new \App\Services\KeywordsExtractionService();

    // Registrar feedback
    $sucesso = false;
    if ($tipo === 'positivo') {
        $sucesso = $service->feedbackPositivo($palavra);
    } else {
        $sucesso = $service->feedbackNegativo($palavra);
    }

    if (!$sucesso) {
        throw new Exception('Erro ao registrar feedback');
    }

    // Retornar sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Feedback registrado com sucesso',
        'data' => [
            'palavra' => $palavra,
            'tipo' => $tipo
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
