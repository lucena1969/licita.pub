<?php

namespace App\Config;

/**
 * Env - Gerenciador de variáveis de ambiente
 */
class Env
{
    private static bool $loaded = false;
    private static array $vars = [];

    /**
     * Carregar arquivo .env
     */
    public static function load(string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $path ?? __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            error_log("[Env] Arquivo .env não encontrado: {$envFile}");
            self::$loaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remover aspas
                $value = trim($value, '"\'');

                self::$vars[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obter variável de ambiente
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$vars[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Verificar se variável existe
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$vars[$key]) || getenv($key) !== false;
    }
}
