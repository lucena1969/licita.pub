#!/usr/bin/env php
<?php
/**
 * Script para Importar TODAS as Licitações Abertas
 *
 * Executa o sincronizar_pncp.php múltiplas vezes para importar
 * todas as ~32.800 licitações abertas disponíveis no PNCP
 *
 * Uso:
 *   php importar_todas.php
 *   php importar_todas.php --max-lotes=20 (importa 20 lotes = 10.000 licitações)
 *   php importar_todas.php --modalidade=6 (apenas Pregão Eletrônico)
 */

// Cores para output
class Color {
    public static function success($text) { return "\033[0;32m{$text}\033[0m"; }
    public static function error($text) { return "\033[0;31m{$text}\033[0m"; }
    public static function info($text) { return "\033[0;34m{$text}\033[0m"; }
    public static function warning($text) { return "\033[0;33m{$text}\033[0m"; }
    public static function header($text) { return "\033[1;36m{$text}\033[0m"; }
}

// Parse argumentos
$opcoes = [];
foreach ($argv as $arg) {
    if (preg_match('/--(.+)=(.+)/', $arg, $matches)) {
        $opcoes[$matches[1]] = $matches[2];
    }
}

// Configurações
$maxLotes = (int)($opcoes['max-lotes'] ?? 100); // 100 lotes = ~50.000 licitações
$modalidade = $opcoes['modalidade'] ?? null;
$paginasPorLote = 10; // Cada lote importa 10 páginas = 500 licitações

echo Color::header("\n" . str_repeat("=", 70)) . "\n";
echo Color::header("IMPORTAÇÃO COMPLETA - LICITAÇÕES ABERTAS DO PNCP") . "\n";
echo Color::header(str_repeat("=", 70)) . "\n\n";
echo Color::info("Iniciado em: " . date('d/m/Y H:i:s')) . "\n";
echo Color::info("Lotes máximos: {$maxLotes}") . "\n";
echo Color::info("Páginas por lote: {$paginasPorLote}") . "\n";
echo Color::info("Licitações por lote: ~" . ($paginasPorLote * 50)) . "\n";
echo Color::info("Total estimado: ~" . ($maxLotes * $paginasPorLote * 50) . " licitações") . "\n";
if ($modalidade) {
    echo Color::info("Filtro modalidade: {$modalidade}") . "\n";
}
echo "\n";

$totalNovos = 0;
$totalAtualizados = 0;
$totalErros = 0;
$tempoInicio = time();

for ($lote = 1; $lote <= $maxLotes; $lote++) {
    $paginaInicial = (($lote - 1) * $paginasPorLote) + 1;

    echo Color::header("\n" . str_repeat("-", 70)) . "\n";
    echo Color::header("LOTE {$lote} de {$maxLotes}") . "\n";
    echo Color::header("Páginas: {$paginaInicial} a " . ($paginaInicial + $paginasPorLote - 1)) . "\n";
    echo Color::header(str_repeat("-", 70)) . "\n\n";

    // Construir comando
    $cmd = "php " . __DIR__ . "/sincronizar_pncp.php --pagina={$paginaInicial} --max-paginas={$paginasPorLote}";

    if ($modalidade) {
        $cmd .= " --modalidade={$modalidade}";
    }

    // Executar
    $output = [];
    $returnCode = 0;
    exec($cmd . " 2>&1", $output, $returnCode);

    // Mostrar output
    foreach ($output as $line) {
        echo $line . "\n";
    }

    // Verificar se teve sucesso
    if ($returnCode !== 0) {
        echo Color::error("\nErro ao executar lote {$lote}. Abortando importação.\n");
        break;
    }

    // Extrair estatísticas do output (buscar linhas com números)
    foreach ($output as $line) {
        if (preg_match('/Novas licitações.*?(\d+)/', $line, $matches)) {
            $totalNovos += (int)$matches[1];
        }
        if (preg_match('/atualizadas.*?(\d+)/', $line, $matches)) {
            $totalAtualizados += (int)$matches[1];
        }
        if (preg_match('/Erros.*?(\d+)/', $line, $matches)) {
            $totalErros += (int)$matches[1];
        }
    }

    // Se não importou nada, provavelmente chegou ao fim
    if (stripos(implode(' ', $output), 'Novas licitações') !== false) {
        $ultimaLinha = end($output);
        if (stripos($ultimaLinha, '0') !== false && stripos($ultimaLinha, 'Novas') !== false) {
            echo Color::warning("\n✓ Nenhuma licitação nova encontrada. Importação concluída.\n");
            break;
        }
    }

    // Pausa entre lotes (5 segundos)
    if ($lote < $maxLotes) {
        echo Color::info("\nAguardando 5 segundos antes do próximo lote...\n");
        sleep(5);
    }

    // Mostrar progresso a cada 5 lotes
    if ($lote % 5 === 0) {
        $tempoDecorrido = time() - $tempoInicio;
        $minutos = floor($tempoDecorrido / 60);
        $segundos = $tempoDecorrido % 60;

        echo "\n" . Color::header(str_repeat("=", 70)) . "\n";
        echo Color::header("PROGRESSO GERAL") . "\n";
        echo Color::header(str_repeat("=", 70)) . "\n";
        echo Color::success("Lotes processados: {$lote} de {$maxLotes}") . "\n";
        echo Color::success("Total importado: " . ($totalNovos + $totalAtualizados) . " licitações") . "\n";
        echo Color::info("Tempo decorrido: {$minutos}m {$segundos}s") . "\n";
        echo Color::header(str_repeat("=", 70)) . "\n";
    }
}

// Resultado final
$tempoTotal = time() - $tempoInicio;
$minutosTotal = floor($tempoTotal / 60);
$segundosTotal = $tempoTotal % 60;

echo "\n" . Color::header(str_repeat("=", 70)) . "\n";
echo Color::header("IMPORTAÇÃO CONCLUÍDA") . "\n";
echo Color::header(str_repeat("=", 70)) . "\n";
echo Color::success("✓ Novas licitações importadas: {$totalNovos}") . "\n";
echo Color::info("↻ Licitações atualizadas: {$totalAtualizados}") . "\n";
echo Color::error("✗ Erros: {$totalErros}") . "\n";
echo Color::success("📊 TOTAL IMPORTADO: " . ($totalNovos + $totalAtualizados) . " licitações") . "\n";
echo Color::info("⏱️  Tempo total: {$minutosTotal}m {$segundosTotal}s") . "\n";
echo Color::info("🏁 Finalizado em: " . date('d/m/Y H:i:s')) . "\n";
echo Color::header(str_repeat("=", 70)) . "\n\n";

exit(0);
