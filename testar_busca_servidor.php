<?php
/**
 * SCRIPT DE TESTE - BUSCA DE LICITAÇÕES
 *
 * Execute este script no servidor para testar a busca:
 * php testar_busca_servidor.php
 *
 * Ou acesse via navegador:
 * https://licita.pub/testar_busca_servidor.php
 */

// Configuração
$host = 'localhost';
$dbname = 'u590097272_licitapub';
$username = 'u590097272_neto';
$password = ''; // Preencha com a senha

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Busca - Licita.pub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #1351b4; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #1351b4; color: white; }
        .query-box { background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid #1351b4; }
        .time { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste de Busca - Licita.pub</h1>

        <?php
        try {
            // Conectar ao banco
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo '<div class="test-section">';
            echo '<h2>✅ Conexão com Banco de Dados</h2>';
            echo '<p class="success">Conectado com sucesso!</p>';
            echo '</div>';

            // Teste 1: Verificar estrutura da tabela
            echo '<div class="test-section">';
            echo '<h2>📊 Teste 1: Estrutura da Tabela</h2>';

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM licitacoes");
            $total = $stmt->fetch()['total'];

            echo "<p><strong>Total de licitações:</strong> {$total}</p>";

            if ($total == 0) {
                echo '<p class="warning">⚠️ Tabela vazia! Execute a sincronização PNCP primeiro.</p>';
            }
            echo '</div>';

            // Teste 2: Verificar índices FULLTEXT
            echo '<div class="test-section">';
            echo '<h2>🔧 Teste 2: Índices FULLTEXT</h2>';

            $stmt = $pdo->query("
                SELECT INDEX_NAME, COLUMN_NAME, INDEX_TYPE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = '$dbname'
                  AND TABLE_NAME = 'licitacoes'
                  AND INDEX_TYPE = 'FULLTEXT'
            ");

            $indices = $stmt->fetchAll();

            if (count($indices) > 0) {
                echo '<p class="success">✅ Índices FULLTEXT encontrados:</p>';
                echo '<table>';
                echo '<tr><th>Nome do Índice</th><th>Coluna</th><th>Tipo</th></tr>';
                foreach ($indices as $idx) {
                    echo "<tr><td>{$idx['INDEX_NAME']}</td><td>{$idx['COLUMN_NAME']}</td><td>{$idx['INDEX_TYPE']}</td></tr>";
                }
                echo '</table>';
            } else {
                echo '<p class="error">❌ Nenhum índice FULLTEXT encontrado!</p>';
                echo '<p>Execute o script <code>corrigir_busca.sql</code> para criar os índices.</p>';
            }
            echo '</div>';

            // Teste 3: Comparar LIKE vs FULLTEXT
            if ($total > 0) {
                echo '<div class="test-section">';
                echo '<h2>⚡ Teste 3: Comparação de Performance</h2>';

                $termo = 'serviço'; // Termo de teste

                // Teste com LIKE
                $inicio = microtime(true);
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total
                    FROM licitacoes
                    WHERE LOWER(objeto) LIKE LOWER(:termo)
                ");
                $stmt->execute([':termo' => "%$termo%"]);
                $resultadoLike = $stmt->fetch()['total'];
                $tempoLike = (microtime(true) - $inicio) * 1000; // em ms

                echo '<div class="query-box">';
                echo '<strong>Busca com LIKE:</strong><br>';
                echo "<code>WHERE LOWER(objeto) LIKE '%{$termo}%'</code><br>";
                echo "<span class='time'>Tempo: " . number_format($tempoLike, 2) . " ms</span><br>";
                echo "Resultados: {$resultadoLike}";
                echo '</div>';

                // Teste com FULLTEXT (se índices existirem)
                if (count($indices) > 0) {
                    $inicio = microtime(true);
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total
                        FROM licitacoes
                        WHERE MATCH(objeto) AGAINST(:termo IN BOOLEAN MODE)
                    ");
                    $stmt->execute([':termo' => $termo]);
                    $resultadoFulltext = $stmt->fetch()['total'];
                    $tempoFulltext = (microtime(true) - $inicio) * 1000; // em ms

                    echo '<div class="query-box">';
                    echo '<strong>Busca com FULLTEXT:</strong><br>';
                    echo "<code>WHERE MATCH(objeto) AGAINST('{$termo}' IN BOOLEAN MODE)</code><br>";
                    echo "<span class='time'>Tempo: " . number_format($tempoFulltext, 2) . " ms</span><br>";
                    echo "Resultados: {$resultadoFulltext}";
                    echo '</div>';

                    // Comparação
                    $ganho = $tempoLike > 0 ? ($tempoLike / $tempoFulltext) : 0;

                    echo '<p><strong>Ganho de Performance:</strong> ';
                    if ($ganho > 1) {
                        echo "<span class='success'>" . number_format($ganho, 1) . "x mais rápido!</span>";
                    } else {
                        echo "<span class='warning'>Sem ganho significativo (tabela pequena ou cache)</span>";
                    }
                    echo '</p>';
                }

                echo '</div>';

                // Teste 4: Busca real com diferentes termos
                echo '<div class="test-section">';
                echo '<h2>🔎 Teste 4: Busca Real com Diferentes Termos</h2>';

                $termosTeste = ['computador', 'serviço', 'material', 'pregão', 'licitação'];

                echo '<table>';
                echo '<tr><th>Termo</th><th>Resultados (LIKE)</th><th>Resultados (FULLTEXT)</th><th>Tempo LIKE</th><th>Tempo FULLTEXT</th></tr>';

                foreach ($termosTeste as $termo) {
                    // LIKE
                    $inicio = microtime(true);
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM licitacoes WHERE LOWER(objeto) LIKE LOWER(:termo)");
                    $stmt->execute([':termo' => "%$termo%"]);
                    $likeCount = $stmt->fetch()['total'];
                    $likeTime = (microtime(true) - $inicio) * 1000;

                    // FULLTEXT
                    $fulltextCount = 0;
                    $fulltextTime = 0;
                    if (count($indices) > 0) {
                        $inicio = microtime(true);
                        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM licitacoes WHERE MATCH(objeto) AGAINST(:termo IN BOOLEAN MODE)");
                        $stmt->execute([':termo' => $termo]);
                        $fulltextCount = $stmt->fetch()['total'];
                        $fulltextTime = (microtime(true) - $inicio) * 1000;
                    }

                    echo "<tr>";
                    echo "<td><strong>{$termo}</strong></td>";
                    echo "<td>{$likeCount}</td>";
                    echo "<td>{$fulltextCount}</td>";
                    echo "<td>" . number_format($likeTime, 2) . " ms</td>";
                    echo "<td>" . number_format($fulltextTime, 2) . " ms</td>";
                    echo "</tr>";
                }

                echo '</table>';
                echo '</div>';

                // Teste 5: Exemplos de resultados
                echo '<div class="test-section">';
                echo '<h2>📄 Teste 5: Exemplos de Resultados</h2>';

                if (count($indices) > 0) {
                    $stmt = $pdo->prepare("
                        SELECT pncp_id, numero, LEFT(objeto, 150) as objeto_preview, uf, municipio
                        FROM licitacoes
                        WHERE MATCH(objeto) AGAINST('computador serviço' IN BOOLEAN MODE)
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $resultados = $stmt->fetchAll();

                    if (count($resultados) > 0) {
                        echo '<p>Buscando por: <code>computador serviço</code></p>';
                        echo '<table>';
                        echo '<tr><th>PNCP ID</th><th>Número</th><th>Objeto (preview)</th><th>UF</th><th>Município</th></tr>';
                        foreach ($resultados as $row) {
                            echo "<tr>";
                            echo "<td>{$row['pncp_id']}</td>";
                            echo "<td>{$row['numero']}</td>";
                            echo "<td>{$row['objeto_preview']}...</td>";
                            echo "<td>{$row['uf']}</td>";
                            echo "<td>{$row['municipio']}</td>";
                            echo "</tr>";
                        }
                        echo '</table>';
                    } else {
                        echo '<p class="warning">Nenhum resultado encontrado para este termo.</p>';
                    }
                } else {
                    echo '<p class="warning">⚠️ Índices FULLTEXT não encontrados. Execute <code>corrigir_busca.sql</code> primeiro.</p>';
                }

                echo '</div>';
            }

            // Resumo final
            echo '<div class="test-section">';
            echo '<h2>📋 Resumo dos Testes</h2>';

            echo '<ul>';
            echo '<li>✅ <strong>Conexão:</strong> OK</li>';
            echo "<li>" . ($total > 0 ? '✅' : '⚠️') . " <strong>Dados:</strong> {$total} licitações</li>";
            echo '<li>' . (count($indices) > 0 ? '✅' : '❌') . " <strong>Índices FULLTEXT:</strong> " . count($indices) . " encontrados</li>";
            echo '</ul>';

            if (count($indices) == 0) {
                echo '<h3 class="error">⚠️ AÇÃO NECESSÁRIA</h3>';
                echo '<p>Execute o script SQL de correção:</p>';
                echo '<pre>mysql -u u590097272_neto -p u590097272_licitapub < corrigir_busca.sql</pre>';
            } else {
                echo '<h3 class="success">✅ TUDO OK!</h3>';
                echo '<p>Os índices FULLTEXT estão configurados corretamente. Agora atualize o Controller:</p>';
                echo '<ol>';
                echo '<li>Fazer backup: <code>LicitacaoController.php.backup</code></li>';
                echo '<li>Substituir por: <code>LicitacaoController_FIXED.php</code></li>';
                echo '<li>Testar a API</li>';
                echo '</ol>';
            }

            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="test-section">';
            echo '<h2 class="error">❌ Erro de Conexão</h2>';
            echo '<p>Não foi possível conectar ao banco de dados.</p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '<p><strong>Verifique:</strong></p>';
            echo '<ul>';
            echo '<li>Credenciais do banco de dados</li>';
            echo '<li>Hostname (localhost ou IP)</li>';
            echo '<li>Nome do banco de dados</li>';
            echo '<li>Permissões do usuário</li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <div class="test-section">
            <h2>📚 Próximos Passos</h2>
            <ol>
                <li><strong>Se índices não existirem:</strong> Execute <code>corrigir_busca.sql</code></li>
                <li><strong>Atualizar Controller:</strong> Substitua por <code>LicitacaoController_FIXED.php</code></li>
                <li><strong>Testar API:</strong> Execute <code>testar_busca_completo.sh</code></li>
                <li><strong>Testar no Frontend:</strong> Acesse a página de consultas e faça buscas</li>
            </ol>
        </div>

        <div class="test-section">
            <p style="text-align: center; color: #666; font-size: 0.9em;">
                Licita.pub - Sistema de Licitações Públicas<br>
                Teste de Busca v1.0.0
            </p>
        </div>
    </div>
</body>
</html>
