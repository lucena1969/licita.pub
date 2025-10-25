#!/usr/bin/env php
<?php
/**
 * Script de Backup Automático antes do Deploy
 *
 * Faz backup completo do banco de dados antes de executar migrações
 *
 * Uso:
 *   php backup_antes_deploy.php
 *   php backup_antes_deploy.php --remote (backup do servidor remoto)
 */

// Carregar configurações
$rootDir = __DIR__;
$backupDir = $rootDir . '/database/backups';

// Criar diretório de backups se não existir
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Cores para output
class Color {
    public static function success($text) { return "\033[0;32m✓ {$text}\033[0m\n"; }
    public static function error($text) { return "\033[0;31m✗ {$text}\033[0m\n"; }
    public static function info($text) { return "\033[0;34mℹ {$text}\033[0m\n"; }
    public static function warning($text) { return "\033[0;33m⚠ {$text}\033[0m\n"; }
    public static function header($text) { return "\033[1;36m\n{'='*60}\n{$text}\n{'='*60}\033[0m\n"; }
}

class BackupManager {
    private array $config;
    private string $backupDir;

    public function __construct(string $backupDir) {
        $this->backupDir = $backupDir;
        $this->carregarConfig();
    }

    private function carregarConfig(): void {
        // Tentar carregar do .env
        $envFile = __DIR__ . '/backend/.env';

        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);

            $this->config = [
                'host' => $env['DB_HOST'] ?? 'localhost',
                'port' => $env['DB_PORT'] ?? '3306',
                'database' => $env['DB_DATABASE'] ?? 'licitapub',
                'username' => $env['DB_USERNAME'] ?? 'root',
                'password' => $env['DB_PASSWORD'] ?? '',
            ];
        } else {
            // Configuração padrão
            $this->config = [
                'host' => 'localhost',
                'port' => '3306',
                'database' => 'licitapub',
                'username' => 'root',
                'password' => '',
            ];
        }
    }

    public function executarBackup(): bool {
        echo Color::header('BACKUP DO BANCO DE DADOS');

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$this->config['database']}_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;

        echo Color::info("Banco: {$this->config['database']}");
        echo Color::info("Host: {$this->config['host']}");
        echo Color::info("Arquivo: {$filename}");
        echo "\n";

        // Método 1: Tentar usar mysqldump (mais rápido)
        if ($this->backupViaMyDump($filepath)) {
            return $this->finalizarBackup($filepath);
        }

        // Método 2: Backup via PHP/PDO (fallback)
        echo Color::warning("mysqldump não disponível, usando backup via PHP...");

        if ($this->backupViaPDO($filepath)) {
            return $this->finalizarBackup($filepath);
        }

        echo Color::error("Não foi possível criar o backup!");
        return false;
    }

    private function backupViaMyDump(string $filepath): bool {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $user = $this->config['username'];
        $pass = $this->config['password'];
        $db = $this->config['database'];

        // Construir comando mysqldump
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            !empty($pass) ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($db),
            escapeshellarg($filepath)
        );

        // Executar
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            echo Color::success("Backup criado via mysqldump!");
            return true;
        }

        return false;
    }

    private function backupViaPDO(string $filepath): bool {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            );

            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $sql = $this->gerarBackupSQL($pdo);

            file_put_contents($filepath, $sql);

            echo Color::success("Backup criado via PHP/PDO!");
            return true;

        } catch (PDOException $e) {
            echo Color::error("Erro ao conectar no banco: " . $e->getMessage());
            return false;
        }
    }

    private function gerarBackupSQL(PDO $pdo): string {
        $sql = "-- Backup automático do Licita.pub\n";
        $sql .= "-- Data: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Banco: {$this->config['database']}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        // Obter lista de tabelas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            echo Color::info("  → Exportando tabela: {$table}");

            // Estrutura da tabela
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql .= "-- Tabela: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";

            // Dados da tabela
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                $sql .= "-- Dados de {$table}\n";

                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, $row);

                    $columns = array_keys($row);

                    $sql .= sprintf(
                        "INSERT INTO `{$table}` (`%s`) VALUES (%s);\n",
                        implode('`, `', $columns),
                        implode(', ', $values)
                    );
                }

                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $sql;
    }

    private function finalizarBackup(string $filepath): bool {
        $size = filesize($filepath);
        $sizeFormatted = $this->formatBytes($size);

        echo "\n";
        echo Color::success("BACKUP CONCLUÍDO COM SUCESSO!");
        echo Color::info("Arquivo: " . basename($filepath));
        echo Color::info("Tamanho: {$sizeFormatted}");
        echo Color::info("Local: {$filepath}");
        echo "\n";

        // Criar cópia de segurança adicional
        $latestBackup = $this->backupDir . '/latest_backup.sql';
        copy($filepath, $latestBackup);

        echo Color::info("Cópia de segurança: latest_backup.sql");
        echo "\n";

        return true;
    }

    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function listarBackups(): void {
        echo Color::header('BACKUPS DISPONÍVEIS');

        $files = glob($this->backupDir . '/backup_*.sql');
        rsort($files); // Mais recentes primeiro

        if (empty($files)) {
            echo Color::warning("Nenhum backup encontrado.");
            return;
        }

        echo sprintf("%-50s | %-15s | %-20s\n", 'Arquivo', 'Tamanho', 'Data');
        echo str_repeat('-', 90) . "\n";

        foreach ($files as $file) {
            $name = basename($file);
            $size = $this->formatBytes(filesize($file));
            $date = date('d/m/Y H:i:s', filemtime($file));

            echo sprintf("%-50s | %-15s | %-20s\n", $name, $size, $date);
        }

        echo "\n";
    }

    public function restaurarBackup(string $filename): bool {
        $filepath = $this->backupDir . '/' . $filename;

        if (!file_exists($filepath)) {
            echo Color::error("Arquivo de backup não encontrado: {$filename}");
            return false;
        }

        echo Color::header('RESTAURAR BACKUP');
        echo Color::warning("ATENÇÃO: Isso vai SOBRESCREVER o banco de dados atual!");
        echo Color::warning("Tem certeza? Digite 'CONFIRMO' para continuar: ");

        $resposta = trim(fgets(STDIN));

        if ($resposta !== 'CONFIRMO') {
            echo Color::info("Operação cancelada.");
            return false;
        }

        $sql = file_get_contents($filepath);

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            );

            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $pdo->exec($sql);

            echo Color::success("Backup restaurado com sucesso!");
            return true;

        } catch (PDOException $e) {
            echo Color::error("Erro ao restaurar: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================================
// EXECUÇÃO
// ============================================================

try {
    $manager = new BackupManager($backupDir);

    $comando = $argv[1] ?? null;

    switch ($comando) {
        case '--listar':
        case '--list':
            $manager->listarBackups();
            break;

        case '--restaurar':
        case '--restore':
            if (!isset($argv[2])) {
                echo Color::error("Uso: php backup_antes_deploy.php --restaurar <nome_arquivo>");
                exit(1);
            }
            $manager->restaurarBackup($argv[2]);
            break;

        default:
            $manager->executarBackup();
            break;
    }

} catch (Exception $e) {
    echo Color::error("ERRO: " . $e->getMessage());
    exit(1);
}
