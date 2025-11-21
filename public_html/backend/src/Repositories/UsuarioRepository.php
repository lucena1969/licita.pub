<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Usuario;
use PDO;

class UsuarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Criar novo usuário
     */
    public function create(Usuario $usuario): Usuario
    {
        $sql = "INSERT INTO usuarios (
            id, email, senha, nome, telefone, cpf_cnpj,
            email_verificado, token_verificacao, token_verificacao_expira,
            ativo, plano
        ) VALUES (
            :id, :email, :senha, :nome, :telefone, :cpf_cnpj,
            :email_verificado, :token_verificacao, :token_verificacao_expira,
            :ativo, :plano
        )";

        $stmt = $this->db->prepare($sql);

        $usuario->id = Usuario::generateUUID();

        $stmt->execute([
            ':id' => $usuario->id,
            ':email' => $usuario->email,
            ':senha' => $usuario->senha,
            ':nome' => $usuario->nome,
            ':telefone' => $usuario->telefone,
            ':cpf_cnpj' => $usuario->cpf_cnpj,
            ':email_verificado' => $usuario->email_verificado ? 1 : 0,
            ':token_verificacao' => $usuario->token_verificacao,
            ':token_verificacao_expira' => $usuario->token_verificacao_expira,
            ':ativo' => $usuario->ativo ? 1 : 0,
            ':plano' => $usuario->plano,
        ]);

        return $this->findById($usuario->id);
    }

    /**
     * Buscar usuário por ID
     */
    public function findById(string $id): ?Usuario
    {
        $sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch();

        return $data ? Usuario::fromArray($data) : null;
    }

    /**
     * Buscar usuário por email
     */
    public function findByEmail(string $email): ?Usuario
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        $data = $stmt->fetch();

        return $data ? Usuario::fromArray($data) : null;
    }

    /**
     * Atualizar usuário
     */
    public function update(Usuario $usuario): bool
    {
        $sql = "UPDATE usuarios SET
            email = :email,
            nome = :nome,
            telefone = :telefone,
            cpf_cnpj = :cpf_cnpj,
            email_verificado = :email_verificado,
            token_verificacao = :token_verificacao,
            token_verificacao_expira = :token_verificacao_expira,
            token_reset_senha = :token_reset_senha,
            token_reset_senha_expira = :token_reset_senha_expira,
            ativo = :ativo,
            plano = :plano,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $usuario->id,
            ':email' => $usuario->email,
            ':nome' => $usuario->nome,
            ':telefone' => $usuario->telefone,
            ':cpf_cnpj' => $usuario->cpf_cnpj,
            ':email_verificado' => $usuario->email_verificado ? 1 : 0,
            ':token_verificacao' => $usuario->token_verificacao,
            ':token_verificacao_expira' => $usuario->token_verificacao_expira,
            ':token_reset_senha' => $usuario->token_reset_senha,
            ':token_reset_senha_expira' => $usuario->token_reset_senha_expira,
            ':ativo' => $usuario->ativo ? 1 : 0,
            ':plano' => $usuario->plano,
        ]);
    }

    /**
     * Atualizar senha
     */
    public function updatePassword(string $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE usuarios SET senha = :senha, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $userId,
            ':senha' => $hashedPassword,
        ]);
    }

    /**
     * Buscar usuário por token de reset de senha
     */
    public function findByResetToken(string $token): ?Usuario
    {
        $sql = "SELECT * FROM usuarios WHERE token_reset_senha = :token AND token_reset_senha_expira > NOW() LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);

        $data = $stmt->fetch();

        return $data ? Usuario::fromArray($data) : null;
    }

    /**
     * Verificar se email existe
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(*) as count FROM usuarios WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        $result = $stmt->fetch();

        return $result['count'] > 0;
    }

    /**
     * Deletar usuário
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Incrementar contador de consultas do usuário
     */
    public function incrementarConsulta(string $usuarioId): bool
    {
        $sql = "UPDATE usuarios
                SET consultas_hoje = consultas_hoje + 1,
                    primeira_consulta_em = COALESCE(primeira_consulta_em, NOW())
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $usuarioId]);
    }
}
