<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\ItemLicitacao;
use PDO;
use Exception;

/**
 * Repository: ItemLicitacaoRepository
 *
 * Gerencia operações de banco de dados para itens de licitações
 *
 * @package App\Repositories
 * @version 1.0.0
 */
class ItemLicitacaoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Inserir ou atualizar item (UPSERT)
     *
     * @param ItemLicitacao $item
     * @return array ['inserido' => bool, 'item' => ItemLicitacao]
     * @throws Exception
     */
    public function upsert(ItemLicitacao $item): array
    {
        // Validar antes de salvar
        $errors = $item->validate();
        if (!empty($errors)) {
            throw new Exception('Validação falhou: ' . implode(', ', $errors));
        }

        try {
            $sql = "INSERT INTO itens_licitacao (
                        id, licitacao_id, numero_item, codigo_catmat, codigo_ncm,
                        descricao, descricao_complementar, quantidade, unidade_medida,
                        valor_unitario_estimado, valor_total_estimado,
                        tipo_item, tipo_item_nome, categoria_item,
                        criterio_julgamento, orcamento_sigiloso, beneficio_me,
                        situacao, situacao_nome, tem_resultado,
                        data_inclusao_pncp, data_atualizacao_pncp,
                        sincronizado_em, atualizado_em
                    ) VALUES (
                        :id, :licitacao_id, :numero_item, :codigo_catmat, :codigo_ncm,
                        :descricao, :descricao_complementar, :quantidade, :unidade_medida,
                        :valor_unitario_estimado, :valor_total_estimado,
                        :tipo_item, :tipo_item_nome, :categoria_item,
                        :criterio_julgamento, :orcamento_sigiloso, :beneficio_me,
                        :situacao, :situacao_nome, :tem_resultado,
                        :data_inclusao_pncp, :data_atualizacao_pncp,
                        NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        codigo_catmat = VALUES(codigo_catmat),
                        codigo_ncm = VALUES(codigo_ncm),
                        descricao = VALUES(descricao),
                        descricao_complementar = VALUES(descricao_complementar),
                        quantidade = VALUES(quantidade),
                        unidade_medida = VALUES(unidade_medida),
                        valor_unitario_estimado = VALUES(valor_unitario_estimado),
                        valor_total_estimado = VALUES(valor_total_estimado),
                        tipo_item = VALUES(tipo_item),
                        tipo_item_nome = VALUES(tipo_item_nome),
                        categoria_item = VALUES(categoria_item),
                        criterio_julgamento = VALUES(criterio_julgamento),
                        orcamento_sigiloso = VALUES(orcamento_sigiloso),
                        beneficio_me = VALUES(beneficio_me),
                        situacao = VALUES(situacao),
                        situacao_nome = VALUES(situacao_nome),
                        tem_resultado = VALUES(tem_resultado),
                        data_inclusao_pncp = VALUES(data_inclusao_pncp),
                        data_atualizacao_pncp = VALUES(data_atualizacao_pncp),
                        atualizado_em = NOW()";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':id' => $item->id,
                ':licitacao_id' => $item->licitacao_id,
                ':numero_item' => $item->numero_item,
                ':codigo_catmat' => $item->codigo_catmat,
                ':codigo_ncm' => $item->codigo_ncm,
                ':descricao' => $item->descricao,
                ':descricao_complementar' => $item->descricao_complementar,
                ':quantidade' => $item->quantidade,
                ':unidade_medida' => $item->unidade_medida,
                ':valor_unitario_estimado' => $item->valor_unitario_estimado,
                ':valor_total_estimado' => $item->valor_total_estimado,
                ':tipo_item' => $item->tipo_item,
                ':tipo_item_nome' => $item->tipo_item_nome,
                ':categoria_item' => $item->categoria_item,
                ':criterio_julgamento' => $item->criterio_julgamento,
                ':orcamento_sigiloso' => $item->orcamento_sigiloso ? 1 : 0,
                ':beneficio_me' => $item->beneficio_me ? 1 : 0,
                ':situacao' => $item->situacao,
                ':situacao_nome' => $item->situacao_nome,
                ':tem_resultado' => $item->tem_resultado ? 1 : 0,
                ':data_inclusao_pncp' => $item->data_inclusao_pncp,
                ':data_atualizacao_pncp' => $item->data_atualizacao_pncp,
            ]);

            $inserido = $stmt->rowCount() === 1;

            return [
                'inserido' => $inserido,
                'item' => $item
            ];

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao salvar item: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buscar item por ID
     *
     * @param string $id
     * @return ItemLicitacao|null
     */
    public function findById(string $id): ?ItemLicitacao
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM itens_licitacao
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return $data ? $this->mapToModel($data) : null;

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao buscar item: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar itens de uma licitação
     *
     * @param string $licitacaoId
     * @param array $filtros (opcional: com_catmat, tipo, valor_min, valor_max)
     * @return array
     */
    public function findByLicitacaoId(string $licitacaoId, array $filtros = []): array
    {
        try {
            $sql = "SELECT * FROM itens_licitacao WHERE licitacao_id = ?";
            $params = [$licitacaoId];

            // Filtro: apenas itens com CATMAT
            if (!empty($filtros['com_catmat'])) {
                $sql .= " AND codigo_catmat IS NOT NULL";
            }

            // Filtro: tipo (M ou S)
            if (!empty($filtros['tipo'])) {
                $sql .= " AND tipo_item = ?";
                $params[] = $filtros['tipo'];
            }

            // Filtro: valor mínimo
            if (isset($filtros['valor_min'])) {
                $sql .= " AND valor_unitario_estimado >= ?";
                $params[] = $filtros['valor_min'];
            }

            // Filtro: valor máximo
            if (isset($filtros['valor_max'])) {
                $sql .= " AND valor_unitario_estimado <= ?";
                $params[] = $filtros['valor_max'];
            }

            $sql .= " ORDER BY numero_item ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $itens = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $itens[] = $this->mapToModel($row);
            }

            return $itens;

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao buscar itens: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar itens por código CATMAT
     *
     * @param string $codigoCatmat
     * @param int $limite
     * @return array
     */
    public function findByCatmat(string $codigoCatmat, int $limite = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM itens_licitacao
                WHERE codigo_catmat = ?
                ORDER BY sincronizado_em DESC
                LIMIT ?
            ");

            $stmt->execute([$codigoCatmat, $limite]);

            $itens = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $itens[] = $this->mapToModel($row);
            }

            return $itens;

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao buscar por CATMAT: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar itens similares por descrição (FULLTEXT search)
     *
     * @param string $termo
     * @param int $limite
     * @return array
     */
    public function buscarSimilares(string $termo, int $limite = 20): array
    {
        try {
            // FULLTEXT search
            $stmt = $this->db->prepare("
                SELECT *,
                       MATCH(descricao, descricao_complementar)
                       AGAINST(? IN NATURAL LANGUAGE MODE) as relevancia
                FROM itens_licitacao
                WHERE MATCH(descricao, descricao_complementar)
                      AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevancia DESC, sincronizado_em DESC
                LIMIT ?
            ");

            $stmt->execute([$termo, $termo, $limite]);

            $itens = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item = $this->mapToModel($row);
                $itens[] = [
                    'item' => $item,
                    'relevancia' => $row['relevancia']
                ];
            }

            return $itens;

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro na busca textual: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Deletar todos itens de uma licitação
     *
     * @param string $licitacaoId
     * @return bool
     */
    public function deleteByLicitacaoId(string $licitacaoId): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM itens_licitacao
                WHERE licitacao_id = ?
            ");

            $stmt->execute([$licitacaoId]);

            return true;

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao deletar itens: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar itens de uma licitação
     *
     * @param string $licitacaoId
     * @return int
     */
    public function countByLicitacaoId(string $licitacaoId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM itens_licitacao
                WHERE licitacao_id = ?
            ");

            $stmt->execute([$licitacaoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total'] ?? 0);

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao contar itens: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obter estatísticas gerais
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) as total_itens,
                    COUNT(DISTINCT licitacao_id) as total_licitacoes,
                    COUNT(CASE WHEN codigo_catmat IS NOT NULL THEN 1 END) as itens_com_catmat,
                    COUNT(CASE WHEN tipo_item = 'M' THEN 1 END) as total_materiais,
                    COUNT(CASE WHEN tipo_item = 'S' THEN 1 END) as total_servicos,
                    AVG(valor_unitario_estimado) as valor_medio,
                    MIN(valor_unitario_estimado) as valor_minimo,
                    MAX(valor_unitario_estimado) as valor_maximo,
                    SUM(valor_total_estimado) as valor_total_geral,
                    MAX(sincronizado_em) as ultima_sincronizacao
                FROM itens_licitacao
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            error_log("[ItemLicitacaoRepository] Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapear dados do banco para Model
     *
     * @param array $data
     * @return ItemLicitacao
     */
    private function mapToModel(array $data): ItemLicitacao
    {
        $item = new ItemLicitacao();

        $item->id = $data['id'];
        $item->licitacao_id = $data['licitacao_id'];
        $item->numero_item = (int)$data['numero_item'];
        $item->codigo_catmat = $data['codigo_catmat'];
        $item->codigo_ncm = $data['codigo_ncm'];
        $item->descricao = $data['descricao'];
        $item->descricao_complementar = $data['descricao_complementar'];
        $item->quantidade = (float)$data['quantidade'];
        $item->unidade_medida = $data['unidade_medida'];
        $item->valor_unitario_estimado = $data['valor_unitario_estimado'] ? (float)$data['valor_unitario_estimado'] : null;
        $item->valor_total_estimado = $data['valor_total_estimado'] ? (float)$data['valor_total_estimado'] : null;
        $item->tipo_item = $data['tipo_item'];
        $item->tipo_item_nome = $data['tipo_item_nome'];
        $item->categoria_item = $data['categoria_item'];
        $item->criterio_julgamento = $data['criterio_julgamento'];
        $item->orcamento_sigiloso = (bool)$data['orcamento_sigiloso'];
        $item->beneficio_me = (bool)$data['beneficio_me'];
        $item->situacao = $data['situacao'];
        $item->situacao_nome = $data['situacao_nome'];
        $item->tem_resultado = (bool)$data['tem_resultado'];
        $item->data_inclusao_pncp = $data['data_inclusao_pncp'];
        $item->data_atualizacao_pncp = $data['data_atualizacao_pncp'];
        $item->sincronizado_em = $data['sincronizado_em'];
        $item->atualizado_em = $data['atualizado_em'];

        return $item;
    }
}
