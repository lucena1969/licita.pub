<?php

namespace App\Repositories;

use App\Models\LimiteIP;
use PDO;

/**
 * Repository: LimiteIPRepository
 *
 * Gerencia operações de banco de dados para limites por IP (usuários anônimos)
 */
class LimiteIPRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Buscar limite por IP
     */
    public function findByIP(string $ip): ?LimiteIP
    {
        $sql = "SELECT * FROM limites_ip WHERE ip = :ip LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ip' => $ip]);

        $data = $stmt->fetch();

        return $data ? LimiteIP::fromArray($data) : null;
    }

    /**
     * Criar novo registro de limite para IP
     */
    public function create(LimiteIP $limiteIP): bool
    {
        $sql = "
            INSERT INTO limites_ip (ip, consultas_hoje, primeira_consulta_em)
            VALUES (:ip, :consultas_hoje, :primeira_consulta_em)
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':ip' => $limiteIP->ip,
            ':consultas_hoje' => $limiteIP->consultas_hoje,
            ':primeira_consulta_em' => $limiteIP->primeira_consulta_em,
        ]);
    }

    /**
     * Atualizar registro existente
     */
    public function update(LimiteIP $limiteIP): bool
    {
        $sql = "
            UPDATE limites_ip
            SET
                consultas_hoje = :consultas_hoje,
                primeira_consulta_em = :primeira_consulta_em,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE ip = :ip
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':ip' => $limiteIP->ip,
            ':consultas_hoje' => $limiteIP->consultas_hoje,
            ':primeira_consulta_em' => $limiteIP->primeira_consulta_em,
        ]);
    }

    /**
     * Incrementar contador de consultas para um IP
     * Retorna o LimiteIP atualizado
     */
    public function incrementarConsulta(string $ip): LimiteIP
    {
        // Buscar ou criar registro
        $limiteIP = $this->findByIP($ip);

        if (!$limiteIP) {
            // Criar novo registro
            $limiteIP = new LimiteIP($ip, 1, date('Y-m-d H:i:s'));
            $this->create($limiteIP);
        } else {
            // Resetar se passou 24h
            if ($limiteIP->passou24h()) {
                $limiteIP->resetar();
            }

            // Incrementar
            $limiteIP->incrementarConsulta();
            $this->update($limiteIP);
        }

        return $limiteIP;
    }

    /**
     * Resetar consultas se passou 24h
     * Retorna true se resetou, false se ainda não passou 24h
     */
    public function resetarSePassou24h(string $ip): bool
    {
        $limiteIP = $this->findByIP($ip);

        if (!$limiteIP) {
            return false;
        }

        if ($limiteIP->passou24h()) {
            $limiteIP->resetar();
            $this->update($limiteIP);
            return true;
        }

        return false;
    }

    /**
     * Limpar registros inativos há mais de X dias (para manutenção)
     * @param int $dias Número de dias de inatividade (padrão: 7)
     */
    public function limparRegistrosAntigos(int $dias = 7): int
    {
        $sql = "
            DELETE FROM limites_ip
            WHERE atualizado_em < DATE_SUB(NOW(), INTERVAL :dias DAY)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dias' => $dias]);

        return $stmt->rowCount();
    }

    /**
     * Obter estatísticas de IPs anônimos
     */
    public function getEstatisticas(): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_ips,
                SUM(consultas_hoje) as total_consultas,
                AVG(consultas_hoje) as media_consultas,
                COUNT(CASE WHEN consultas_hoje >= :limite THEN 1 END) as ips_no_limite
            FROM limites_ip
            WHERE primeira_consulta_em > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':limite' => LimiteIP::LIMITE_ANONIMO]);

        return $stmt->fetch() ?: [];
    }

    /**
     * Listar IPs que atingiram o limite
     */
    public function listarIPsNoLimite(): array
    {
        $sql = "
            SELECT *
            FROM limites_ip
            WHERE consultas_hoje >= :limite
              AND primeira_consulta_em > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY atualizado_em DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':limite' => LimiteIP::LIMITE_ANONIMO]);

        $results = $stmt->fetchAll();
        return array_map(fn($data) => LimiteIP::fromArray($data), $results);
    }

    /**
     * Resetar todos os limites (usar com cuidado!)
     */
    public function resetarTodos(): int
    {
        $sql = "
            UPDATE limites_ip
            SET consultas_hoje = 0,
                primeira_consulta_em = NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Contar total de IPs únicos ativos (últimas 24h)
     */
    public function contarIPsAtivos(): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM limites_ip
            WHERE primeira_consulta_em > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return (int)($result['total'] ?? 0);
    }
}
