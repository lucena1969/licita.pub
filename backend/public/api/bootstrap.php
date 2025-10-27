<?php
/**
 * Bootstrap da API
 *
 * Arquivo incluído por todos os endpoints da API
 * - Carrega autoloader
 * - Configura headers CORS
 * - Trata erros
 * - Define funções auxiliares
 */

// Headers de erro devem ser JSON
header('Content-Type: application/json; charset=utf-8');

// Autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Aplicar CORS Middleware
use App\Middleware\CorsMiddleware;

$corsMiddleware = new CorsMiddleware();
$corsMiddleware->handle();

// Se for OPTIONS request, parar aqui
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handler de erros global
set_exception_handler(function ($exception) {
    error_log("API Exception: " . $exception->getMessage());
    error_log("Stack trace: " . $exception->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'ERRO_SERVIDOR',
        'message' => 'Erro interno do servidor',
        'debug' => getenv('APP_ENV') === 'development' ? $exception->getMessage() : null
    ]);
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("API Error [$errno]: $errstr in $errfile on line $errline");

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/**
 * Função auxiliar para enviar resposta JSON
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Função auxiliar para validar método HTTP
 */
function validateMethod(string|array $allowedMethods): void
{
    $method = $_SERVER['REQUEST_METHOD'];
    $allowed = is_array($allowedMethods) ? $allowedMethods : [$allowedMethods];

    if (!in_array($method, $allowed)) {
        jsonResponse([
            'success' => false,
            'error' => 'METODO_NAO_PERMITIDO',
            'message' => "Método $method não permitido. Permitidos: " . implode(', ', $allowed)
        ], 405);
    }
}

/**
 * Função auxiliar para pegar input JSON
 */
function getJsonInput(): ?array
{
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return null;
    }

    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse([
            'success' => false,
            'error' => 'JSON_INVALIDO',
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ], 400);
    }

    return $data;
}
