<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use App\Repositories\SessaoRepository;

class AuthService
{
    private UsuarioRepository $usuarioRepo;
    private SessaoRepository $sessaoRepo;

    public function __construct()
    {
        $this->usuarioRepo = new UsuarioRepository();
        $this->sessaoRepo = new SessaoRepository();
    }

    /**
     * Registrar novo usuário
     */
    public function register(array $data): array
    {
        // Validar dados
        $validation = ValidatorService::validateRegistro($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        // Verificar se email já existe
        if ($this->usuarioRepo->emailExists($data['email'])) {
            return [
                'success' => false,
                'errors' => ['Email já cadastrado'],
            ];
        }

        // Criar usuário
        $usuario = new Usuario();
        $usuario->email = strtolower(trim($data['email']));
        $usuario->senha = password_hash($data['senha'], PASSWORD_BCRYPT);
        $usuario->nome = trim($data['nome']);
        $usuario->telefone = isset($data['telefone']) ? preg_replace('/[^0-9]/', '', $data['telefone']) : null;
        $usuario->cpf_cnpj = isset($data['cpf_cnpj']) ? preg_replace('/[^0-9]/', '', $data['cpf_cnpj']) : null;
        $usuario->plano = 'FREE';
        $usuario->ativo = true;
        $usuario->email_verificado = false;

        // Gerar token de verificação (para futuro envio de email)
        $usuario->token_verificacao = bin2hex(random_bytes(32));
        $usuario->token_verificacao_expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        try {
            $usuarioCriado = $this->usuarioRepo->create($usuario);

            return [
                'success' => true,
                'usuario' => $usuarioCriado->toArray(),
                'message' => 'Usuário cadastrado com sucesso',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao cadastrar usuário: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Login
     */
    public function login(string $email, string $senha, string $ip, ?string $userAgent = null): array
    {
        // Validar email
        $emailValidation = ValidatorService::validateEmail($email);
        if (!$emailValidation['valid']) {
            return [
                'success' => false,
                'errors' => ['Email ou senha inválidos'],
            ];
        }

        // Buscar usuário
        $usuario = $this->usuarioRepo->findByEmail(strtolower(trim($email)));

        if (!$usuario) {
            return [
                'success' => false,
                'errors' => ['Email ou senha inválidos'],
            ];
        }

        // Verificar senha
        if (!password_verify($senha, $usuario->senha)) {
            return [
                'success' => false,
                'errors' => ['Email ou senha inválidos'],
            ];
        }

        // Verificar se usuário está ativo
        if (!$usuario->ativo) {
            return [
                'success' => false,
                'errors' => ['Usuário inativo. Entre em contato com o suporte.'],
            ];
        }

        // Criar sessão
        $sessionId = bin2hex(random_bytes(32));
        $expiresIn = 30 * 24 * 60 * 60; // 30 dias

        try {
            $this->sessaoRepo->create($sessionId, $usuario->id, $ip, $userAgent, $expiresIn);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'usuario' => $usuario->toArray(),
                'expires_in' => $expiresIn,
                'message' => 'Login realizado com sucesso',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao criar sessão: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Logout
     */
    public function logout(string $sessionId): array
    {
        try {
            $this->sessaoRepo->delete($sessionId);

            return [
                'success' => true,
                'message' => 'Logout realizado com sucesso',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao fazer logout: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Verificar sessão
     */
    public function verificarSessao(string $sessionId): ?Usuario
    {
        $sessao = $this->sessaoRepo->findById($sessionId);

        if (!$sessao) {
            return null;
        }

        $usuario = $this->usuarioRepo->findById($sessao['usuario_id']);

        if (!$usuario || !$usuario->ativo) {
            return null;
        }

        // Renovar sessão (sliding expiration)
        $this->sessaoRepo->renew($sessionId);

        return $usuario;
    }

    /**
     * Obter usuário atual
     */
    public function me(string $sessionId): array
    {
        $usuario = $this->verificarSessao($sessionId);

        if (!$usuario) {
            return [
                'success' => false,
                'errors' => ['Sessão inválida ou expirada'],
            ];
        }

        return [
            'success' => true,
            'usuario' => $usuario->toArray(),
        ];
    }

    /**
     * Solicitar reset de senha
     */
    public function solicitarResetSenha(string $email): array
    {
        $usuario = $this->usuarioRepo->findByEmail(strtolower(trim($email)));

        if (!$usuario) {
            // Por segurança, retornar sucesso mesmo se email não existir
            return [
                'success' => true,
                'message' => 'Se o email estiver cadastrado, você receberá instruções para redefinir sua senha',
            ];
        }

        // Gerar token de reset
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $usuario->token_reset_senha = $token;
        $usuario->token_reset_senha_expira = $expira;

        try {
            $this->usuarioRepo->update($usuario);

            // TODO: Enviar email com link de reset
            // Por enquanto, retornar o token (apenas para desenvolvimento)

            return [
                'success' => true,
                'message' => 'Se o email estiver cadastrado, você receberá instruções para redefinir sua senha',
                'token' => $token, // REMOVER EM PRODUÇÃO
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao processar solicitação: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Reset de senha com token
     */
    public function resetSenha(string $token, string $novaSenha): array
    {
        // Validar nova senha
        $validation = ValidatorService::validatePassword($novaSenha);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        // Buscar usuário pelo token
        $usuario = $this->usuarioRepo->findByResetToken($token);

        if (!$usuario) {
            return [
                'success' => false,
                'errors' => ['Token inválido ou expirado'],
            ];
        }

        // Atualizar senha
        $hashedPassword = password_hash($novaSenha, PASSWORD_BCRYPT);

        try {
            $this->usuarioRepo->updatePassword($usuario->id, $hashedPassword);

            // Limpar token
            $usuario->token_reset_senha = null;
            $usuario->token_reset_senha_expira = null;
            $this->usuarioRepo->update($usuario);

            // Invalidar todas as sessões do usuário (forçar novo login)
            $this->sessaoRepo->deleteByUsuarioId($usuario->id);

            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao alterar senha: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Alterar senha (usuário logado)
     */
    public function alterarSenha(string $sessionId, string $senhaAtual, string $novaSenha): array
    {
        $usuario = $this->verificarSessao($sessionId);

        if (!$usuario) {
            return [
                'success' => false,
                'errors' => ['Sessão inválida ou expirada'],
            ];
        }

        // Verificar senha atual
        if (!password_verify($senhaAtual, $usuario->senha)) {
            return [
                'success' => false,
                'errors' => ['Senha atual incorreta'],
            ];
        }

        // Validar nova senha
        $validation = ValidatorService::validatePassword($novaSenha);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        // Atualizar senha
        $hashedPassword = password_hash($novaSenha, PASSWORD_BCRYPT);

        try {
            $this->usuarioRepo->updatePassword($usuario->id, $hashedPassword);

            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao alterar senha: ' . $e->getMessage()],
            ];
        }
    }
}
