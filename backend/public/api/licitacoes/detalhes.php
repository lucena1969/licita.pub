<?php
/**
 * GET /api/licitacoes/detalhes.php?id=xxx
 *
 * Detalhes completos de uma licitação (CONSOME LIMITE)
 * - Aplica LimiteConsultaMiddleware
 * - Retorna 429 se atingiu limite
 * - Registra consulta no histórico
 * - Não requer autenticação (mas se autenticado, usa limite do usuário)
 *
 * Limites:
 * - Anônimo (por IP): 5 consultas/dia
 * - FREE (cadastrado): 10 consultas/dia
 * - PREMIUM: ilimitado
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\LicitacaoController;
use App\Middleware\LimiteConsultaMiddleware;

// Validar método
validateMethod('GET');

// Validar parâmetro ID
$pncpId = $_GET['id'] ?? null;

if (!$pncpId) {
    jsonResponse([
        'success' => false,
        'error' => 'ID_OBRIGATORIO',
        'message' => 'Parâmetro "id" é obrigatório'
    ], 400);
}

// Aplicar middleware de limite
$limiteMiddleware = new LimiteConsultaMiddleware();
$request = new stdClass();

$limiteMiddleware->handle($request, function($req) use ($pncpId) {
    // Se passou pelo middleware, executar controller
    $controller = new LicitacaoController();
    $response = $controller->detalhes($pncpId, $req);

    // Retornar resposta
    jsonResponse($response, $response['success'] ? 200 : ($response['error'] === 'NAO_ENCONTRADO' ? 404 : 500));
});
