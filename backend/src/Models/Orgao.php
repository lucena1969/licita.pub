<?php

namespace App\Models;

class Orgao
{
    public string $id;
    public string $cnpj;
    public string $razao_social;
    public ?string $nome_fantasia = null;
    public string $esfera;
    public string $poder;
    public string $uf;
    public ?string $municipio = null;
    public ?string $tipo = null;
    public ?string $email = null;
    public ?string $telefone = null;
    public int $total_licitacoes = 0;
    public int $total_contratos = 0;
    public ?string $sincronizado_em = null;
    public ?string $atualizado_em = null;

    /**
     * Criar instância a partir de array
     */
    public static function fromArray(array $data): self
    {
        $orgao = new self();

        $orgao->id = $data['id'];
        $orgao->cnpj = $data['cnpj'];
        $orgao->razao_social = $data['razao_social'];
        $orgao->nome_fantasia = $data['nome_fantasia'] ?? null;
        $orgao->esfera = $data['esfera'];
        $orgao->poder = $data['poder'];
        $orgao->uf = $data['uf'];
        $orgao->municipio = $data['municipio'] ?? null;
        $orgao->tipo = $data['tipo'] ?? null;
        $orgao->email = $data['email'] ?? null;
        $orgao->telefone = $data['telefone'] ?? null;
        $orgao->total_licitacoes = (int)($data['total_licitacoes'] ?? 0);
        $orgao->total_contratos = (int)($data['total_contratos'] ?? 0);
        $orgao->sincronizado_em = $data['sincronizado_em'] ?? null;
        $orgao->atualizado_em = $data['atualizado_em'] ?? null;

        return $orgao;
    }

    /**
     * Criar instância a partir de dados do PNCP
     */
    public static function fromPNCP(array $pncpData): self
    {
        $orgao = new self();

        // Mapear campos do PNCP para nosso modelo
        $orgao->id = $pncpData['id'] ?? $pncpData['codigoUnidade'] ?? '';
        $orgao->cnpj = self::formatarCNPJ($pncpData['cnpj'] ?? '');
        $orgao->razao_social = $pncpData['razaoSocial'] ?? $pncpData['nome'] ?? '';
        $orgao->nome_fantasia = $pncpData['nomeFantasia'] ?? $pncpData['sigla'] ?? null;
        $orgao->esfera = strtoupper($pncpData['esfera'] ?? 'MUNICIPAL');
        $orgao->poder = strtoupper($pncpData['poder'] ?? 'EXECUTIVO');
        $orgao->uf = strtoupper($pncpData['uf'] ?? '');
        $orgao->municipio = $pncpData['municipio'] ?? $pncpData['nomeMunicipio'] ?? null;
        $orgao->tipo = $pncpData['tipo'] ?? null;
        $orgao->email = $pncpData['email'] ?? null;
        $orgao->telefone = $pncpData['telefone'] ?? null;

        return $orgao;
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cnpj' => $this->cnpj,
            'razao_social' => $this->razao_social,
            'nome_fantasia' => $this->nome_fantasia,
            'esfera' => $this->esfera,
            'poder' => $this->poder,
            'uf' => $this->uf,
            'municipio' => $this->municipio,
            'tipo' => $this->tipo,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'total_licitacoes' => $this->total_licitacoes,
            'total_contratos' => $this->total_contratos,
            'sincronizado_em' => $this->sincronizado_em,
            'atualizado_em' => $this->atualizado_em,
        ];
    }

    /**
     * Formatar CNPJ
     */
    private static function formatarCNPJ(string $cnpj): string
    {
        // Remove tudo que não é número
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Preenche com zeros à esquerda se necessário
        return str_pad($cnpj, 14, '0', STR_PAD_LEFT);
    }

    /**
     * Validar dados do órgão
     */
    public function validar(): array
    {
        $erros = [];

        if (empty($this->id)) {
            $erros[] = 'ID do órgão é obrigatório';
        }

        if (empty($this->cnpj)) {
            $erros[] = 'CNPJ é obrigatório';
        }

        if (empty($this->razao_social)) {
            $erros[] = 'Razão social é obrigatória';
        }

        if (empty($this->esfera) || !in_array($this->esfera, ['FEDERAL', 'ESTADUAL', 'MUNICIPAL'])) {
            $erros[] = 'Esfera inválida (deve ser FEDERAL, ESTADUAL ou MUNICIPAL)';
        }

        if (empty($this->poder) || !in_array($this->poder, ['EXECUTIVO', 'LEGISLATIVO', 'JUDICIARIO'])) {
            $erros[] = 'Poder inválido (deve ser EXECUTIVO, LEGISLATIVO ou JUDICIARIO)';
        }

        if (empty($this->uf) || strlen($this->uf) !== 2) {
            $erros[] = 'UF inválida (deve ter 2 caracteres)';
        }

        return $erros;
    }
}
