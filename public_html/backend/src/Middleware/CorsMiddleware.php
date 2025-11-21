<?php

namespace App\Middleware;

class CorsMiddleware
{
    /**
     * Aplicar headers CORS
     */
    public static function handle(): void
    {
        // Definir origens permitidas (ajustar conforme necessário)
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            'http://localhost',
            'http://licita.pub.local',
            'https://licita.pub',
            'https://www.licita.pub',
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Verificar se a origem está na lista de permitidas
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // Em desenvolvimento, permitir todas as origens
            // REMOVER EM PRODUÇÃO
            if (self::isDevelopment()) {
                header("Access-Control-Allow-Origin: *");
            }
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 3600");

        // Tratar requisições OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Verificar se está em desenvolvimento
     */
    private static function isDevelopment(): bool
    {
        $env = $_ENV['APP_ENV'] ?? 'production';
        return in_array($env, ['development', 'dev', 'local']);
    }
}
