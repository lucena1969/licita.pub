<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Obter conexão PDO com MySQL
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $dbname = $_ENV['DB_DATABASE'] ?? 'licitapub';
                $username = $_ENV['DB_USERNAME'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                // Adicionar opção MySQL se disponível
                if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
                }

                self::$connection = new PDO($dsn, $username, $password, $options);

                // Se MYSQL_ATTR_INIT_COMMAND não estiver disponível, executar diretamente
                if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    self::$connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
                }

            } catch (PDOException $e) {
                error_log("Erro de conexão com banco de dados: " . $e->getMessage());
                throw new \Exception("Não foi possível conectar ao banco de dados");
            }
        }

        return self::$connection;
    }

    /**
     * Fechar conexão
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }

    /**
     * Testar conexão
     */
    public static function testConnection(): bool
    {
        try {
            $conn = self::getConnection();
            $stmt = $conn->query("SELECT 1");
            return $stmt !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
