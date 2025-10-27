<?php

namespace App\Controllers;

use App\Config\Database;
use App\Services\LimiteService;
use PDO;

/**
 * Controller: LicitacaoController
 *
 * Gerencia endpoints de licitações
 * - listar: Listagem simples (sem limite)
 * - buscar: Busca com filtros (sem limite)
 * - detalhes: Detalhes completos (COM LIMITE - usa LimiteConsultaMiddleware)
 */
class LicitacaoController
{
    private PDO $db;
    private LimiteService $limiteService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->limiteService = new LimiteService($this->db);
    }

    /**
     * GET /api/licitacoes/listar.php
     *
     * Lista licitações (sem consumir limite)
     * - Retorna apenas campos básicos
     * - Paginação
     * - Ordenação
     */
    public function listar(): array
    {
        try {
            // Parâmetros de paginação
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
            $offset = ($pagina - 1) * $limite;

            // Ordenação
            $ordenar = $_GET['ordenar'] ?? 'created_at';
            $direcao = strtoupper($_GET['direcao'] ?? 'DESC');

            // Campos permitidos para ordenação
            $camposPermitidos = ['created_at', 'data_publicacao', 'valor_estimado', 'data_abertura'];
            if (!in_array($ordenar, $camposPermitidos)) {
                $ordenar = 'created_at';
            }

            if (!in_array($direcao, ['ASC', 'DESC'])) {
                $direcao = 'DESC';
            }

            // Query base
            $sql = "
                SELECT
                    pncp_id,
                    numero_controle,
                    modalidade,
                    objeto_simplificado,
                    situacao,
                    data_publicacao,
                    valor_estimado,
                    orgao_cnpj,
                    municipio,
                    uf
                FROM licitacoes
                ORDER BY $ordenar $direcao
                LIMIT :limite OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $licitacoes = $stmt->fetchAll();

            // Contar total
            $sqlCount = "SELECT COUNT(*) as total FROM licitacoes";
            $stmtCount = $this->db->query($sqlCount);
            $total = $stmtCount->fetch()['total'];

            return [
                'success' => true,
                'data' => $licitacoes,
                'paginacao' => [
                    'pagina' => $pagina,
                    'limite' => $limite,
                    'total' => (int)$total,
                    'total_paginas' => ceil($total / $limite)
                ]
            ];

        } catch (\Exception $e) {
            error_log("Erro ao listar licitações: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao listar licitações'
            ];
        }
    }

    /**
     * GET /api/licitacoes/buscar.php
     *
     * Busca licitações com filtros (sem consumir limite)
     * - Filtros: UF, município, modalidade, palavra-chave
     */
    public function buscar(): array
    {
        try {
            // Parâmetros de busca
            $uf = $_GET['uf'] ?? null;
            $municipio = $_GET['municipio'] ?? null;
            $modalidade = $_GET['modalidade'] ?? null;
            $palavraChave = $_GET['q'] ?? null;
            $situacao = $_GET['situacao'] ?? null;

            // Paginação
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
            $offset = ($pagina - 1) * $limite;

            // Construir query
            $where = [];
            $params = [];

            if ($uf) {
                $where[] = "uf = :uf";
                $params[':uf'] = strtoupper($uf);
            }

            if ($municipio) {
                $where[] = "LOWER(municipio) LIKE LOWER(:municipio)";
                $params[':municipio'] = "%$municipio%";
            }

            if ($modalidade) {
                $where[] = "modalidade = :modalidade";
                $params[':modalidade'] = $modalidade;
            }

            if ($situacao) {
                $where[] = "situacao = :situacao";
                $params[':situacao'] = $situacao;
            }

            if ($palavraChave) {
                $where[] = "(
                    LOWER(objeto_simplificado) LIKE LOWER(:q) OR
                    LOWER(objeto_detalhado) LIKE LOWER(:q) OR
                    LOWER(numero_controle) LIKE LOWER(:q)
                )";
                $params[':q'] = "%$palavraChave%";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                SELECT
                    pncp_id,
                    numero_controle,
                    modalidade,
                    objeto_simplificado,
                    situacao,
                    data_publicacao,
                    valor_estimado,
                    orgao_cnpj,
                    municipio,
                    uf
                FROM licitacoes
                $whereClause
                ORDER BY created_at DESC
                LIMIT :limite OFFSET :offset
            ";

            $stmt = $this->db->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $licitacoes = $stmt->fetchAll();

            // Contar total
            $sqlCount = "SELECT COUNT(*) as total FROM licitacoes $whereClause";
            $stmtCount = $this->db->prepare($sqlCount);

            foreach ($params as $key => $value) {
                $stmtCount->bindValue($key, $value);
            }

            $stmtCount->execute();
            $total = $stmtCount->fetch()['total'];

            return [
                'success' => true,
                'data' => $licitacoes,
                'filtros' => [
                    'uf' => $uf,
                    'municipio' => $municipio,
                    'modalidade' => $modalidade,
                    'palavra_chave' => $palavraChave,
                    'situacao' => $situacao
                ],
                'paginacao' => [
                    'pagina' => $pagina,
                    'limite' => $limite,
                    'total' => (int)$total,
                    'total_paginas' => ceil($total / $limite)
                ]
            ];

        } catch (\Exception $e) {
            error_log("Erro ao buscar licitações: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar licitações'
            ];
        }
    }

    /**
     * GET /api/licitacoes/detalhes.php?id=xxx
     *
     * Detalhes completos de uma licitação (CONSOME LIMITE)
     * - Deve ser usado APÓS LimiteConsultaMiddleware
     * - Registra consulta no histórico
     */
    public function detalhes(?string $pncpId = null, ?object $request = null): array
    {
        try {
            if (!$pncpId) {
                return [
                    'success' => false,
                    'error' => 'ID_OBRIGATORIO',
                    'message' => 'ID da licitação é obrigatório'
                ];
            }

            // Buscar licitação completa
            $sql = "SELECT * FROM licitacoes WHERE pncp_id = :pncp_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':pncp_id' => $pncpId]);

            $licitacao = $stmt->fetch();

            if (!$licitacao) {
                return [
                    'success' => false,
                    'error' => 'NAO_ENCONTRADO',
                    'message' => 'Licitação não encontrada'
                ];
            }

            // Registrar consulta (incrementar contador)
            if ($request) {
                $usuario = $request->usuario ?? null;
                $ip = $request->ip ?? LimiteService::getClientIP();
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

                $this->limiteService->registrarConsulta(
                    $usuario,
                    $ip,
                    $pncpId,
                    null, // filtros (não se aplica aqui)
                    $userAgent
                );
            }

            // Retornar dados completos + informações de limite
            $response = [
                'success' => true,
                'data' => $licitacao
            ];

            // Adicionar info de limite se disponível
            if ($request && isset($request->limite)) {
                $response['limite'] = $request->limite['info'];
            }

            return $response;

        } catch (\Exception $e) {
            error_log("Erro ao buscar detalhes: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar detalhes da licitação'
            ];
        }
    }

    /**
     * GET /api/licitacoes/estatisticas.php
     *
     * Estatísticas gerais (sem consumir limite)
     */
    public function estatisticas(): array
    {
        try {
            $sql = "
                SELECT
                    COUNT(*) as total,
                    COUNT(DISTINCT uf) as total_ufs,
                    COUNT(DISTINCT orgao_cnpj) as total_orgaos,
                    SUM(valor_estimado) as valor_total,
                    AVG(valor_estimado) as valor_medio
                FROM licitacoes
            ";

            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch();

            return [
                'success' => true,
                'data' => [
                    'total_licitacoes' => (int)$stats['total'],
                    'total_ufs' => (int)$stats['total_ufs'],
                    'total_orgaos' => (int)$stats['total_orgaos'],
                    'valor_total' => (float)$stats['valor_total'],
                    'valor_medio' => (float)$stats['valor_medio']
                ]
            ];

        } catch (\Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'ERRO_SERVIDOR',
                'message' => 'Erro ao buscar estatísticas'
            ];
        }
    }
}
