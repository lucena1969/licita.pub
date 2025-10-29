<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Simples 2</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; max-width: 900px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: white; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h2 { background: #667eea; color: white; padding: 10px; border-radius: 5px; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <h1>🔍 Teste Simples 2 - Verificação de Arquivos</h1>

    <div class="box">
        <h2>1. PHP Funciona?</h2>
        <p class="success">✅ SIM! Versão: <?php echo phpversion(); ?></p>
        <p>Servidor: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?></p>
    </div>

    <div class="box">
        <h2>2. Onde estamos?</h2>
        <pre><?php
echo "Arquivo atual:\n";
echo "  " . __FILE__ . "\n\n";

echo "Diretório atual (__DIR__):\n";
echo "  " . __DIR__ . "\n\n";

echo "Diretório pai (dirname(__DIR__)):\n";
echo "  " . dirname(__DIR__) . "\n";
        ?></pre>
    </div>

    <div class="box">
        <h2>3. Estrutura de Pastas</h2>
        <pre><?php
$backend = dirname(__DIR__);
echo "📁 Backend: $backend\n\n";

echo "Conteúdo do backend/:\n";
$files = @scandir($backend);
if ($files) {
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $path = "$backend/$f";
            $type = is_dir($path) ? '[DIR] ' : '[FILE]';
            echo "  $type $f\n";
        }
    }
} else {
    echo "  ❌ Não foi possível ler o diretório\n";
}
        ?></pre>
    </div>

    <div class="box">
        <h2>4. Arquivos Críticos</h2>
        <?php
        $backend = dirname(__DIR__);
        $arquivos = [
            '.env' => $backend . '/.env',
            'Config.php' => $backend . '/src/Config/Config.php',
            'Database.php' => $backend . '/src/Config/Database.php',
            'LicitacaoRepository.php' => $backend . '/src/Repositories/LicitacaoRepository.php',
            'PNCPService.php' => $backend . '/src/Services/PNCPService.php',
        ];

        $todosExistem = true;
        foreach ($arquivos as $nome => $caminho) {
            $existe = file_exists($caminho);
            $class = $existe ? 'success' : 'error';
            $icon = $existe ? '✅' : '❌';

            echo "<p class='$class'>$icon <strong>$nome</strong></p>";
            echo "<p style='margin-left: 30px; color: #666; font-size: 13px;'>$caminho</p>";

            if ($existe) {
                echo "<p style='margin-left: 30px; color: #999; font-size: 12px;'>Tamanho: " . filesize($caminho) . " bytes</p>";
            }

            if (!$existe) {
                $todosExistem = false;
            }
        }
        ?>
    </div>

    <div class="box">
        <h2>5. Variáveis de Ambiente (.env)</h2>
        <?php
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            echo "<p class='success'>✅ Arquivo .env encontrado</p>";

            // Ler .env e mostrar (sem valores sensíveis)
            $envContent = file_get_contents($envPath);
            $lines = explode("\n", $envContent);

            echo "<pre>";
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') {
                    continue;
                }

                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    if (stripos($key, 'PASSWORD') !== false || stripos($key, 'SECRET') !== false) {
                        echo "$key=****** (oculto)\n";
                    } else {
                        echo "$key=$value\n";
                    }
                }
            }
            echo "</pre>";
        } else {
            echo "<p class='error'>❌ Arquivo .env NÃO encontrado</p>";
        }
        ?>
    </div>

    <?php if ($todosExistem): ?>
    <div class="box" style="background: #d4edda; border-left: 5px solid #28a745;">
        <h2 style="background: #28a745;">✅ Todos os Arquivos Encontrados!</h2>
        <p><strong>Agora você pode testar a conexão com banco.</strong></p>
        <p>Próximo passo: <a href="testar_conexao.php">Testar Conexão com Banco</a></p>

        <hr style="margin: 20px 0;">

        <h3>📋 Scripts Disponíveis:</h3>
        <ul>
            <li><a href="verificar_duplicatas_web.php">🔍 Verificar Duplicatas</a></li>
            <li><a href="limpar_duplicatas_web.php">🗑️ Limpar Duplicatas</a> (senha: licita2025)</li>
            <li><a href="admin_duplicatas.php">🏠 Menu Admin</a></li>
        </ul>
    </div>
    <?php else: ?>
    <div class="box" style="background: #f8d7da; border-left: 5px solid #dc3545;">
        <h2 style="background: #dc3545;">❌ Alguns Arquivos Estão Faltando</h2>
        <p><strong>Ação necessária:</strong> Fazer upload completo da pasta backend/</p>
        <p>Certifique-se de incluir:</p>
        <ul>
            <li>backend/.env</li>
            <li>backend/src/Config/Config.php</li>
            <li>backend/src/Config/Database.php</li>
            <li>Todas as pastas: src/, public/, cron/, database/</li>
        </ul>
    </div>
    <?php endif; ?>

    <div class="box" style="background: #e7f3ff;">
        <h2 style="background: #667eea;">📝 Informações</h2>
        <p><strong>Este teste NÃO tenta conectar ao banco</strong> - apenas verifica se os arquivos existem.</p>
        <p>Para testar conexão, use o link acima após confirmar que todos os arquivos estão presentes.</p>
    </div>

</body>
</html>
