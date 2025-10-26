#!/usr/bin/env php
<?php
/**
 * Script de Sincronização com PNCP
 *
 * Pode ser executado manualmente ou via cron job
 *
 * Uso:
 *   php sincronizar_pncp.php
 *   php sincronizar_pncp.php --ultimos-dias=7
 *   php sincronizar_pncp.php --uf=SP
 *   php sincronizar_pncp.php --modalidade=6 (Pregão Eletrônico)
 *
 * Cron job (executar todo dia às 06:00):
 *   0 6 * * * /usr/bin/php /caminho/para/backend/cron/sincronizar_pncp.php >> /var/log/pncp_sync.log 2>&1
 */

// Carregar autoload
$rootDir = dirname(__DIR__);
require_once $rootDir . '/src/Config/Config.php';
require_once $rootDir . '/src/Config/Database.php';
require_once $rootDir . '/src/Models/Licitacao.php';
require_once $rootDir . '/src/Models/Orgao.php';
require_once $rootDir . '/src/Repositories/LicitacaoRepository.php';
require_once $rootDir . '/src/Repositories/OrgaoRepository.php';
require_once $rootDir . '/src/Services/PNCPService.php';

use App\Config\Config;
use App\Services\PNCPService;

// Carregar variáveis de ambiente do .env
Config::load();

// Cores para output
class Color {
    public static function success($text) { return "\033[0;32m✓ {$text}\033[0m"; }
    public static function error($text) { return "\033[0;31m✗ {$text}\033[0m"; }
    public static function info($text) { return "\033[0;34mℹ {$text}\033[0m"; }
    public static function warning($text) { return "\033[0;33m⚠ {$text}\033[0m"; }
    public static function header($text) { return "\033[1;36m\n{'='*60}\n{$text}\n{'='*60}\033[0m"; }
}

// Parse argumentos
$opcoes = [];
foreach ($argv as $arg) {
    if (preg_match('/--(.+)=(.+)/', $arg, $matches)) {
        $opcoes[$matches[1]] = $matches[2];
    }
}

// Configurar filtros
$filtros = [];

// Período (padrão: últimos 7 dias)
$diasAtras = (int)($opcoes['ultimos-dias'] ?? 7);
$filtros['dataInicial'] = date('Ymd', strtotime("-{$diasAtras} days"));
$filtros['dataFinal'] = date('Ymd');

// UF
if (!empty($opcoes['uf'])) {
    $filtros['uf'] = strtoupper($opcoes['uf']);
}

// Modalidade
if (!empty($opcoes['modalidade'])) {
    $filtros['codigoModalidadeContratacao'] = (int)$opcoes['modalidade'];
}

// Executar sincronização
try {
    echo Color::header('SINCRONIZAÇÃO COM PNCP - LICITA.PUB');
    echo "\n";
    echo Color::info("Iniciado em: " . date('d/m/Y H:i:s'));
    echo "\n";
    echo Color::info("Período: " . date('d/m/Y', strtotime($filtros['dataInicial'])) . " até " . date('d/m/Y', strtotime($filtros['dataFinal'])));
    echo "\n";

    if (!empty($filtros['uf'])) {
        echo Color::info("UF: {$filtros['uf']}");
        echo "\n";
    }

    if (!empty($filtros['codigoModalidadeContratacao'])) {
        echo Color::info("Modalidade: {$filtros['codigoModalidadeContratacao']}");
        echo "\n";
    }

    echo "\n";

    $service = new PNCPService();
    $resultado = $service->sincronizarLicitacoes($filtros);

    echo "\n";
    echo Color::header('RESULTADO DA SINCRONIZAÇÃO');
    echo "\n";

    if ($resultado['sucesso']) {
        echo Color::success("Sincronização concluída com sucesso!");
        echo "\n\n";

        $stats = $resultado['stats'];
        echo "📊 Estatísticas:\n";
        echo "  • Novas licitações:        " . Color::success((string)$stats['novos']) . "\n";
        echo "  • Licitações atualizadas:  " . Color::info((string)$stats['atualizados']) . "\n";
        echo "  • Erros:                   " . Color::error((string)$stats['erros']) . "\n";
        echo "  • Puladas:                 " . Color::warning((string)$stats['pulados']) . "\n";
        echo "\n";
        echo "⏱️  Tempo de execução: {$resultado['duracao']} segundos\n";
        echo "🏁 Finalizado em: {$resultado['finalizado']}\n";

    } else {
        echo Color::error("Sincronização falhou!");
        echo "\n\n";
        echo Color::error("Erro: {$resultado['erro']}");
        echo "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n";
    echo Color::error("ERRO FATAL: " . $e->getMessage());
    echo "\n";
    echo Color::error("Stack trace:");
    echo "\n";
    echo $e->getTraceAsString();
    echo "\n";
    exit(1);
}

echo "\n";
exit(0);
