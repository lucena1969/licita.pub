<?php

namespace App\Services;

use App\Repositories\ItemAtaRepository;

/**
 * InteligenciaPrecoService
 *
 * Serviço de Inteligência de Preços
 * Compara preços do governo (PNCP) com preços de mercado (Mercado Livre)
 * e calcula oportunidades de margem para fornecedores MEI/ME
 */
class InteligenciaPrecoService
{
    private ItemAtaRepository $itemRepository;
    private MercadoLivreAPI $mercadoLivreAPI;

    public function __construct()
    {
        $this->itemRepository = new ItemAtaRepository();
        $this->mercadoLivreAPI = new MercadoLivreAPI();
    }

    /**
     * Comparar preços: Governo vs Mercado
     *
     * @param string $termo Termo de busca
     * @param array $filtros Filtros adicionais (uf, vigente)
     * @param string|null $userId ID do usuário (para OAuth)
     * @return array Oportunidades encontradas
     */
    public function compararPrecos(string $termo, array $filtros = [], ?string $userId = null): array
    {
        $termo = trim($termo);

        if (strlen($termo) < 3) {
            return [
                'success' => false,
                'error' => 'Digite pelo menos 3 caracteres para pesquisar'
            ];
        }

        try {
            // Se usuário fornecido, tentar usar OAuth
            if ($userId) {
                try {
                    $oauthService = new MercadoLivreOAuthService();
                    $accessToken = $oauthService->getValidAccessToken($userId);
                    if ($accessToken) {
                        $this->mercadoLivreAPI->setAccessToken($accessToken);
                    }
                } catch (\Exception $e) {
                    error_log("[InteligenciaPreco] Erro ao obter token OAuth: " . $e->getMessage());
                }
            }
            // 1. Buscar preços do governo (banco local - cache do PNCP)
            error_log("[InteligenciaPreco] Buscando no PNCP: {$termo}");

            $params = [
                'limit' => 20,
                'offset' => 0
            ];

            // Adicionar filtro de vigente
            if (isset($filtros['vigente']) && $filtros['vigente'] === 'SIM') {
                $params['vigente'] = true;
            }

            if (!empty($filtros['uf'])) {
                $params['uf'] = $filtros['uf'];
            }

            // Usar tabela licitacoes ao invés de itens_ata (que está vazia/sem JOIN)
            error_log("[InteligenciaPreco] ANTES de chamar buscarLicitacoesPorPalavraChave - Params: " . json_encode($params));
            $itensGoverno = $this->itemRepository->buscarLicitacoesPorPalavraChave($termo, $params);
            error_log("[InteligenciaPreco] DEPOIS da busca - Total itens retornados: " . count($itensGoverno));

            if (count($itensGoverno) > 0) {
                error_log("[InteligenciaPreco] Primeiro item: " . json_encode([
                    'descricao' => $itensGoverno[0]->descricao ?? 'N/A',
                    'valor' => $itensGoverno[0]->valor_unitario ?? 'N/A'
                ]));
            } else {
                error_log("[InteligenciaPreco] ATENÇÃO: Nenhum item retornado!");
            }

            // 2. Buscar preços no Mercado Livre
            error_log("[InteligenciaPreco] Buscando no Mercado Livre: {$termo}");

            $resultadoML = $this->mercadoLivreAPI->search($termo, 10);

            if (!$resultadoML['success']) {
                // Se ML falhar, retornar apenas dados do governo
                return [
                    'success' => true,
                    'oportunidades' => [],
                    'itens_governo' => $itensGoverno,
                    'produtos_mercado' => [],
                    'warning' => 'Não foi possível consultar o Mercado Livre'
                ];
            }

            $produtosML = $resultadoML['produtos'] ?? [];

            // 3. Fazer matching e calcular oportunidades
            $oportunidades = $this->calcularOportunidades($itensGoverno, $produtosML);

            return [
                'success' => true,
                'total_oportunidades' => count($oportunidades),
                'oportunidades' => $oportunidades,
                'total_itens_governo' => count($itensGoverno),
                'total_produtos_mercado' => count($produtosML),
                'termo_busca' => $termo
            ];

        } catch (\Exception $e) {
            error_log("[InteligenciaPreco] Erro: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao processar comparação de preços'
            ];
        }
    }

    /**
     * Calcular oportunidades (matching + cálculo de margem)
     *
     * @param array $itensGoverno Itens do governo
     * @param array $produtosML Produtos do Mercado Livre
     * @return array Oportunidades ranqueadas por margem
     */
    private function calcularOportunidades(array $itensGoverno, array $produtosML): array
    {
        $oportunidades = [];

        foreach ($itensGoverno as $itemGov) {
            // Para cada item do governo, tentar encontrar produto similar no ML
            $melhorMatch = $this->encontrarMelhorMatch($itemGov, $produtosML);

            if ($melhorMatch) {
                $precoGoverno = (float)($itemGov->valor_unitario ?? 0);
                $precoMercado = (float)$melhorMatch['preco'];

                // Calcular margem
                $margem = $precoGoverno - $precoMercado;
                $margemPercentual = $precoGoverno > 0
                    ? ($margem / $precoGoverno) * 100
                    : 0;

                // Apenas incluir se margem for positiva (oportunidade real)
                if ($margem > 0) {
                    $oportunidades[] = [
                        // Dados do governo
                        'governo' => [
                            'item_id' => $itemGov->id ?? null,
                            'descricao' => $itemGov->descricao ?? '',
                            'preco' => $precoGoverno,
                            'unidade' => $itemGov->unidade ?? 'UN',
                            'quantidade_disponivel' => $itemGov->quantidade_disponivel ?? 0,
                            'ata_numero' => $itemGov->ata_numero ?? null,
                            'orgao' => $itemGov->orgao_gerenciador_nome ?? 'Não informado',
                            'uf' => $itemGov->uf ?? null,
                        ],
                        // Dados do mercado
                        'mercado' => [
                            'produto_id' => $melhorMatch['id'],
                            'titulo' => $melhorMatch['titulo'],
                            'preco' => $precoMercado,
                            'disponivel' => $melhorMatch['disponivel'],
                            'frete_gratis' => $melhorMatch['frete_gratis'],
                            'permalink' => $melhorMatch['permalink'],
                            'thumbnail' => $melhorMatch['thumbnail'],
                        ],
                        // Cálculo de oportunidade
                        'oportunidade' => [
                            'margem_reais' => round($margem, 2),
                            'margem_percentual' => round($margemPercentual, 2),
                            'classificacao' => $this->classificarOportunidade($margemPercentual),
                        ]
                    ];
                }
            }
        }

        // Ordenar por margem (maior primeiro)
        usort($oportunidades, function($a, $b) {
            return $b['oportunidade']['margem_reais'] <=> $a['oportunidade']['margem_reais'];
        });

        return $oportunidades;
    }

    /**
     * Encontrar melhor match entre item governo e produtos ML
     * (Matching simples por similaridade de texto)
     *
     * @param array $itemGov Item do governo
     * @param array $produtosML Produtos do ML
     * @return array|null Melhor produto correspondente
     */
    private function encontrarMelhorMatch($itemGov, array $produtosML): ?array
    {
        if (empty($produtosML)) {
            return null;
        }

        $descricaoGov = strtolower($itemGov->descricao ?? '');
        $melhorScore = 0;
        $melhorProduto = null;

        foreach ($produtosML as $produto) {
            $tituloProduto = strtolower($produto['titulo']);

            // Calcular similaridade (palavras em comum)
            $score = $this->calcularSimilaridade($descricaoGov, $tituloProduto);

            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhorProduto = $produto;
            }
        }

        // Retornar apenas se similaridade for razoável (> 30%)
        return $melhorScore > 0.3 ? $melhorProduto : reset($produtosML);
    }

    /**
     * Calcular similaridade entre duas strings
     * (Algoritmo simples baseado em palavras comuns)
     *
     * @param string $str1
     * @param string $str2
     * @return float Score entre 0 e 1
     */
    private function calcularSimilaridade(string $str1, string $str2): float
    {
        // Remover stop words e caracteres especiais
        $stopWords = ['de', 'da', 'do', 'para', 'com', 'em', 'a', 'o', 'e'];

        $palavras1 = array_diff(
            preg_split('/\s+/', $str1),
            $stopWords
        );

        $palavras2 = array_diff(
            preg_split('/\s+/', $str2),
            $stopWords
        );

        if (empty($palavras1) || empty($palavras2)) {
            return 0;
        }

        // Contar palavras em comum
        $comuns = count(array_intersect($palavras1, $palavras2));
        $total = max(count($palavras1), count($palavras2));

        return $total > 0 ? $comuns / $total : 0;
    }

    /**
     * Classificar oportunidade por margem
     *
     * @param float $margemPercentual
     * @return string Classificação
     */
    private function classificarOportunidade(float $margemPercentual): string
    {
        if ($margemPercentual >= 30) {
            return 'EXCELENTE';
        } elseif ($margemPercentual >= 20) {
            return 'MUITO_BOA';
        } elseif ($margemPercentual >= 10) {
            return 'BOA';
        } elseif ($margemPercentual >= 5) {
            return 'RAZOAVEL';
        } else {
            return 'BAIXA';
        }
    }
}
