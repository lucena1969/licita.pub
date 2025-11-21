<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - Licita.pub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔧 Teste de Conexão e Configuração</h1>

        <?php
        echo "<h2>1. Teste de PHP</h2>";
        echo "<p class='success'>✅ PHP funcionando! Versão: " . phpversion() . "</p>";

        echo "<h2>2. Teste de Caminhos</h2>";
        echo "<pre>";
        echo "__DIR__: " . __DIR__ . "\n";
        echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
        echo "</pre>";

        echo "<h2>3. Teste de Arquivos</h2>";
        $files = [
            dirname(__DIR__) . '/src/Config/Config.php',
            dirname(__DIR__) . '/src/Config/Database.php',
            dirname(__DIR__) . '/.env'
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                echo "<p class='success'>✅ Existe: " . basename($file) . "</p>";
            } else {
                echo "<p class='error'>❌ NÃO existe: " . $file . "</p>";
            }
        }

        echo "<h2>4. Teste de Conexão com Banco</h2>";

        try {
            define('BASE_PATH', dirname(__DIR__));
            require_once BASE_PATH . '/src/Config/Config.php';
            require_once BASE_PATH . '/src/Config/Database.php';

            use App\Config\Config;
            use App\Config\Database;

            Config::load();

            echo "<p class='success'>✅ Classes carregadas com sucesso</p>";

            $db = Database::getConnection();
            echo "<p class='success'>✅ Conexão com banco estabelecida!</p>";

            // Testar query
            $stmt = $db->query("SELECT COUNT(*) as total FROM licitacoes");
            $result = $stmt->fetch();

            echo "<p class='success'>✅ Query executada com sucesso!</p>";
            echo "<p>Total de licitações no banco: <strong>" . $result['total'] . "</strong></p>";

            // Testar duplicatas
            $sqlDup = "SELECT COUNT(*) as total_duplicatas FROM (
                SELECT pncp_id, COUNT(*) as qtd
                FROM licitacoes
                GROUP BY pncp_id
                HAVING COUNT(*) > 1
            ) as duplicatas";

            $stmtDup = $db->query($sqlDup);
            $resultDup = $stmtDup->fetch();

            if ($resultDup['total_duplicatas'] > 0) {
                echo "<p class='error'>⚠️ Encontradas " . $resultDup['total_duplicatas'] . " duplicatas</p>";
            } else {
                echo "<p class='success'>✅ Nenhuma duplicata encontrada</p>";
            }

        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }

        echo "<h2>5. Variáveis de Ambiente</h2>";
        echo "<pre>";
        echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'não definido') . "\n";
        echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? 'não definido') . "\n";
        echo "DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'não definido') . "\n";
        echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '***' : 'não definido') . "\n";
        echo "</pre>";

        echo "<hr>";
        echo "<p><a href='admin_duplicatas.php'>← Voltar para Admin Duplicatas</a></p>";
        ?>
    </div>
</body>
</html>
