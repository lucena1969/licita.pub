<?php
/**
 * GET /api/licitacoes/estatisticas.php
 *
 * Estatísticas gerais (sem consumir limite)
 * - Total de licitações
 * - Total de UFs
 * - Total de órgãos
 * - Valores totais e médios
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\LicitacaoController;

// Validar método
validateMethod('GET');

// Instanciar controller
$controller = new LicitacaoController();

// Executar
$response = $controller->estatisticas();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 500);
