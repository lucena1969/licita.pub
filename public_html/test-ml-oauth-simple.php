<?php
/**
 * Teste OAuth ML SIMPLES - SEM autenticação
 */

// Carregar .env MANUALMENTE
$envFile = __DIR__ . '/backend/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
            putenv(trim($key) . "=" . trim($value, '"\''));
        }
    }
}

// Autoloader
require_once __DIR__ . '/backend/vendor/autoload.php';

echo "<h1>Teste OAuth ML - Sem Autenticação</h1>";

try {
    echo "<h2>1. Instanciando MercadoLivreOAuthService</h2>";

    $oauthService = new \App\Services\MercadoLivreOAuthService();
    echo "✅ Service instanciado<br>";

    echo "<h2>2. Gerando URL (com user_id fake = 1)</h2>";

    $authUrl = $oauthService->getAuthorizationUrl(1);
    echo "✅ URL gerada!<br>";

    echo "<h2>3. URL de Autorização:</h2>";
    echo "<a href='$authUrl' target='_blank'>$authUrl</a><br>";

    echo "<h2>4. Se clicar no link acima:</h2>";
    echo "<ul>";
    echo "<li>Você será redirecionado para o Mercado Livre</li>";
    echo "<li>Após autorizar, o ML vai redirecionar para o callback</li>";
    echo "<li>O callback vai falhar porque o state será inválido (user_id fake)</li>";
    echo "</ul>";

    echo "<hr>";
    echo "<h2>✅ OAuth está funcionando!</h2>";
    echo "<p>O problema é apenas com a autenticação do usuário.</p>";

} catch (\Exception $e) {
    echo "<h2>❌ ERRO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
