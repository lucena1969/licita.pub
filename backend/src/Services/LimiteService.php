<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\LimiteIP;
use App\Repositories\UsuarioRepository;
use App\Repositories\LimiteIPRepository;
use PDO;

/**
 * Service: LimiteService
 *
 * 核心 do sistema freemium - gerencia limites de consultas
 *
 * Regras:
 * - ANÔNIMO (por IP): 5 consultas/dia
 * - FREE (cadastrado): 10 consultas/dia
 * - PREMIUM: ilimitado (99999 consultas/dia)
 *
 * Reset: 24h após primeira consulta do dia
 */
class LimiteService
{
    private UsuarioRepository $usuarioRepository;
    private LimiteIPRepository $limiteIPRepository;
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->usuarioRepository = new UsuarioRepository($db);
        $this->limiteIPRepository = new LimiteIPRepository($db);
    }

    /**
     * Verificar se usuário/IP pode fazer consulta
     *
     * @param Usuario|null $usuario Usuario autenticado (null se anônimo)
     * @param string $ip IP do cliente
     * @return array ['permitido' => bool, 'restantes' => int, 'tipo' => string, 'mensagem' => string, 'info' => array]
     */
    public function verificarLimite(?Usuario $usuario, string $ip): array
    {
        // Se usuário está logado
        if ($usuario) {
            return $this->verificarLimiteUsuario($usuario);
        }

        // Se é anônimo (por IP)
        return $this->verificarLimiteIP($ip);
    }

    /**
     * Verificar limite para usuário autenticado
     */
    private function verificarLimiteUsuario(Usuario $usuario): array
    {
        // PREMIUM = ilimitado
        if ($usuario->isPremium()) {
            return [
                'permitido' => true,
                'restantes' => 99999,
                'tipo' => 'PREMIUM',
                'mensagem' => 'Consultas ilimitadas',
                'info' => [
                    'limite_diario' => 99999,
                    'consultas_hoje' => $usuario->consultas_hoje,
                    'plano' => $usuario->plano
                ]
            ];
        }

        // FREE = 10 consultas/dia
        // Resetar se passou 24h
        if ($usuario->passou24h()) {
            $usuario->resetarConsultas();
            $this->usuarioRepository->update($usuario);
        }

        $permitido = !$usuario->atingiuLimite();
        $restantes = $usuario->getLimiteRestante();

        return [
            'permitido' => $permitido,
            'restantes' => $restantes,
            'tipo' => 'FREE',
            'mensagem' => $permitido
                ? "Você tem $restantes consultas restantes hoje"
                : 'Você atingiu o limite diário de consultas. Tente novamente amanhã ou atualize para o plano PREMIUM.',
            'info' => [
                'limite_diario' => $usuario->limite_diario,
                'consultas_hoje' => $usuario->consultas_hoje,
                'restantes' => $restantes,
                'atingiu_limite' => $usuario->atingiuLimite(),
                'tempo_restante_segundos' => $usuario->getTempoRestanteParaReset(),
                'tempo_restante_formatado' => $usuario->getTempoRestanteFormatado(),
                'primeira_consulta_em' => $usuario->primeira_consulta_em,
                'plano' => $usuario->plano
            ]
        ];
    }

    /**
     * Verificar limite para IP anônimo
     */
    private function verificarLimiteIP(string $ip): array
    {
        // Buscar ou criar registro
        $limiteIP = $this->limiteIPRepository->findByIP($ip);

        if (!$limiteIP) {
            // Primeiro acesso deste IP
            return [
                'permitido' => true,
                'restantes' => LimiteIP::LIMITE_ANONIMO,
                'tipo' => 'ANONIMO',
                'mensagem' => 'Você tem ' . LimiteIP::LIMITE_ANONIMO . ' consultas gratuitas. Cadastre-se para ter 10 consultas/dia!',
                'info' => [
                    'limite_diario' => LimiteIP::LIMITE_ANONIMO,
                    'consultas_hoje' => 0,
                    'restantes' => LimiteIP::LIMITE_ANONIMO,
                    'atingiu_limite' => false
                ]
            ];
        }

        // Resetar se passou 24h
        if ($limiteIP->passou24h()) {
            $limiteIP->resetar();
            $this->limiteIPRepository->update($limiteIP);
        }

        $permitido = !$limiteIP->atingiuLimite();
        $restantes = $limiteIP->getLimiteRestante();

        return [
            'permitido' => $permitido,
            'restantes' => $restantes,
            'tipo' => 'ANONIMO',
            'mensagem' => $permitido
                ? "Você tem $restantes consultas restantes. Cadastre-se grátis para ter 10 consultas/dia!"
                : 'Você atingiu o limite de consultas gratuitas. Cadastre-se para ter 10 consultas/dia!',
            'info' => $limiteIP->getInfoLimite()
        ];
    }

    /**
     * Registrar consulta (incrementar contador)
     *
     * @param Usuario|null $usuario Usuario autenticado (null se anônimo)
     * @param string $ip IP do cliente
     * @param string $licitacaoId ID da licitação consultada
     * @param array|null $filtros Filtros aplicados
     * @param string|null $userAgent User agent do navegador
     * @return bool Sucesso
     */
    public function registrarConsulta(
        ?Usuario $usuario,
        string $ip,
        string $licitacaoId,
        ?array $filtros = null,
        ?string $userAgent = null
    ): bool {
        try {
            $this->db->beginTransaction();

            // 1. Incrementar contador
            if ($usuario) {
                $this->usuarioRepository->incrementarConsulta($usuario->id);
                $tipoUsuario = $usuario->isPremium() ? 'PREMIUM' : 'FREE';
                $usuarioId = $usuario->id;
            } else {
                $this->limiteIPRepository->incrementarConsulta($ip);
                $tipoUsuario = 'ANONIMO';
                $usuarioId = null;
            }

            // 2. Salvar no histórico (analytics)
            $this->salvarHistorico($usuarioId, $ip, $tipoUsuario, $licitacaoId, $filtros, $userAgent);

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erro ao registrar consulta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Salvar consulta no histórico (para analytics)
     */
    private function salvarHistorico(
        ?string $usuarioId,
        string $ip,
        string $tipoUsuario,
        string $licitacaoId,
        ?array $filtros,
        ?string $userAgent
    ): void {
        $sql = "
            INSERT INTO historico_consultas
            (usuario_id, ip, tipo_usuario, licitacao_pncp_id, filtros, user_agent)
            VALUES
            (:usuario_id, :ip, :tipo_usuario, :licitacao_pncp_id, :filtros, :user_agent)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':ip' => $ip,
            ':tipo_usuario' => $tipoUsuario,
            ':licitacao_pncp_id' => $licitacaoId,
            ':filtros' => $filtros ? json_encode($filtros) : null,
            ':user_agent' => $userAgent
        ]);
    }

    /**
     * Calcular tempo restante para reset (em segundos)
     */
    public function calcularTempoRestante(?string $timestamp): int
    {
        if (!$timestamp) {
            return 0;
        }

        try {
            $primeira = new \DateTime($timestamp);
            $reset = clone $primeira;
            $reset->modify('+24 hours');
            $agora = new \DateTime();

            $diff = $reset->getTimestamp() - $agora->getTimestamp();
            return max(0, $diff);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Verificar se passou 24h desde um timestamp
     */
    public function passou24h(?string $timestamp): bool
    {
        if (!$timestamp) {
            return false;
        }

        try {
            $primeira = new \DateTime($timestamp);
            $agora = new \DateTime();
            $diff = $agora->getTimestamp() - $primeira->getTimestamp();

            return $diff >= 86400; // 24 horas
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obter IP do cliente (considerando proxies e balanceadores)
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Se X-Forwarded-For contém múltiplos IPs, pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obter estatísticas gerais de uso
     */
    public function getEstatisticas(): array
    {
        // Stats de IPs anônimos
        $statsIP = $this->limiteIPRepository->getEstatisticas();

        // Stats de usuários
        $sqlUsuarios = "
            SELECT
                plano,
                COUNT(*) as total_usuarios,
                SUM(consultas_hoje) as total_consultas,
                AVG(consultas_hoje) as media_consultas,
                COUNT(CASE WHEN consultas_hoje >= limite_diario THEN 1 END) as usuarios_no_limite
            FROM usuarios
            WHERE primeira_consulta_em > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY plano
        ";

        $stmt = $this->db->query($sqlUsuarios);
        $statsUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ips_anonimos' => $statsIP,
            'usuarios' => $statsUsuarios,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
