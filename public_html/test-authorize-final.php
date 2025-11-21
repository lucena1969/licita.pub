<?php
/**
 * Teste FINAL - Acessar authorize.php via cURL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste Final - Endpoint Authorize.php</h1>";

echo "<h2>Testando com cURL (sem autenticação)</h2>";

$url = 'https://licita.pub/backend/public/api/oauth/mercadolivre/authorize.php';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "<h3>Resultado:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($httpCode === 302 || $httpCode === 301) {
    echo "✅ <strong>Redirect detectado!</strong> Isso é esperado (sem autenticação).<br>";

    // Extrair Location header
    if (preg_match('/Location: (.+)/i', $headers, $matches)) {
        $location = trim($matches[1]);
        echo "<p><strong>Redirecionando para:</strong> <a href='$location'>$location</a></p>";

        if (strpos($location, 'login.html') !== false) {
            echo "✅ <strong>CORRETO:</strong> Redirecionando para login (não autenticado)<br>";
        } elseif (strpos($location, 'auth.mercadolivre.com.br') !== false) {
            echo "✅ <strong>INESPERADO MAS BOM:</strong> Redirecionando para Mercado Livre (OAuth funcionando!)<br>";
        }
    }
} elseif ($httpCode === 500) {
    echo "❌ <strong>ERRO 500!</strong> Ainda há problema.<br>";
    echo "<h4>Headers:</h4><pre>" . htmlspecialchars($headers) . "</pre>";
    echo "<h4>Body:</h4><pre>" . htmlspecialchars($body) . "</pre>";
} elseif ($httpCode === 200) {
    echo "⚠️ <strong>HTTP 200:</strong> Página retornou conteúdo (pode ser erro sendo exibido)<br>";
    echo "<h4>Body:</h4><pre>" . htmlspecialchars(substr($body, 0, 1000)) . "</pre>";
} else {
    echo "⚠️ <strong>HTTP $httpCode:</strong> Código inesperado<br>";
    echo "<h4>Headers:</h4><pre>" . htmlspecialchars($headers) . "</pre>";
    echo "<h4>Body:</h4><pre>" . htmlspecialchars($body) . "</pre>";
}

echo "<hr>";
echo "<h2>📋 Conclusão</h2>";

if ($httpCode === 302 || $httpCode === 301) {
    echo "<p>✅ <strong>SUCESSO!</strong> O endpoint está funcionando corretamente.</p>";
    echo "<p>Para testar o fluxo completo:</p>";
    echo "<ol>";
    echo "<li>Faça login em: <a href='https://licita.pub/frontend/login.html' target='_blank'>https://licita.pub/frontend/login.html</a></li>";
    echo "<li>Acesse: <a href='https://licita.pub/frontend/inteligencia-precos.html' target='_blank'>Inteligência de Preços</a></li>";
    echo "<li>Clique em 'Autorizar Mercado Livre'</li>";
    echo "</ol>";
} elseif ($httpCode === 500) {
    echo "<p>❌ <strong>PROBLEMA PERSISTE.</strong> Verifique os logs do servidor.</p>";
    echo "<p>Acesse o painel Hostinger → Logs → Error Log</p>";
    echo "<p>Procure por linhas recentes com '[OAuth ML Authorize]'</p>";
} else {
    echo "<p>⚠️ Comportamento inesperado. Analise os detalhes acima.</p>";
}

echo "<hr>";
echo "<h2>🔧 Verificar Logs no Servidor</h2>";
echo "<p>Se ainda houver problemas, os logs agora mostram cada etapa:</p>";
echo "<pre>";
echo "[OAuth ML Authorize] Iniciando endpoint\n";
echo "[OAuth ML Authorize] Verificando autenticação\n";
echo "[OAuth ML Authorize] Usuário não autenticado, redirecionando para login\n";
echo "OU\n";
echo "[OAuth ML Authorize] Usuário autenticado: ID = X\n";
echo "[OAuth ML Authorize] Gerando URL de autorização\n";
echo "[OAuth ML Authorize] URL gerada com sucesso\n";
echo "[OAuth ML Authorize] Redirecionando para: [URL]\n";
echo "</pre>";
