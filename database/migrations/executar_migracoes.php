#!/usr/bin/env php
<?php
/**
 * Script PHP para executar migrações do banco de dados
 *
 * Uso:
 *   php executar_migracoes.php
 *   php executar_migracoes.php --rollback (para reverter)
 *   php executar_migracoes.php --verificar (apenas verificar status)
 */

// Carregar configurações
$rootDir = dirname(__DIR__, 2);
require_once $rootDir . '/backend/src/Config/Database.php';

use App\Config\Database;

// Cores para output no terminal
class TerminalColor {
    public static function success($text) {
        return "\033[0;32m✓ {$text}\033[0m\n";
    }

    public static function error($text) {
        return "\033[0;31m✗ {$text}\033[0m\n";
    }

    public static function info($text) {
        return "\033[0;34mℹ {$text}\033[0m\n";
    }

    public static function warning($text) {
        return "\033[0;33m⚠ {$text}\033[0m\n";
    }

    public static function header($text) {
        return "\033[1;36m\n{'='*60}\n{$text}\n{'='*60}\033[0m\n";
    }
}

class MigrationRunner {
    private PDO $db;
    private string $migrationDir;

    private array $migrations = [
        '001_criar_tabela_orgaos.sql',
        '002_criar_tabela_contratos.sql',
        '003_criar_tabela_atas_registro_preco.sql',
        '004_criar_tabela_planos_contratacao_anual.sql',
    ];

    public function __construct() {
        $this->db = Database::getConnection();
        $this->migrationDir = __DIR__;
    }

    /**
     * Executar todas as migrações
     */
    public function executarMigracoes(): bool {
        echo TerminalColor::header('EXECUTANDO MIGRAÇÕES DO LICITA.PUB');

        // Verificar se já existem tabelas
        if ($this->verificarTabelasExistentes()) {
            echo TerminalColor::warning('Algumas tabelas já existem. Deseja continuar? (s/n): ');
            $resposta = trim(fgets(STDIN));
            if (strtolower($resposta) !== 's') {
                echo TerminalColor::info('Operação cancelada pelo usuário.');
                return false;
            }
        }

        $sucesso = true;
        $totalMigracoes = count($this->migrations);

        foreach ($this->migrations as $index => $migration) {
            $numero = $index + 1;
            echo TerminalColor::info("[$numero/$totalMigracoes] Executando: $migration");

            $resultado = $this->executarArquivoSQL($migration);

            if ($resultado) {
                echo TerminalColor::success("Migração $migration concluída!");
            } else {
                echo TerminalColor::error("Erro ao executar migração $migration");
                $sucesso = false;
                break;
            }

            sleep(1); // Pausa entre migrações
        }

        if ($sucesso) {
            echo TerminalColor::header('TODAS AS MIGRAÇÕES FORAM CONCLUÍDAS COM SUCESSO!');
            $this->exibirResumo();
        }

        return $sucesso;
    }

    /**
     * Executar arquivo SQL
     */
    private function executarArquivoSQL(string $arquivo): bool {
        $caminhoCompleto = $this->migrationDir . '/' . $arquivo;

        if (!file_exists($caminhoCompleto)) {
            echo TerminalColor::error("Arquivo não encontrado: $caminhoCompleto");
            return false;
        }

        $sql = file_get_contents($caminhoCompleto);

        // Remover comentários
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Dividir por ponto-e-vírgula (statements)
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !preg_match('/^(SOURCE|DO SLEEP)/i', $stmt)
        );

        try {
            $this->db->beginTransaction();

            foreach ($statements as $statement) {
                if (empty($statement)) continue;

                // Pular comandos específicos do MySQL CLI
                if (preg_match('/^(SET|COMMIT|SELECT.*AS\s+\'\')/i', $statement)) {
                    continue;
                }

                $this->db->exec($statement);
            }

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            echo TerminalColor::error("Erro SQL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar se tabelas já existem
     */
    private function verificarTabelasExistentes(): bool {
        $tabelas = ['orgaos', 'contratos', 'atas_registro_preco', 'planos_contratacao_anual'];

        foreach ($tabelas as $tabela) {
            $stmt = $this->db->query("SHOW TABLES LIKE '$tabela'");
            if ($stmt->rowCount() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Exibir resumo das tabelas criadas
     */
    private function exibirResumo(): void {
        echo "\n📊 RESUMO DO BANCO DE DADOS:\n\n";

        // Tabelas criadas
        $stmt = $this->db->query("
            SELECT
                TABLE_NAME AS tabela,
                TABLE_ROWS AS linhas,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS tamanho_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ");

        $tabelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Tabelas criadas: " . count($tabelas) . "\n\n";

        foreach ($tabelas as $tabela) {
            printf(
                "  %-35s | %8s linhas | %8s MB\n",
                $tabela['tabela'],
                number_format($tabela['linhas']),
                $tabela['tamanho_mb']
            );
        }

        // Foreign keys
        $stmt = $this->db->query("
            SELECT COUNT(*) as total
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        $fks = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nForeign Keys: {$fks['total']}\n";

        // Índices FULLTEXT
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT INDEX_NAME) as total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND INDEX_TYPE = 'FULLTEXT'
        ");

        $fulltext = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Índices FULLTEXT: {$fulltext['total']}\n";
    }

    /**
     * Reverter migrações (rollback)
     */
    public function rollback(): bool {
        echo TerminalColor::header('REVERTENDO MIGRAÇÕES (ROLLBACK)');
        echo TerminalColor::warning('ATENÇÃO: Isso vai DELETAR todas as tabelas criadas e seus dados!');
        echo TerminalColor::warning('Tem certeza? Digite "CONFIRMO" para continuar: ');

        $resposta = trim(fgets(STDIN));
        if ($resposta !== 'CONFIRMO') {
            echo TerminalColor::info('Operação cancelada.');
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Desabilitar foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');

            // Ordem inversa de remoção
            $tabelas = [
                'adesoes_ata',
                'itens_ata',
                'atas_registro_preco',
                'planos_contratacao_anual',
                'categorias_pca',
                'aditivos_contratuais',
                'contratos',
                'orgaos'
            ];

            foreach ($tabelas as $tabela) {
                $this->db->exec("DROP TABLE IF EXISTS `$tabela`");
                echo TerminalColor::info("Tabela $tabela removida");
            }

            // Remover foreign key de licitacoes
            $this->db->exec('ALTER TABLE licitacoes DROP FOREIGN KEY IF EXISTS fk_licitacoes_orgao');

            // Reabilitar foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');

            $this->db->commit();

            echo TerminalColor::success('Rollback concluído com sucesso!');
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            echo TerminalColor::error('Erro ao fazer rollback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar status das migrações
     */
    public function verificarStatus(): void {
        echo TerminalColor::header('STATUS DAS MIGRAÇÕES');

        $tabelas = [
            'orgaos' => '001',
            'contratos' => '002',
            'aditivos_contratuais' => '002',
            'atas_registro_preco' => '003',
            'itens_ata' => '003',
            'adesoes_ata' => '003',
            'planos_contratacao_anual' => '004',
            'categorias_pca' => '004'
        ];

        echo "\nTabelas do banco de dados:\n\n";

        foreach ($tabelas as $tabela => $migracao) {
            $stmt = $this->db->query("SHOW TABLES LIKE '$tabela'");
            $existe = $stmt->rowCount() > 0;

            if ($existe) {
                $stmt = $this->db->query("SELECT COUNT(*) as total FROM `$tabela`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                echo TerminalColor::success(sprintf("[$migracao] %-35s | %8s registros", $tabela, $count));
            } else {
                echo TerminalColor::error("[$migracao] $tabela - NÃO EXISTE");
            }
        }
    }
}

// ============================================================
// EXECUÇÃO DO SCRIPT
// ============================================================

try {
    $runner = new MigrationRunner();

    // Verificar argumentos
    $comando = $argv[1] ?? null;

    switch ($comando) {
        case '--rollback':
            $runner->rollback();
            break;

        case '--verificar':
        case '--status':
            $runner->verificarStatus();
            break;

        default:
            $runner->executarMigracoes();
            break;
    }

} catch (Exception $e) {
    echo TerminalColor::error('ERRO FATAL: ' . $e->getMessage());
    exit(1);
}
