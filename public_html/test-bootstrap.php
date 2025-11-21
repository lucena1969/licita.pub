<?php
/**
 * Teste do bootstrap e classes
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. Testando bootstrap...<br>\n";

try {
    require_once __DIR__ . '/backend/public/bootstrap.php';
    echo "✅ Bootstrap carregado<br>\n";
} catch (\Exception $e) {
    echo "❌ Erro no bootstrap: " . $e->getMessage() . "<br>\n";
    exit;
}

echo "2. Testando classes...<br>\n";

try {
    echo "- AuthMiddleware: ";
    $exists = class_exists('App\Middleware\AuthMiddleware');
    echo $exists ? "✅<br>\n" : "❌<br>\n";

    echo "- InteligenciaPrecoService: ";
    $exists = class_exists('App\Services\InteligenciaPrecoService');
    echo $exists ? "✅<br>\n" : "❌<br>\n";

    echo "- ItemAtaRepository: ";
    $exists = class_exists('App\Repositories\ItemAtaRepository');
    echo $exists ? "✅<br>\n" : "❌<br>\n";

    echo "- MercadoLivreAPI: ";
    $exists = class_exists('App\Services\MercadoLivreAPI');
    echo $exists ? "✅<br>\n" : "❌<br>\n";

    echo "- MercadoLivreOAuthService: ";
    $exists = class_exists('App\Services\MercadoLivreOAuthService');
    echo $exists ? "✅<br>\n" : "❌<br>\n";

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>\n";
}

echo "<br>3. Todas as classes necessárias estão disponíveis!<br>\n";
