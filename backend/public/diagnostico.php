<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Final - Licita.pub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        h1 { color: #667eea; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🎯 Teste Final de Configuração</h1>
        <p><strong>Arquivo:</strong> Na raiz do public_html</p>
        <p><strong>Caminho backend:</strong> <code>__DIR__ . '/backend'</code></p>
    </div>

    <div class="box">
        <?php
        echo "<h2>1. ✅ Teste de PHP</h2>";
        echo "<p class='success'>PHP funcionando! Versão: " . phpversion() . "</p>";
        echo "<p>Sistema: " . PHP_OS . "</p>";
        echo "<p>Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "</p>";
        ?>
    </div>

    <div class="box">
        <?php
        echo "<h2>2. 📁 Descobrindo Caminhos</h2>";
        echo "<pre>";
        echo "Diretório deste arquivo (__DIR__):\n";
        echo "  → " . __DIR__ . "\n\n";

        // Testar todas as possibilidades
        $possibilidades = [
            'backend' => __DIR__ . '/backend',
            'backend/backend' => __DIR__ . '/backend/backend',
            './backend' => getcwd() . '/backend',
        ];

        $backendPath = null;
        foreach ($possibilidades as $nome => $caminho) {
            $existe = is_dir($caminho);
            echo "Testando '$nome': $caminho\n";
            echo "  Status: " . ($existe ? '✅ EXISTE' : '❌ não existe') . "\n";

            if ($existe && is_null($backendPath)) {
                $backendPath = $caminho;
                echo "  → ESTE SERÁ USADO!\n";
            }
            echo "\n";
        }

        if ($backendPath) {
            echo "✅ Backend encontrado em: $backendPath\n";
        } else {
            echo "❌ Backend NÃO encontrado!\n";
        }
        echo "</pre>";
        ?>
    </div>

    <div class="box">
        <?php
        echo "<h2>3. 🔍 Verificando Arquivos Críticos</h2>";

        if (!isset($backendPath) || !$backendPath) {
            echo "<p class='error'>❌ Caminho do backend não foi encontrado. Revise a seção anterior.</p>";
        } else {
            $arquivosCriticos = [
                'src/Config/Config.php' => 'Carrega variáveis .env',
                'src/Config/Database.php' => 'Conexão com MySQL',
                '.env' => 'Credenciais do banco',
                'src/Repositories/LicitacaoRepository.php' => 'Repository com UPSERT',
                'src/Services/PNCPService.php' => 'Service que usa UPSERT',
            ];

            $todosExistem = true;
            foreach ($arquivosCriticos as $arquivo => $descricao) {
                $caminhoCompleto = $backendPath . '/' . $arquivo;
                $existe = file_exists($caminhoCompleto);

                if ($existe) {
                    echo "<p class='success'>✅ " . $arquivo . "</p>";
                    echo "<p style='margin-left: 25px; color: #666; font-size: 13px;'>→ " . $descricao . "</p>";
                } else {
                    echo "<p class='error'>❌ " . $arquivo . "</p>";
                    echo "<p style='margin-left: 25px; color: #666; font-size: 13px;'>→ " . $descricao . " (procurado em: $caminhoCompleto)</p>";
                    $todosExistem = false;
                }
            }

            if (!$todosExistem) {
                echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;'>";
                echo "<p class='warning'>⚠️ ATENÇÃO: Alguns arquivos estão faltando!</p>";
                echo "<p>Certifique-se de fazer upload de toda a pasta backend/</p>";
                echo "</div>";
            }
        }
        ?>
    </div>

    <div class="box">
        <?php
        echo "<h2>4. 🔌 Teste de Conexão com Banco de Dados</h2>";

        if (!isset($backendPath) || !$backendPath) {
            echo "<p class='error'>❌ Não é possível testar conexão sem o caminho do backend.</p>";
        } else {
            try {
                // Verificar se arquivos existem antes de tentar carregar
                $configFile = $backendPath . '/src/Config/Config.php';
                $dbFile = $backendPath . '/src/Config/Database.php';

                if (!file_exists($configFile)) {
                    throw new Exception("Arquivo Config.php não encontrado em: $configFile");
                }

                if (!file_exists($dbFile)) {
                    throw new Exception("Arquivo Database.php não encontrado em: $dbFile");
                }

                // Definir constante BASE_PATH
                if (!defined('BASE_PATH')) {
                    define('BASE_PATH', $backendPath);
                }

                // Carregar classes
                require_once $configFile;
                require_once $dbFile;

                echo "<p class='success'>✅ Classes carregadas com sucesso</p>";

                // Usar as classes
                use App\Config\Config;
                use App\Config\Database;

                // Carregar .env
                Config::load();
                echo "<p class='success'>✅ Arquivo .env carregado</p>";

                // Conectar ao banco
                $db = Database::getConnection();
                echo "<p class='success'>✅ Conexão com banco estabelecida!</p>";

                // Testar query simples
                $stmt = $db->query("SELECT COUNT(*) as total FROM licitacoes");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                echo "<p class='success'>✅ Query executada com sucesso!</p>";
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
                echo "<p style='font-size: 18px; margin: 0;'>📊 Total de licitações no banco: <strong>" . number_format($result['total'], 0, ',', '.') . "</strong></p>";
                echo "</div>";

                // Testar duplicatas
                echo "<h3 style='margin-top: 30px;'>🔍 Verificando Duplicatas</h3>";

                $sqlDup = "SELECT
                    COUNT(DISTINCT pncp_id) as pncp_unicos,
                    COUNT(*) as total_registros,
                    (COUNT(*) - COUNT(DISTINCT pncp_id)) as duplicatas
                    FROM licitacoes";

                $stmtDup = $db->query($sqlDup);
                $resultDup = $stmtDup->fetch(PDO::FETCH_ASSOC);

                echo "<pre>";
                echo "Total de registros:     " . number_format($resultDup['total_registros'], 0, ',', '.') . "\n";
                echo "PNCP IDs únicos:        " . number_format($resultDup['pncp_unicos'], 0, ',', '.') . "\n";
                echo "Duplicatas encontradas: " . number_format($resultDup['duplicatas'], 0, ',', '.') . "\n";
                echo "</pre>";

                if ($resultDup['duplicatas'] > 0) {
                    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 10px;'>";
                    echo "<p class='warning'>⚠️ Existem " . $resultDup['duplicatas'] . " registros duplicados</p>";
                    echo "<p style='margin: 10px 0 0 0;'><a href='verificar_duplicatas_web.php' style='color: #667eea; font-weight: bold;'>→ Ver Detalhes das Duplicatas</a></p>";
                    echo "</div>";
                } else {
                    echo "<p class='success'>✅ Nenhuma duplicata encontrada - Banco está limpo!</p>";
                }

            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545; border-radius: 5px;'>";
                echo "<p class='error'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<details style='margin-top: 15px;'>";
                echo "<summary style='cursor: pointer; font-weight: bold;'>Ver detalhes técnicos</summary>";
                echo "<pre style='margin-top: 10px; font-size: 11px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</details>";
                echo "</div>";
            }
        }
        ?>
    </div>

    <div class="box">
        <?php
        if (isset($_ENV)) {
            echo "<h2>5. 🔐 Variáveis de Ambiente</h2>";
            echo "<pre>";
            echo "DB_HOST:     " . ($_ENV['DB_HOST'] ?? '❌ não definido') . "\n";
            echo "DB_PORT:     " . ($_ENV['DB_PORT'] ?? '❌ não definido') . "\n";
            echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? '❌ não definido') . "\n";
            echo "DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? '❌ não definido') . "\n";
            echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) && !empty($_ENV['DB_PASSWORD']) ? '✅ ****** (definido)' : '❌ não definido') . "\n";
            echo "</pre>";
        }
        ?>
    </div>

    <div class="box" style="background: #e7f3ff; border-left: 4px solid #667eea;">
        <h2>📝 Próximos Passos</h2>

        <?php if (isset($todosExistem) && $todosExistem && isset($db)): ?>
            <p class='success'>✅ Tudo está funcionando corretamente!</p>
            <p style="margin-top: 20px;"><strong>Agora corrija os outros scripts com o caminho certo:</strong></p>
            <pre style="margin: 15px 0;">
// Em verificar_duplicatas_web.php, limpar_duplicatas_web.php, etc:
define('BASE_PATH', __DIR__ . '/backend');  // ou o caminho correto descoberto acima
            </pre>
        <?php else: ?>
            <p class='error'>❌ Alguns problemas foram encontrados</p>
            <p>Revise os erros acima e me envie:</p>
            <ul>
                <li>O resultado da seção 2 (caminhos)</li>
                <li>O resultado da seção 3 (arquivos)</li>
                <li>Qualquer mensagem de erro da seção 4</li>
            </ul>
        <?php endif; ?>
    </div>

</body>
</html>
