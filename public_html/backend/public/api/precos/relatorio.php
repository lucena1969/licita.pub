<?php
/**
 * POST /api/precos/relatorio.php
 *
 * Gerar dados para relatório PDF de pesquisa de preços
 *
 * Body JSON:
 * {
 *   "descricao": "notebook dell i5",
 *   "itens_selecionados": [1, 5, 8, 12],
 *   "filtros": {
 *     "uf": "SP",
 *     "data_inicio": "2024-01-01",
 *     "data_fim": "2025-01-01"
 *   },
 *   "observacoes": "Pesquisa de preços para Pregão Eletrônico 01/2025"
 * }
 *
 * Retorna dados estruturados para geração de PDF
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\PrecoController;

// Validar método
validateMethod('POST');

// Instanciar controller
$controller = new PrecoController();

// Executar
$response = $controller->gerarRelatorio();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 400);
