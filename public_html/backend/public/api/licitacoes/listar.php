<?php
/**
 * GET /api/licitacoes/listar.php
 *
 * Lista licitações (sem consumir limite)
 * - Retorna apenas campos básicos
 * - Paginação disponível
 * - Não requer autenticação
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\LicitacaoController;

// Validar método
validateMethod('GET');

// Instanciar controller
$controller = new LicitacaoController();

// Executar listagem
$response = $controller->listar();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 500);
