<?php
/**
 * GET /api/precos/estatisticas.php
 *
 * Obter estatísticas de preços (min, max, média, mediana, etc)
 *
 * Parâmetros:
 * - q: string (obrigatório) - Descrição do produto/serviço
 * - uf: string (opcional) - Filtrar por UF
 * - vigente: bool (opcional) - Default: true
 *
 * Retorna:
 * {
 *   "total_registros": 45,
 *   "menor_preco": 2350.00,
 *   "maior_preco": 4890.00,
 *   "preco_medio": 3425.50,
 *   "preco_mediano": 3380.00,
 *   "desvio_padrao": 580.23,
 *   "percentil_25": 2900.00,
 *   "percentil_75": 3900.00
 * }
 *
 * Exemplo:
 * /api/precos/estatisticas.php?q=notebook&uf=SP
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\PrecoController;

// Validar método
validateMethod('GET');

// Instanciar controller
$controller = new PrecoController();

// Executar
$response = $controller->estatisticas();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 400);
