<?php
/**
 * GET /api/precos/por-uf.php
 *
 * Buscar preços agrupados por UF
 *
 * Parâmetros:
 * - q: string (obrigatório) - Descrição do produto/serviço
 * - vigente: bool (opcional) - Default: true
 *
 * Retorna:
 * [
 *   {
 *     "uf": "SP",
 *     "quantidade": 15,
 *     "menor_preco": 2800.00,
 *     "maior_preco": 4200.00,
 *     "preco_medio": 3200.00,
 *     "itens": [...]
 *   },
 *   ...
 * ]
 *
 * Exemplo:
 * /api/precos/por-uf.php?q=notebook
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\PrecoController;

// Validar método
validateMethod('GET');

// Instanciar controller
$controller = new PrecoController();

// Executar
$response = $controller->porUF();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 400);
