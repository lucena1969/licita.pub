<?php

namespace App\Services;

use App\Models\Licitacao;
use App\Models\Orgao;
use App\Repositories\LicitacaoRepository;
use App\Repositories\OrgaoRepository;
use Exception;

/**
 * Service para integração com a API do PNCP
 *
 * Documentação oficial: https://pncp.gov.br/api/consulta/swagger-ui/index.html
 */
class PNCPService
{
    private const BASE_URL = 'https://pncp.gov.br/api/consulta/v1';
    private const TIMEOUT = 30; // segundos
    private const MAX_RETRIES = 3;

    private LicitacaoRepository $licitacaoRepo;
    private OrgaoRepository $orgaoRepo;
    private array $stats = [
        'novos' => 0,
        'atualizados' => 0,
        'erros' => 0,
        'pulados' => 0,
    ];

    public function __construct()
    {
        $this->licitacaoRepo = new LicitacaoRepository();
        $this->orgaoRepo = new OrgaoRepository();
    }

    /**
     * Sincronizar licitações do PNCP
     *
     * @param array $filtros Filtros para busca
     * @return array Estatísticas da sincronização
     */
    public function sincronizarLicitacoes(array $filtros = []): array
    {
        $this->resetStats();
        $iniciado = date('Y-m-d H:i:s');

        try {
            // Parâmetros padrão
            $params = array_merge([
                'dataInicial' => date('Ymd', strtotime('-7 days')), // Últimos 7 dias
                'dataFinal' => date('Ymd'),
                'tamanhoPagina' => 50,
                'pagina' => 1,
            ], $filtros);

            $totalPaginas = 1;
            $paginaAtual = $params['pagina'];

            do {
                echo "Sincronizando página {$paginaAtual} de {$totalPaginas}...\n";

                $params['pagina'] = $paginaAtual;
                $response = $this->fazerRequisicao('/contratos', $params);

                if (!$response || empty($response['data'])) {
                    echo "Nenhum dado retornado na página {$paginaAtual}\n";
                    break;
                }

                // Atualizar total de páginas
                if (isset($response['pagination'])) {
                    $totalPaginas = $response['pagination']['totalPages'] ?? 1;
                }

                // Processar cada licitação
                foreach ($response['data'] as $item) {
                    $this->processarLicitacao($item);
                }

                $paginaAtual++;

                // Pausa entre requisições para não sobrecarregar a API
                usleep(500000); // 0.5 segundo

            } while ($paginaAtual <= $totalPaginas && $paginaAtual <= 10); // Limite de 10 páginas por execução

            $finalizado = date('Y-m-d H:i:s');

            return [
                'sucesso' => true,
                'stats' => $this->stats,
                'iniciado' => $iniciado,
                'finalizado' => $finalizado,
                'duracao' => strtotime($finalizado) - strtotime($iniciado),
            ];

        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'stats' => $this->stats,
                'iniciado' => $iniciado,
                'finalizado' => date('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * Processar uma licitação individual
     */
    private function processarLicitacao(array $item): void
    {
        try {
            // Verificar se já existe
            $pncpId = $item['numeroControlePNCP'] ?? $item['numeroControlePncpCompra'] ?? $item['numeroCompra'] ?? $item['id'] ?? null;

            if (!$pncpId) {
                $this->stats['erros']++;
                echo "  ✗ Licitação sem ID\n";
                return;
            }

            $licitacaoExistente = $this->licitacaoRepo->findByPncpId($pncpId);

            // Garantir que o órgão existe
            $orgaoId = $item['unidadeOrgao']['codigoUnidade'] ?? $item['codigoUnidadeCompradora'] ?? $item['orgaoId'] ?? null;

            if ($orgaoId) {
                $this->garantirOrgao($orgaoId, $item);
            }

            // Criar/atualizar licitação
            $licitacao = $this->mapearLicitacaoDoPNCP($item);

            if ($licitacaoExistente) {
                $licitacao->id = $licitacaoExistente->id;
                $this->licitacaoRepo->update($licitacao);
                $this->stats['atualizados']++;
                echo "  ↻ Atualizada: {$pncpId}\n";
            } else {
                $this->licitacaoRepo->create($licitacao);
                $this->stats['novos']++;
                echo "  ✓ Nova: {$pncpId}\n";
            }

        } catch (Exception $e) {
            $this->stats['erros']++;
            echo "  ✗ Erro: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Garantir que o órgão existe no banco
     */
    private function garantirOrgao(string $orgaoId, array $dadosLicitacao): void
    {
        $orgaoExistente = $this->orgaoRepo->findById($orgaoId);

        if (!$orgaoExistente) {
            // Criar órgão básico com dados da licitação
            $orgao = new Orgao();
            $orgao->id = $orgaoId;
            $orgao->cnpj = $dadosLicitacao['cnpjUnidadeCompradora'] ?? str_pad($orgaoId, 14, '0', STR_PAD_LEFT);
            $orgao->razao_social = $dadosLicitacao['nomeUnidadeCompradora'] ?? 'Órgão ' . $orgaoId;
            $orgao->nome_fantasia = $dadosLicitacao['siglaUnidadeCompradora'] ?? null;
            $orgao->esfera = 'MUNICIPAL'; // Assumir municipal por padrão
            $orgao->poder = 'EXECUTIVO';
            $orgao->uf = $dadosLicitacao['uf'] ?? 'SP';
            $orgao->municipio = $dadosLicitacao['municipio'] ?? null;

            $this->orgaoRepo->upsert($orgao);
        }
    }

    /**
     * Mapear dados do PNCP para modelo Licitacao
     */
    private function mapearLicitacaoDoPNCP(array $item): Licitacao
    {
        $licitacao = new Licitacao();

        $licitacao->pncp_id = $item['numeroControlePNCP'] ?? $item['numeroControlePncpCompra'] ?? $item['numeroCompra'] ?? $item['id'];
        $licitacao->orgao_id = $item['unidadeOrgao']['codigoUnidade'] ?? $item['codigoUnidadeCompradora'] ?? $item['orgaoId'];
        $licitacao->numero = $item['numeroContratoEmpenho'] ?? $item['numeroProcesso'] ?? $item['numero'] ?? $licitacao->pncp_id;
        $licitacao->objeto = $item['objetoContrato'] ?? $item['objeto'] ?? $item['descricao'] ?? '';
        $licitacao->modalidade = $this->mapearModalidade($item['tipoContrato']['id'] ?? $item['codigoModalidade'] ?? $item['modalidade'] ?? 0);
        $licitacao->situacao = 'ATIVO'; // Contratos geralmente estão ativos
        $licitacao->valor_estimado = $item['valorGlobal'] ?? $item['valorInicial'] ?? $item['valorEstimado'] ?? $item['valor'] ?? null;

        // Datas
        $licitacao->data_publicacao = $this->formatarData($item['dataPublicacaoPncp'] ?? $item['dataPublicacao'] ?? date('Y-m-d'));
        $licitacao->data_abertura = $this->formatarData($item['dataAssinatura'] ?? $item['dataAberturaProposta'] ?? $item['dataAbertura'] ?? null);
        $licitacao->data_encerramento = $this->formatarData($item['dataVigenciaFim'] ?? $item['dataEncerramentoProposta'] ?? $item->dataEncerramento ?? null);

        // Localização
        $licitacao->uf = strtoupper($item['unidadeOrgao']['ufSigla'] ?? $item['uf'] ?? $item['unidadeFederacao'] ?? 'SP');
        $licitacao->municipio = $item['unidadeOrgao']['municipioNome'] ?? $item['municipio'] ?? $item['nomeMunicipio'] ?? '';

        // URLs
        $licitacao->url_edital = $item['urlCipi'] ?? $item['urlEdital'] ?? null;
        $licitacao->url_pncp = $item['linkSistemaOrigem'] ?? "https://pncp.gov.br/app/contratos/{$licitacao->pncp_id}";

        // Órgão
        $licitacao->nome_orgao = $item['unidadeOrgao']['nomeUnidade'] ?? $item['orgaoEntidade']['razaoSocial'] ?? $item['nomeUnidadeCompradora'] ?? $item['nomeOrgao'] ?? '';
        $licitacao->cnpj_orgao = $item['orgaoEntidade']['cnpj'] ?? $item['cnpjUnidadeCompradora'] ?? $item['cnpjOrgao'] ?? '';

        return $licitacao;
    }

    /**
     * Mapear código de modalidade do PNCP
     */
    private function mapearModalidade(int $codigo): string
    {
        $modalidades = [
            1 => 'CONCORRENCIA',
            2 => 'TOMADA_PRECOS',
            3 => 'CONVITE',
            4 => 'CONCURSO',
            5 => 'LEILAO',
            6 => 'PREGAO_ELETRONICO',
            7 => 'PREGAO_PRESENCIAL',
            8 => 'DISPENSA',
            9 => 'INEXIGIBILIDADE',
            10 => 'DIALOGO_COMPETITIVO',
            11 => 'CREDENCIAMENTO',
            12 => 'PRE_QUALIFICACAO',
        ];

        return $modalidades[$codigo] ?? 'OUTROS';
    }

    /**
     * Mapear situação da licitação
     */
    private function mapearSituacao(string $situacao): string
    {
        $situacao = strtoupper($situacao);

        $mapeamento = [
            'ATIVA' => 'ATIVO',
            'PUBLICADA' => 'ATIVO',
            'EM_ANDAMENTO' => 'ATIVO',
            'ENCERRADA' => 'ENCERRADO',
            'CANCELADA' => 'CANCELADO',
            'SUSPENSA' => 'SUSPENSO',
            'REVOGADA' => 'CANCELADO',
        ];

        return $mapeamento[$situacao] ?? 'ATIVO';
    }

    /**
     * Formatar data do PNCP
     */
    private function formatarData(?string $data): ?string
    {
        if (!$data) {
            return null;
        }

        // Se já está no formato Y-m-d H:i:s
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $data)) {
            return $data;
        }

        // Se está no formato Ymd (20250101)
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $data, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        // Tentar outros formatos
        try {
            $date = new \DateTime($data);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Fazer requisição à API do PNCP
     */
    private function fazerRequisicao(string $endpoint, array $params = [], int $tentativa = 1): ?array
    {
        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Licita.pub/1.0',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        // Retry em caso de erro
        if ($httpCode !== 200 || !$response) {
            if ($tentativa < self::MAX_RETRIES) {
                echo "  Erro HTTP {$httpCode}, tentando novamente ({$tentativa}/" . self::MAX_RETRIES . ")...\n";
                sleep(2 * $tentativa); // Backoff exponencial
                return $this->fazerRequisicao($endpoint, $params, $tentativa + 1);
            }

            throw new Exception("Erro na requisição: HTTP {$httpCode} - {$error}");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Resetar estatísticas
     */
    private function resetStats(): void
    {
        $this->stats = [
            'novos' => 0,
            'atualizados' => 0,
            'erros' => 0,
            'pulados' => 0,
        ];
    }

    /**
     * Obter estatísticas da última sincronização
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
