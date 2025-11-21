<?php

namespace App\Controllers;

use App\Services\ConsultaPrecoService;
use App\Repositories\ItemAtaRepository;
use App\Repositories\AtaRegistroPrecoRepository;

/**
 * Controller: PrecoController
 *
 * Endpoints para consulta de preços de itens de atas de registro de preços
 * Usado por servidores públicos para pesquisa de mercado (Lei 14.133/2021)
 */
class PrecoController
{
    private ConsultaPrecoService $consultaService;

    public function __construct()
    {
        $itemRepo = new ItemAtaRepository();
        $ataRepo = new AtaRegistroPrecoRepository();
        $this->consultaService = new ConsultaPrecoService($itemRepo, $ataRepo);
    }

    /**
     * GET /api/precos/buscar
     *
     * Buscar preços por descrição do produto/serviço
     *
     * Parâmetros:
     * - q: string (obrigatório) - Descrição do produto/serviço
     * - uf: string (opcional) - Filtrar por UF
     * - valor_min: float (opcional) - Valor mínimo
     * - valor_max: float (opcional) - Valor máximo
     * - unidade: string (opcional) - Unidade de medida
     * - vigente: bool (opcional) - Apenas atas vigentes (default: true)
     * - com_saldo: bool (opcional) - Apenas com quantidade disponível
     * - pagina: int (opcional) - Página (default: 1)
     * - limite: int (opcional) - Itens por página (default: 50, max: 100)
     */
    public function buscar(): array
    {
        try {
            // Validar descrição
            $descricao = $_GET['q'] ?? $_GET['descricao'] ?? null;

            if (!$descricao || strlen(trim($descricao)) < 3) {
                return [
                    'success' => false,
                    'error' => 'DESCRICAO_INVALIDA',
                    'message' => 'Descrição deve ter no mínimo 3 caracteres'
                ];
            }

            // Filtros
            $filtros = [
                'vigente' => isset($_GET['vigente']) ? (bool)$_GET['vigente'] : true,
                'com_saldo' => isset($_GET['com_saldo']) ? (bool)$_GET['com_saldo'] : false,
                'limit' => min(100, max(10, (int)($_GET['limite'] ?? 50))),
                'offset' => (max(1, (int)($_GET['pagina'] ?? 1)) - 1) * (int)($_GET['limite'] ?? 50)
            ];

            // Filtros opcionais
            if (isset($_GET['uf'])) {
                $filtros['uf'] = strtoupper($_GET['uf']);
            }

            if (isset($_GET['valor_min'])) {
                $filtros['valor_min'] = (float)$_GET['valor_min'];
            }

            if (isset($_GET['valor_max'])) {
                $filtros['valor_max'] = (float)$_GET['valor_max'];
            }

            if (isset($_GET['unidade'])) {
                $filtros['unidade'] = strtoupper($_GET['unidade']);
            }

            // Buscar preços
            $itens = $this->consultaService->buscarPrecos($descricao, $filtros);

            return [
                'success' => true,
                'data' => $itens,
                'filtros' => [
                    'descricao' => $descricao,
                    'uf' => $filtros['uf'] ?? null,
                    'valor_min' => $filtros['valor_min'] ?? null,
                    'valor_max' => $filtros['valor_max'] ?? null,
                    'unidade' => $filtros['unidade'] ?? null,
                    'vigente' => $filtros['vigente'],
                    'com_saldo' => $filtros['com_saldo']
                ],
                'paginacao' => [
                    'pagina' => (int)($_GET['pagina'] ?? 1),
                    'limite' => $filtros['limit'],
                    'total' => count($itens)
                ]
            ];

        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => 'PARAMETRO_INVALIDO',
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            error_log("Erro ao buscar preços: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar preços'
            ];
        }
    }

    /**
     * GET /api/precos/estatisticas
     *
     * Obter estatísticas de preços para um produto/serviço
     *
     * Parâmetros:
     * - q: string (obrigatório) - Descrição do produto/serviço
     * - uf: string (opcional) - Filtrar por UF
     * - vigente: bool (opcional) - Apenas atas vigentes (default: true)
     */
    public function estatisticas(): array
    {
        try {
            $descricao = $_GET['q'] ?? $_GET['descricao'] ?? null;

            if (!$descricao || strlen(trim($descricao)) < 3) {
                return [
                    'success' => false,
                    'error' => 'DESCRICAO_INVALIDA',
                    'message' => 'Descrição deve ter no mínimo 3 caracteres'
                ];
            }

            // Filtros
            $filtros = [
                'vigente' => isset($_GET['vigente']) ? (bool)$_GET['vigente'] : true
            ];

            if (isset($_GET['uf'])) {
                $filtros['uf'] = strtoupper($_GET['uf']);
            }

            // Obter estatísticas
            $stats = $this->consultaService->obterEstatisticas($descricao, $filtros);

            return [
                'success' => true,
                'data' => $stats,
                'filtros' => [
                    'descricao' => $descricao,
                    'uf' => $filtros['uf'] ?? null,
                    'vigente' => $filtros['vigente']
                ]
            ];

        } catch (\Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao obter estatísticas'
            ];
        }
    }

    /**
     * GET /api/precos/por-uf
     *
     * Buscar preços agrupados por UF
     *
     * Parâmetros:
     * - q: string (obrigatório) - Descrição do produto/serviço
     * - vigente: bool (opcional) - Apenas atas vigentes (default: true)
     */
    public function porUF(): array
    {
        try {
            $descricao = $_GET['q'] ?? $_GET['descricao'] ?? null;

            if (!$descricao || strlen(trim($descricao)) < 3) {
                return [
                    'success' => false,
                    'error' => 'DESCRICAO_INVALIDA',
                    'message' => 'Descrição deve ter no mínimo 3 caracteres'
                ];
            }

            $filtros = [
                'vigente' => isset($_GET['vigente']) ? (bool)$_GET['vigente'] : true
            ];

            $resultados = $this->consultaService->buscarPorUF($descricao, $filtros);

            return [
                'success' => true,
                'data' => $resultados
            ];

        } catch (\Exception $e) {
            error_log("Erro ao buscar por UF: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar por UF'
            ];
        }
    }

    /**
     * GET /api/precos/similares/{id}
     *
     * Buscar itens similares (sugestões)
     */
    public function similares(?string $itemId = null): array
    {
        try {
            if (!$itemId) {
                $itemId = $_GET['id'] ?? null;
            }

            if (!$itemId) {
                return [
                    'success' => false,
                    'error' => 'ID_OBRIGATORIO',
                    'message' => 'ID do item é obrigatório'
                ];
            }

            $limit = min(10, max(3, (int)($_GET['limite'] ?? 5)));

            $itens = $this->consultaService->buscarSimilares($itemId, $limit);

            return [
                'success' => true,
                'data' => $itens
            ];

        } catch (\Exception $e) {
            error_log("Erro ao buscar similares: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar similares'
            ];
        }
    }

    /**
     * GET /api/precos/melhores-ofertas
     *
     * Obter ranking de melhores ofertas (menores preços)
     */
    public function melhoresOfertas(): array
    {
        try {
            $limit = min(50, max(5, (int)($_GET['limite'] ?? 10)));

            $itens = $this->consultaService->obterMelhoresOfertas($limit);

            return [
                'success' => true,
                'data' => $itens
            ];

        } catch (\Exception $e) {
            error_log("Erro ao buscar melhores ofertas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar melhores ofertas'
            ];
        }
    }

    /**
     * POST /api/precos/relatorio
     *
     * Gerar dados para relatório PDF de pesquisa de preços
     *
     * Body JSON:
     * {
     *   "descricao": "notebook",
     *   "itens_selecionados": [1, 5, 8, 12],
     *   "filtros": {
     *     "uf": "SP",
     *     "data_inicio": "2024-01-01",
     *     "data_fim": "2025-01-01"
     *   },
     *   "observacoes": "Pesquisa para Pregão 01/2025"
     * }
     */
    public function gerarRelatorio(): array
    {
        try {
            // Ler JSON do body
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                return [
                    'success' => false,
                    'error' => 'JSON_INVALIDO',
                    'message' => 'JSON inválido no body da requisição'
                ];
            }

            // Validar campos obrigatórios
            if (empty($input['descricao'])) {
                return [
                    'success' => false,
                    'error' => 'DESCRICAO_OBRIGATORIA',
                    'message' => 'Descrição é obrigatória'
                ];
            }

            if (empty($input['itens_selecionados']) || !is_array($input['itens_selecionados'])) {
                return [
                    'success' => false,
                    'error' => 'ITENS_OBRIGATORIOS',
                    'message' => 'Selecione pelo menos um item'
                ];
            }

            // Preparar opções
            $opcoes = [
                'filtros' => $input['filtros'] ?? [],
                'observacoes' => $input['observacoes'] ?? '',
                'data_inicio' => $input['filtros']['data_inicio'] ?? null,
                'data_fim' => $input['filtros']['data_fim'] ?? null
            ];

            // Gerar dados do relatório
            $dados = $this->consultaService->gerarDadosRelatorio(
                $input['descricao'],
                $input['itens_selecionados'],
                $opcoes
            );

            return [
                'success' => true,
                'data' => $dados
            ];

        } catch (\Exception $e) {
            error_log("Erro ao gerar relatório: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao gerar relatório'
            ];
        }
    }

    /**
     * POST /api/precos/validar-limite
     *
     * Validar se usuário pode fazer consulta (limites de plano)
     *
     * TODO: Implementar verificação de limites por plano
     */
    public function validarLimite(): array
    {
        // TODO: Implementar quando tiver sistema de planos
        return [
            'success' => true,
            'data' => [
                'pode_consultar' => true,
                'consultas_restantes' => 999,
                'plano' => 'FREE'
            ]
        ];
    }
}
