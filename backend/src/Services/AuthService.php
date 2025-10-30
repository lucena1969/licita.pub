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

        // Telefone - converter vazio para null
        $telefone = isset($data['telefone']) ? preg_replace('/[^0-9]/', '', $data['telefone']) : '';
        $usuario->telefone = !empty($telefone) ? $telefone : null;

        // CPF/CNPJ - converter vazio para null
        $cpfCnpj = isset($data['cpf_cnpj']) ? preg_replace('/[^0-9]/', '', $data['cpf_cnpj']) : '';
        $usuario->cpf_cnpj = !empty($cpfCnpj) ? $cpfCnpj : null;
        $usuario->plano = 'FREE';
        $usuario->ativo = true;
        $usuario->email_verificado = false;

        // Gerar token de verificação (para futuro envio de email)
        $usuario->token_verificacao = bin2hex(random_bytes(32));
        $usuario->token_verificacao_expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        try {
            $usuarioCriado = $this->usuarioRepo->create($usuario);

            // Enviar email de verificação
            $emailService = new EmailService();
            $emailEnviado = $emailService->enviarEmailVerificacao(
                $usuarioCriado->email,
                $usuarioCriado->nome,
                $usuarioCriado->token_verificacao
            );

            if (!$emailEnviado) {
                error_log("Falha ao enviar email de verificação para: {$usuarioCriado->email}");
            }

            return [
                'success' => true,
                'usuario' => $usuarioCriado->toArray(),
                'message' => 'Usuário cadastrado com sucesso! Verifique seu email para confirmar o cadastro.',
                'email_enviado' => $emailEnviado,
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

        // Verificar se email foi verificado
        if (!$usuario->email_verificado) {
            return [
                'success' => false,
                'errors' => ['Email não verificado. Verifique sua caixa de entrada e clique no link de confirmação.'],
                'email_nao_verificado' => true,
                'email' => $usuario->email,
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

    /**
     * Verificar email com token
     */
    public function verificarEmail(string $token): array
    {
        // Buscar usuário pelo token
        $usuario = $this->usuarioRepo->findByVerificationToken($token);

        if (!$usuario) {
            return [
                'success' => false,
                'errors' => ['Token inválido ou expirado'],
            ];
        }

        // Verificar se token ainda é válido
        if ($usuario->token_verificacao_expira && strtotime($usuario->token_verificacao_expira) < time()) {
            return [
                'success' => false,
                'errors' => ['Token expirado. Solicite um novo email de verificação'],
            ];
        }

        // Marcar email como verificado
        try {
            $this->usuarioRepo->markEmailAsVerified($usuario->id);

            return [
                'success' => true,
                'message' => 'Email verificado com sucesso! Você já pode fazer login.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao verificar email: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Reenviar email de verificação
     */
    public function reenviarEmailVerificacao(string $email): array
    {
        $usuario = $this->usuarioRepo->findByEmail(strtolower(trim($email)));

        if (!$usuario) {
            // Por segurança, retornar sucesso mesmo se email não existir
            return [
                'success' => true,
                'message' => 'Se o email estiver cadastrado e não verificado, você receberá um novo link de verificação',
            ];
        }

        // Se já verificado, não reenviar
        if ($usuario->email_verificado) {
            return [
                'success' => false,
                'errors' => ['Este email já foi verificado'],
            ];
        }

        // Gerar novo token
        $novoToken = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $usuario->token_verificacao = $novoToken;
        $usuario->token_verificacao_expira = $expira;

        try {
            $this->usuarioRepo->update($usuario);

            // Enviar email
            $emailService = new EmailService();
            $enviado = $emailService->enviarEmailVerificacao(
                $usuario->email,
                $usuario->nome,
                $novoToken
            );

            if (!$enviado) {
                error_log("Falha ao enviar email de verificação para: {$usuario->email}");
            }

            return [
                'success' => true,
                'message' => 'Se o email estiver cadastrado e não verificado, você receberá um novo link de verificação',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro ao processar solicitação: ' . $e->getMessage()],
            ];
        }
    }
}
