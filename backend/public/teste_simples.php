<?php
// Teste ultra-simples - só mostra informações básicas
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Simples</title>
</head>
<body>
    <h1>Teste Básico PHP</h1>

    <h2>1. PHP Funcionando?</h2>
    <p><?php echo "✅ SIM! PHP versão: " . phpversion(); ?></p>

    <h2>2. Informações do Servidor</h2>
    <pre><?php
    echo "Sistema: " . PHP_OS . "\n";
    echo "Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
    echo "Diretório atual: " . __DIR__ . "\n";
    ?></pre>

    <h2>3. Extensões PHP</h2>
    <p>PDO: <?php echo extension_loaded('pdo') ? '✅ Instalado' : '❌ NÃO instalado'; ?></p>
    <p>PDO MySQL: <?php echo extension_loaded('pdo_mysql') ? '✅ Instalado' : '❌ NÃO instalado'; ?></p>

    <h2>4. Teste de Caminhos</h2>
    <pre><?php
    // Descobrir estrutura real
    $currentDir = __DIR__;
    echo "Diretório atual (public): $currentDir\n";

    $backendDir = dirname(__DIR__);
    echo "Diretório backend: $backendDir\n";

    $rootDir = dirname(dirname(__DIR__));
    echo "Diretório raiz: $rootDir\n\n";

    // O caminho correto é a partir de backend/public
    $base = dirname(__DIR__); // backend
    echo "Base path (backend): $base\n\n";

    $files = [
        'src/Config/Config.php',
        'src/Config/Database.php',
        '.env'
    ];

    foreach ($files as $file) {
        $fullPath = $base . '/' . $file;
        $exists = file_exists($fullPath);
        echo ($exists ? '✅' : '❌') . " $file\n";
        echo "   Caminho: $fullPath\n";
    }
    ?></pre>

    <h2>5. Teste de .env</h2>
    <pre><?php
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        echo "✅ Arquivo .env encontrado em: $envPath\n";
        echo "Tamanho: " . filesize($envPath) . " bytes\n";
        echo "Permissões: " . substr(sprintf('%o', fileperms($envPath)), -4) . "\n";
    } else {
        echo "❌ Arquivo .env NÃO encontrado\n";
        echo "Procurado em: $envPath\n";

        echo "\n📁 Estrutura de diretórios:\n";
        echo "Conteúdo de " . dirname(__DIR__) . ":\n";
        $files = @scandir(dirname(__DIR__));
        if ($files) {
            foreach ($files as $f) {
                if ($f !== '.' && $f !== '..') {
                    $path = dirname(__DIR__) . '/' . $f;
                    $type = is_dir($path) ? '[DIR]' : '[FILE]';
                    echo "  $type $f\n";
                }
            }
        }
    }
    ?></pre>

    <h2>6. Erros PHP</h2>
    <p>Display errors: <?php echo ini_get('display_errors') ? 'ON' : 'OFF'; ?></p>
    <p>Error reporting: <?php echo ini_get('error_reporting'); ?></p>

</body>
</html>
