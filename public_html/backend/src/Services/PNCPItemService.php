<?php

namespace App\Services;

use App\Models\ItemLicitacao;
use App\Repositories\ItemLicitacaoRepository;
use App\Services\CatmatMatchingService;
use Exception;

/**
 * PNCPItemService
 *
 * Serviço para sincronizar itens de licitações do PNCP
 *
 * API Endpoint: https://pncp.gov.br/api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/itens
 *
 * @package App\Services
 * @version 1.0.0
 */
class PNCPItemService
{
    private const API_BASE_URL = 'https://pncp.gov.br/api/pncp/v1';
    private const TIMEOUT = 30; // segundos
    private const MAX_RETRIES = 3;

    private ItemLicitacaoRepository $itemRepo;
    private ?CatmatMatchingService $catmatService = null;

    private array $stats = [
        'novos' => 0,
        'atualizados' => 0,
        'erros' => 0,
        'sem_catmat' => 0,
        'catmat_encontrado' => 0,
    ];

    public function __construct()
    {
        $this->itemRepo = new ItemLicitacaoRepository();
    }

    /**
     * Sincronizar itens de uma licitação
     *
     * @param string $licitacaoId ID da licitação no banco local
     * @param string $pncpId ID do PNCP (formato: CNPJ-X-SEQUENCIAL/ANO)
     * @param bool $usarCatmatMatching Se deve buscar CATMAT quando null
     * @return array Resultado da sincronização
     */
    public function sincronizarItens(string $licitacaoId, string $pncpId, bool $usarCatmatMatching = true): array
    {
        $this->resetStats();

        try {
            error_log("[PNCPItemService] Sincronizando itens: {$pncpId}");

            // Parse do pncp_id
            $parsed = $this->parsePncpId($pncpId);
            if (!$parsed) {
                throw new Exception("Formato inválido de pncp_id: {$pncpId}");
            }

            // Buscar itens na API do PNCP
            $itensAPI = $this->buscarItensAPI($parsed['cnpj'], $parsed['ano'], $parsed['sequencial']);

            if (empty($itensAPI)) {
                error_log("[PNCPItemService] Nenhum item retornado pela API");
                return [
                    'success' => false,
                    'message' => 'Licitação sem itens cadastrados no PNCP',
                    'stats' => $this->stats
                ];
            }

            error_log("[PNCPItemService] {$itensAPI['total']} itens encontrados na API");

            // Deletar itens antigos antes de inserir novos (resincronização)
            $this->itemRepo->deleteByLicitacaoId($licitacaoId);

            // Processar cada item
            foreach ($itensAPI['itens'] as $itemData) {
                try {
                    $item = $this->mapearItemDoPNCP($itemData, $licitacaoId);

                    // Se não tem CATMAT e deve buscar
                    if ($usarCatmatMatching && !$item->temCatmat() && !empty($item->descricao)) {
                        $this->tentarEncontrarCatmat($item);
                    }

                    // Salvar item
                    $resultado = $this->itemRepo->upsert($item);

                    if ($resultado['inserido']) {
                        $this->stats['novos']++;
                    } else {
                        $this->stats['atualizados']++;
                    }

                    if (!$item->temCatmat()) {
                        $this->stats['sem_catmat']++;
                    }

                } catch (Exception $e) {
                    $this->stats['erros']++;
                    error_log("[PNCPItemService] Erro ao processar item {$itemData['numeroItem']}: " . $e->getMessage());
                }
            }

            error_log("[PNCPItemService] Sincronização concluída: {$this->stats['novos']} novos, {$this->stats['erros']} erros");

            return [
                'success' => true,
                'licitacao_id' => $licitacaoId,
                'pncp_id' => $pncpId,
                'total_itens' => count($itensAPI['itens']),
                'stats' => $this->stats
            ];

        } catch (Exception $e) {
            error_log("[PNCPItemService] Erro fatal: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->stats
            ];
        }
    }

    /**
     * Buscar itens na API do PNCP
     *
     * @param string $cnpj
     * @param string $ano
     * @param string $sequencial
     * @return array|null
     */
    private function buscarItensAPI(string $cnpj, string $ano, string $sequencial): ?array
    {
        $url = self::API_BASE_URL . "/orgaos/{$cnpj}/compras/{$ano}/{$sequencial}/itens";

        error_log("[PNCPItemService] Requisição API: {$url}");

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Licita.pub/1.0',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("[PNCPItemService] Erro cURL: {$curlError}");
            return null;
        }

        if ($httpCode !== 200) {
            error_log("[PNCPItemService] HTTP {$httpCode}: {$response}");
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[PNCPItemService] Erro JSON: " . json_last_error_msg());
            return null;
        }

        // A API retorna array direto de itens
        if (is_array($data)) {
            return [
                'total' => count($data),
                'itens' => $data
            ];
        }

        return null;
    }

    /**
     * Mapear item do PNCP para Model ItemLicitacao
     *
     * @param array $itemPNCP
     * @param string $licitacaoId
     * @return ItemLicitacao
     */
    private function mapearItemDoPNCP(array $itemPNCP, string $licitacaoId): ItemLicitacao
    {
        $item = new ItemLicitacao();

        $item->licitacao_id = $licitacaoId;
        $item->numero_item = (int)($itemPNCP['numeroItem'] ?? 0);

        // Códigos de catálogo
        $item->codigo_catmat = $itemPNCP['catalogoCodigoItem'] ?? $itemPNCP['codigoItemCatalogo'] ?? null;
        $item->codigo_ncm = $itemPNCP['ncmNbsCodigo'] ?? null;

        // Descrição
        $item->descricao = $itemPNCP['descricao'] ?? '';
        $item->descricao_complementar = $itemPNCP['informacaoComplementar'] ?? null;

        // Quantidades e valores
        $item->quantidade = (float)($itemPNCP['quantidade'] ?? 1.0);
        $item->unidade_medida = $this->normalizarUnidade($itemPNCP['unidadeMedida'] ?? 'UN');
        $item->valor_unitario_estimado = isset($itemPNCP['valorUnitarioEstimado'])
            ? (float)$itemPNCP['valorUnitarioEstimado']
            : null;
        $item->valor_total_estimado = isset($itemPNCP['valorTotal'])
            ? (float)$itemPNCP['valorTotal']
            : null;

        // Se não tem valor total, calcular
        if ($item->valor_total_estimado === null) {
            $item->calcularValorTotal();
        }

        // Tipo e classificação
        $item->tipo_item = $itemPNCP['materialOuServico'] ?? null; // M ou S
        $item->tipo_item_nome = $itemPNCP['materialOuServicoNome'] ?? null;
        $item->categoria_item = $itemPNCP['itemCategoriaNome'] ?? $itemPNCP['categoriaItemCatalogo'] ?? null;

        // Critérios
        $item->criterio_julgamento = $itemPNCP['criterioJulgamentoNome'] ?? null;
        $item->orcamento_sigiloso = (bool)($itemPNCP['orcamentoSigiloso'] ?? false);
        $item->beneficio_me = $this->verificarBeneficioME($itemPNCP);

        // Status
        $item->situacao = $this->mapearSituacao($itemPNCP['situacaoCompraItem'] ?? 1);
        $item->situacao_nome = $itemPNCP['situacaoCompraItemNome'] ?? null;
        $item->tem_resultado = (bool)($itemPNCP['temResultado'] ?? false);

        // Timestamps do PNCP
        $item->data_inclusao_pncp = $this->formatarDataPNCP($itemPNCP['dataInclusao'] ?? null);
        $item->data_atualizacao_pncp = $this->formatarDataPNCP($itemPNCP['dataAtualizacao'] ?? null);

        return $item;
    }

    /**
     * Tentar encontrar código CATMAT via CatmatMatchingService
     *
     * @param ItemLicitacao $item
     * @return void
     */
    private function tentarEncontrarCatmat(ItemLicitacao &$item): void
    {
        try {
            if ($this->catmatService === null) {
                $this->catmatService = new CatmatMatchingService();
            }

            $resultado = $this->catmatService->encontrarCodigoCatmat($item->descricao);

            if ($resultado && !empty($resultado['codigo_catmat'])) {
                $item->codigo_catmat = (string)$resultado['codigo_catmat'];
                $this->stats['catmat_encontrado']++;
                error_log("[PNCPItemService] CATMAT encontrado via matching: {$item->codigo_catmat}");
            }

        } catch (Exception $e) {
            error_log("[PNCPItemService] Erro ao buscar CATMAT: " . $e->getMessage());
        }
    }

    /**
     * Parse do pncp_id
     *
     * Formato esperado: CNPJ-X-SEQUENCIAL/ANO
     * Exemplo: 08259606000158-1-000827/2025
     *
     * @param string $pncpId
     * @return array|null ['cnpj', 'ano', 'sequencial']
     */
    private function parsePncpId(string $pncpId): ?array
    {
        // Formato: CNPJ-X-SEQUENCIAL/ANO
        if (!preg_match('/^(\d+)-(\d+)-(\d+)\/(\d{4})$/', $pncpId, $matches)) {
            return null;
        }

        return [
            'cnpj' => $matches[1],
            'tipo' => $matches[2],
            'sequencial' => $matches[3],
            'ano' => $matches[4]
        ];
    }

    /**
     * Normalizar unidade de medida
     *
     * @param string $unidade
     * @return string
     */
    private function normalizarUnidade(string $unidade): string
    {
        $unidade = strtoupper(trim($unidade));

        // Normalizar variações comuns
        $mapa = [
            'UNIDADE' => 'UN',
            'UNID' => 'UN',
            'UND' => 'UN',
            'SERVICO' => 'SV',
            'SERVIÇO' => 'SV',
            'METRO' => 'M',
            'METROS' => 'M',
            'QUILOGRAMA' => 'KG',
            'LITRO' => 'L',
            'LITROS' => 'L',
        ];

        return $mapa[$unidade] ?? substr($unidade, 0, 10);
    }

    /**
     * Verificar se tem benefício para ME/EPP
     *
     * @param array $itemPNCP
     * @return bool
     */
    private function verificarBeneficioME(array $itemPNCP): bool
    {
        // Campo tipoBeneficio: 1=Exclusivo ME/EPP, 2=Cota ME/EPP, 3=Ampla participação, 4=Sem benefício
        $tipoBeneficio = (int)($itemPNCP['tipoBeneficio'] ?? 4);

        return in_array($tipoBeneficio, [1, 2]); // Exclusivo ou Cota
    }

    /**
     * Mapear situação do item
     *
     * @param int $situacaoId
     * @return string
     */
    private function mapearSituacao(int $situacaoId): string
    {
        $mapa = [
            1 => 'EM_ANDAMENTO',
            2 => 'HOMOLOGADO',
            3 => 'CANCELADO',
            4 => 'DESERTO',
            5 => 'FRACASSADO',
        ];

        return $mapa[$situacaoId] ?? 'EM_ANDAMENTO';
    }

    /**
     * Formatar data do PNCP
     *
     * @param string|null $data
     * @return string|null
     */
    private function formatarDataPNCP(?string $data): ?string
    {
        if (!$data) {
            return null;
        }

        try {
            $date = new \DateTime($data);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Resetar estatísticas
     *
     * @return void
     */
    private function resetStats(): void
    {
        $this->stats = [
            'novos' => 0,
            'atualizados' => 0,
            'erros' => 0,
            'sem_catmat' => 0,
            'catmat_encontrado' => 0,
        ];
    }

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
