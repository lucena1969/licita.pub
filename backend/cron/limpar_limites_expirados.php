<?php
/**
 * Cron Job: Limpar limites expirados
 *
 * Execução: Diariamente às 3h
 * Comando: 0 3 * * * /usr/bin/php /home/u590097272/domains/licita.pub/public_html/backend/cron/limpar_limites_expirados.php >> /home/u590097272/logs/limpeza.log 2>&1
 *
 * Tarefas:
 * - Remove registros de IPs inativos há mais de 7 dias da tabela limites_ip
 * - Remove sessões expiradas da tabela sessoes
 * - Remove registros antigos do histórico de consultas (opcional)
 */

// Ajustar timezone
date_default_timezone_set('America/Sao_Paulo');

echo "\n=== CRON: Limpeza de Limites Expirados ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// Carregar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Repositories\LimiteIPRepository;

try {
    // Conectar ao banco
    $db = Database::getInstance()->getConnection();

    echo "✓ Conectado ao banco de dados\n\n";

    // =========================================================================
    // 1. LIMPAR REGISTROS DE IPs INATIVOS
    // =========================================================================

    echo "1. Limpando registros de IPs inativos...\n";

    $limiteIPRepo = new LimiteIPRepository($db);
    $diasInatividade = 7; // Manter apenas últimos 7 dias

    $removidosIP = $limiteIPRepo->limparRegistrosAntigos($diasInatividade);

    echo "   ✓ $removidosIP registros de IP removidos (inativos há mais de $diasInatividade dias)\n\n";

    // =========================================================================
    // 2. LIMPAR SESSÕES EXPIRADAS
    // =========================================================================

    echo "2. Limpando sessões expiradas...\n";

    $sqlSessoes = "DELETE FROM sessoes WHERE expira_em < NOW()";
    $stmtSessoes = $db->prepare($sqlSessoes);
    $stmtSessoes->execute();
    $removidosSessoes = $stmtSessoes->rowCount();

    echo "   ✓ $removidosSessoes sessões expiradas removidas\n\n";

    // =========================================================================
    // 3. LIMPAR HISTÓRICO ANTIGO (OPCIONAL)
    // =========================================================================

    echo "3. Limpando histórico de consultas antigo...\n";

    // Manter apenas últimos 90 dias de histórico
    $diasHistorico = 90;
    $sqlHistorico = "
        DELETE FROM historico_consultas
        WHERE consultado_em < DATE_SUB(NOW(), INTERVAL :dias DAY)
    ";

    $stmtHistorico = $db->prepare($sqlHistorico);
    $stmtHistorico->execute([':dias' => $diasHistorico]);
    $removidosHistorico = $stmtHistorico->rowCount();

    echo "   ✓ $removidosHistorico registros de histórico removidos (mais de $diasHistorico dias)\n\n";

    // =========================================================================
    // 4. OTIMIZAR TABELAS
    // =========================================================================

    echo "4. Otimizando tabelas...\n";

    $tabelas = ['limites_ip', 'sessoes', 'historico_consultas'];

    foreach ($tabelas as $tabela) {
        try {
            $db->exec("OPTIMIZE TABLE $tabela");
            echo "   ✓ Tabela $tabela otimizada\n";
        } catch (\Exception $e) {
            echo "   ⚠ Erro ao otimizar $tabela: {$e->getMessage()}\n";
        }
    }

    echo "\n";

    // =========================================================================
    // 5. ESTATÍSTICAS FINAIS
    // =========================================================================

    echo "5. Estatísticas após limpeza:\n";

    // Contar registros restantes
    $sqlStats = "
        SELECT
            (SELECT COUNT(*) FROM limites_ip) as total_ips,
            (SELECT COUNT(*) FROM sessoes) as total_sessoes,
            (SELECT COUNT(*) FROM historico_consultas) as total_historico
    ";

    $stmtStats = $db->query($sqlStats);
    $stats = $stmtStats->fetch();

    echo "   - IPs ativos: {$stats['total_ips']}\n";
    echo "   - Sessões ativas: {$stats['total_sessoes']}\n";
    echo "   - Registros de histórico: {$stats['total_historico']}\n";

    echo "\n=== LIMPEZA CONCLUÍDA COM SUCESSO ===\n";
    echo "Total removido: " . ($removidosIP + $removidosSessoes + $removidosHistorico) . " registros\n\n";

    exit(0);

} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";

    // Enviar email de alerta (opcional)
    $emailAdmin = getenv('ADMIN_EMAIL');
    if ($emailAdmin) {
        $assunto = "[ERRO] Cron de Limpeza - Licita.pub";
        $mensagem = "Erro ao executar cron de limpeza:\n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString();
        @mail($emailAdmin, $assunto, $mensagem);
    }

    exit(1);
}
