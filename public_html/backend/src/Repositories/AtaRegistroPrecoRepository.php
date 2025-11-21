<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\AtaRegistroPreco;
use PDO;

class AtaRegistroPrecoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar nova ata
     */
    public function create(AtaRegistroPreco $ata): AtaRegistroPreco
    {
        $sql = "INSERT INTO atas_registro_preco (
            id, pncp_id, licitacao_id, numero, objeto,
            orgao_gerenciador_id, orgao_gerenciador_nome, orgao_gerenciador_cnpj,
            data_assinatura, data_vigencia_inicio, data_vigencia_fim,
            situacao, permite_adesao, uf, municipio,
            url_ata, url_pncp
        ) VALUES (
            :id, :pncp_id, :licitacao_id, :numero, :objeto,
            :orgao_gerenciador_id, :orgao_gerenciador_nome, :orgao_gerenciador_cnpj,
            :data_assinatura, :data_vigencia_inicio, :data_vigencia_fim,
            :situacao, :permite_adesao, :uf, :municipio,
            :url_ata, :url_pncp
        )";

        $stmt = $this->db->prepare($sql);

        if (empty($ata->id)) {
            $ata->id = AtaRegistroPreco::generateUUID();
        }

        $stmt->execute([
            ':id' => $ata->id,
            ':pncp_id' => $ata->pncp_id,
            ':licitacao_id' => $ata->licitacao_id,
            ':numero' => $ata->numero,
            ':objeto' => $ata->objeto,
            ':orgao_gerenciador_id' => $ata->orgao_gerenciador_id,
            ':orgao_gerenciador_nome' => $ata->orgao_gerenciador_nome,
            ':orgao_gerenciador_cnpj' => $ata->orgao_gerenciador_cnpj,
            ':data_assinatura' => $ata->data_assinatura,
            ':data_vigencia_inicio' => $ata->data_vigencia_inicio,
            ':data_vigencia_fim' => $ata->data_vigencia_fim,
            ':situacao' => $ata->situacao,
            ':permite_adesao' => $ata->permite_adesao ? 1 : 0,
            ':uf' => $ata->uf,
            ':municipio' => $ata->municipio,
            ':url_ata' => $ata->url_ata,
            ':url_pncp' => $ata->url_pncp,
        ]);

        return $this->findById($ata->id);
    }

    /**
     * Inserir ou atualizar ata (UPSERT)
     */
    public function upsert(AtaRegistroPreco $ata): array
    {
        if (empty($ata->id)) {
            $ata->id = AtaRegistroPreco::generateUUID();
        }

        $sql = "INSERT INTO atas_registro_preco (
            id, pncp_id, licitacao_id, numero, objeto,
            orgao_gerenciador_id, orgao_gerenciador_nome, orgao_gerenciador_cnpj,
            data_assinatura, data_vigencia_inicio, data_vigencia_fim,
            situacao, permite_adesao, uf, municipio,
            url_ata, url_pncp
        ) VALUES (
            :id, :pncp_id, :licitacao_id, :numero, :objeto,
            :orgao_gerenciador_id, :orgao_gerenciador_nome, :orgao_gerenciador_cnpj,
            :data_assinatura, :data_vigencia_inicio, :data_vigencia_fim,
            :situacao, :permite_adesao, :uf, :municipio,
            :url_ata, :url_pncp
        ) ON DUPLICATE KEY UPDATE
            numero = VALUES(numero),
            objeto = VALUES(objeto),
            orgao_gerenciador_nome = VALUES(orgao_gerenciador_nome),
            data_vigencia_fim = VALUES(data_vigencia_fim),
            situacao = VALUES(situacao),
            permite_adesao = VALUES(permite_adesao),
            municipio = VALUES(municipio),
            url_ata = VALUES(url_ata),
            atualizado_em = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $ata->id,
            ':pncp_id' => $ata->pncp_id,
            ':licitacao_id' => $ata->licitacao_id,
            ':numero' => $ata->numero,
            ':objeto' => $ata->objeto,
            ':orgao_gerenciador_id' => $ata->orgao_gerenciador_id,
            ':orgao_gerenciador_nome' => $ata->orgao_gerenciador_nome,
            ':orgao_gerenciador_cnpj' => $ata->orgao_gerenciador_cnpj,
            ':data_assinatura' => $ata->data_assinatura,
            ':data_vigencia_inicio' => $ata->data_vigencia_inicio,
            ':data_vigencia_fim' => $ata->data_vigencia_fim,
            ':situacao' => $ata->situacao,
            ':permite_adesao' => $ata->permite_adesao ? 1 : 0,
            ':uf' => $ata->uf,
            ':municipio' => $ata->municipio,
            ':url_ata' => $ata->url_ata,
            ':url_pncp' => $ata->url_pncp,
        ]);

        $inserido = $stmt->rowCount() === 1;

        return [
            'inserido' => $inserido,
            'ata' => $this->findByPncpId($ata->pncp_id) ?? $ata
        ];
    }

    /**
     * Buscar ata por ID
     */
    public function findById(string $id): ?AtaRegistroPreco
    {
        $sql = "SELECT * FROM atas_registro_preco WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return AtaRegistroPreco::fromArray($data);
    }

    /**
     * Buscar ata por PNCP ID
     */
    public function findByPncpId(string $pncpId): ?AtaRegistroPreco
    {
        $sql = "SELECT * FROM atas_registro_preco WHERE pncp_id = :pncp_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pncp_id' => $pncpId]);

        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return AtaRegistroPreco::fromArray($data);
    }

    /**
     * Buscar atas vigentes
     */
    public function findVigentes(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM atas_registro_preco
                WHERE situacao = :situacao
                AND data_vigencia_fim >= CURDATE()
                ORDER BY data_vigencia_fim ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':situacao', AtaRegistroPreco::SITUACAO_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $atas = [];
        while ($row = $stmt->fetch()) {
            $atas[] = AtaRegistroPreco::fromArray($row);
        }

        return $atas;
    }

    /**
     * Buscar atas por UF
     */
    public function findByUF(string $uf, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM atas_registro_preco
                WHERE uf = :uf
                ORDER BY data_vigencia_fim DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uf', strtoupper($uf), PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $atas = [];
        while ($row = $stmt->fetch()) {
            $atas[] = AtaRegistroPreco::fromArray($row);
        }

        return $atas;
    }

    /**
     * Buscar atas por órgão
     */
    public function findByOrgao(string $orgaoId, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM atas_registro_preco
                WHERE orgao_gerenciador_id = :orgao_id
                ORDER BY data_vigencia_fim DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':orgao_id', $orgaoId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $atas = [];
        while ($row = $stmt->fetch()) {
            $atas[] = AtaRegistroPreco::fromArray($row);
        }

        return $atas;
    }

    /**
     * Buscar atas com texto no objeto (FULLTEXT)
     */
    public function buscarPorTexto(string $texto, ?array $filtros = []): array
    {
        $where = ["MATCH(objeto) AGAINST(:texto IN NATURAL LANGUAGE MODE)"];
        $params = [':texto' => $texto];

        // Filtros opcionais
        if (isset($filtros['uf'])) {
            $where[] = "uf = :uf";
            $params[':uf'] = $filtros['uf'];
        }

        if (isset($filtros['situacao'])) {
            $where[] = "situacao = :situacao";
            $params[':situacao'] = $filtros['situacao'];
        }

        if (isset($filtros['vigente'])) {
            $where[] = "data_vigencia_fim >= CURDATE()";
        }

        if (isset($filtros['permite_adesao'])) {
            $where[] = "permite_adesao = :permite_adesao";
            $params[':permite_adesao'] = $filtros['permite_adesao'] ? 1 : 0;
        }

        $limit = $filtros['limit'] ?? 100;
        $offset = $filtros['offset'] ?? 0;

        $sql = "SELECT * FROM atas_registro_preco
                WHERE " . implode(' AND ', $where) . "
                ORDER BY data_vigencia_fim DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $atas = [];
        while ($row = $stmt->fetch()) {
            $atas[] = AtaRegistroPreco::fromArray($row);
        }

        return $atas;
    }

    /**
     * Contar total de atas
     */
    public function count(?array $filtros = []): int
    {
        $where = ["1=1"];
        $params = [];

        if (isset($filtros['uf'])) {
            $where[] = "uf = :uf";
            $params[':uf'] = $filtros['uf'];
        }

        if (isset($filtros['situacao'])) {
            $where[] = "situacao = :situacao";
            $params[':situacao'] = $filtros['situacao'];
        }

        if (isset($filtros['vigente'])) {
            $where[] = "data_vigencia_fim >= CURDATE()";
        }

        $sql = "SELECT COUNT(*) as total FROM atas_registro_preco WHERE " . implode(' AND ', $where);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Atualizar situação da ata
     */
    public function atualizarSituacao(string $id, string $situacao): bool
    {
        $sql = "UPDATE atas_registro_preco
                SET situacao = :situacao,
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':situacao' => $situacao
        ]);
    }

    /**
     * Deletar ata
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM atas_registro_preco WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Atualizar atas vencidas (executar via cron)
     */
    public function marcarAtasVencidas(): int
    {
        $sql = "UPDATE atas_registro_preco
                SET situacao = :situacao_vencido,
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE data_vigencia_fim < CURDATE()
                AND situacao = :situacao_ativo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':situacao_vencido' => AtaRegistroPreco::SITUACAO_VENCIDO,
            ':situacao_ativo' => AtaRegistroPreco::SITUACAO_ATIVO
        ]);

        return $stmt->rowCount();
    }
}
