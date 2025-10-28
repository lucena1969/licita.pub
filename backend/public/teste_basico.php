<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Básico</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: white; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Teste Básico</h1>

    <h2>1. PHP Funciona?</h2>
    <p class="success">✅ SIM! Versão: <?php echo phpversion(); ?></p>

    <h2>2. Onde estou?</h2>
    <pre><?php
echo "Arquivo: " . __FILE__ . "\n";
echo "Diretório: " . __DIR__ . "\n";
    ?></pre>

    <h2>3. Estrutura de pastas</h2>
    <pre><?php
$backend = dirname(__DIR__);
echo "Backend: $backend\n\n";

echo "Arquivos em backend/:\n";
$files = @scandir($backend);
if ($files) {
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $type = is_dir("$backend/$f") ? '[DIR] ' : '[FILE]';
            echo "  $type $f\n";
        }
    }
}
    ?></pre>

    <h2>4. Arquivo .env existe?</h2>
    <?php
$env = dirname(__DIR__) . '/.env';
if (file_exists($env)) {
    echo "<p class='success'>✅ SIM! Em: $env</p>";
    echo "<p>Tamanho: " . filesize($env) . " bytes</p>";
} else {
    echo "<p class='error'>❌ NÃO! Procurado em: $env</p>";
}
    ?>

    <h2>5. Config.php existe?</h2>
    <?php
$config = dirname(__DIR__) . '/src/Config/Config.php';
if (file_exists($config)) {
    echo "<p class='success'>✅ SIM! Em: $config</p>";
} else {
    echo "<p class='error'>❌ NÃO! Procurado em: $config</p>";
}
    ?>

    <h2>6. Database.php existe?</h2>
    <?php
$db = dirname(__DIR__) . '/src/Config/Database.php';
if (file_exists($db)) {
    echo "<p class='success'>✅ SIM! Em: $db</p>";
} else {
    echo "<p class='error'>❌ NÃO! Procurado em: $db</p>";
}
    ?>

    <?php
    // Se todos existem, tentar conectar
    if (file_exists($env) && file_exists($config) && file_exists($db)) {
        echo "<h2>7. Tentando conectar ao banco...</h2>";
        try {
            define('BASE_PATH', dirname(__DIR__));
            require_once $config;
            require_once $db;

            use App\Config\Config;
            use App\Config\Database;

            Config::load();
            echo "<p class='success'>✅ Config carregado</p>";

            $pdo = Database::getConnection();
            echo "<p class='success'>✅ Conexão estabelecida</p>";

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM licitacoes");
            $result = $stmt->fetch();
            echo "<p class='success'>✅ Query executada: " . $result['total'] . " licitações</p>";

        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
        }
    }
    ?>

    <hr>
    <p><strong>Se todos os testes passaram (✅), você pode usar:</strong></p>
    <ul>
        <li><a href="verificar_duplicatas_web.php">Verificar Duplicatas</a></li>
        <li><a href="limpar_duplicatas_web.php">Limpar Duplicatas</a></li>
        <li><a href="admin_duplicatas.php">Menu Admin</a></li>
    </ul>
</body>
</html>
