<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class SessaoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar nova sessão
     */
    public function create(string $sessionId, string $usuarioId, string $ip, ?string $userAgent = null, int $expiresInSeconds = 86400): bool
    {
        $sql = "INSERT INTO sessoes (id, usuario_id, ip, user_agent, expira_em)
                VALUES (:id, :usuario_id, :ip, :user_agent, DATE_ADD(NOW(), INTERVAL :expires SECOND))";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $sessionId,
            ':usuario_id' => $usuarioId,
            ':ip' => $ip,
            ':user_agent' => $userAgent,
            ':expires' => $expiresInSeconds,
        ]);
    }

    /**
     * Buscar sessão por ID
     */
    public function findById(string $sessionId): ?array
    {
        $sql = "SELECT * FROM sessoes WHERE id = :id AND expira_em > NOW() LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $sessionId]);

        $data = $stmt->fetch();

        return $data ?: null;
    }

    /**
     * Buscar sessões ativas de um usuário
     */
    public function findByUsuarioId(string $usuarioId): array
    {
        $sql = "SELECT * FROM sessoes WHERE usuario_id = :usuario_id AND expira_em > NOW() ORDER BY criado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Deletar sessão (logout)
     */
    public function delete(string $sessionId): bool
    {
        $sql = "DELETE FROM sessoes WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $sessionId]);
    }

    /**
     * Deletar todas as sessões de um usuário
     */
    public function deleteByUsuarioId(string $usuarioId): bool
    {
        $sql = "DELETE FROM sessoes WHERE usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':usuario_id' => $usuarioId]);
    }

    /**
     * Limpar sessões expiradas (executar via cron)
     */
    public function deleteExpired(): int
    {
        $sql = "DELETE FROM sessoes WHERE expira_em <= NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Renovar sessão (atualizar expira_em)
     */
    public function renew(string $sessionId, int $expiresInSeconds = 86400): bool
    {
        $sql = "UPDATE sessoes SET expira_em = DATE_ADD(NOW(), INTERVAL :expires SECOND) WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $sessionId,
            ':expires' => $expiresInSeconds,
        ]);
    }
}
