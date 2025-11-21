<?php

namespace App\Models;

/**
 * Model: ItemLicitacao
 *
 * Representa um item de uma licitação sincronizado do PNCP
 *
 * @package App\Models
 * @version 1.0.0
 */
class ItemLicitacao
{
    // Identificação
    public string $id;
    public string $licitacao_id;
    public int $numero_item;

    // Códigos de catálogo
    public ?string $codigo_catmat = null;
    public ?string $codigo_ncm = null;

    // Descrição
    public string $descricao;
    public ?string $descricao_complementar = null;

    // Quantidades e valores
    public float $quantidade;
    public string $unidade_medida = 'UN';
    public ?float $valor_unitario_estimado = null;
    public ?float $valor_total_estimado = null;

    // Tipo e classificação
    public ?string $tipo_item = null; // M ou S
    public ?string $tipo_item_nome = null;
    public ?string $categoria_item = null;

    // Critérios
    public ?string $criterio_julgamento = null;
    public bool $orcamento_sigiloso = false;
    public bool $beneficio_me = false;

    // Status
    public string $situacao = 'EM_ANDAMENTO';
    public ?string $situacao_nome = null;
    public bool $tem_resultado = false;

    // Timestamps
    public ?string $data_inclusao_pncp = null;
    public ?string $data_atualizacao_pncp = null;
    public string $sincronizado_em;
    public string $atualizado_em;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->id = $this->generateUUID();
        $this->sincronizado_em = date('Y-m-d H:i:s');
        $this->atualizado_em = date('Y-m-d H:i:s');
    }

    /**
     * Gerar UUID v4
     *
     * @return string
     */
    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Converter para array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'licitacao_id' => $this->licitacao_id,
            'numero_item' => $this->numero_item,
            'codigo_catmat' => $this->codigo_catmat,
            'codigo_ncm' => $this->codigo_ncm,
            'descricao' => $this->descricao,
            'descricao_complementar' => $this->descricao_complementar,
            'quantidade' => $this->quantidade,
            'unidade_medida' => $this->unidade_medida,
            'valor_unitario_estimado' => $this->valor_unitario_estimado,
            'valor_total_estimado' => $this->valor_total_estimado,
            'tipo_item' => $this->tipo_item,
            'tipo_item_nome' => $this->tipo_item_nome,
            'categoria_item' => $this->categoria_item,
            'criterio_julgamento' => $this->criterio_julgamento,
            'orcamento_sigiloso' => $this->orcamento_sigiloso,
            'beneficio_me' => $this->beneficio_me,
            'situacao' => $this->situacao,
            'situacao_nome' => $this->situacao_nome,
            'tem_resultado' => $this->tem_resultado,
            'data_inclusao_pncp' => $this->data_inclusao_pncp,
            'data_atualizacao_pncp' => $this->data_atualizacao_pncp,
            'sincronizado_em' => $this->sincronizado_em,
            'atualizado_em' => $this->atualizado_em,
        ];
    }

    /**
     * Validar item
     *
     * @return array Erros de validação (vazio se válido)
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->licitacao_id)) {
            $errors[] = 'licitacao_id é obrigatório';
        }

        if (empty($this->numero_item) || $this->numero_item < 1) {
            $errors[] = 'numero_item deve ser maior que zero';
        }

        if (empty($this->descricao)) {
            $errors[] = 'descricao é obrigatória';
        }

        if ($this->quantidade <= 0) {
            $errors[] = 'quantidade deve ser maior que zero';
        }

        if (empty($this->unidade_medida)) {
            $errors[] = 'unidade_medida é obrigatória';
        }

        return $errors;
    }

    /**
     * Verificar se item é válido
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Calcular valor total estimado automaticamente
     *
     * @return void
     */
    public function calcularValorTotal(): void
    {
        if ($this->valor_unitario_estimado !== null && $this->quantidade > 0) {
            $this->valor_total_estimado = $this->valor_unitario_estimado * $this->quantidade;
        }
    }

    /**
     * Verificar se item tem código CATMAT
     *
     * @return bool
     */
    public function temCatmat(): bool
    {
        return !empty($this->codigo_catmat);
    }

    /**
     * Verificar se é material
     *
     * @return bool
     */
    public function isMaterial(): bool
    {
        return $this->tipo_item === 'M';
    }

    /**
     * Verificar se é serviço
     *
     * @return bool
     */
    public function isServico(): bool
    {
        return $this->tipo_item === 'S';
    }

    /**
     * Formatar valor unitário em reais
     *
     * @return string
     */
    public function formatarValorUnitario(): string
    {
        if ($this->valor_unitario_estimado === null) {
            return '-';
        }

        return 'R$ ' . number_format($this->valor_unitario_estimado, 2, ',', '.');
    }

    /**
     * Formatar valor total em reais
     *
     * @return string
     */
    public function formatarValorTotal(): string
    {
        if ($this->valor_total_estimado === null) {
            return '-';
        }

        return 'R$ ' . number_format($this->valor_total_estimado, 2, ',', '.');
    }
}
