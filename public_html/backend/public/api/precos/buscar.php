<?php
/**
 * GET /api/precos/buscar.php
 *
 * Buscar preços de itens por descrição
 *
 * Parâmetros:
 * - q: string (obrigatório) - Descrição do produto/serviço
 * - uf: string (opcional) - Filtrar por UF
 * - valor_min: float (opcional)
 * - valor_max: float (opcional)
 * - unidade: string (opcional)
 * - vigente: bool (opcional) - Default: true
 * - com_saldo: bool (opcional)
 * - pagina: int (opcional) - Default: 1
 * - limite: int (opcional) - Default: 50
 *
 * Exemplo:
 * /api/precos/buscar.php?q=notebook&uf=SP&valor_max=5000
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\PrecoController;

// Validar método
validateMethod('GET');

// Instanciar controller
$controller = new PrecoController();

// Executar busca
$response = $controller->buscar();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 400);
