<?php

namespace App\Services;

use Exception;

/**
 * ComprasGovAPIService
 *
 * Serviço para integração com API de Dados Abertos do Governo Federal
 * API: https://dadosabertos.compras.gov.br
 *
 * Funcionalidades:
 * - Consultar preços praticados (SISPP)
 * - Buscar código CATMAT por descrição
 * - Detalhes de compras específicas
 *
 * @package App\Services
 * @version 1.0.0
 * @author Licita.pub
 */
class ComprasGovAPIService
{
    /**
     * URLs base das APIs
     */
    private const API_BASE_URL = 'https://dadosabertos.compras.gov.br';
    private const API_LEGACY_URL = 'http://compras.dados.gov.br';

    /**
     * Endpoints disponíveis
     */
    private const ENDPOINT_CONSULTAR_MATERIAL = '/modulo-pesquisa-preco/1_consultarMaterial';
    private const ENDPOINT_CONSULTAR_DETALHE = '/modulo-pesquisa-preco/2_consultarMaterialDetalhe';
    private const ENDPOINT_CATALOGO = '/materiais/v1/materiais.json';
    private const ENDPOINT_PRECOS_PRATICADOS = '/licitacoes/id/preco_praticado';

    /**
     * Configurações
     */
    private const TIMEOUT = 30; // segundos
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // segundos
    private const MAX_RECORDS_PER_PAGE = 500;

    /**
     * User Agent para identificação
     */
    private const USER_AGENT = 'Licita.pub/1.0 (Integracao API Governo)';

    /**
     * Estatísticas de uso
     */
    private array $stats = [
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'cache_hits' => 0,
        'total_time_ms' => 0
    ];

    /**
     * Consultar preços praticados pelo governo para um material
     *
     * @param int $codigoCatmat Código do item no catálogo CATMAT
     * @param array $filtros Filtros opcionais (uf, uasg, limite)
     * @return array Dados processados dos preços praticados
     * @throws Exception Se houver erro na consulta
     */
    public function consultarMaterial(int $codigoCatmat, array $filtros = []): array
    {
        $startTime = microtime(true);

        try {
            error_log("[ComprasGovAPI] Consultando material CATMAT: {$codigoCatmat}");

            // Montar parâmetros da requisição
            $params = [
                'codigoItemCatalogo' => $codigoCatmat,
                'tamanhoPagina' => $filtros['limite'] ?? 100
            ];

            // Filtros opcionais
            if (!empty($filtros['uf'])) {
                $params['uf'] = $filtros['uf'];
            }

            if (!empty($filtros['uasg'])) {
                $params['codigoUasg'] = $filtros['uasg'];
            }

            if (!empty($filtros['pagina'])) {
                $params['pagina'] = $filtros['pagina'];
            }

            // Fazer requisição com retry
            $response = $this->makeRequest(
                self::API_BASE_URL . self::ENDPOINT_CONSULTAR_MATERIAL,
                $params
            );

            // Processar resposta
            $resultado = $this->processarRespostaPrecos($response);

            $elapsedTime = (microtime(true) - $startTime) * 1000;
            error_log(sprintf("[ComprasGovAPI] Consulta concluída em %.2fms. Total: %d registros",
                $elapsedTime, $resultado['total_registros']));

            return $resultado;

        } catch (Exception $e) {
            $this->stats['failed_requests']++;
            error_log("[ComprasGovAPI] Erro ao consultar material: " . $e->getMessage());
            throw new Exception("Erro ao consultar preços do governo: " . $e->getMessage());
        }
    }

    /**
     * Consultar detalhes de uma compra específica
     *
     * @param string $idCompra Identificador da compra
     * @param array $filtros Filtros opcionais
     * @return array Detalhes da compra
     * @throws Exception Se houver erro na consulta
     */
    public function consultarMaterialDetalhe(string $idCompra, array $filtros = []): array
    {
        try {
            error_log("[ComprasGovAPI] Consultando detalhes da compra: {$idCompra}");

            $params = [
                'idCompra' => $idCompra,
                'tamanhoPagina' => $filtros['limite'] ?? 100
            ];

            if (!empty($filtros['codigoCatmat'])) {
                $params['codigoItemCatalogo'] = $filtros['codigoCatmat'];
            }

            $response = $this->makeRequest(
                self::API_BASE_URL . self::ENDPOINT_CONSULTAR_DETALHE,
                $params
            );

            return $this->processarRespostaDetalhe($response);

        } catch (Exception $e) {
            $this->stats['failed_requests']++;
            error_log("[ComprasGovAPI] Erro ao consultar detalhes: " . $e->getMessage());
            throw new Exception("Erro ao consultar detalhes da compra: " . $e->getMessage());
        }
    }

    /**
     * Buscar código CATMAT por descrição
     *
     * @param string $descricao Descrição do produto
     * @param int $limite Limite de resultados
     * @return array Lista de códigos CATMAT encontrados
     * @throws Exception Se houver erro na busca
     */
    public function buscarCatalogo(string $descricao, int $limite = 10): array
    {
        try {
            error_log("[ComprasGovAPI] Buscando no catálogo: {$descricao}");

            $params = [
                'descricao' => $descricao,
                'limite' => min($limite, 100)
            ];

            $response = $this->makeRequest(
                self::API_LEGACY_URL . self::ENDPOINT_CATALOGO,
                $params
            );

            return $this->processarRespostaCatalogo($response);

        } catch (Exception $e) {
            $this->stats['failed_requests']++;
            error_log("[ComprasGovAPI] Erro ao buscar catálogo: " . $e->getMessage());

            // Retornar array vazio ao invés de falhar
            return [
                'success' => false,
                'total' => 0,
                'itens' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar preços praticados pela API legada (SISPP)
     *
     * @param string $id Identificador do preço praticado
     * @param string $formato Formato da resposta (json, xml, csv, html)
     * @return array Dados dos preços praticados
     * @throws Exception Se houver erro na consulta
     */
    public function buscarPrecoPraticado(string $id, string $formato = 'json'): array
    {
        try {
            error_log("[ComprasGovAPI] Buscando preço praticado: {$id}");

            $url = self::API_LEGACY_URL . self::ENDPOINT_PRECOS_PRATICADOS .
                   "/{$id}/itens.{$formato}";

            $response = $this->makeRequest($url);

            return $this->processarRespostaPrecosPraticados($response);

        } catch (Exception $e) {
            $this->stats['failed_requests']++;
            error_log("[ComprasGovAPI] Erro ao buscar preço praticado: " . $e->getMessage());
            throw new Exception("Erro ao buscar preço praticado: " . $e->getMessage());
        }
    }

    /**
     * Testar conexão com a API
     *
     * @return bool True se API está acessível
     */
    public function testarConexao(): bool
    {
        try {
            error_log("[ComprasGovAPI] Testando conexão com API...");

            // Tentar requisição simples
            $ch = curl_init(self::API_BASE_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $isOnline = ($httpCode >= 200 && $httpCode < 500);

            error_log("[ComprasGovAPI] Status da API: " . ($isOnline ? 'ONLINE' : 'OFFLINE') .
                     " (HTTP {$httpCode})");

            return $isOnline;

        } catch (Exception $e) {
            error_log("[ComprasGovAPI] Erro ao testar conexão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fazer requisição HTTP com retry automático
     *
     * @param string $url URL completa
     * @param array $params Parâmetros GET
     * @param int $attempt Tentativa atual (para recursão)
     * @return array Resposta decodificada
     * @throws Exception Se todas as tentativas falharem
     */
    private function makeRequest(string $url, array $params = [], int $attempt = 1): array
    {
        $this->stats['total_requests']++;

        try {
            // Montar URL com parâmetros
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            error_log("[ComprasGovAPI] Requisição (tentativa {$attempt}): {$url}");

            // Inicializar cURL
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Accept-Encoding: gzip, deflate',
                    'Cache-Control: no-cache'
                ]
            ]);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $elapsedTime = (microtime(true) - $startTime) * 1000;

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);

            curl_close($ch);

            $this->stats['total_time_ms'] += $elapsedTime;

            error_log(sprintf("[ComprasGovAPI] Resposta HTTP %d em %.2fms", $httpCode, $elapsedTime));

            // Verificar erros de cURL
            if ($curlErrno !== 0) {
                throw new Exception("Erro cURL ({$curlErrno}): {$curlError}");
            }

            // Verificar código HTTP
            if ($httpCode >= 500) {
                throw new Exception("Erro no servidor da API (HTTP {$httpCode})");
            }

            if ($httpCode === 404) {
                error_log("[ComprasGovAPI] Recurso não encontrado (404)");
                return [
                    'success' => false,
                    'total' => 0,
                    'itens' => [],
                    'error' => 'Recurso não encontrado'
                ];
            }

            if ($httpCode >= 400) {
                throw new Exception("Erro na requisição (HTTP {$httpCode})");
            }

            // Decodificar resposta JSON
            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Se não for JSON válido, tentar XML (API legada)
                if (strpos($response, '<?xml') === 0) {
                    $data = $this->parseXMLResponse($response);
                } else {
                    throw new Exception("Resposta inválida da API: " . json_last_error_msg());
                }
            }

            $this->stats['successful_requests']++;

            return $data;

        } catch (Exception $e) {
            error_log("[ComprasGovAPI] Erro na requisição: " . $e->getMessage());

            // Retry automático
            if ($attempt < self::MAX_RETRIES) {
                $nextAttempt = $attempt + 1;
                error_log("[ComprasGovAPI] Tentando novamente em " . self::RETRY_DELAY . "s... (tentativa {$nextAttempt})");

                sleep(self::RETRY_DELAY);
                return $this->makeRequest($url, [], $nextAttempt);
            }

            // Todas as tentativas falharam
            throw new Exception("Falha após " . self::MAX_RETRIES . " tentativas: " . $e->getMessage());
        }
    }

    /**
     * Processar resposta de consulta de preços
     *
     * @param array $response Resposta da API
     * @return array Dados processados
     */
    private function processarRespostaPrecos(array $response): array
    {
        $itens = [];
        $precos = [];

        // Extrair itens (estrutura pode variar)
        $rawItems = $response['itens'] ?? $response['data'] ?? $response['resultado'] ?? [];

        foreach ($rawItems as $item) {
            $valorUnitario = (float)($item['valorUnitario'] ?? $item['valor_unitario'] ?? 0);

            if ($valorUnitario > 0) {
                $precos[] = $valorUnitario;

                $itens[] = [
                    'id_compra' => $item['idCompra'] ?? $item['id_compra'] ?? null,
                    'codigo_catmat' => $item['codigoItemCatalogo'] ?? $item['codigo_item_material'] ?? null,
                    'descricao' => $item['descricaoItem'] ?? $item['descricao'] ?? '',
                    'valor_unitario' => $valorUnitario,
                    'valor_total' => (float)($item['valorTotal'] ?? $item['valor_total'] ?? 0),
                    'quantidade' => (float)($item['quantidade'] ?? 1),
                    'unidade' => $item['unidade'] ?? 'UN',
                    'data_compra' => $item['dataCompra'] ?? $item['data_compra'] ?? null,
                    'orgao' => [
                        'nome' => $item['nomeOrgao'] ?? $item['orgao'] ?? '',
                        'uasg' => $item['uasg'] ?? $item['codigoUasg'] ?? null,
                        'uf' => $item['uf'] ?? null,
                        'municipio' => $item['municipio'] ?? null
                    ],
                    'fornecedor' => [
                        'cnpj' => $item['cnpjFornecedor'] ?? $item['cnpj_fornecedor'] ?? null,
                        'nome' => $item['nomeFornecedor'] ?? $item['fornecedor'] ?? ''
                    ],
                    'produto' => [
                        'marca' => $item['marca'] ?? null,
                        'modelo' => $item['modelo'] ?? null
                    ],
                    'licitacao' => [
                        'modalidade' => $item['modalidade'] ?? null,
                        'numero' => $item['numeroCompra'] ?? $item['numero_compra'] ?? null
                    ]
                ];
            }
        }

        // Calcular estatísticas
        $estatisticas = $this->calcularEstatisticas($precos);

        return [
            'success' => true,
            'total_registros' => count($itens),
            'periodo' => [
                'data_inicio' => $this->encontrarDataMinima($itens),
                'data_fim' => $this->encontrarDataMaxima($itens)
            ],
            'estatisticas' => $estatisticas,
            'itens' => $itens
        ];
    }

    /**
     * Processar resposta de detalhes
     *
     * @param array $response Resposta da API
     * @return array Dados processados
     */
    private function processarRespostaDetalhe(array $response): array
    {
        return [
            'success' => true,
            'data' => $response
        ];
    }

    /**
     * Processar resposta de catálogo
     *
     * @param array $response Resposta da API
     * @return array Dados processados
     */
    private function processarRespostaCatalogo(array $response): array
    {
        $itens = [];

        $rawItems = $response['itens'] ?? $response['data'] ?? $response['resultado'] ?? [];

        foreach ($rawItems as $item) {
            $itens[] = [
                'codigo_catmat' => $item['codigo'] ?? $item['id'] ?? null,
                'descricao' => $item['descricao'] ?? $item['nome'] ?? '',
                'categoria' => $item['categoria'] ?? null,
                'unidade' => $item['unidade'] ?? null
            ];
        }

        return [
            'success' => true,
            'total' => count($itens),
            'itens' => $itens
        ];
    }

    /**
     * Processar resposta de preços praticados (API legada)
     *
     * @param array $response Resposta da API
     * @return array Dados processados
     */
    private function processarRespostaPrecosPraticados(array $response): array
    {
        // Estrutura similar a processarRespostaPrecos
        return $this->processarRespostaPrecos($response);
    }

    /**
     * Parse de resposta XML (API legada)
     *
     * @param string $xml String XML
     * @return array Dados convertidos
     */
    private function parseXMLResponse(string $xml): array
    {
        try {
            $xmlObj = simplexml_load_string($xml);
            $json = json_encode($xmlObj);
            return json_decode($json, true);
        } catch (Exception $e) {
            error_log("[ComprasGovAPI] Erro ao parsear XML: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcular estatísticas de preços
     *
     * @param array $precos Array de preços
     * @return array Estatísticas calculadas
     */
    private function calcularEstatisticas(array $precos): array
    {
        if (empty($precos)) {
            return [
                'preco_medio' => 0,
                'preco_minimo' => 0,
                'preco_maximo' => 0,
                'desvio_padrao' => 0,
                'mediana' => 0
            ];
        }

        $total = count($precos);
        $soma = array_sum($precos);
        $media = $soma / $total;

        // Desvio padrão
        $variancia = 0;
        foreach ($precos as $preco) {
            $variancia += pow($preco - $media, 2);
        }
        $desvioPadrao = sqrt($variancia / $total);

        // Mediana
        sort($precos);
        $meio = floor($total / 2);
        if ($total % 2 == 0) {
            $mediana = ($precos[$meio - 1] + $precos[$meio]) / 2;
        } else {
            $mediana = $precos[$meio];
        }

        return [
            'preco_medio' => round($media, 2),
            'preco_minimo' => round(min($precos), 2),
            'preco_maximo' => round(max($precos), 2),
            'desvio_padrao' => round($desvioPadrao, 2),
            'mediana' => round($mediana, 2),
            'total_registros' => $total
        ];
    }

    /**
     * Encontrar data mínima nos itens
     *
     * @param array $itens Array de itens
     * @return string|null Data mais antiga
     */
    private function encontrarDataMinima(array $itens): ?string
    {
        $datas = array_filter(array_column($itens, 'data_compra'));
        return !empty($datas) ? min($datas) : null;
    }

    /**
     * Encontrar data máxima nos itens
     *
     * @param array $itens Array de itens
     * @return string|null Data mais recente
     */
    private function encontrarDataMaxima(array $itens): ?string
    {
        $datas = array_filter(array_column($itens, 'data_compra'));
        return !empty($datas) ? max($datas) : null;
    }

    /**
     * Obter estatísticas de uso do serviço
     *
     * @return array Estatísticas
     */
    public function getStats(): array
    {
        $avgTime = $this->stats['total_requests'] > 0
            ? $this->stats['total_time_ms'] / $this->stats['total_requests']
            : 0;

        return array_merge($this->stats, [
            'avg_response_time_ms' => round($avgTime, 2),
            'success_rate' => $this->stats['total_requests'] > 0
                ? round(($this->stats['successful_requests'] / $this->stats['total_requests']) * 100, 2)
                : 0
        ]);
    }

    /**
     * Resetar estatísticas
     */
    public function resetStats(): void
    {
        $this->stats = [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'cache_hits' => 0,
            'total_time_ms' => 0
        ];
    }
}
