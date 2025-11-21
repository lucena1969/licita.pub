<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    /**
     * Verificar autenticação
     * Retorna o usuário autenticado ou null
     */
    public static function verificar(): ?\App\Models\Usuario
    {
        // Obter session_id do header Authorization ou cookie
        $sessionId = self::getSessionId();

        if (!$sessionId) {
            return null;
        }

        $authService = new AuthService();
        return $authService->verificarSessao($sessionId);
    }

    /**
     * Exigir autenticação
     * Retorna o usuário ou encerra a execução com erro 401
     */
    public static function exigir(): \App\Models\Usuario
    {
        $usuario = self::verificar();

        if (!$usuario) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => ['Não autenticado. Faça login para continuar.'],
            ]);
            exit;
        }

        return $usuario;
    }

    /**
     * Obter session_id do request
     */
    private static function getSessionId(): ?string
    {
        // Tentar obter do header Authorization (Bearer token)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Tentar obter do cookie
        if (isset($_COOKIE['session_id'])) {
            return $_COOKIE['session_id'];
        }

        // Tentar obter do query parameter (menos seguro, apenas para testes)
        if (isset($_GET['session_id'])) {
            return $_GET['session_id'];
        }

        return null;
    }

    /**
     * Obter IP do cliente
     */
    public static function getClientIp(): string
    {
        // Verificar headers de proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Se tiver múltiplos IPs (proxy chain), pegar o primeiro
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return $ip;
    }

    /**
     * Obter User Agent
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
