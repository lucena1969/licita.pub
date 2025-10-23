<?php

namespace App\Models;

class Licitacao
{
    public ?string $id = null;
    public string $pncp_id;
    public string $orgao_id;
    public string $numero;
    public string $objeto;
    public string $modalidade;
    public string $situacao;
    public ?float $valor_estimado = null;
    public string $data_publicacao;
    public ?string $data_abertura = null;
    public ?string $data_encerramento = null;
    public string $uf;
    public string $municipio;
    public ?string $url_edital = null;
    public string $url_pncp;
    public string $nome_orgao;
    public string $cnpj_orgao;
    public ?string $sincronizado_em = null;
    public ?string $atualizado_em = null;

    /**
     * Criar instância a partir de array
     */
    public static function fromArray(array $data): self
    {
        $licitacao = new self();

        $licitacao->id = $data['id'] ?? null;
        $licitacao->pncp_id = $data['pncp_id'];
        $licitacao->orgao_id = $data['orgao_id'];
        $licitacao->numero = $data['numero'];
        $licitacao->objeto = $data['objeto'];
        $licitacao->modalidade = $data['modalidade'];
        $licitacao->situacao = $data['situacao'];
        $licitacao->valor_estimado = isset($data['valor_estimado']) ? (float)$data['valor_estimado'] : null;
        $licitacao->data_publicacao = $data['data_publicacao'];
        $licitacao->data_abertura = $data['data_abertura'] ?? null;
        $licitacao->data_encerramento = $data['data_encerramento'] ?? null;
        $licitacao->uf = $data['uf'];
        $licitacao->municipio = $data['municipio'];
        $licitacao->url_edital = $data['url_edital'] ?? null;
        $licitacao->url_pncp = $data['url_pncp'];
        $licitacao->nome_orgao = $data['nome_orgao'];
        $licitacao->cnpj_orgao = $data['cnpj_orgao'];
        $licitacao->sincronizado_em = $data['sincronizado_em'] ?? null;
        $licitacao->atualizado_em = $data['atualizado_em'] ?? null;

        return $licitacao;
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pncp_id' => $this->pncp_id,
            'numero' => $this->numero,
            'objeto' => $this->objeto,
            'modalidade' => $this->modalidade,
            'situacao' => $this->situacao,
            'valor_estimado' => $this->valor_estimado,
            'data_publicacao' => $this->data_publicacao,
            'data_abertura' => $this->data_abertura,
            'data_encerramento' => $this->data_encerramento,
            'uf' => $this->uf,
            'municipio' => $this->municipio,
            'nome_orgao' => $this->nome_orgao,
            'cnpj_orgao' => $this->cnpj_orgao,
            'url_edital' => $this->url_edital,
            'url_pncp' => $this->url_pncp,
            'sincronizado_em' => $this->sincronizado_em,
            'atualizado_em' => $this->atualizado_em,
        ];
    }

    /**
     * Gerar UUID v4
     */
    public static function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
