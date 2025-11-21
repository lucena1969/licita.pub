<?php

namespace App\Models;

/**
 * Model: AtaRegistroPreco
 *
 * Representa uma Ata de Registro de Preços do PNCP
 */
class AtaRegistroPreco
{
    public string $id;
    public string $pncp_id;
    public ?string $licitacao_id;
    public string $numero;
    public string $objeto;
    public string $orgao_gerenciador_id;
    public string $orgao_gerenciador_nome;
    public string $orgao_gerenciador_cnpj;
    public string $data_assinatura;
    public string $data_vigencia_inicio;
    public string $data_vigencia_fim;
    public string $situacao;
    public bool $permite_adesao;
    public string $uf;
    public ?string $municipio;
    public ?string $url_ata;
    public string $url_pncp;
    public string $sincronizado_em;
    public string $atualizado_em;

    // Situações possíveis
    public const SITUACAO_ATIVO = 'ATIVO';
    public const SITUACAO_CANCELADO = 'CANCELADO';
    public const SITUACAO_VENCIDO = 'VENCIDO';
    public const SITUACAO_SUSPENSO = 'SUSPENSO';

    public function __construct(
        string $pncp_id,
        string $numero,
        string $objeto,
        string $orgao_gerenciador_id,
        string $orgao_gerenciador_nome,
        string $orgao_gerenciador_cnpj,
        string $data_assinatura,
        string $data_vigencia_inicio,
        string $data_vigencia_fim,
        string $uf,
        string $url_pncp,
        string $situacao = self::SITUACAO_ATIVO,
        bool $permite_adesao = true,
        ?string $licitacao_id = null,
        ?string $municipio = null,
        ?string $url_ata = null
    ) {
        $this->id = self::generateUUID();
        $this->pncp_id = $pncp_id;
        $this->licitacao_id = $licitacao_id;
        $this->numero = $numero;
        $this->objeto = $objeto;
        $this->orgao_gerenciador_id = $orgao_gerenciador_id;
        $this->orgao_gerenciador_nome = $orgao_gerenciador_nome;
        $this->orgao_gerenciador_cnpj = $orgao_gerenciador_cnpj;
        $this->data_assinatura = $data_assinatura;
        $this->data_vigencia_inicio = $data_vigencia_inicio;
        $this->data_vigencia_fim = $data_vigencia_fim;
        $this->situacao = $situacao;
        $this->permite_adesao = $permite_adesao;
        $this->uf = $uf;
        $this->municipio = $municipio;
        $this->url_ata = $url_ata;
        $this->url_pncp = $url_pncp;
        $this->sincronizado_em = date('Y-m-d H:i:s');
        $this->atualizado_em = date('Y-m-d H:i:s');
    }

    /**
     * Criar AtaRegistroPreco a partir de array
     */
    public static function fromArray(array $data): self
    {
        $ata = new self(
            $data['pncp_id'],
            $data['numero'],
            $data['objeto'],
            $data['orgao_gerenciador_id'],
            $data['orgao_gerenciador_nome'],
            $data['orgao_gerenciador_cnpj'],
            $data['data_assinatura'],
            $data['data_vigencia_inicio'],
            $data['data_vigencia_fim'],
            $data['uf'],
            $data['url_pncp'],
            $data['situacao'] ?? self::SITUACAO_ATIVO,
            (bool)($data['permite_adesao'] ?? true),
            $data['licitacao_id'] ?? null,
            $data['municipio'] ?? null,
            $data['url_ata'] ?? null
        );

        // Se vier do banco, usar ID e timestamps existentes
        if (isset($data['id'])) {
            $ata->id = $data['id'];
        }
        if (isset($data['sincronizado_em'])) {
            $ata->sincronizado_em = $data['sincronizado_em'];
        }
        if (isset($data['atualizado_em'])) {
            $ata->atualizado_em = $data['atualizado_em'];
        }

        return $ata;
    }

    /**
     * Criar AtaRegistroPreco a partir de dados do PNCP
     */
    public static function fromPNCP(array $pncpData): self
    {
        // Extrair dados relevantes da resposta do PNCP
        $pncp_id = $pncpData['numeroControlePNCPAta'] ?? '';
        $numero = $pncpData['numeroAtaRegistroPreco'] ?? '';
        $objeto = $pncpData['objetoContratacao'] ?? '';

        // Órgão gerenciador
        $orgao_cnpj = $pncpData['cnpjOrgao'] ?? '';
        $orgao_nome = $pncpData['nomeOrgao'] ?? '';
        $orgao_id = $pncpData['codigoUnidadeOrgao'] ?? $orgao_cnpj;

        // Datas
        $data_assinatura = $pncpData['dataAssinatura'] ?? date('Y-m-d');
        $vigencia_inicio = $pncpData['vigenciaInicio'] ?? date('Y-m-d');
        $vigencia_fim = $pncpData['vigenciaFim'] ?? date('Y-m-d', strtotime('+1 year'));

        // Localização
        $uf = self::extrairUF($pncpData);
        $municipio = $pncpData['municipio'] ?? null;

        // URL do PNCP
        $url_pncp = "https://pncp.gov.br/app/atas/{$pncp_id}";

        // Situação
        $situacao = self::SITUACAO_ATIVO;
        if (isset($pncpData['cancelado']) && $pncpData['cancelado']) {
            $situacao = self::SITUACAO_CANCELADO;
        } elseif (strtotime($vigencia_fim) < time()) {
            $situacao = self::SITUACAO_VENCIDO;
        }

        return new self(
            $pncp_id,
            $numero,
            $objeto,
            $orgao_id,
            $orgao_nome,
            $orgao_cnpj,
            $data_assinatura,
            $vigencia_inicio,
            $vigencia_fim,
            $uf,
            $url_pncp,
            $situacao
        );
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pncp_id' => $this->pncp_id,
            'licitacao_id' => $this->licitacao_id,
            'numero' => $this->numero,
            'objeto' => $this->objeto,
            'orgao_gerenciador_id' => $this->orgao_gerenciador_id,
            'orgao_gerenciador_nome' => $this->orgao_gerenciador_nome,
            'orgao_gerenciador_cnpj' => $this->orgao_gerenciador_cnpj,
            'data_assinatura' => $this->data_assinatura,
            'data_vigencia_inicio' => $this->data_vigencia_inicio,
            'data_vigencia_fim' => $this->data_vigencia_fim,
            'situacao' => $this->situacao,
            'permite_adesao' => $this->permite_adesao,
            'uf' => $this->uf,
            'municipio' => $this->municipio,
            'url_ata' => $this->url_ata,
            'url_pncp' => $this->url_pncp,
            'sincronizado_em' => $this->sincronizado_em,
            'atualizado_em' => $this->atualizado_em,
        ];
    }

    /**
     * Verificar se ata está vigente
     */
    public function estaVigente(): bool
    {
        $hoje = date('Y-m-d');
        return $this->situacao === self::SITUACAO_ATIVO
            && $this->data_vigencia_inicio <= $hoje
            && $this->data_vigencia_fim >= $hoje;
    }

    /**
     * Verificar se ata permite adesão
     */
    public function permiteAdesao(): bool
    {
        return $this->permite_adesao && $this->estaVigente();
    }

    /**
     * Calcular dias restantes de vigência
     */
    public function diasRestantesVigencia(): int
    {
        $hoje = new \DateTime();
        $fim = new \DateTime($this->data_vigencia_fim);

        if ($fim < $hoje) {
            return 0;
        }

        $diff = $hoje->diff($fim);
        return $diff->days;
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
     * Extrair UF dos dados do PNCP
     */
    private static function extrairUF(array $pncpData): string
    {
        // Tentar diferentes campos possíveis
        if (isset($pncpData['uf'])) {
            return strtoupper(substr($pncpData['uf'], 0, 2));
        }

        if (isset($pncpData['unidadeOrgao']['ufSigla'])) {
            return strtoupper($pncpData['unidadeOrgao']['ufSigla']);
        }

        if (isset($pncpData['orgaoEntidade']['ufSigla'])) {
            return strtoupper($pncpData['orgaoEntidade']['ufSigla']);
        }

        return 'BR'; // Fallback
    }

    /**
     * Validar dados da ata
     */
    public function validar(): array
    {
        $erros = [];

        if (empty($this->pncp_id)) {
            $erros[] = 'PNCP ID é obrigatório';
        }

        if (empty($this->numero)) {
            $erros[] = 'Número da ata é obrigatório';
        }

        if (empty($this->objeto)) {
            $erros[] = 'Objeto é obrigatório';
        }

        if (strlen($this->uf) !== 2) {
            $erros[] = 'UF deve ter 2 caracteres';
        }

        // Validar datas
        if (strtotime($this->data_vigencia_inicio) > strtotime($this->data_vigencia_fim)) {
            $erros[] = 'Data de vigência início não pode ser maior que data fim';
        }

        return $erros;
    }
}
