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
    public string $plano = 'GRATUITO';
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
        $usuario->plano = $data['plano'] ?? 'GRATUITO';
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
}
