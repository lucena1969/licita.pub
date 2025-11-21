<?php

namespace App\Services;

/**
 * Service: ComprasDadosGovService
 *
 * Cliente HTTP para API compras.dados.gov.br
 * Busca itens de registro de preços (dados históricos até 2020)
 */
class ComprasDadosGovService
{
    private string $baseUrl = 'https://compras.dados.gov.br';
    private int $timeout = 30;
    private int $maxRetries = 3;

    /**
     * Buscar itens de um registro de preço
     */
    public function buscarItensRegistroPreco(string $registroPrecoId, int $offset = 0): ?array
    {
        $url = "{$this->baseUrl}/licitacoes/id/registro_preco/{$registroPrecoId}/itens.json";

        if ($offset > 0) {
            $url .= "?offset={$offset}";
        }

        $response = $this->fazerRequisicaoComRetry($url);

        if (!$response) {
            return null;
        }

        return $response;
    }

    /**
     * Buscar fornecedores de um item específico
     */
    public function buscarFornecedoresItem(string $registroPrecoId, int $numeroItem): ?array
    {
        $url = "{$this->baseUrl}/licitacoes/id/registro_preco/{$registroPrecoId}/itens/{$numeroItem}/fornecedores.json";

        $response = $this->fazerRequisicaoComRetry($url);

        if (!$response) {
            return null;
        }

        return $response;
    }

    /**
     * Buscar detalhes do registro de preço
     */
    public function buscarRegistroPreco(string $registroPrecoId): ?array
    {
        $url = "{$this->baseUrl}/licitacoes/id/registro_preco/{$registroPrecoId}.json";

        $response = $this->fazerRequisicaoComRetry($url);

        if (!$response) {
            return null;
        }

        return $response;
    }

    /**
     * Buscar UASG (Unidade Administrativa)
     */
    public function buscarUASG(int $codigoUasg): ?array
    {
        $url = "{$this->baseUrl}/licitacoes/id/uasg/{$codigoUasg}.json";

        $response = $this->fazerRequisicaoComRetry($url);

        if (!$response) {
            return null;
        }

        return $response;
    }

    /**
     * Fazer requisição HTTP com retry logic
     * (API instável segundo relatos)
     */
    private function fazerRequisicaoComRetry(string $url, int $tentativa = 1): ?array
    {
        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: LicitaPub/1.0'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            // Erro de CURL
            if ($error) {
                error_log("ComprasDadosGov CURL Error (tentativa {$tentativa}): {$error} - URL: {$url}");

                // Retry se não for a última tentativa
                if ($tentativa < $this->maxRetries) {
                    usleep(1000000); // 1 segundo
                    return $this->fazerRequisicaoComRetry($url, $tentativa + 1);
                }

                return null;
            }

            // HTTP 404 ou 400 - não faz retry
            if ($httpCode === 404 || $httpCode === 400) {
                error_log("ComprasDadosGov HTTP {$httpCode}: {$url}");
                return null;
            }

            // HTTP 500, 502, 503 - tenta novamente
            if ($httpCode >= 500 && $httpCode < 600) {
                error_log("ComprasDadosGov HTTP {$httpCode} (tentativa {$tentativa}): {$url}");

                if ($tentativa < $this->maxRetries) {
                    usleep(2000000); // 2 segundos
                    return $this->fazerRequisicaoComRetry($url, $tentativa + 1);
                }

                return null;
            }

            // HTTP 200 - sucesso
            if ($httpCode === 200) {
                $data = json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("ComprasDadosGov JSON Error: " . json_last_error_msg() . " - URL: {$url}");
                    return null;
                }

                return $data;
            }

            // Outros códigos HTTP
            error_log("ComprasDadosGov HTTP {$httpCode} inesperado: {$url}");
            return null;

        } catch (\Exception $e) {
            error_log("ComprasDadosGov Exception (tentativa {$tentativa}): " . $e->getMessage() . " - URL: {$url}");

            if ($tentativa < $this->maxRetries) {
                usleep(1000000);
                return $this->fazerRequisicaoComRetry($url, $tentativa + 1);
            }

            return null;
        }
    }

    /**
     * Extrair itens da resposta (formato HATEOAS)
     */
    public function extrairItens(array $response): array
    {
        if (!isset($response['_embedded']['itensRegistroPreco'])) {
            return [];
        }

        return $response['_embedded']['itensRegistroPreco'];
    }

    /**
     * Verificar se há mais páginas
     */
    public function temMaisPaginas(array $response): bool
    {
        // Se retornou menos itens que o esperado, acabou
        $count = $response['count'] ?? 0;

        // API normalmente retorna 500 itens por página
        return $count >= 500;
    }

    /**
     * Obter próximo offset
     */
    public function proximoOffset(array $response): int
    {
        $offset = $response['offset'] ?? 0;
        $count = $response['count'] ?? 0;

        return $offset + $count;
    }

    /**
     * Normalizar item para formato padronizado
     */
    public function normalizarItem(array $item): array
    {
        return [
            'registro_preco_id' => $item['numero_registro_preco'] ?? null,
            'numero_item' => $item['numero_item_licitacao'] ?? 0,
            'descricao' => $item['descricao_detalhada'] ?? '',
            'marca' => $item['marca'] ?? null,
            'unidade' => $item['unidade'] ?? 'UN',
            'valor_unitario' => $item['valor_unitario'] ?? null,
            'valor_total' => $item['valor_total'] ?? null,
            'quantidade_total' => $item['quantidade_total'] ?? 0,
            'quantidade_empenhada' => $item['quantidade_empenhada'] ?? 0,
            'quantidade_disponivel' => $item['quantidade_a_empenhar'] ?? 0,
            'cnpj_fornecedor' => $item['cnpj_fornecedor'] ?? '',
            'classificacao_fornecedor' => $item['classificacaoFornecedor'] ?? null,
            'codigo_material' => $item['codigo_item_material'] ?? null,
            'codigo_servico' => $item['codigo_item_servico'] ?? null,
            'uasg' => $item['uasg'] ?? null,
            'modalidade' => $item['modalidade'] ?? null,
            'data_assinatura' => $item['data_assinatura'] ?? null,
            'data_inicio_validade' => $item['data_inicio_validade'] ?? null,
            'data_fim_validade' => $item['data_fim_validade'] ?? null,
        ];
    }

    /**
     * Validar item (verificar se tem dados mínimos)
     */
    public function itemValido(array $item): bool
    {
        // Precisa ter pelo menos descrição ou código de material/serviço
        $temDescricao = !empty($item['descricao_detalhada']) || !empty($item['marca']);
        $temCodigo = !empty($item['codigo_item_material']) || !empty($item['codigo_item_servico']);

        return $temDescricao || $temCodigo;
    }

    /**
     * Configurar timeout personalizado
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }

    /**
     * Configurar número máximo de tentativas
     */
    public function setMaxRetries(int $retries): void
    {
        $this->maxRetries = $retries;
    }
}
