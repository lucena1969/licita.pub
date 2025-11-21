<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Licitacao;
use PDO;

class LicitacaoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar nova licitação
     */
    public function create(Licitacao $licitacao): Licitacao
    {
        $sql = "INSERT INTO licitacoes (
            id, pncp_id, orgao_id, numero, objeto, objeto_detalhado, modalidade, situacao,
            valor_estimado, data_publicacao, data_abertura, data_encerramento,
            uf, municipio, url_edital, url_pncp, nome_orgao, cnpj_orgao
        ) VALUES (
            :id, :pncp_id, :orgao_id, :numero, :objeto, :objeto_detalhado, :modalidade, :situacao,
            :valor_estimado, :data_publicacao, :data_abertura, :data_encerramento,
            :uf, :municipio, :url_edital, :url_pncp, :nome_orgao, :cnpj_orgao
        )";

        $stmt = $this->db->prepare($sql);

        $licitacao->id = Licitacao::generateUUID();

        $stmt->execute([
            ':id' => $licitacao->id,
            ':pncp_id' => $licitacao->pncp_id,
            ':orgao_id' => $licitacao->orgao_id,
            ':numero' => $licitacao->numero,
            ':objeto' => $licitacao->objeto,
            ':objeto_detalhado' => $licitacao->objeto_detalhado,
            ':modalidade' => $licitacao->modalidade,
            ':situacao' => $licitacao->situacao,
            ':valor_estimado' => $licitacao->valor_estimado,
            ':data_publicacao' => $licitacao->data_publicacao,
            ':data_abertura' => $licitacao->data_abertura,
            ':data_encerramento' => $licitacao->data_encerramento,
            ':uf' => $licitacao->uf,
            ':municipio' => $licitacao->municipio,
            ':url_edital' => $licitacao->url_edital,
            ':url_pncp' => $licitacao->url_pncp,
            ':nome_orgao' => $licitacao->nome_orgao,
            ':cnpj_orgao' => $licitacao->cnpj_orgao,
        ]);

        return $this->findById($licitacao->id);
    }

    /**
     * Inserir ou atualizar licitação (UPSERT)
     *
     * Usa o campo pncp_id como chave única.
     * Se o pncp_id já existir, atualiza TODOS os campos (exceto id e created_at).
     * Se não existir, insere um novo registro.
     *
     * ⚠️ REQUER: Índice UNIQUE em pncp_id
     *    Execute: backend/database/migrations/004_adicionar_unique_pncp_id.sql
     *
     * @param Licitacao $licitacao Objeto licitação com dados para inserir/atualizar
     * @return array ['inserido' => bool, 'licitacao' => Licitacao]
     */
    public function upsert(Licitacao $licitacao): array
    {
        // Gerar ID se não existir
        if (empty($licitacao->id)) {
            $licitacao->id = Licitacao::generateUUID();
        }

        $sql = "INSERT INTO licitacoes (
            id, pncp_id, orgao_id, numero, objeto, objeto_detalhado, modalidade, situacao,
            valor_estimado, data_publicacao, data_abertura, data_encerramento,
            uf, municipio, url_edital, url_pncp, nome_orgao, cnpj_orgao
        ) VALUES (
            :id, :pncp_id, :orgao_id, :numero, :objeto, :objeto_detalhado, :modalidade, :situacao,
            :valor_estimado, :data_publicacao, :data_abertura, :data_encerramento,
            :uf, :municipio, :url_edital, :url_pncp, :nome_orgao, :cnpj_orgao
        ) ON DUPLICATE KEY UPDATE
            objeto = VALUES(objeto),
            objeto_detalhado = VALUES(objeto_detalhado),
            modalidade = VALUES(modalidade),
            situacao = VALUES(situacao),
            valor_estimado = VALUES(valor_estimado),
            data_publicacao = VALUES(data_publicacao),
            data_abertura = VALUES(data_abertura),
            data_encerramento = VALUES(data_encerramento),
            uf = VALUES(uf),
            municipio = VALUES(municipio),
            url_edital = VALUES(url_edital),
            url_pncp = VALUES(url_pncp),
            nome_orgao = VALUES(nome_orgao),
            cnpj_orgao = VALUES(cnpj_orgao),
            numero = VALUES(numero),
            orgao_id = VALUES(orgao_id),
            atualizado_em = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $licitacao->id,
            ':pncp_id' => $licitacao->pncp_id,
            ':orgao_id' => $licitacao->orgao_id,
            ':numero' => $licitacao->numero,
            ':objeto' => $licitacao->objeto,
            ':objeto_detalhado' => $licitacao->objeto_detalhado,
            ':modalidade' => $licitacao->modalidade,
            ':situacao' => $licitacao->situacao,
            ':valor_estimado' => $licitacao->valor_estimado,
            ':data_publicacao' => $licitacao->data_publicacao,
            ':data_abertura' => $licitacao->data_abertura,
            ':data_encerramento' => $licitacao->data_encerramento,
            ':uf' => $licitacao->uf,
            ':municipio' => $licitacao->municipio,
            ':url_edital' => $licitacao->url_edital,
            ':url_pncp' => $licitacao->url_pncp,
            ':nome_orgao' => $licitacao->nome_orgao,
            ':cnpj_orgao' => $licitacao->cnpj_orgao,
        ]);

        // Verificar se foi inserção (1) ou atualização (2)
        // rowCount() retorna 1 para INSERT, 2 para UPDATE, 0 se nada mudou
        $rowCount = $stmt->rowCount();
        $inserido = ($rowCount === 1);

        $licitacaoAtualizada = $this->findByPncpId($licitacao->pncp_id);

        return [
            'inserido' => $inserido,
            'licitacao' => $licitacaoAtualizada
        ];
    }

    /**
     * Buscar licitação por ID
     */
    public function findById(string $id): ?Licitacao
    {
        $sql = "SELECT * FROM licitacoes WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch();

        return $data ? Licitacao::fromArray($data) : null;
    }

    /**
     * Buscar licitação por PNCP ID
     */
    public function findByPncpId(string $pncpId): ?Licitacao
    {
        $sql = "SELECT * FROM licitacoes WHERE pncp_id = :pncp_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pncp_id' => $pncpId]);

        $data = $stmt->fetch();

        return $data ? Licitacao::fromArray($data) : null;
    }

    /**
     * Listar licitações com filtros e paginação
     */
    public function findAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM licitacoes WHERE 1=1";
        $params = [];

        // Filtros
        if (!empty($filters['uf'])) {
            $sql .= " AND uf = :uf";
            $params[':uf'] = $filters['uf'];
        }

        if (!empty($filters['municipio'])) {
            $sql .= " AND municipio LIKE :municipio";
            $params[':municipio'] = '%' . $filters['municipio'] . '%';
        }

        if (!empty($filters['modalidade'])) {
            $sql .= " AND modalidade = :modalidade";
            $params[':modalidade'] = $filters['modalidade'];
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND situacao = :situacao";
            $params[':situacao'] = $filters['situacao'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND (objeto LIKE :busca OR numero LIKE :busca OR nome_orgao LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        // Ordenação
        $sql .= " ORDER BY data_publicacao DESC, created_at DESC";

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
            $results[] = Licitacao::fromArray($row);
        }

        return $results;
    }

    /**
     * Contar total de licitações com filtros
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM licitacoes WHERE 1=1";
        $params = [];

        // Mesmos filtros do findAll
        if (!empty($filters['uf'])) {
            $sql .= " AND uf = :uf";
            $params[':uf'] = $filters['uf'];
        }

        if (!empty($filters['municipio'])) {
            $sql .= " AND municipio LIKE :municipio";
            $params[':municipio'] = '%' . $filters['municipio'] . '%';
        }

        if (!empty($filters['modalidade'])) {
            $sql .= " AND modalidade = :modalidade";
            $params[':modalidade'] = $filters['modalidade'];
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND situacao = :situacao";
            $params[':situacao'] = $filters['situacao'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND (objeto LIKE :busca OR numero LIKE :busca OR nome_orgao LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();

        return (int)$result['total'];
    }

    /**
     * Obter estatísticas
     */
    public function getEstatisticas(): array
    {
        // Total e soma de valores
        $sql = "SELECT
            COUNT(*) as total_licitacoes,
            SUM(valor_estimado) as total_valor
        FROM licitacoes";

        $stmt = $this->db->query($sql);
        $totais = $stmt->fetch();

        // Por UF
        $sql = "SELECT uf, COUNT(*) as total FROM licitacoes GROUP BY uf ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $porUf = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Por modalidade
        $sql = "SELECT modalidade, COUNT(*) as total FROM licitacoes GROUP BY modalidade ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $porModalidade = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Por situação
        $sql = "SELECT situacao, COUNT(*) as total FROM licitacoes GROUP BY situacao ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $porSituacao = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'total_licitacoes' => (int)$totais['total_licitacoes'],
            'total_valor' => number_format((float)$totais['total_valor'], 2, '.', ''),
            'por_uf' => $porUf,
            'por_modalidade' => $porModalidade,
            'por_situacao' => $porSituacao,
        ];
    }

    /**
     * Atualizar licitação
     */
    public function update(Licitacao $licitacao): bool
    {
        $sql = "UPDATE licitacoes SET
            objeto = :objeto,
            modalidade = :modalidade,
            situacao = :situacao,
            valor_estimado = :valor_estimado,
            data_abertura = :data_abertura,
            data_encerramento = :data_encerramento,
            url_edital = :url_edital,
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $licitacao->id,
            ':objeto' => $licitacao->objeto,
            ':modalidade' => $licitacao->modalidade,
            ':situacao' => $licitacao->situacao,
            ':valor_estimado' => $licitacao->valor_estimado,
            ':data_abertura' => $licitacao->data_abertura,
            ':data_encerramento' => $licitacao->data_encerramento,
            ':url_edital' => $licitacao->url_edital,
        ]);
    }
}
