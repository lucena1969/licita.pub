<?php
/**
 * API: Extrair Palavras-Chave Inteligentes
 * Endpoint para extrair keywords otimizadas de descrições de licitações
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

    // Validar campo obrigatório
    if (empty($data['descricao'])) {
        throw new Exception('Campo obrigatório: descricao');
    }

    $descricao = trim($data['descricao']);
    $limite = isset($data['limite']) ? (int)$data['limite'] : 4;

    // Validar limite
    if ($limite < 1 || $limite > 10) {
        throw new Exception('Limite deve estar entre 1 e 10');
    }

    // Criar serviço de extração
    $service = new \App\Services\KeywordsExtractionService();

    // Extrair keywords
    $resultado = $service->extrairKeywords($descricao, $limite);

    // Retornar sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $resultado
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
