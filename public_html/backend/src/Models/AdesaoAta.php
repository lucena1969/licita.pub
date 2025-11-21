<?php

namespace App\Models;

/**
 * Model: AdesaoAta
 *
 * Representa uma adesão de um órgão a uma Ata de Registro de Preços
 * Conhecida como "carona" - quando um órgão usa a ata de outro
 */
class AdesaoAta
{
    public string $id;
    public string $ata_id;
    public string $orgao_aderente_id;
    public string $orgao_aderente_nome;
    public string $orgao_aderente_cnpj;
    public string $data_adesao;
    public ?float $valor_estimado;
    public string $situacao;
    public ?string $url_documento;
    public string $created_at;

    // Situações possíveis
    public const SITUACAO_ATIVO = 'ATIVO';
    public const SITUACAO_CANCELADO = 'CANCELADO';
    public const SITUACAO_CONCLUIDO = 'CONCLUIDO';

    public function __construct(
        string $ata_id,
        string $orgao_aderente_id,
        string $orgao_aderente_nome,
        string $orgao_aderente_cnpj,
        string $data_adesao,
        string $situacao = self::SITUACAO_ATIVO,
        ?float $valor_estimado = null,
        ?string $url_documento = null
    ) {
        $this->id = self::generateUUID();
        $this->ata_id = $ata_id;
        $this->orgao_aderente_id = $orgao_aderente_id;
        $this->orgao_aderente_nome = $orgao_aderente_nome;
        $this->orgao_aderente_cnpj = self::limparCNPJ($orgao_aderente_cnpj);
        $this->data_adesao = $data_adesao;
        $this->situacao = $situacao;
        $this->valor_estimado = $valor_estimado;
        $this->url_documento = $url_documento;
        $this->created_at = date('Y-m-d H:i:s');
    }

    /**
     * Criar AdesaoAta a partir de array
     */
    public static function fromArray(array $data): self
    {
        $adesao = new self(
            $data['ata_id'],
            $data['orgao_aderente_id'],
            $data['orgao_aderente_nome'],
            $data['orgao_aderente_cnpj'],
            $data['data_adesao'],
            $data['situacao'] ?? self::SITUACAO_ATIVO,
            isset($data['valor_estimado']) ? (float)$data['valor_estimado'] : null,
            $data['url_documento'] ?? null
        );

        // Se vier do banco, usar ID e timestamp existentes
        if (isset($data['id'])) {
            $adesao->id = $data['id'];
        }
        if (isset($data['created_at'])) {
            $adesao->created_at = $data['created_at'];
        }

        return $adesao;
    }

    /**
     * Criar AdesaoAta a partir de dados do PNCP
     */
    public static function fromPNCP(string $ata_id, array $pncpData): self
    {
        // Extrair dados do órgão aderente
        $orgao_id = $pncpData['codigoUnidadeOrgao'] ?? $pncpData['orgaoAderenteCodigo'] ?? '';
        $orgao_nome = $pncpData['nomeOrgao'] ?? $pncpData['orgaoAderenteNome'] ?? '';
        $orgao_cnpj = $pncpData['cnpjOrgao'] ?? $pncpData['orgaoAderenteCnpj'] ?? '';

        // Data da adesão
        $data_adesao = $pncpData['dataAdesao'] ?? date('Y-m-d');

        // Valor estimado (opcional)
        $valor_estimado = isset($pncpData['valorEstimado']) ? (float)$pncpData['valorEstimado'] : null;

        // URL do documento
        $url_documento = $pncpData['urlDocumento'] ?? null;

        // Situação
        $situacao = self::SITUACAO_ATIVO;
        if (isset($pncpData['cancelado']) && $pncpData['cancelado']) {
            $situacao = self::SITUACAO_CANCELADO;
        } elseif (isset($pncpData['concluido']) && $pncpData['concluido']) {
            $situacao = self::SITUACAO_CONCLUIDO;
        }

        return new self(
            $ata_id,
            $orgao_id,
            $orgao_nome,
            $orgao_cnpj,
            $data_adesao,
            $situacao,
            $valor_estimado,
            $url_documento
        );
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ata_id' => $this->ata_id,
            'orgao_aderente_id' => $this->orgao_aderente_id,
            'orgao_aderente_nome' => $this->orgao_aderente_nome,
            'orgao_aderente_cnpj' => $this->orgao_aderente_cnpj,
            'data_adesao' => $this->data_adesao,
            'valor_estimado' => $this->valor_estimado,
            'situacao' => $this->situacao,
            'url_documento' => $this->url_documento,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Verificar se adesão está ativa
     */
    public function estaAtiva(): bool
    {
        return $this->situacao === self::SITUACAO_ATIVO;
    }

    /**
     * Calcular tempo desde a adesão
     */
    public function diasDesdeAdesao(): int
    {
        $hoje = new \DateTime();
        $dataAdesao = new \DateTime($this->data_adesao);

        $diff = $dataAdesao->diff($hoje);
        return $diff->days;
    }

    /**
     * Formatar valor estimado
     */
    public function formatarValorEstimado(): string
    {
        if ($this->valor_estimado === null) {
            return 'Não informado';
        }

        return 'R$ ' . number_format($this->valor_estimado, 2, ',', '.');
    }

    /**
     * Gerar UUID v4
     */
    public static function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Limpar CNPJ (remover pontos, traços, etc)
     */
    private static function limparCNPJ(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }

    /**
     * Validar dados da adesão
     */
    public function validar(): array
    {
        $erros = [];

        if (empty($this->ata_id)) {
            $erros[] = 'ID da ata é obrigatório';
        }

        if (empty($this->orgao_aderente_id)) {
            $erros[] = 'ID do órgão aderente é obrigatório';
        }

        if (empty($this->orgao_aderente_nome)) {
            $erros[] = 'Nome do órgão aderente é obrigatório';
        }

        if (empty($this->orgao_aderente_cnpj)) {
            $erros[] = 'CNPJ do órgão aderente é obrigatório';
        } elseif (strlen($this->orgao_aderente_cnpj) !== 14) {
            $erros[] = 'CNPJ deve ter 14 dígitos';
        }

        if (empty($this->data_adesao)) {
            $erros[] = 'Data de adesão é obrigatória';
        }

        if ($this->valor_estimado !== null && $this->valor_estimado < 0) {
            $erros[] = 'Valor estimado não pode ser negativo';
        }

        if (!in_array($this->situacao, [self::SITUACAO_ATIVO, self::SITUACAO_CANCELADO, self::SITUACAO_CONCLUIDO])) {
            $erros[] = 'Situação inválida';
        }

        return $erros;
    }

    /**
     * Calcular economia estimada (comparado com licitação própria)
     */
    public function calcularEconomiaEstimada(float $custoLicitacaoPropria): float
    {
        if ($this->valor_estimado === null) {
            return 0;
        }

        // Estima-se que uma adesão economiza ~80% do custo de uma licitação própria
        $custoAdesao = $this->valor_estimado * 0.02; // 2% de custo administrativo
        $economiaAbsoluta = $custoLicitacaoPropria - $custoAdesao;

        return max(0, $economiaAbsoluta);
    }

    /**
     * Verificar se CNPJ é válido (validação básica de formato)
     */
    public function cnpjValido(): bool
    {
        $cnpj = $this->orgao_aderente_cnpj;

        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        return true;
    }

    /**
     * Formatar CNPJ para exibição
     */
    public function formatarCNPJ(): string
    {
        $cnpj = $this->orgao_aderente_cnpj;

        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2)
        );
    }
}
