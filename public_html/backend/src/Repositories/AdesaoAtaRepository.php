<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\AdesaoAta;
use PDO;

class AdesaoAtaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar nova adesão
     */
    public function create(AdesaoAta $adesao): AdesaoAta
    {
        $sql = "INSERT INTO adesoes_ata (
            id, ata_id, orgao_aderente_id, orgao_aderente_nome, orgao_aderente_cnpj,
            data_adesao, valor_estimado, situacao, url_documento
        ) VALUES (
            :id, :ata_id, :orgao_aderente_id, :orgao_aderente_nome, :orgao_aderente_cnpj,
            :data_adesao, :valor_estimado, :situacao, :url_documento
        )";

        $stmt = $this->db->prepare($sql);

        if (empty($adesao->id)) {
            $adesao->id = AdesaoAta::generateUUID();
        }

        $stmt->execute([
            ':id' => $adesao->id,
            ':ata_id' => $adesao->ata_id,
            ':orgao_aderente_id' => $adesao->orgao_aderente_id,
            ':orgao_aderente_nome' => $adesao->orgao_aderente_nome,
            ':orgao_aderente_cnpj' => $adesao->orgao_aderente_cnpj,
            ':data_adesao' => $adesao->data_adesao,
            ':valor_estimado' => $adesao->valor_estimado,
            ':situacao' => $adesao->situacao,
            ':url_documento' => $adesao->url_documento,
        ]);

        return $this->findById($adesao->id);
    }

    /**
     * Buscar adesão por ID
     */
    public function findById(string $id): ?AdesaoAta
    {
        $sql = "SELECT * FROM adesoes_ata WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return AdesaoAta::fromArray($data);
    }

    /**
     * Buscar adesões por ata
     */
    public function findByAta(string $ataId): array
    {
        $sql = "SELECT * FROM adesoes_ata
                WHERE ata_id = :ata_id
                ORDER BY data_adesao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ata_id' => $ataId]);

        $adesoes = [];
        while ($row = $stmt->fetch()) {
            $adesoes[] = AdesaoAta::fromArray($row);
        }

        return $adesoes;
    }

    /**
     * Buscar adesões por órgão aderente
     */
    public function findByOrgao(string $orgaoId): array
    {
        $sql = "SELECT ad.*, at.numero as ata_numero, at.objeto
                FROM adesoes_ata ad
                INNER JOIN atas_registro_preco at ON ad.ata_id = at.id
                WHERE ad.orgao_aderente_id = :orgao_id
                ORDER BY ad.data_adesao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':orgao_id' => $orgaoId]);

        $adesoes = [];
        while ($row = $stmt->fetch()) {
            $adesao = AdesaoAta::fromArray($row);
            // Adicionar dados extras da ata
            $adesao->ata_numero = $row['ata_numero'] ?? null;
            $adesao->ata_objeto = $row['objeto'] ?? null;
            $adesoes[] = $adesao;
        }

        return $adesoes;
    }

    /**
     * Buscar adesões por CNPJ do órgão
     */
    public function findByCnpj(string $cnpj): array
    {
        $sql = "SELECT ad.*, at.numero as ata_numero, at.objeto
                FROM adesoes_ata ad
                INNER JOIN atas_registro_preco at ON ad.ata_id = at.id
                WHERE ad.orgao_aderente_cnpj = :cnpj
                ORDER BY ad.data_adesao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cnpj' => preg_replace('/[^0-9]/', '', $cnpj)]);

        $adesoes = [];
        while ($row = $stmt->fetch()) {
            $adesoes[] = AdesaoAta::fromArray($row);
        }

        return $adesoes;
    }

    /**
     * Contar adesões por ata
     */
    public function countByAta(string $ataId): int
    {
        $sql = "SELECT COUNT(*) as total FROM adesoes_ata WHERE ata_id = :ata_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ata_id' => $ataId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obter estatísticas de adesões
     */
    public function obterEstatisticas(): array
    {
        $sql = "SELECT
                    COUNT(*) as total_adesoes,
                    COUNT(DISTINCT ata_id) as atas_com_adesao,
                    COUNT(DISTINCT orgao_aderente_id) as orgaos_distintos,
                    SUM(valor_estimado) as valor_total,
                    AVG(valor_estimado) as valor_medio
                FROM adesoes_ata
                WHERE situacao = :situacao";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':situacao' => AdesaoAta::SITUACAO_ATIVO]);

        return $stmt->fetch();
    }

    /**
     * Obter ranking de atas mais utilizadas (com mais adesões)
     */
    public function obterAtasMaisUtilizadas(int $limit = 10): array
    {
        $sql = "SELECT
                    at.id,
                    at.numero,
                    at.objeto,
                    at.orgao_gerenciador_nome,
                    COUNT(ad.id) as total_adesoes,
                    SUM(ad.valor_estimado) as valor_total_adesoes
                FROM atas_registro_preco at
                INNER JOIN adesoes_ata ad ON at.id = ad.ata_id
                WHERE ad.situacao = :situacao
                GROUP BY at.id
                ORDER BY total_adesoes DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':situacao', AdesaoAta::SITUACAO_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obter ranking de órgãos que mais fazem adesões
     */
    public function obterOrgaosMaisAderentes(int $limit = 10): array
    {
        $sql = "SELECT
                    orgao_aderente_id,
                    orgao_aderente_nome,
                    orgao_aderente_cnpj,
                    COUNT(*) as total_adesoes,
                    SUM(valor_estimado) as valor_total
                FROM adesoes_ata
                WHERE situacao = :situacao
                GROUP BY orgao_aderente_id, orgao_aderente_nome, orgao_aderente_cnpj
                ORDER BY total_adesoes DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':situacao', AdesaoAta::SITUACAO_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Buscar adesões recentes
     */
    public function findRecentes(int $limit = 10): array
    {
        $sql = "SELECT ad.*, at.numero as ata_numero, at.objeto
                FROM adesoes_ata ad
                INNER JOIN atas_registro_preco at ON ad.ata_id = at.id
                ORDER BY ad.data_adesao DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $adesoes = [];
        while ($row = $stmt->fetch()) {
            $adesoes[] = AdesaoAta::fromArray($row);
        }

        return $adesoes;
    }

    /**
     * Atualizar situação da adesão
     */
    public function atualizarSituacao(string $id, string $situacao): bool
    {
        $sql = "UPDATE adesoes_ata
                SET situacao = :situacao
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':situacao' => $situacao
        ]);
    }

    /**
     * Deletar adesão
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM adesoes_ata WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verificar se órgão já aderiu a uma ata específica
     */
    public function jaAderiu(string $ataId, string $orgaoId): bool
    {
        $sql = "SELECT COUNT(*) FROM adesoes_ata
                WHERE ata_id = :ata_id
                AND orgao_aderente_id = :orgao_id
                AND situacao = :situacao";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ata_id' => $ataId,
            ':orgao_id' => $orgaoId,
            ':situacao' => AdesaoAta::SITUACAO_ATIVO
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Obter valor total de adesões por período
     */
    public function valorTotalPorPeriodo(string $dataInicio, string $dataFim): float
    {
        $sql = "SELECT COALESCE(SUM(valor_estimado), 0) as total
                FROM adesoes_ata
                WHERE data_adesao BETWEEN :data_inicio AND :data_fim
                AND situacao = :situacao";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
            ':situacao' => AdesaoAta::SITUACAO_ATIVO
        ]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Buscar adesões com filtros avançados
     */
    public function buscarComFiltros(?array $filtros = []): array
    {
        $where = ["1=1"];
        $params = [];

        if (isset($filtros['ata_id'])) {
            $where[] = "ad.ata_id = :ata_id";
            $params[':ata_id'] = $filtros['ata_id'];
        }

        if (isset($filtros['situacao'])) {
            $where[] = "ad.situacao = :situacao";
            $params[':situacao'] = $filtros['situacao'];
        }

        if (isset($filtros['data_inicio'])) {
            $where[] = "ad.data_adesao >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }

        if (isset($filtros['data_fim'])) {
            $where[] = "ad.data_adesao <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }

        if (isset($filtros['valor_min'])) {
            $where[] = "ad.valor_estimado >= :valor_min";
            $params[':valor_min'] = $filtros['valor_min'];
        }

        $limit = $filtros['limit'] ?? 100;
        $offset = $filtros['offset'] ?? 0;

        $sql = "SELECT ad.*, at.numero as ata_numero, at.objeto
                FROM adesoes_ata ad
                INNER JOIN atas_registro_preco at ON ad.ata_id = at.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ad.data_adesao DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $adesoes = [];
        while ($row = $stmt->fetch()) {
            $adesoes[] = AdesaoAta::fromArray($row);
        }

        return $adesoes;
    }
}
