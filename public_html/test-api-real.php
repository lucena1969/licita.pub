<?php
/**
 * Simula requisição real via HTTP
 */

$termo = 'notebook';
// Usar o domínio correto configurado no VirtualHost
$url = "http://licita.pub.local/backend/public/api/inteligencia/buscar-precos-governo.php?q=" . urlencode($termo);

echo "=== TESTE REQUISIÇÃO HTTP REAL ===\n\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "Erro cURL: $error\n";
}
echo "\n--- RESPOSTA RAW ---\n";
echo $response;
echo "\n--- FIM RESPOSTA ---\n\n";

// Tentar parse JSON
$json = json_decode($response, true);
if ($json) {
    echo "✅ JSON válido!\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    if (isset($json['error'])) {
        echo "Error: " . $json['error'] . "\n";
    }
} else {
    echo "❌ JSON inválido!\n";
    echo "Erro: " . json_last_error_msg() . "\n";

    // Verificar se é HTML
    if (strpos($response, '<') !== false) {
        echo "\n⚠️ Resposta parece ser HTML/XML\n";
        echo "Primeiros 500 caracteres:\n";
        echo substr($response, 0, 500) . "\n";
    }
}
