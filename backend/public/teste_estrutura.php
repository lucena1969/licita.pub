<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste de Estrutura</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: white; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Descobrindo Estrutura Real do Servidor</h1>

    <?php
    echo "<h2>1. Onde estamos agora?</h2><pre>";
    echo "__FILE__: " . __FILE__ . "\n";
    echo "__DIR__: " . __DIR__ . "\n";
    echo "getcwd(): " . getcwd() . "\n";
    echo "</pre>";

    echo "<h2>2. Explorando estrutura</h2><pre>";

    // Começar do diretório atual e subir
    $current = __DIR__;
    echo "📁 Diretório atual: $current\n\n";

    // Listar conteúdo do diretório atual
    echo "Conteúdo de " . basename($current) . "/:\n";
    $files = scandir($current);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $type = is_dir("$current/$f") ? '[DIR] ' : '[FILE]';
            echo "  $type $f\n";
        }
    }

    // Subir 1 nível
    $parent = dirname($current);
    echo "\n📁 Diretório pai: $parent\n\n";
    echo "Conteúdo de " . basename($parent) . "/:\n";
    $files = scandir($parent);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $type = is_dir("$parent/$f") ? '[DIR] ' : '[FILE]';
            echo "  $type $f\n";
        }
    }

    // Subir 2 níveis
    $grandparent = dirname($parent);
    echo "\n📁 Diretório avô: $grandparent\n\n";
    echo "Conteúdo de " . basename($grandparent) . "/:\n";
    $files = scandir($grandparent);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $type = is_dir("$grandparent/$f") ? '[DIR] ' : '[FILE]';
            echo "  $type $f\n";
        }
    }

    echo "</pre>";

    echo "<h2>3. Procurando arquivos importantes</h2><pre>";

    // Possíveis localizações
    $possiveisCaminhos = [
        // Se backend está no mesmo nível de public_html
        dirname($current) . '/backend',

        // Se backend está dentro de public_html
        $current . '/backend',

        // Se os arquivos estão soltos em public_html
        $current,

        // Se estamos dentro de backend/public
        dirname($current),

        // Raiz do projeto
        dirname(dirname($current)),
    ];

    foreach ($possiveisCaminhos as $idx => $path) {
        echo "\n🔍 Testando caminho " . ($idx + 1) . ": $path\n";

        if (is_dir($path)) {
            echo "   [✅] Diretório existe\n";

            $testar = [
                'src/Config/Config.php',
                'src/Config/Database.php',
                '.env',
                'backend/src/Config/Config.php',
                'backend/.env',
            ];

            foreach ($testar as $file) {
                $fullPath = $path . '/' . $file;
                if (file_exists($fullPath)) {
                    echo "   [✅] Encontrado: $file\n";
                    echo "        → $fullPath\n";
                }
            }
        } else {
            echo "   [❌] Diretório não existe\n";
        }
    }

    echo "</pre>";

    echo "<h2>4. Recomendação</h2><pre>";

    // Verificar onde os arquivos estão
    $backendDentroPublicHtml = is_dir($current . '/backend');
    $backendForaPublicHtml = is_dir(dirname($current) . '/backend');
    $arquivosSoltos = file_exists($current . '/src/Config/Config.php');

    if ($backendDentroPublicHtml) {
        echo "✅ ENCONTRADO: pasta 'backend' DENTRO de public_html\n";
        echo "Estrutura: public_html/backend/src/Config/...\n\n";
        echo "Use este caminho nos scripts:\n";
        echo "  \$basePath = __DIR__ . '/backend';\n";
    } elseif ($backendForaPublicHtml) {
        echo "✅ ENCONTRADO: pasta 'backend' FORA de public_html\n";
        echo "Estrutura: licita.pub/backend/src/Config/...\n\n";
        echo "Use este caminho nos scripts:\n";
        echo "  \$basePath = dirname(__DIR__) . '/backend';\n";
    } elseif ($arquivosSoltos) {
        echo "✅ ENCONTRADO: arquivos soltos em public_html\n";
        echo "Estrutura: public_html/src/Config/...\n\n";
        echo "Use este caminho nos scripts:\n";
        echo "  \$basePath = __DIR__;\n";
    } else {
        echo "❌ NÃO ENCONTRADO: pasta backend ou arquivos\n\n";
        echo "AÇÃO NECESSÁRIA: Fazer upload da pasta 'backend' para:\n";
        echo "  OPÇÃO 1: $current/backend/ (dentro de public_html)\n";
        echo "  OPÇÃO 2: " . dirname($current) . "/backend/ (ao lado de public_html)\n";
    }

    echo "</pre>";
    ?>

    <hr>
    <p><a href="teste_simples.php">← Voltar</a></p>
</body>
</html>
