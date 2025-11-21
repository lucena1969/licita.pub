<?php
/**
 * Versão com debug extremo
 */

echo "PASSO 1: Iniciando<br>";
flush();

// Carregar .env MANUALMENTE
$envFile = __DIR__ . '/../../../../.env';
echo "PASSO 2: ENV path: $envFile<br>";
flush();

if (!file_exists($envFile)) {
    die("ERRO: .env não existe<br>");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"\'');
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}
echo "PASSO 3: .env carregado<br>";
flush();

// Verificar variáveis críticas
echo "PASSO 4: Verificando variáveis<br>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NÃO DEFINIDO') . "<br>";
echo "ML_CLIENT_ID: " . ($_ENV['ML_CLIENT_ID'] ?? 'NÃO DEFINIDO') . "<br>";
flush();

// Autoloader
echo "PASSO 5: Carregando autoload<br>";
flush();

require_once __DIR__ . '/../../../../vendor/autoload.php';
echo "PASSO 6: Autoload carregado<br>";
flush();

try {
    echo "PASSO 7: Importando classes<br>";
    flush();

    use App\Services\MercadoLivreOAuthService;
    use App\Middleware\AuthMiddleware;

    echo "PASSO 8: Classes importadas<br>";
    flush();

    echo "PASSO 9: Chamando AuthMiddleware::verificar()<br>";
    flush();

    $usuario = AuthMiddleware::verificar();

    echo "PASSO 10: Método executado<br>";
    flush();

    if (!$usuario) {
        echo "PASSO 11: Usuário NÃO autenticado<br>";
        echo "Redirecionaria para login<br>";
        exit;
    }

    echo "PASSO 11: Usuário autenticado: ID = {$usuario->id}<br>";
    flush();

    echo "PASSO 12: Instanciando MercadoLivreOAuthService<br>";
    flush();

    $oauthService = new MercadoLivreOAuthService();

    echo "PASSO 13: Gerando URL<br>";
    flush();

    $authUrl = $oauthService->getAuthorizationUrl($usuario->id);

    echo "PASSO 14: URL gerada!<br>";
    echo "URL: $authUrl<br>";
    echo "<br>✅ TUDO FUNCIONOU!<br>";

} catch (Exception $e) {
    echo "<br>❌ ERRO CAPTURADO:<br>";
    echo "Mensagem: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<br>❌ FATAL ERROR:<br>";
    echo "Mensagem: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
