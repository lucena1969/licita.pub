<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Final - Licita.pub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        h1 { color: #667eea; margin-top: 0; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-top: 0; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .alert-success { background: #d4edda; border-color: #28a745; }
        .alert-error { background: #f8d7da; border-color: #dc3545; }
        .alert-warning { background: #fff3cd; border-color: #ffc107; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔧 Diagnóstico Final do Sistema</h1>
        <p><strong>Este arquivo está em:</strong> backend/public/diagnostico_final.php</p>
        <p><strong>Estrutura detectada pelo teste_estrutura.php:</strong></p>
        <pre>public_html/backend/ ✅ EXISTE
public_html/backend/src/Config/Config.php ✅ EXISTE
public_html/backend/.env ✅ EXISTE</pre>
    </div>

    <?php
    // ========================================
    // CONFIGURAÇÃO DO CAMINHO CORRETO
    // ========================================
    // Estamos em: public_html/backend/public/diagnostico_final.php
    // Precisamos: public_html/backend/
    // Solução: dirname(__DIR__) = subir 1 nível de /public/

    $backendPath = dirname(__DIR__); // De backend/public para backend/
    ?>

    <div class="box">
        <h2>1. 📍 Localização e Caminhos</h2>
        <pre><?php
echo "Arquivo atual (__FILE__):\n";
echo "  → " . __FILE__ . "\n\n";

echo "Diretório atual (__DIR__):\n";
echo "  → " . __DIR__ . "\n\n";

echo "Diretório PAI (dirname(__DIR__)):\n";
echo "  → " . $backendPath . "\n\n";

echo "✅ USANDO ESTE COMO BASE: $backendPath\n";
        ?></pre>
    </div>

    <div class="box">
        <h2>2. 🔍 Verificando Arquivos do Projeto</h2>
        <?php
        $arquivos = [
            'src/Config/Config.php',
            'src/Config/Database.php',
            '.env',
            'src/Models/Licitacao.php',
            'src/Repositories/LicitacaoRepository.php',
            'src/Services/PNCPService.php',
        ];

        $todosExistem = true;
        foreach ($arquivos as $arq) {
            $caminho = $backendPath . '/' . $arq;
            $existe = file_exists($caminho);

            if ($existe) {
                echo "<p class='success'>✅ $arq</p>";
            } else {
                echo "<p class='error'>❌ $arq</p>";
                echo "<p style='margin-left:25px;color:#999;font-size:12px;'>Procurado em: $caminho</p>";
                $todosExistem = false;
            }
        }

        if (!$todosExistem) {
            echo "<div class='alert alert-error'>";
            echo "<p class='error'>❌ Alguns arquivos não foram encontrados!</p>";
            echo "<p>Certifique-se de fazer upload de toda a pasta backend/</p>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="box">
        <h2>3. 🔌 Teste de Conexão com Banco</h2>
        <?php
        if (!$todosExistem) {
            echo "<div class='alert alert-warning'>";
            echo "<p class='warning'>⚠️ Pulando teste de conexão pois faltam arquivos</p>";
            echo "</div>";
        } else {
            try {
                // Definir BASE_PATH
                if (!defined('BASE_PATH')) {
                    define('BASE_PATH', $backendPath);
                }

                // Carregar classes
                require_once $backendPath . '/src/Config/Config.php';
                require_once $backendPath . '/src/Config/Database.php';

                use App\Config\Config;
                use App\Config\Database;

                Config::load();
                echo "<p class='success'>✅ Config carregado (.env lido)</p>";

                $db = Database::getConnection();
                echo "<p class='success'>✅ Conexão com banco estabelecida</p>";

                $stmt = $db->query("SELECT COUNT(*) as total FROM licitacoes");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                echo "<div class='alert alert-success'>";
                echo "<p style='font-size:18px;margin:0;'>📊 <strong>Total de licitações:</strong> " . number_format($result['total'], 0, ',', '.') . "</p>";
                echo "</div>";

                // Verificar duplicatas
                $sqlDup = "SELECT COUNT(DISTINCT pncp_id) as unicos, COUNT(*) as total FROM licitacoes";
                $stmtDup = $db->query($sqlDup);
                $resDup = $stmtDup->fetch(PDO::FETCH_ASSOC);

                $duplicatas = $resDup['total'] - $resDup['unicos'];

                echo "<p><strong>Análise de Duplicatas:</strong></p>";
                echo "<pre>";
                echo "Total de registros:  " . number_format($resDup['total'], 0, ',', '.') . "\n";
                echo "PNCP IDs únicos:     " . number_format($resDup['unicos'], 0, ',', '.') . "\n";
                echo "Duplicatas:          " . number_format($duplicatas, 0, ',', '.') . "\n";
                echo "</pre>";

                if ($duplicatas > 0) {
                    echo "<div class='alert alert-warning'>";
                    echo "<p class='warning'>⚠️ Há $duplicatas registros duplicados</p>";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-success'>";
                    echo "<p class='success'>✅ Nenhuma duplicata - Banco limpo!</p>";
                    echo "</div>";
                }

            } catch (Exception $e) {
                echo "<div class='alert alert-error'>";
                echo "<p class='error'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<details><summary>Ver stack trace</summary>";
                echo "<pre style='font-size:11px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</details>";
                echo "</div>";
            }
        }
        ?>
    </div>

    <div class="box">
        <h2>4. ⚙️ Configuração Recomendada para os Scripts</h2>
        <p>Use este código em todos os scripts PHP (verificar_duplicatas_web.php, limpar_duplicatas_web.php, etc):</p>
        <pre><code>// No início do arquivo PHP, use:
define('BASE_PATH', dirname(__DIR__));  // Se estiver em backend/public/

// OU

define('BASE_PATH', __DIR__ . '/backend');  // Se estiver na raiz de public_html/

// Depois:
require_once BASE_PATH . '/src/Config/Config.php';
require_once BASE_PATH . '/src/Config/Database.php';</code></pre>

        <?php if (isset($todosExistem) && $todosExistem && isset($db)): ?>
        <div class="alert alert-success">
            <p class='success'>✅ TUDO FUNCIONANDO!</p>
            <p><strong>Caminho correto identificado:</strong> <code>dirname(__DIR__)</code></p>
            <p style="margin-top:15px;"><strong>Agora você pode:</strong></p>
            <ol>
                <li>Corrigir os scripts com o caminho: <code>dirname(__DIR__)</code></li>
                <li>Fazer upload dos scripts corrigidos</li>
                <li>Usar: <a href="verificar_duplicatas_web.php">verificar_duplicatas_web.php</a></li>
                <li>Usar: <a href="limpar_duplicatas_web.php">limpar_duplicatas_web.php</a></li>
            </ol>
        </div>
        <?php endif; ?>
    </div>

    <div class="box" style="background:#e7f3ff;">
        <h2>📋 Resumo do Problema do .htaccess</h2>
        <p>Você tem 2 arquivos .htaccess:</p>
        <ul>
            <li><strong>Raiz (.htaccess):</strong> Redireciona tudo para backend/public/</li>
            <li><strong>backend/public/.htaccess:</strong> Configurações adicionais</li>
        </ul>
        <p><strong>Por isso:</strong></p>
        <ul>
            <li>✅ Arquivos em <code>backend/public/</code> funcionam</li>
            <li>❌ Arquivos na raiz de public_html NÃO funcionam (são redirecionados)</li>
        </ul>
        <p class="success"><strong>Solução:</strong> Sempre colocar arquivos PHP em <code>backend/public/</code></p>
    </div>

</body>
</html>
