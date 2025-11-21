<?php
/**
 * Diagnóstico Completo - API do Governo
 * Testa todos os endpoints possíveis
 */

header('Content-Type: text/html; charset=utf-8');
set_time_limit(120);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico API Governo</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #444; border-radius: 5px; }
        .success { background: #1e3a1e; border-color: #4caf50; }
        .error { background: #3a1e1e; border-color: #f44336; }
        .warning { background: #3a3a1e; border-color: #ff9800; }
        .info { background: #1e2a3a; border-color: #2196f3; }
        h2 { color: #61dafb; margin-top: 0; }
        pre { background: #000; padding: 10px; overflow-x: auto; border-radius: 3px; }
        .status { font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Completo - API do Governo Federal</h1>

<?php

// ============================================
// TESTE 1: Conectividade Básica
// ============================================
echo "<div class='test info'>";
echo "<h2>TESTE 1: Conectividade Básica</h2>";

$hosts = [
    'Google (controle)' => 'https://www.google.com',
    'Portal Gov.br' => 'https://www.gov.br',
    'Dados Abertos Compras' => 'https://dadosabertos.compras.gov.br',
    'Compras Dados Gov (legado)' => 'https://compras.dados.gov.br',
    'Compras Dados Gov HTTP' => 'http://compras.dados.gov.br'
];

foreach ($hosts as $name => $url) {
    echo "<strong>Testando: $name</strong><br>";
    echo "URL: $url<br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Não seguir redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request

    $start = microtime(true);
    curl_exec($ch);
    $time = round((microtime(true) - $start) * 1000, 2);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 400) {
        echo "✅ <span class='status' style='color: #4caf50'>OK</span> - HTTP $httpCode - {$time}ms<br>";
    } elseif ($httpCode >= 300 && $httpCode < 400) {
        echo "⚠️ <span class='status' style='color: #ff9800'>REDIRECT</span> - HTTP $httpCode - {$time}ms<br>";
    } elseif ($error) {
        echo "❌ <span class='status' style='color: #f44336'>ERRO</span> - $error<br>";
    } else {
        echo "❌ <span class='status' style='color: #f44336'>FALHA</span> - HTTP $httpCode - {$time}ms<br>";
    }
    echo "<br>";
}
echo "</div>";

// ============================================
// TESTE 2: Endpoints da API (GET simples)
// ============================================
echo "<div class='test info'>";
echo "<h2>TESTE 2: Endpoints da API</h2>";

$endpoints = [
    'Catálogo de Materiais (HTTPS)' => 'https://compras.dados.gov.br/materiais/v1/materiais.json?limite=1',
    'Catálogo de Materiais (HTTP)' => 'http://compras.dados.gov.br/materiais/v1/materiais.json?limite=1',
    'API Dados Abertos - Status' => 'https://dadosabertos.compras.gov.br/swagger-ui.html',
];

foreach ($endpoints as $name => $url) {
    echo "<strong>Testando: $name</strong><br>";
    echo "URL: $url<br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Licita.pub/1.0 (Teste Diagnostico)');

    $start = microtime(true);
    $response = curl_exec($ch);
    $time = round((microtime(true) - $start) * 1000, 2);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    echo "HTTP Code: $httpCode<br>";
    echo "Content-Type: $contentType<br>";
    echo "Tempo: {$time}ms<br>";

    if ($error) {
        echo "❌ <span class='status' style='color: #f44336'>ERRO cURL</span>: $error<br>";
    } elseif ($httpCode == 200) {
        echo "✅ <span class='status' style='color: #4caf50'>SUCESSO</span><br>";
        echo "Tamanho resposta: " . strlen($response) . " bytes<br>";

        // Tentar parse JSON
        if (strpos($contentType, 'json') !== false) {
            $json = json_decode($response, true);
            if ($json) {
                echo "✅ JSON válido<br>";
                echo "<pre>" . substr(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 500) . "...</pre>";
            } else {
                echo "❌ JSON inválido<br>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            }
        } else {
            echo "<pre>" . htmlspecialchars(substr($response, 0, 300)) . "...</pre>";
        }
    } else {
        echo "❌ <span class='status' style='color: #f44336'>FALHA</span> HTTP $httpCode<br>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
    echo "<br>";
}
echo "</div>";

// ============================================
// TESTE 3: DNS Resolution
// ============================================
echo "<div class='test info'>";
echo "<h2>TESTE 3: Resolução DNS</h2>";

$domains = [
    'compras.dados.gov.br',
    'dadosabertos.compras.gov.br',
    'www.gov.br'
];

foreach ($domains as $domain) {
    echo "<strong>Domain: $domain</strong><br>";
    $ip = gethostbyname($domain);

    if ($ip === $domain) {
        echo "❌ <span class='status' style='color: #f44336'>FALHA</span> - Não resolveu DNS<br>";
    } else {
        echo "✅ <span class='status' style='color: #4caf50'>OK</span> - IP: $ip<br>";
    }
    echo "<br>";
}
echo "</div>";

// ============================================
// TESTE 4: Testar nossa API local
// ============================================
echo "<div class='test info'>";
echo "<h2>TESTE 4: Nossa API Local</h2>";

$localTests = [
    'API Mock' => 'http://licita.pub.local/backend/public/api/inteligencia/buscar-precos-governo-mock.php?q=notebook',
    'API Real' => 'http://licita.pub.local/backend/public/api/inteligencia/buscar-precos-governo.php?q=notebook'
];

foreach ($localTests as $name => $url) {
    echo "<strong>$name</strong><br>";
    echo "URL: $url<br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);

    $start = microtime(true);
    $response = curl_exec($ch);
    $time = round((microtime(true) - $start) * 1000, 2);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ <span class='status' style='color: #f44336'>ERRO</span>: $error<br>";
    } elseif ($httpCode == 200) {
        $json = json_decode($response, true);
        if ($json && isset($json['success'])) {
            if ($json['success']) {
                echo "✅ <span class='status' style='color: #4caf50'>SUCESSO</span> - {$time}ms<br>";
                if (isset($json['metadata']['modo'])) {
                    echo "Modo: " . $json['metadata']['modo'] . "<br>";
                }
            } else {
                echo "⚠️ <span class='status' style='color: #ff9800'>API retornou erro</span><br>";
                echo "Erro: " . ($json['error'] ?? 'N/A') . "<br>";
            }
        } else {
            echo "❌ JSON inválido ou sem campo 'success'<br>";
        }
    } else {
        echo "❌ HTTP $httpCode<br>";
    }
    echo "<br>";
}
echo "</div>";

// ============================================
// RESUMO
// ============================================
echo "<div class='test warning'>";
echo "<h2>📊 RESUMO DO DIAGNÓSTICO</h2>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Servidor:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>PHP:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>cURL:</strong> " . (function_exists('curl_version') ? curl_version()['version'] : 'N/A') . "</p>";
echo "</div>";

?>

</body>
</html>
