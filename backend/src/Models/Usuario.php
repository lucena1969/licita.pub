<?php

namespace App\Models;

class Usuario
{
    public ?string $id = null;
    public string $email;
    public string $senha;
    public string $nome;
    public ?string $telefone = null;
    public ?string $cpf_cnpj = null;
    public bool $email_verificado = false;
    public ?string $token_verificacao = null;
    public ?string $token_verificacao_expira = null;
    public ?string $token_reset_senha = null;
    public ?string $token_reset_senha_expira = null;
    public bool $ativo = true;
    public string $plano = 'FREE';
    public int $consultas_hoje = 0;
    public ?string $primeira_consulta_em = null;
    public int $limite_diario = 10;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Criar instância a partir de array
     */
    public static function fromArray(array $data): self
    {
        $usuario = new self();

        $usuario->id = $data['id'] ?? null;
        $usuario->email = $data['email'];
        $usuario->senha = $data['senha'];
        $usuario->nome = $data['nome'];
        $usuario->telefone = $data['telefone'] ?? null;
        $usuario->cpf_cnpj = $data['cpf_cnpj'] ?? null;
        $usuario->email_verificado = (bool)($data['email_verificado'] ?? false);
        $usuario->token_verificacao = $data['token_verificacao'] ?? null;
        $usuario->token_verificacao_expira = $data['token_verificacao_expira'] ?? null;
        $usuario->token_reset_senha = $data['token_reset_senha'] ?? null;
        $usuario->token_reset_senha_expira = $data['token_reset_senha_expira'] ?? null;
        $usuario->ativo = (bool)($data['ativo'] ?? true);
        $usuario->plano = $data['plano'] ?? 'FREE';
        $usuario->consultas_hoje = (int)($data['consultas_hoje'] ?? 0);
        $usuario->primeira_consulta_em = $data['primeira_consulta_em'] ?? null;
        $usuario->limite_diario = (int)($data['limite_diario'] ?? 10);
        $usuario->created_at = $data['created_at'] ?? null;
        $usuario->updated_at = $data['updated_at'] ?? null;

        return $usuario;
    }

    /**
     * Converter para array
     */
    public function toArray(bool $hideSensitive = true): array
    {
        $data = [
            'id' => $this->id,
            'email' => $this->email,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'cpf_cnpj' => $this->cpf_cnpj,
            'email_verificado' => $this->email_verificado,
            'ativo' => $this->ativo,
            'plano' => $this->plano,
            'consultas_hoje' => $this->consultas_hoje,
            'primeira_consulta_em' => $this->primeira_consulta_em,
            'limite_diario' => $this->limite_diario,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Não incluir senha e tokens em respostas
        if (!$hideSensitive) {
            $data['senha'] = $this->senha;
            $data['token_verificacao'] = $this->token_verificacao;
            $data['token_verificacao_expira'] = $this->token_verificacao_expira;
            $data['token_reset_senha'] = $this->token_reset_senha;
            $data['token_reset_senha_expira'] = $this->token_reset_senha_expira;
        }

        return $data;
    }

    /**
     * Gerar UUID v4
     */
    public static function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Verificar se é usuário premium (consultas ilimitadas)
     */
    public function isPremium(): bool
    {
        return in_array($this->plano, ['PREMIUM', 'INTERMEDIARIO', 'BASICO']);
    }

    /**
     * Verificar se atingiu o limite diário
     */
    public function atingiuLimite(): bool
    {
        return $this->consultas_hoje >= $this->limite_diario;
    }

    /**
     * Obter quantas consultas ainda restam
     */
    public function getLimiteRestante(): int
    {
        $restante = $this->limite_diario - $this->consultas_hoje;
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
            $primeira = new \DateTime($this->primeira_consulta_em);
            $agora = new \DateTime();
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
            $primeira = new \DateTime($this->primeira_consulta_em);
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
     * Formatar tempo restante para exibição
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
     * Resetar contadores de consultas (após 24h)
     */
    public function resetarConsultas(): void
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
            $this->primeira_consulta_em = (new \DateTime())->format('Y-m-d H:i:s');
        }
    }
}
