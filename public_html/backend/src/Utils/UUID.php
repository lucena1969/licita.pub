<?php

namespace App\Utils;

class UUID
{
    /**
     * Gera um UUID v4 (aleatório)
     *
     * @return string UUID no formato xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     */
    public static function generate(): string
    {
        // Gerar 16 bytes aleatórios
        $data = random_bytes(16);

        // Configurar bits de versão (4) e variante (RFC 4122)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Versão 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variante RFC 4122

        // Formatar como UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Valida se uma string é um UUID válido
     *
     * @param string $uuid
     * @return bool
     */
    public static function isValid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
}
