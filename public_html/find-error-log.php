<?php
/**
 * Script para encontrar o arquivo de log de erros
 */

echo "<h1>Localizando arquivo de log de erros</h1>";

// Verificar configuração PHP
echo "<h2>Configuração PHP</h2>";
echo "error_log: " . ini_get('error_log') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "error_reporting: " . error_reporting() . "<br>";

echo "<h2>Possíveis locais de log:</h2>";

$possibleLogs = [
    '/home/u590097272/logs/error_log',
    '/home/u590097272/public_html/error_log',
    '/home/u590097272/domains/licita.pub/logs/error_log',
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log',
    __DIR__ . '/../../error_log',
    '/var/log/apache2/error.log',
    '/var/log/httpd/error.log',
    '/usr/local/lsws/logs/error.log',
];

foreach ($possibleLogs as $log) {
    if (file_exists($log)) {
        echo "✅ ENCONTRADO: $log<br>";
        echo "Tamanho: " . filesize($log) . " bytes<br>";

        if (is_readable($log)) {
            echo "Últimas 20 linhas:<br>";
            echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:300px;'>";
            $lines = file($log);
            $lastLines = array_slice($lines, -20);
            echo htmlspecialchars(implode('', $lastLines));
            echo "</pre><hr>";
        } else {
            echo "⚠️ Arquivo existe mas não tem permissão de leitura<br><hr>";
        }
    }
}

echo "<h2>Triggering um erro para teste:</h2>";
trigger_error("TESTE DE LOG - " . date('Y-m-d H:i:s'), E_USER_WARNING);
echo "Erro de teste disparado. Verifique os logs acima.<br>";
