<?php

namespace App\Models;

/**
 * Model: ItemAta
 *
 * Representa um item de uma Ata de Registro de Preços
 * Este é o "coração" da consulta de preços!
 */
#[\AllowDynamicProperties]
class ItemAta
{
    public string $id;
    public string $ata_id;
    public int $numero_item;
    public string $descricao;
    public string $unidade;
    public string $fornecedor_nome;
    public string $fornecedor_cnpj;
    public float $valor_unitario;
    public float $quantidade_total;
    public float $quantidade_disponivel;
    public ?float $valor_total; // Calculado automaticamente no banco
    public string $created_at;
    public string $updated_at;

    // Propriedades adicionais que podem vir de JOINs
    public ?string $ata_numero = null;
    public ?string $orgao_gerenciador_nome = null;
    public ?string $uf = null;

    // Unidades de medida comuns
    public const UNIDADE_UNIDADE = 'UN';
    public const UNIDADE_QUILOGRAMA = 'KG';
    public const UNIDADE_METRO = 'M';
    public const UNIDADE_METRO_QUADRADO = 'M²';
    public const UNIDADE_LITRO = 'L';
    public const UNIDADE_HORA = 'H';
    public const UNIDADE_CAIXA = 'CX';
    public const UNIDADE_PACOTE = 'PCT';

    public function __construct(
        string $ata_id,
        int $numero_item,
        string $descricao,
        string $unidade,
        string $fornecedor_nome,
        string $fornecedor_cnpj,
        float $valor_unitario,
        float $quantidade_total,
        float $quantidade_disponivel = null
    ) {
        $this->id = self::generateUUID();
        $this->ata_id = $ata_id;
        $this->numero_item = $numero_item;
        $this->descricao = $descricao;
        $this->unidade = strtoupper($unidade);
        $this->fornecedor_nome = $fornecedor_nome;
        $this->fornecedor_cnpj = self::limparCNPJ($fornecedor_cnpj);
        $this->valor_unitario = $valor_unitario;
        $this->quantidade_total = $quantidade_total;
        $this->quantidade_disponivel = $quantidade_disponivel ?? $quantidade_total;
        $this->valor_total = null; // Será calculado pelo banco
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    /**
     * Criar ItemAta a partir de array
     */
    public static function fromArray(array $data): self
    {
        $item = new self(
            $data['ata_id'],
            (int)$data['numero_item'],
            $data['descricao'],
            $data['unidade'],
            $data['fornecedor_nome'],
            $data['fornecedor_cnpj'],
            (float)$data['valor_unitario'],
            (float)$data['quantidade_total'],
            isset($data['quantidade_disponivel']) ? (float)$data['quantidade_disponivel'] : null
        );

        // Se vier do banco, usar ID e timestamps existentes
        if (isset($data['id'])) {
            $item->id = $data['id'];
        }
        if (isset($data['valor_total'])) {
            $item->valor_total = (float)$data['valor_total'];
        }
        if (isset($data['created_at'])) {
            $item->created_at = $data['created_at'];
        }
        if (isset($data['updated_at'])) {
            $item->updated_at = $data['updated_at'];
        }

        return $item;
    }

    /**
     * Criar ItemAta a partir de dados do PNCP
     */
    public static function fromPNCP(string $ata_id, array $pncpData, int $numero_item): self
    {
        // Extrair descrição
        $descricao = $pncpData['descricao'] ?? $pncpData['descricaoDetalhada'] ?? '';

        // Extrair unidade
        $unidade = $pncpData['unidadeMedida'] ?? $pncpData['unidade'] ?? 'UN';

        // Extrair fornecedor
        $fornecedor_nome = $pncpData['fornecedorNome'] ?? $pncpData['nomeRazaoSocialFornecedor'] ?? '';
        $fornecedor_cnpj = $pncpData['fornecedorCnpj'] ?? $pncpData['niFornecedor'] ?? '';

        // Extrair valores
        $valor_unitario = (float)($pncpData['valorUnitario'] ?? $pncpData['precoUnitario'] ?? 0);
        $quantidade_total = (float)($pncpData['quantidadeTotal'] ?? $pncpData['quantidade'] ?? 0);
        $quantidade_disponivel = (float)($pncpData['quantidadeDisponivel'] ?? $quantidade_total);

        return new self(
            $ata_id,
            $numero_item,
            $descricao,
            $unidade,
            $fornecedor_nome,
            $fornecedor_cnpj,
            $valor_unitario,
            $quantidade_total,
            $quantidade_disponivel
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
            'numero_item' => $this->numero_item,
            'descricao' => $this->descricao,
            'unidade' => $this->unidade,
            'fornecedor_nome' => $this->fornecedor_nome,
            'fornecedor_cnpj' => $this->fornecedor_cnpj,
            'valor_unitario' => $this->valor_unitario,
            'quantidade_total' => $this->quantidade_total,
            'quantidade_disponivel' => $this->quantidade_disponivel,
            'valor_total' => $this->valor_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Verificar se item tem saldo disponível
     */
    public function temSaldoDisponivel(): bool
    {
        return $this->quantidade_disponivel > 0;
    }

    /**
     * Calcular percentual disponível
     */
    public function percentualDisponivel(): float
    {
        if ($this->quantidade_total == 0) {
            return 0;
        }

        return ($this->quantidade_disponivel / $this->quantidade_total) * 100;
    }

    /**
     * Calcular valor total (se não vier do banco)
     */
    public function calcularValorTotal(): float
    {
        return $this->valor_unitario * $this->quantidade_total;
    }

    /**
     * Normalizar descrição para busca
     */
    public function normalizarDescricao(): string
    {
        $desc = $this->descricao;

        // Converter para maiúsculas
        $desc = mb_strtoupper($desc, 'UTF-8');

        // Remover acentos
        $desc = self::removerAcentos($desc);

        // Remover caracteres especiais (manter apenas letras, números e espaços)
        $desc = preg_replace('/[^A-Z0-9\s]/', ' ', $desc);

        // Remover múltiplos espaços
        $desc = preg_replace('/\s+/', ' ', $desc);

        return trim($desc);
    }

    /**
     * Extrair palavras-chave da descrição
     */
    public function extrairPalavrasChave(): array
    {
        $descricaoNormalizada = $this->normalizarDescricao();

        // Palavras ignoradas (stop words)
        $stopWords = ['DE', 'DA', 'DO', 'PARA', 'COM', 'EM', 'A', 'O', 'E', 'OU'];

        $palavras = explode(' ', $descricaoNormalizada);

        // Filtrar palavras muito curtas e stop words
        $palavrasChave = array_filter($palavras, function ($palavra) use ($stopWords) {
            return strlen($palavra) > 2 && !in_array($palavra, $stopWords);
        });

        return array_values($palavrasChave);
    }

    /**
     * Formatar preço para exibição
     */
    public function formatarPreco(): string
    {
        return 'R$ ' . number_format($this->valor_unitario, 2, ',', '.');
    }

    /**
     * Formatar quantidade
     */
    public function formatarQuantidade(): string
    {
        return number_format($this->quantidade_disponivel, 2, ',', '.') . ' ' . $this->unidade;
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
     * Remover acentos de string
     */
    private static function removerAcentos(string $string): string
    {
        $map = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        return strtr($string, $map);
    }

    /**
     * Validar dados do item
     */
    public function validar(): array
    {
        $erros = [];

        if (empty($this->ata_id)) {
            $erros[] = 'ID da ata é obrigatório';
        }

        if ($this->numero_item <= 0) {
            $erros[] = 'Número do item deve ser maior que zero';
        }

        if (empty($this->descricao)) {
            $erros[] = 'Descrição é obrigatória';
        }

        if (empty($this->unidade)) {
            $erros[] = 'Unidade é obrigatória';
        }

        if ($this->valor_unitario < 0) {
            $erros[] = 'Valor unitário não pode ser negativo';
        }

        if ($this->quantidade_total < 0) {
            $erros[] = 'Quantidade total não pode ser negativa';
        }

        if ($this->quantidade_disponivel < 0) {
            $erros[] = 'Quantidade disponível não pode ser negativa';
        }

        if ($this->quantidade_disponivel > $this->quantidade_total) {
            $erros[] = 'Quantidade disponível não pode ser maior que quantidade total';
        }

        return $erros;
    }

    /**
     * Comparar preço com outro item (retorna diferença percentual)
     */
    public function compararPreco(ItemAta $outroItem): float
    {
        if ($outroItem->valor_unitario == 0) {
            return 0;
        }

        $diferenca = $this->valor_unitario - $outroItem->valor_unitario;
        return ($diferenca / $outroItem->valor_unitario) * 100;
    }
}
