<?php

namespace App\Services;

/**
 * MercadoLivreAPI
 *
 * Serviço para integração com a API pública do Mercado Livre
 * Documentação: https://developers.mercadolivre.com.br/
 */
class MercadoLivreAPI
{
    private const BASE_URL = 'https://api.mercadolibre.com';
    private const SITE_ID = 'MLB'; // Brasil
    private int $timeout = 10; // segundos
    private ?string $accessToken = null;

    /**
     * Definir access token para requisições autenticadas
     */
    public function setAccessToken(?string $token): void
    {
        $this->accessToken = $token;
    }

    /**
     * Buscar produtos no Mercado Livre
     *
     * @param string $query Termo de busca
     * @param int $limit Quantidade de resultados (padrão: 10, máximo: 50)
     * @return array Resultado da busca
     */
    public function search(string $query, int $limit = 10): array
    {
        // Validar termo
        $query = trim($query);
        if (strlen($query) < 3) {
            return [
                'success' => false,
                'error' => 'Termo de busca deve ter pelo menos 3 caracteres'
            ];
        }

        // Limitar resultados (API ML aceita até 50)
        $limit = min($limit, 50);

        try {
            // Montar URL
            $url = self::BASE_URL . '/sites/' . self::SITE_ID . '/search?' . http_build_query([
                'q' => $query,
                'limit' => $limit,
                'offset' => 0
            ]);

            // Log
            error_log("[MercadoLivreAPI] Buscando: {$query} (limit: {$limit})");

            // Preparar headers
            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ];

            // Adicionar token se disponível
            if ($this->accessToken) {
                $headers[] = 'Authorization: Bearer ' . $this->accessToken;
            }

            // Fazer requisição
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Verificar erro de conexão
            if ($curlError) {
                error_log("[MercadoLivreAPI] Erro cURL: {$curlError}");
                return [
                    'success' => false,
                    'error' => 'Erro ao conectar com Mercado Livre'
                ];
            }

            // Verificar HTTP code
            if ($httpCode !== 200) {
                error_log("[MercadoLivreAPI] HTTP {$httpCode}");
                return [
                    'success' => false,
                    'error' => "Erro na API Mercado Livre (HTTP {$httpCode})"
                ];
            }

            // Parsear JSON
            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Erro ao processar resposta da API'
                ];
            }

            // Verificar se há resultados
            if (!isset($data['results']) || !is_array($data['results'])) {
                return [
                    'success' => true,
                    'total' => 0,
                    'produtos' => []
                ];
            }

            // Processar resultados
            $produtos = array_map(function($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'titulo' => $item['title'] ?? 'Produto sem título',
                    'preco' => (float)($item['price'] ?? 0),
                    'moeda' => $item['currency_id'] ?? 'BRL',
                    'disponivel' => (int)($item['available_quantity'] ?? 0),
                    'condicao' => $item['condition'] ?? 'unknown', // new, used
                    'thumbnail' => $item['thumbnail'] ?? null,
                    'permalink' => $item['permalink'] ?? null,
                    'vendedor_id' => $item['seller']['id'] ?? null,
                    'frete_gratis' => $item['shipping']['free_shipping'] ?? false,
                ];
            }, $data['results']);

            // Filtrar apenas produtos com preço
            $produtos = array_filter($produtos, fn($p) => $p['preco'] > 0);

            return [
                'success' => true,
                'total' => count($produtos),
                'produtos' => array_values($produtos), // Reindexar
                'query' => $query
            ];

        } catch (\Exception $e) {
            error_log("[MercadoLivreAPI] Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro inesperado ao buscar produtos'
            ];
        }
    }

    /**
     * Obter detalhes de um produto específico
     *
     * @param string $itemId ID do produto no ML
     * @return array Detalhes do produto
     */
    public function getItem(string $itemId): array
    {
        try {
            $url = self::BASE_URL . '/items/' . $itemId;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: Licita.pub/1.0'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'Produto não encontrado'
                ];
            }

            $data = json_decode($response, true);

            return [
                'success' => true,
                'produto' => $data
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao obter detalhes do produto'
            ];
        }
    }

    /**
     * Configurar timeout personalizado
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }
}
