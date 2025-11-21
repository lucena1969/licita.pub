#!/usr/bin/env php
<?php
/**
 * Script CLI: Sincronizar Itens de Licitações do PNCP
 *
 * Busca licitações no banco e sincroniza seus itens via API do PNCP
 *
 * Uso:
 *   php sincronizar_itens_pncp.php
 *   php sincronizar_itens_pncp.php --limite=50
 *   php sincronizar_itens_pncp.php --licitacao-id=abc-123-xyz
 *   php sincronizar_itens_pncp.php --resincronizar
 *   php sincronizar_itens_pncp.php --usar-catmat-matching
 *
 * Parâmetros:
 *   --limite=N              Quantidade de licitações a processar (padrão: 100)
 *   --licitacao-id=ID       Sincronizar apenas uma licitação específica
 *   --resincronizar         Reprocessar licitações que já têm itens
 *   --usar-catmat-matching  Buscar CATMAT quando não disponível na API
 *   --delay=N               Segundos de espera entre requisições (padrão: 1)
 */

// Carregar dependências
$rootDir = dirname(__DIR__);
require_once $rootDir . '/src/Config/Config.php';
require_once $rootDir . '/src/Config/Database.php';
require_once $rootDir . '/src/Models/ItemLicitacao.php';
require_once $rootDir . '/src/Repositories/ItemLicitacaoRepository.php';
require_once $rootDir . '/src/Services/PNCPItemService.php';
require_once $rootDir . '/src/Services/CatmatMatchingService.php';
require_once $rootDir . '/src/Services/KeywordsExtractionService.php';

use App\Config\Config;
use App\Config\Database;
use App\Services\PNCPItemService;
use App\Repositories\ItemLicitacaoRepository;

// Carregar .env
Config::load();

// Cores para output
class Color {
    public static function success($text) { return "\033[0;32m✓ {$text}\033[0m"; }
    public static function error($text) { return "\033[0;31m✗ {$text}\033[0m"; }
    public static function info($text) { return "\033[0;34mℹ {$text}\033[0m"; }
    public static function warning($text) { return "\033[0;33m⚠ {$text}\033[0m"; }
    public static function header($text) { return "\033[1;36m{$text}\033[0m"; }
}

// Parse argumentos
$opcoes = [];
foreach ($argv as $arg) {
    if (preg_match('/--(.+)=(.+)/', $arg, $matches)) {
        $opcoes[$matches[1]] = $matches[2];
    } elseif (preg_match('/--(.+)/', $arg, $matches)) {
        $opcoes[$matches[1]] = true;
    }
}

// Configurações
$limite = (int)($opcoes['limite'] ?? 100);
$licitacaoId = $opcoes['licitacao-id'] ?? null;
$resincronizar = isset($opcoes['resincronizar']);
$usarCatmatMatching = isset($opcoes['usar-catmat-matching']);
$delay = (int)($opcoes['delay'] ?? 1);

echo Color::header("\n" . str_repeat("=", 60));
echo Color::header("SINCRONIZAR ITENS DE LICITAÇÕES - PNCP");
echo Color::header(str_repeat("=", 60) . "\n");
echo Color::info("Iniciado em: " . date('d/m/Y H:i:s')) . "\n";
echo Color::info("Limite: {$limite} licitações") . "\n";
echo Color::info("CATMAT Matching: " . ($usarCatmatMatching ? 'SIM' : 'NÃO')) . "\n";
echo Color::info("Delay: {$delay}s entre requisições") . "\n\n";

try {
    $db = Database::getConnection();
    $itemRepo = new ItemLicitacaoRepository();
    $service = new PNCPItemService();

    // Buscar licitações para processar
    if ($licitacaoId) {
        // Processar apenas uma licitação específica
        $sql = "SELECT id, pncp_id, objeto FROM licitacoes WHERE id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$licitacaoId]);
    } else {
        // Buscar licitações sem itens ou para resincronizar
        if ($resincronizar) {
            $sql = "SELECT l.id, l.pncp_id, l.objeto
                    FROM licitacoes l
                    WHERE l.pncp_id IS NOT NULL
                    ORDER BY l.sincronizado_em DESC
                    LIMIT :limite";
        } else {
            $sql = "SELECT l.id, l.pncp_id, l.objeto
                    FROM licitacoes l
                    LEFT JOIN itens_licitacao i ON i.licitacao_id = l.id
                    WHERE l.pncp_id IS NOT NULL
                      AND i.id IS NULL
                    GROUP BY l.id
                    ORDER BY l.sincronizado_em DESC
                    LIMIT :limite";
        }

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
    }

    $licitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($licitacoes);

    if ($total === 0) {
        echo Color::warning("Nenhuma licitação encontrada para processar!") . "\n";
        echo Color::info("Todas as licitações já possuem itens ou não têm pncp_id válido.") . "\n";
        exit(0);
    }

    echo Color::success("Encontradas {$total} licitações para processar") . "\n\n";

    // Estatísticas globais
    $totalSucesso = 0;
    $totalErros = 0;
    $totalItens = 0;
    $totalComCatmat = 0;

    // Processar cada licitação
    foreach ($licitacoes as $index => $lic) {
        $numero = $index + 1;
        $pncpId = $lic['pncp_id'];
        $objetoCurto = substr($lic['objeto'], 0, 60);

        echo "[{$numero}/{$total}] {$pncpId}\n";
        echo "  Objeto: {$objetoCurto}...\n";

        try {
            // Sincronizar itens
            $resultado = $service->sincronizarItens($lic['id'], $pncpId, $usarCatmatMatching);

            if ($resultado['success']) {
                $stats = $resultado['stats'];
                $totalItens += $resultado['total_itens'];

                $comCatmat = $stats['novos'] + $stats['atualizados'] - $stats['sem_catmat'];
                $totalComCatmat += $comCatmat;

                echo Color::success("  {$resultado['total_itens']} itens sincronizados") . "\n";
                echo Color::info("  {$comCatmat} com CATMAT | {$stats['sem_catmat']} sem CATMAT");

                if ($usarCatmatMatching && $stats['catmat_encontrado'] > 0) {
                    echo Color::success(" | {$stats['catmat_encontrado']} CATMAT via matching");
                }

                echo "\n";

                $totalSucesso++;
            } else {
                echo Color::warning("  " . ($resultado['message'] ?? $resultado['error'])) . "\n";
                $totalErros++;
            }

            // Aguardar antes da próxima requisição
            if ($numero < $total) {
                sleep($delay);
            }

        } catch (Exception $e) {
            echo Color::error("  Erro: " . $e->getMessage()) . "\n";
            $totalErros++;
        }

        echo "\n";
    }

    // Resultado final
    echo Color::header(str_repeat("=", 60));
    echo Color::header("RESULTADO FINAL");
    echo Color::header(str_repeat("=", 60) . "\n");
    echo Color::success("Licitações processadas: {$totalSucesso}") . "\n";
    echo Color::success("Total de itens sincronizados: {$totalItens}") . "\n";
    echo Color::success("Itens com CATMAT: {$totalComCatmat}") . "\n";
    echo Color::error("Erros: {$totalErros}") . "\n";

    // Estatísticas do banco
    $statsDB = $itemRepo->getEstatisticas();
    echo "\n" . Color::header("ESTATÍSTICAS DO BANCO") . "\n";
    echo Color::info("Total de itens: " . number_format($statsDB['total_itens'])) . "\n";
    echo Color::info("Total de licitações: " . number_format($statsDB['total_licitacoes'])) . "\n";
    echo Color::info("Itens com CATMAT: " . number_format($statsDB['itens_com_catmat']) . " (" . round(($statsDB['itens_com_catmat'] / max($statsDB['total_itens'], 1)) * 100, 1) . "%)") . "\n";
    echo Color::info("Materiais: " . number_format($statsDB['total_materiais'])) . "\n";
    echo Color::info("Serviços: " . number_format($statsDB['total_servicos'])) . "\n";
    echo Color::info("Valor médio: R$ " . number_format($statsDB['valor_medio'] ?? 0, 2, ',', '.')) . "\n";
    echo Color::info("Finalizado em: " . date('d/m/Y H:i:s')) . "\n\n";

} catch (Exception $e) {
    echo "\n" . Color::error("ERRO FATAL: " . $e->getMessage()) . "\n";
    echo Color::error("Stack trace:") . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
