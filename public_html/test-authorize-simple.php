<?php
/**
 * Teste SIMPLES - Executar o código do authorize.php linha por linha
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Teste Simples - Executando authorize.php passo a passo</h1>";

try {
    echo "<h2>Passo 1: Autoloader</h2>";
    $autoloadPath = __DIR__ . '/backend/vendor/autoload.php';
    echo "Path: $autoloadPath<br>";

    if (!file_exists($autoloadPath)) {
        throw new Exception("Autoload não encontrado");
    }

    require_once $autoloadPath;
    echo "✅ Autoload carregado<br>";

    echo "<h2>Passo 2: Dotenv</h2>";
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend');
    $dotenv->load();
    echo "✅ Dotenv carregado<br>";

    echo "ML_CLIENT_ID: " . ($_ENV['ML_CLIENT_ID'] ?? 'NÃO ENCONTRADO') . "<br>";

    echo "<h2>Passo 3: AuthMiddleware</h2>";
    $auth = new \App\Middleware\AuthMiddleware();
    echo "✅ AuthMiddleware instanciado<br>";

    echo "<h2>Passo 4: Verificar Sessão</h2>";
    try {
        $usuario = $auth->verificarSessao();

        if ($usuario) {
            echo "✅ Usuário autenticado: " . print_r($usuario, true) . "<br>";
        } else {
            echo "⚠️ Usuário não autenticado (esperado se você não fez login)<br>";
            echo "Seria redirecionado para: /frontend/login.html<br>";
        }
    } catch (Exception $e) {
        echo "⚠️ Erro ao verificar sessão: " . $e->getMessage() . "<br>";
        echo "Stack: <pre>" . $e->getTraceAsString() . "</pre>";
    }

    echo "<h2>Passo 5: MercadoLivreOAuthService</h2>";
    $oauthService = new \App\Services\MercadoLivreOAuthService();
    echo "✅ MercadoLivreOAuthService instanciado<br>";

    echo "<h2>Passo 6: Gerar URL (com user_id fake = 1)</h2>";
    $authUrl = $oauthService->getAuthorizationUrl(1);
    echo "✅ URL gerada com sucesso!<br>";
    echo "URL: <a href='$authUrl' target='_blank'>$authUrl</a><br>";

    echo "<hr>";
    echo "<h2>✅ TODOS OS PASSOS EXECUTADOS COM SUCESSO!</h2>";
    echo "<p>O código do authorize.php está correto e funcional.</p>";
    echo "<p><strong>Conclusão:</strong> O erro 500 está sendo causado por algo ANTES do código PHP ser executado.</p>";

} catch (Exception $e) {
    echo "<hr>";
    echo "<h2>❌ ERRO ENCONTRADO!</h2>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h2>🔍 Possíveis Causas do Erro 500</h2>";
echo "<ol>";
echo "<li><strong>.htaccess com regras problemáticas</strong> - Pode estar bloqueando o acesso ao arquivo</li>";
echo "<li><strong>Permissões de arquivo</strong> - O authorize.php pode não ter permissão de execução</li>";
echo "<li><strong>PHP memory_limit ou max_execution_time</strong> - Limites muito baixos</li>";
echo "<li><strong>Erro fatal silencioso</strong> - Erro antes mesmo do error_log funcionar</li>";
echo "<li><strong>Módulo PHP faltando</strong> - Como php-curl, php-mbstring, etc</li>";
echo "</ol>";

echo "<h2>📝 Próximos Passos</h2>";
echo "<ol>";
echo "<li>Verifique os logs no painel Hostinger (Error Log)</li>";
echo "<li>Verifique o arquivo .htaccess em <code>backend/public/api/oauth/mercadolivre/</code></li>";
echo "<li>Verifique as permissões do authorize.php (deve ser 644 ou 755)</li>";
echo "</ol>";
