<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== TESTE DE DEBUG - MULTIPLOS CAMINHOS ===\n\n";

// 1. Verificar autoloader com multiplos caminhos
$autoloaderPaths = [
    __DIR__ . '/../../vendor/autoload.php',      // Produção (Hostinger)
    __DIR__ . '/../../../vendor/autoload.php',   // Localhost (XAMPP)
];

$autoloaderPath = null;
foreach ($autoloaderPaths as $path) {
    echo "Testando: $path\n";
    if (file_exists($path)) {
        echo "  ✓ ENCONTRADO!\n";
        $autoloaderPath = $path;
        break;
    } else {
        echo "  ✗ Não existe\n";
    }
}

echo "\n";

if ($autoloaderPath) {
    echo "1. Autoloader encontrado: $autoloaderPath\n\n";
    try {
        require_once $autoloaderPath;
        echo "2. Autoloader carregado com sucesso\n\n";
    } catch (Exception $e) {
        echo "2. ERRO ao carregar autoloader: " . $e->getMessage() . "\n\n";
        exit;
    }

    // 3. Verificar classe Database
    if (class_exists('\App\Config\Database')) {
        echo "3. Classe Database encontrada\n\n";

        try {
            $db = \App\Config\Database::getConnection();
            echo "4. Conexao com banco: SUCESSO\n";
            echo "   Tipo de conexao: " . get_class($db) . "\n";
        } catch (Exception $e) {
            echo "4. ERRO ao conectar: " . $e->getMessage() . "\n";
        }
    } else {
        echo "3. ERRO: Classe Database NAO encontrada\n";
    }
} else {
    echo "2. ERRO: Autoloader nao existe!\n";
    echo "\n   Tentando outros caminhos:\n";

    $paths = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
        __DIR__ . '/../../../../../vendor/autoload.php'
    ];

    foreach ($paths as $path) {
        echo "   - $path: " . (file_exists($path) ? "EXISTE" : "nao existe") . "\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
