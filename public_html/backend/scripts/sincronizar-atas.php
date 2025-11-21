<?php
/**
 * Script: Sincronizar Atas de Registro de Preços
 *
 * Executa a sincronização de ATAs do PNCP
 *
 * Uso:
 *   php sincronizar-atas.php [dias]
 *
 * Exemplos:
 *   php sincronizar-atas.php        # Últimos 30 dias (padrão)
 *   php sincronizar-atas.php 7      # Últimos 7 dias
 *   php sincronizar-atas.php 90     # Últimos 90 dias
 */

require_once __DIR__ . '/../public/api/bootstrap.php';

use App\Services\AtaService;
use App\Services\PNCPService;
use App\Services\ComprasDadosGovService;
use App\Repositories\AtaRegistroPrecoRepository;
use App\Repositories\ItemAtaRepository;

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Banner
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          LICITA.PUB - SINCRONIZAÇÃO DE ATAs                  ║\n";
echo "║          Atas de Registro de Preços do PNCP                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Determinar período
$dias = isset($argv[1]) ? (int)$argv[1] : 30;

if ($dias < 1 || $dias > 365) {
    echo "❌ Erro: Número de dias deve estar entre 1 e 365.\n";
    exit(1);
}

echo "📅 Período: Últimos {$dias} dias\n";
echo "⏰ Início: " . date('d/m/Y H:i:s') . "\n";
echo "\n";

try {
    // Instanciar services e repositories
    $ataRepository = new AtaRegistroPrecoRepository();
    $itemRepository = new ItemAtaRepository();
    $pncpService = new PNCPService();
    $comprasService = new ComprasDadosGovService();

    $ataService = new AtaService(
        $pncpService,
        $comprasService,
        $ataRepository,
        $itemRepository
    );

    // Calcular datas
    $dataFinal = date('Ymd');
    $dataInicial = date('Ymd', strtotime("-{$dias} days"));

    echo "📊 Estatísticas ANTES da sincronização:\n";
    $statsBefore = $ataService->obterEstatisticas();
    echo "   • Total de ATAs: {$statsBefore['total_atas']}\n";
    echo "   • ATAs vigentes: {$statsBefore['atas_vigentes']}\n";
    echo "   • ATAs vencidas: {$statsBefore['atas_vencidas']}\n";
    echo "\n";

    // Executar sincronização
    $resultado = $ataService->sincronizarAtasPNCP($dataInicial, $dataFinal);

    echo "\n";
    echo "📊 Estatísticas DEPOIS da sincronização:\n";
    $statsAfter = $ataService->obterEstatisticas();
    echo "   • Total de ATAs: {$statsAfter['total_atas']}\n";
    echo "   • ATAs vigentes: {$statsAfter['atas_vigentes']}\n";
    echo "   • ATAs vencidas: {$statsAfter['atas_vencidas']}\n";
    echo "\n";

    echo "📈 RESUMO:\n";
    echo "   • Páginas processadas: {$resultado['paginas_processadas']}\n";
    echo "   • ATAs processadas: {$resultado['total_processadas']}\n";
    echo "   • Novas ATAs: {$resultado['inseridas']}\n";
    echo "   • ATAs atualizadas: {$resultado['atualizadas']}\n";
    echo "   • Erros: {$resultado['erros']}\n";
    echo "\n";

    echo "✅ Sincronização concluída com sucesso!\n";
    echo "⏰ Fim: " . date('d/m/Y H:i:s') . "\n";
    echo "\n";

    exit(0);

} catch (\Exception $e) {
    echo "\n";
    echo "❌ ERRO FATAL:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
    echo "\n\n";
    exit(1);
}
