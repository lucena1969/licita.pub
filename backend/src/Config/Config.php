<?php

namespace App\Config;

class Config
{
    private static bool $loaded = false;

    /**
     * Carregar variáveis de ambiente do .env
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = dirname(__DIR__, 2) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                // Ignorar comentários
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse linha KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remover aspas se existirem
                    $value = trim($value, '"\'');

                    // Definir variável de ambiente
                    if (!array_key_exists($key, $_ENV)) {
                        $_ENV[$key] = $value;
                        putenv("{$key}={$value}");
                    }
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Obter valor de configuração
     */
    public static function get(string $key, $default = null)
    {
        self::load();
        return $_ENV[$key] ?? $default;
    }

    /**
     * Verificar se é ambiente de desenvolvimento
     */
    public static function isDevelopment(): bool
    {
        return self::get('APP_ENV', 'production') === 'development';
    }

    /**
     * Verificar se debug está ativo
     */
    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}
