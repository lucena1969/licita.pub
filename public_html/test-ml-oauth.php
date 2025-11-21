<?php
/**
 * Script de teste para diagnosticar erro no OAuth ML
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste OAuth Mercado Livre</h1>";

// 1. Verificar se .env existe
echo "<h2>1. Verificando arquivo .env</h2>";
$envPath = __DIR__ . '/backend/.env';
if (file_exists($envPath)) {
    echo "✅ Arquivo .env existe<br>";
    echo "Conteúdo:<pre>" . htmlspecialchars(file_get_contents($envPath)) . "</pre>";
} else {
    echo "❌ Arquivo .env NÃO encontrado em: $envPath<br>";
}

// 2. Verificar autoload
echo "<h2>2. Verificando autoload</h2>";
$autoloadPath = __DIR__ . '/backend/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✅ Autoload existe<br>";
    require_once $autoloadPath;
} else {
    echo "❌ Autoload NÃO encontrado<br>";
    die();
}

// 3. Verificar Dotenv
echo "<h2>3. Testando Dotenv</h2>";
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/backend');
    $dotenv->load();
    echo "✅ Dotenv carregado<br>";
    echo "ML_CLIENT_ID: " . ($_ENV['ML_CLIENT_ID'] ?? 'NÃO ENCONTRADO') . "<br>";
    echo "ML_CLIENT_SECRET: " . (isset($_ENV['ML_CLIENT_SECRET']) ? '***' : 'NÃO ENCONTRADO') . "<br>";
    echo "ML_REDIRECT_URI: " . ($_ENV['ML_REDIRECT_URI'] ?? 'NÃO ENCONTRADO') . "<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar Dotenv: " . $e->getMessage() . "<br>";
}

// 4. Verificar classe Env
echo "<h2>4. Testando classe Env</h2>";
try {
    require_once __DIR__ . '/backend/src/Config/Env.php';

    \App\Config\Env::load(__DIR__ . '/backend/.env');
    echo "✅ Classe Env carregada<br>";
    echo "ML_CLIENT_ID via Env::get: " . \App\Config\Env::get('ML_CLIENT_ID', 'NÃO ENCONTRADO') . "<br>";
} catch (Exception $e) {
    echo "❌ Erro na classe Env: " . $e->getMessage() . "<br>";
}

// 5. Verificar Database
echo "<h2>5. Testando conexão com Database</h2>";
try {
    require_once __DIR__ . '/backend/src/Config/Database.php';

    $db = \App\Config\Database::getConnection();
    echo "✅ Conexão com banco estabelecida<br>";

    // Verificar tabelas
    echo "<h3>Verificando tabelas OAuth:</h3>";
    $tables = ['ml_oauth_states', 'ml_oauth_tokens'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela $table existe<br>";
        } else {
            echo "❌ Tabela $table NÃO existe<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro no Database: " . $e->getMessage() . "<br>";
}

// 6. Verificar MercadoLivreOAuthService
echo "<h2>6. Testando MercadoLivreOAuthService</h2>";
try {
    require_once __DIR__ . '/backend/src/Services/MercadoLivreOAuthService.php';

    $oauthService = new \App\Services\MercadoLivreOAuthService();
    echo "✅ MercadoLivreOAuthService instanciado<br>";

    // Tentar gerar URL de autorização (com user_id fake = 1)
    try {
        $authUrl = $oauthService->getAuthorizationUrl(1);
        echo "✅ URL de autorização gerada:<br>";
        echo "<a href='$authUrl' target='_blank'>$authUrl</a><br>";
    } catch (Exception $e) {
        echo "❌ Erro ao gerar URL: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }

} catch (Exception $e) {
    echo "❌ Erro ao instanciar MercadoLivreOAuthService: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Teste concluído!</h2>";
