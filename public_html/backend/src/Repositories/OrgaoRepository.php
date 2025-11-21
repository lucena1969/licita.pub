<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Orgao;
use PDO;

class OrgaoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar ou atualizar órgão
     */
    public function upsert(Orgao $orgao): Orgao
    {
        $sql = "INSERT INTO orgaos (
            id, cnpj, razao_social, nome_fantasia, esfera, poder,
            uf, municipio, tipo, email, telefone
        ) VALUES (
            :id, :cnpj, :razao_social, :nome_fantasia, :esfera, :poder,
            :uf, :municipio, :tipo, :email, :telefone
        ) ON DUPLICATE KEY UPDATE
            razao_social = VALUES(razao_social),
            nome_fantasia = VALUES(nome_fantasia),
            esfera = VALUES(esfera),
            poder = VALUES(poder),
            uf = VALUES(uf),
            municipio = VALUES(municipio),
            tipo = VALUES(tipo),
            email = VALUES(email),
            telefone = VALUES(telefone),
            atualizado_em = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $orgao->id,
            ':cnpj' => $orgao->cnpj,
            ':razao_social' => $orgao->razao_social,
            ':nome_fantasia' => $orgao->nome_fantasia,
            ':esfera' => $orgao->esfera,
            ':poder' => $orgao->poder,
            ':uf' => $orgao->uf,
            ':municipio' => $orgao->municipio,
            ':tipo' => $orgao->tipo,
            ':email' => $orgao->email,
            ':telefone' => $orgao->telefone,
        ]);

        // Retornar o órgão atualizado ou o próprio objeto se não encontrar
        $orgaoAtualizado = $this->findById($orgao->id);
        return $orgaoAtualizado ?? $orgao;
    }

    /**
     * Buscar órgão por ID
     */
    public function findById(string $id): ?Orgao
    {
        $sql = "SELECT * FROM orgaos WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch();

        return $data ? Orgao::fromArray($data) : null;
    }

    /**
     * Buscar órgão por CNPJ
     */
    public function findByCNPJ(string $cnpj): ?Orgao
    {
        $sql = "SELECT * FROM orgaos WHERE cnpj = :cnpj LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cnpj' => $cnpj]);

        $data = $stmt->fetch();

        return $data ? Orgao::fromArray($data) : null;
    }

    /**
     * Listar órgãos com filtros
     */
    public function findAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM orgaos WHERE 1=1";
        $params = [];

        // Filtros
        if (!empty($filters['uf'])) {
            $sql .= " AND uf = :uf";
            $params[':uf'] = $filters['uf'];
        }

        if (!empty($filters['esfera'])) {
            $sql .= " AND esfera = :esfera";
            $params[':esfera'] = $filters['esfera'];
        }

        if (!empty($filters['poder'])) {
            $sql .= " AND poder = :poder";
            $params[':poder'] = $filters['poder'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND (razao_social LIKE :busca OR nome_fantasia LIKE :busca OR cnpj LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        // Ordenação
        $sql .= " ORDER BY razao_social ASC";

        // Paginação
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // Bind params
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = Orgao::fromArray($row);
        }

        return $results;
    }

    /**
     * Contar total de órgãos
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM orgaos WHERE 1=1";
        $params = [];

        // Mesmos filtros do findAll
        if (!empty($filters['uf'])) {
            $sql .= " AND uf = :uf";
            $params[':uf'] = $filters['uf'];
        }

        if (!empty($filters['esfera'])) {
            $sql .= " AND esfera = :esfera";
            $params[':esfera'] = $filters['esfera'];
        }

        if (!empty($filters['poder'])) {
            $sql .= " AND poder = :poder";
            $params[':poder'] = $filters['poder'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND (razao_social LIKE :busca OR nome_fantasia LIKE :busca OR cnpj LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();

        return (int)$result['total'];
    }

    /**
     * Atualizar contadores de licitações e contratos
     */
    public function atualizarContadores(string $orgaoId): void
    {
        $sql = "UPDATE orgaos
                SET
                    total_licitacoes = (SELECT COUNT(*) FROM licitacoes WHERE orgao_id = :orgao_id),
                    total_contratos = (SELECT COUNT(*) FROM contratos WHERE orgao_id = :orgao_id)
                WHERE id = :orgao_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':orgao_id' => $orgaoId]);
    }

    /**
     * Obter estatísticas de órgãos
     */
    public function getEstatisticas(): array
    {
        $sql = "SELECT
            COUNT(*) as total_orgaos,
            COUNT(DISTINCT uf) as total_ufs,
            SUM(total_licitacoes) as total_licitacoes,
            SUM(total_contratos) as total_contratos
        FROM orgaos";

        $stmt = $this->db->query($sql);
        $totais = $stmt->fetch();

        // Por esfera
        $sql = "SELECT esfera, COUNT(*) as total FROM orgaos GROUP BY esfera ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $porEsfera = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Por poder
        $sql = "SELECT poder, COUNT(*) as total FROM orgaos GROUP BY poder ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $porPoder = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Por UF
        $sql = "SELECT uf, COUNT(*) as total FROM orgaos GROUP BY uf ORDER BY total DESC LIMIT 10";
        $stmt = $this->db->query($sql);
        $porUf = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'total_orgaos' => (int)$totais['total_orgaos'],
            'total_ufs' => (int)$totais['total_ufs'],
            'total_licitacoes' => (int)$totais['total_licitacoes'],
            'total_contratos' => (int)$totais['total_contratos'],
            'por_esfera' => $porEsfera,
            'por_poder' => $porPoder,
            'por_uf' => $porUf,
        ];
    }
}
