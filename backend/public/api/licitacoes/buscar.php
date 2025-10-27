<?php
/**
 * GET /api/licitacoes/buscar.php
 *
 * Busca licitações com filtros (sem consumir limite)
 * - Filtros: UF, município, modalidade, palavra-chave
 * - Paginação disponível
 * - Não requer autenticação
 *
 * Parâmetros GET:
 * - uf: string (ex: "SP", "RJ")
 * - municipio: string (ex: "São Paulo")
 * - modalidade: string (ex: "Pregão Eletrônico")
 * - q: string (palavra-chave para busca)
 * - situacao: string (ex: "EM_ANDAMENTO", "CONCLUIDA")
 * - pagina: int (default: 1)
 * - limite: int (default: 20, max: 50)
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\LicitacaoController;

// Validar método
validateMethod('GET');

// Instanciar controller
$controller = new LicitacaoController();

// Executar busca
$response = $controller->buscar();

// Retornar resposta
jsonResponse($response, $response['success'] ? 200 : 500);
