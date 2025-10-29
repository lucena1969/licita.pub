<?php
/**
 * Debug completo do endpoint - simula exatamente o que o frontend faz
 */

// Habilitar log de TODOS os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>DEBUG COMPLETO - Endpoint Detalhes</h1>";

// ID que está dando erro
$idTeste = $_GET['test_id'] ?? '13323274000163-2-000448/2025';

echo "<h2>1. Informações do Ambiente</h2>";
echo "<pre>";
echo "ID de teste: $idTeste\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Script: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

echo "<h2>2. Testando Acesso Direto ao Endpoint Real</h2>";

// Fazer requisição HTTP real ao endpoint
$url = "https://licita.pub/api/licitacoes/detalhes.php?id=" . urlencode($idTeste);

echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Resposta HTTP:</h3>";
echo "<pre>";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "CURL Error: $error\n";
}

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "\n=== HEADERS ===\n";
echo $headers;

echo "\n=== BODY ===\n";
echo $body;

// Tentar decodificar JSON
$json = json_decode($body, true);
if ($json) {
    echo "\n=== JSON DECODIFICADO ===\n";
    print_r($json);
} else {
    echo "\n=== ERRO JSON ===\n";
    echo "JSON inválido: " . json_last_error_msg() . "\n";
}

echo "</pre>";

// Se deu erro 500, vamos investigar
if ($httpCode == 500) {
    echo "<h2 style='color: red;'>❌ ERRO 500 CONFIRMADO!</h2>";

    echo "<h3>3. Verificando Logs de Erro do PHP</h3>";

    // Tentar ler os últimos erros do log
    $possibleLogPaths = [
        '/home/u590097272/logs/error_log',
        '/home/u590097272/domains/licita.pub/logs/error_log',
        ini_get('error_log'),
        '/var/log/php_errors.log',
        '/tmp/php_errors.log'
    ];

    echo "<p>Procurando logs em:</p><ul>";
    foreach ($possibleLogPaths as $path) {
        echo "<li>$path</li>";
        if (file_exists($path) && is_readable($path)) {
            echo " <strong style='color: green;'>(ENCONTRADO)</strong>";

            // Ler últimas 50 linhas
            $lines = file($path);
            $lastLines = array_slice($lines, -50);

            echo "<h4>Últimas linhas do log:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;'>";
            echo htmlspecialchars(implode('', $lastLines));
            echo "</pre>";
            break;
        } else {
            echo " <span style='color: gray;'>(não encontrado)</span>";
        }
    }
    echo "</ul>";

    echo "<h3>4. Teste Direto do Arquivo PHP</h3>";
    echo "<p>Vamos incluir o endpoint diretamente e capturar o erro:</p>";

    $_GET['id'] = $idTeste;

    ob_start();
    try {
        require __DIR__ . '/api/licitacoes/detalhes.php';
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "<div style='background: #fee; border: 2px solid red; padding: 20px; margin: 10px 0;'>";
        echo "<h4>EXCEÇÃO CAPTURADA:</h4>";
        echo "<pre>";
        echo "Tipo: " . get_class($e) . "\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
        echo "\n=== STACK TRACE ===\n";
        echo $e->getTraceAsString();
        echo "</pre>";
        echo "</div>";
    }
    $directOutput = ob_get_clean();

    echo "<h4>Output Direto:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    echo htmlspecialchars($directOutput);
    echo "</pre>";

} else {
    echo "<h2 style='color: green;'>✅ Endpoint respondeu com HTTP $httpCode</h2>";

    if ($httpCode == 200 && $json && $json['success']) {
        echo "<p style='color: green; font-weight: bold;'>ENDPOINT FUNCIONANDO PERFEITAMENTE!</p>";
        echo "<p>O problema deve ser no frontend (JavaScript) ou cache do navegador.</p>";
    }
}

echo "<h2>5. Próximos Passos</h2>";
echo "<ul>";
echo "<li>Se HTTP 500: Verifique os logs acima para ver o erro exato</li>";
echo "<li>Se HTTP 200: O problema é no frontend - verifique o Console do navegador</li>";
echo "<li>Teste abrindo o URL direto no navegador (link acima)</li>";
echo "<li>Teste em aba anônima para descartar cache</li>";
echo "</ul>";
