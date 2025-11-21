<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Obter conexão PDO com MySQL
     * Verifica se a conexão está ativa e reconecta se necessário
     */
    public static function getConnection(): PDO
    {
        // Se não tem conexão, criar nova
        if (self::$connection === null) {
            self::createConnection();
        } else {
            // Verificar se conexão ainda está ativa
            if (!self::isConnectionAlive()) {
                error_log("Conexão MySQL expirou. Reconectando...");
                self::$connection = null;
                self::createConnection();
            }
        }

        return self::$connection;
    }

    /**
     * Criar nova conexão com o banco
     */
    private static function createConnection(): void
    {
        try {
            // Tentar $_ENV primeiro, depois getenv() (compatibilidade com putenv)
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
            $dbname = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'licitapub';
            $username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
            $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // Não usar conexões persistentes em cron jobs
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

    /**
     * Verificar se a conexão ainda está ativa
     */
    private static function isConnectionAlive(): bool
    {
        if (self::$connection === null) {
            return false;
        }

        try {
            // Fazer ping na conexão
            self::$connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
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
