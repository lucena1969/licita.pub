<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\ItemAta;
use PDO;

class ItemAtaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar novo item
     */
    public function create(ItemAta $item): ItemAta
    {
        $sql = "INSERT INTO itens_ata (
            id, ata_id, numero_item, descricao, unidade,
            fornecedor_nome, fornecedor_cnpj,
            valor_unitario, quantidade_total, quantidade_disponivel
        ) VALUES (
            :id, :ata_id, :numero_item, :descricao, :unidade,
            :fornecedor_nome, :fornecedor_cnpj,
            :valor_unitario, :quantidade_total, :quantidade_disponivel
        )";

        $stmt = $this->db->prepare($sql);

        if (empty($item->id)) {
            $item->id = ItemAta::generateUUID();
        }

        $stmt->execute([
            ':id' => $item->id,
            ':ata_id' => $item->ata_id,
            ':numero_item' => $item->numero_item,
            ':descricao' => $item->descricao,
            ':unidade' => $item->unidade,
            ':fornecedor_nome' => $item->fornecedor_nome,
            ':fornecedor_cnpj' => $item->fornecedor_cnpj,
            ':valor_unitario' => $item->valor_unitario,
            ':quantidade_total' => $item->quantidade_total,
            ':quantidade_disponivel' => $item->quantidade_disponivel,
        ]);

        return $this->findById($item->id);
    }

    /**
     * Inserir múltiplos itens de uma vez (bulk insert)
     */
    public function createBulk(array $itens): int
    {
        if (empty($itens)) {
            return 0;
        }

        $sql = "INSERT INTO itens_ata (
            id, ata_id, numero_item, descricao, unidade,
            fornecedor_nome, fornecedor_cnpj,
            valor_unitario, quantidade_total, quantidade_disponivel
        ) VALUES ";

        $values = [];
        $params = [];
        $contador = 0;

        foreach ($itens as $item) {
            if (empty($item->id)) {
                $item->id = ItemAta::generateUUID();
            }

            $values[] = "(
                :id{$contador}, :ata_id{$contador}, :numero_item{$contador}, :descricao{$contador}, :unidade{$contador},
                :fornecedor_nome{$contador}, :fornecedor_cnpj{$contador},
                :valor_unitario{$contador}, :quantidade_total{$contador}, :quantidade_disponivel{$contador}
            )";

            $params[":id{$contador}"] = $item->id;
            $params[":ata_id{$contador}"] = $item->ata_id;
            $params[":numero_item{$contador}"] = $item->numero_item;
            $params[":descricao{$contador}"] = $item->descricao;
            $params[":unidade{$contador}"] = $item->unidade;
            $params[":fornecedor_nome{$contador}"] = $item->fornecedor_nome;
            $params[":fornecedor_cnpj{$contador}"] = $item->fornecedor_cnpj;
            $params[":valor_unitario{$contador}"] = $item->valor_unitario;
            $params[":quantidade_total{$contador}"] = $item->quantidade_total;
            $params[":quantidade_disponivel{$contador}"] = $item->quantidade_disponivel;

            $contador++;
        }

        $sql .= implode(', ', $values);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Buscar item por ID
     */
    public function findById(string $id): ?ItemAta
    {
        $sql = "SELECT * FROM itens_ata WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return ItemAta::fromArray($data);
    }

    /**
     * Buscar itens por ata
     */
    public function findByAta(string $ataId): array
    {
        $sql = "SELECT * FROM itens_ata
                WHERE ata_id = :ata_id
                ORDER BY numero_item ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ata_id' => $ataId]);

        $itens = [];
        while ($row = $stmt->fetch()) {
            $itens[] = ItemAta::fromArray($row);
        }

        return $itens;
    }

    /**
     * CONSULTA DE PREÇOS - Buscar itens por descrição (FULLTEXT)
     * Este é o método PRINCIPAL do sistema de consulta de preços!
     */
    public function buscarPorDescricao(string $descricao, ?array $filtros = []): array
    {
        // Base da query com FULLTEXT search
        $sql = "SELECT i.*, a.numero as ata_numero, a.orgao_gerenciador_nome, a.uf, a.data_vigencia_fim
                FROM itens_ata i
                INNER JOIN atas_registro_preco a ON i.ata_id = a.id
                WHERE MATCH(i.descricao) AGAINST(:descricao IN NATURAL LANGUAGE MODE)";

        $params = [':descricao' => $descricao];

        // Filtros opcionais
        if (isset($filtros['uf'])) {
            $sql .= " AND a.uf = :uf";
            $params[':uf'] = $filtros['uf'];
        }

        if (isset($filtros['vigente'])) {
            $sql .= " AND a.data_vigencia_fim >= CURDATE()";
        }

        if (isset($filtros['com_saldo'])) {
            $sql .= " AND i.quantidade_disponivel > 0";
        }

        if (isset($filtros['valor_min'])) {
            $sql .= " AND i.valor_unitario >= :valor_min";
            $params[':valor_min'] = $filtros['valor_min'];
        }

        if (isset($filtros['valor_max'])) {
            $sql .= " AND i.valor_unitario <= :valor_max";
            $params[':valor_max'] = $filtros['valor_max'];
        }

        if (isset($filtros['unidade'])) {
            $sql .= " AND i.unidade = :unidade";
            $params[':unidade'] = strtoupper($filtros['unidade']);
        }

        // Ordenação
        $orderBy = $filtros['orderBy'] ?? 'valor_unitario';
        $order = $filtros['order'] ?? 'ASC';
        $sql .= " ORDER BY i.{$orderBy} {$order}";

        // Paginação
        $limit = $filtros['limit'] ?? 100;
        $offset = $filtros['offset'] ?? 0;
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $itens = [];
        while ($row = $stmt->fetch()) {
            $item = ItemAta::fromArray($row);
            // Adicionar dados extras da ata
            $item->ata_numero = $row['ata_numero'] ?? null;
            $item->orgao_gerenciador_nome = $row['orgao_gerenciador_nome'] ?? null;
            $itens[] = $item;
        }

        return $itens;
    }

    /**
     * Obter estatísticas de preços para um item
     */
    public function obterEstatisticasPreco(string $descricao, ?array $filtros = []): array
    {
        $sql = "SELECT
                    COUNT(*) as total_registros,
                    MIN(i.valor_unitario) as menor_preco,
                    MAX(i.valor_unitario) as maior_preco,
                    AVG(i.valor_unitario) as preco_medio,
                    STDDEV(i.valor_unitario) as desvio_padrao
                FROM itens_ata i
                INNER JOIN atas_registro_preco a ON i.ata_id = a.id
                WHERE MATCH(i.descricao) AGAINST(:descricao IN NATURAL LANGUAGE MODE)";

        $params = [':descricao' => $descricao];

        if (isset($filtros['uf'])) {
            $sql .= " AND a.uf = :uf";
            $params[':uf'] = $filtros['uf'];
        }

        if (isset($filtros['vigente'])) {
            $sql .= " AND a.data_vigencia_fim >= CURDATE()";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }

    /**
     * Buscar itens similares (para sugestões)
     */
    public function buscarSimilares(string $itemId, int $limit = 5): array
    {
        // Primeiro busca o item original
        $itemOriginal = $this->findById($itemId);
        if (!$itemOriginal) {
            return [];
        }

        // Busca itens com descrição similar
        $sql = "SELECT i.*, a.numero as ata_numero, a.orgao_gerenciador_nome
                FROM itens_ata i
                INNER JOIN atas_registro_preco a ON i.ata_id = a.id
                WHERE i.id != :item_id
                AND MATCH(i.descricao) AGAINST(:descricao IN NATURAL LANGUAGE MODE)
                ORDER BY i.valor_unitario ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $itemOriginal->descricao, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $itens = [];
        while ($row = $stmt->fetch()) {
            $itens[] = ItemAta::fromArray($row);
        }

        return $itens;
    }

    /**
     * Atualizar quantidade disponível
     */
    public function atualizarQuantidadeDisponivel(string $id, float $novaQuantidade): bool
    {
        $sql = "UPDATE itens_ata
                SET quantidade_disponivel = :quantidade,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':quantidade' => $novaQuantidade
        ]);
    }

    /**
     * Deletar item
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM itens_ata WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Deletar todos os itens de uma ata
     */
    public function deleteByAta(string $ataId): int
    {
        $sql = "DELETE FROM itens_ata WHERE ata_id = :ata_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ata_id' => $ataId]);
        return $stmt->rowCount();
    }

    /**
     * Contar itens por ata
     */
    public function countByAta(string $ataId): int
    {
        $sql = "SELECT COUNT(*) as total FROM itens_ata WHERE ata_id = :ata_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ata_id' => $ataId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obter itens com menor preço por descrição
     */
    public function obterMenoresPrecos(int $limit = 10): array
    {
        $sql = "SELECT i.*, a.numero as ata_numero, a.orgao_gerenciador_nome, a.uf
                FROM itens_ata i
                INNER JOIN atas_registro_preco a ON i.ata_id = a.id
                WHERE a.data_vigencia_fim >= CURDATE()
                AND i.quantidade_disponivel > 0
                ORDER BY i.valor_unitario ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $itens = [];
        while ($row = $stmt->fetch()) {
            $itens[] = ItemAta::fromArray($row);
        }

        return $itens;
    }

    /**
     * Buscar por palavra-chave (mais flexível que FULLTEXT)
     */
    public function buscarPorPalavraChave(string $palavraChave, ?array $filtros = []): array
    {
        $sql = "SELECT i.*, a.numero as ata_numero, a.orgao_gerenciador_nome, a.uf
                FROM itens_ata i
                INNER JOIN atas_registro_preco a ON i.ata_id = a.id
                WHERE i.descricao LIKE :palavra_chave";

        $params = [':palavra_chave' => "%{$palavraChave}%"];

        if (isset($filtros['uf'])) {
            $sql .= " AND a.uf = :uf";
            $params[':uf'] = $filtros['uf'];
        }

        if (isset($filtros['vigente'])) {
            $sql .= " AND a.data_vigencia_fim >= CURDATE()";
        }

        $limit = $filtros['limit'] ?? 100;
        $offset = $filtros['offset'] ?? 0;

        $sql .= " ORDER BY i.valor_unitario ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $itens = [];
        while ($row = $stmt->fetch()) {
            $itens[] = ItemAta::fromArray($row);
        }

        return $itens;
    }

    /**
     * Buscar licitações por palavra-chave (ALTERNATIVA para quando itens_ata está vazio)
     * Retorna dados da tabela licitacoes ao invés de itens_ata
     */
    public function buscarLicitacoesPorPalavraChave(string $palavraChave, ?array $filtros = []): array
    {
        $sql = "SELECT id, objeto as descricao, valor_estimado as valor_unitario,
                       uf, municipio, nome_orgao, situacao, data_abertura
                FROM licitacoes
                WHERE objeto LIKE :palavra_chave";

        $params = [':palavra_chave' => "%{$palavraChave}%"];

        if (isset($filtros['uf'])) {
            $sql .= " AND uf = :uf";
            $params[':uf'] = $filtros['uf'];
        }

        if (isset($filtros['vigente'])) {
            $sql .= " AND situacao = 'ATIVO'";
        }

        $limit = $filtros['limit'] ?? 100;
        $offset = $filtros['offset'] ?? 0;

        $sql .= " ORDER BY valor_estimado ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $itens = [];
        while ($row = $stmt->fetch()) {
            // Converter para formato compatível com ItemAta
            $item = new \stdClass();
            $item->id = $row['id'];
            $item->descricao = $row['descricao'];
            $item->valor_unitario = $row['valor_unitario']; // Corrigido: usar o alias correto
            $item->uf = $row['uf'];
            $item->orgao_gerenciador_nome = $row['nome_orgao'];
            $item->municipio = $row['municipio'];
            $item->situacao = $row['situacao'];
            $item->data_abertura = $row['data_abertura'];
            $itens[] = $item;
        }

        return $itens;
    }
}
