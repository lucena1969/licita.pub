<?php
/**
 * Visualizador do log de debug
 */

$logFile = __DIR__ . '/debug-oauth.log';

echo "<h1>Log de Debug OAuth</h1>";

if (!file_exists($logFile)) {
    echo "<p>❌ Arquivo de log não encontrado: $logFile</p>";
    echo "<p>O arquivo será criado quando você acessar authorize-debug.php</p>";
} else {
    $content = file_get_contents($logFile);

    echo "<p><strong>Arquivo:</strong> $logFile</p>";
    echo "<p><strong>Tamanho:</strong> " . filesize($logFile) . " bytes</p>";
    echo "<hr>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";

    echo "<hr>";
    echo "<form method='post'>";
    echo "<button type='submit' name='clear'>Limpar Log</button>";
    echo "</form>";

    if (isset($_POST['clear'])) {
        file_put_contents($logFile, '');
        echo "<script>window.location.reload();</script>";
    }
}

echo "<hr>";
echo "<p><a href='/backend/public/api/oauth/mercadolivre/authorize-debug.php'>Executar authorize-debug.php</a></p>";
echo "<p><a href='/test-authorize-simple.php'>Executar test-authorize-simple.php</a></p>";
