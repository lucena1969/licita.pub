<?php
/**
 * API: Relatório de Palavras-Chave Aprendidas
 * Endpoint para visualizar todas as palavras-chave e seus pesos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
    exit;
}

try {
    // Obter limite (opcional)
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;

    // Validar limite
    if ($limite < 1 || $limite > 500) {
        throw new Exception('Limite deve estar entre 1 e 500');
    }

    // Criar serviço de extração
    $service = new \App\Services\KeywordsExtractionService();

    // Obter relatório
    $relatorio = $service->getRelatorio($limite);

    // Calcular estatísticas
    $stats = [
        'total_palavras' => count($relatorio),
        'peso_medio' => 0,
        'total_ocorrencias' => 0
    ];

    if (!empty($relatorio)) {
        $somaPesos = array_sum(array_column($relatorio, 'peso'));
        $stats['peso_medio'] = round($somaPesos / count($relatorio), 2);
        $stats['total_ocorrencias'] = array_sum(array_column($relatorio, 'ocorrencias'));
    }

    // Retornar sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'palavras' => $relatorio,
            'estatisticas' => $stats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
