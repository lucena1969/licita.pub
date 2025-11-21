<?php

namespace App\Models;

use DateTime;

/**
 * Model: LimiteIP
 *
 * Representa o controle de limites para usuários anônimos por IP
 * - Limite: 5 consultas detalhadas por dia
 * - Reset: 24h após primeira consulta
 */
class LimiteIP
{
    /** @var int Limite diário para usuários anônimos */
    public const LIMITE_ANONIMO = 5;

    public string $ip;
    public int $consultas_hoje = 0;
    public ?string $primeira_consulta_em = null;
    public ?string $criado_em = null;
    public ?string $atualizado_em = null;

    /**
     * Construtor
     */
    public function __construct(
        string $ip,
        int $consultas_hoje = 0,
        ?string $primeira_consulta_em = null,
        ?string $criado_em = null,
        ?string $atualizado_em = null
    ) {
        $this->ip = $ip;
        $this->consultas_hoje = $consultas_hoje;
        $this->primeira_consulta_em = $primeira_consulta_em;
        $this->criado_em = $criado_em;
        $this->atualizado_em = $atualizado_em;
    }

    /**
     * Criar instância a partir de array (resultado do banco)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ip: $data['ip'],
            consultas_hoje: (int)($data['consultas_hoje'] ?? 0),
            primeira_consulta_em: $data['primeira_consulta_em'] ?? null,
            criado_em: $data['criado_em'] ?? null,
            atualizado_em: $data['atualizado_em'] ?? null
        );
    }

    /**
     * Converter para array (para inserir/atualizar no banco)
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'consultas_hoje' => $this->consultas_hoje,
            'primeira_consulta_em' => $this->primeira_consulta_em,
        ];
    }

    /**
     * Verificar se atingiu o limite diário
     */
    public function atingiuLimite(): bool
    {
        return $this->consultas_hoje >= self::LIMITE_ANONIMO;
    }

    /**
     * Obter quantas consultas ainda restam
     */
    public function getLimiteRestante(): int
    {
        $restante = self::LIMITE_ANONIMO - $this->consultas_hoje;
        return max(0, $restante);
    }

    /**
     * Verificar se passou 24h desde a primeira consulta
     */
    public function passou24h(): bool
    {
        if (!$this->primeira_consulta_em) {
            return false;
        }

        try {
            $primeira = new DateTime($this->primeira_consulta_em);
            $agora = new DateTime();
            $diff = $agora->getTimestamp() - $primeira->getTimestamp();

            // 24 horas = 86400 segundos
            return $diff >= 86400;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calcular tempo restante para reset (em segundos)
     */
    public function getTempoRestanteParaReset(): int
    {
        if (!$this->primeira_consulta_em) {
            return 0;
        }

        try {
            $primeira = new DateTime($this->primeira_consulta_em);
            $reset = clone $primeira;
            $reset->modify('+24 hours');
            $agora = new DateTime();

            $diff = $reset->getTimestamp() - $agora->getTimestamp();
            return max(0, $diff);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Formatar tempo restante para exibição (ex: "Renova em 5h 30min")
     */
    public function getTempoRestanteFormatado(): string
    {
        $segundos = $this->getTempoRestanteParaReset();

        if ($segundos === 0) {
            return 'Agora';
        }

        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);

        if ($horas > 0) {
            return sprintf("Renova em %dh %dmin", $horas, $minutos);
        }

        return sprintf("Renova em %d minutos", $minutos);
    }

    /**
     * Resetar contadores (após 24h)
     */
    public function resetar(): void
    {
        $this->consultas_hoje = 0;
        $this->primeira_consulta_em = null;
    }

    /**
     * Incrementar contador de consultas
     */
    public function incrementarConsulta(): void
    {
        $this->consultas_hoje++;

        // Se é a primeira consulta, registrar timestamp
        if ($this->primeira_consulta_em === null) {
            $this->primeira_consulta_em = (new DateTime())->format('Y-m-d H:i:s');
        }
    }

    /**
     * Obter informações de limite em formato para API
     */
    public function getInfoLimite(): array
    {
        return [
            'tipo' => 'ANONIMO',
            'limite_diario' => self::LIMITE_ANONIMO,
            'consultas_hoje' => $this->consultas_hoje,
            'restantes' => $this->getLimiteRestante(),
            'atingiu_limite' => $this->atingiuLimite(),
            'tempo_restante_segundos' => $this->getTempoRestanteParaReset(),
            'tempo_restante_formatado' => $this->getTempoRestanteFormatado(),
            'primeira_consulta_em' => $this->primeira_consulta_em
        ];
    }

    /**
     * Validar formato de IP
     */
    public static function validarIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
