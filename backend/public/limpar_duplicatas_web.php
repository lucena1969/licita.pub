<?php
/**
 * Script WEB para limpar duplicatas de pncp_id
 * Acesse via: https://licita.pub/limpar_duplicatas_web.php
 *
 * ⚠️ ATENÇÃO: Este script DELETA dados permanentemente!
 * ⚠️ Proteja com senha em produção!
 */

// Proteção básica - ALTERE ESTA SENHA!
$SENHA_ADMIN = 'licita2025'; // ⚠️ MUDE ISSO!

session_start();

// Verificar autenticação
if (!isset($_SESSION['autenticado'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
        if ($_POST['senha'] === $SENHA_ADMIN) {
            $_SESSION['autenticado'] = true;
        } else {
            $erro_senha = true;
        }
    }

    if (!isset($_SESSION['autenticado'])) {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Autenticação - Licita.pub</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .login-box {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 400px;
                    width: 100%;
                }
                h1 { color: #667eea; margin-bottom: 10px; font-size: 24px; }
                p { color: #6c757d; margin-bottom: 20px; font-size: 14px; }
                input {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e9ecef;
                    border-radius: 6px;
                    font-size: 16px;
                    margin-bottom: 15px;
                }
                input:focus {
                    outline: none;
                    border-color: #667eea;
                }
                button {
                    width: 100%;
                    padding: 12px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                button:hover {
                    background: #5568d3;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }
                .erro {
                    background: #f8d7da;
                    color: #721c24;
                    padding: 12px;
                    border-radius: 6px;
                    margin-bottom: 15px;
                    border-left: 4px solid #dc3545;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>🔒 Área Restrita</h1>
                <p>Este script pode deletar dados. Digite a senha de administrador:</p>
                <?php if (isset($erro_senha)): ?>
                    <div class="erro">❌ Senha incorreta!</div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="senha" placeholder="Senha de administrador" required autofocus>
                    <button type="submit">Entrar</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Processar limpeza
$executado = false;
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']) && $_POST['confirmar'] === 'SIM') {
    // Definir caminho base (backend está dentro de public_html)
    define('BASE_PATH', __DIR__ . '/backend');

    require_once BASE_PATH . '/src/Config/Config.php';
    require_once BASE_PATH . '/src/Config/Database.php';

    use App\Config\Config;
    use App\Config\Database;

    Config::load();

    try {
        $db = Database::getConnection();
        $db->beginTransaction();

        // Identificar duplicatas
        $sql = "SELECT pncp_id, COUNT(*) as duplicatas,
                GROUP_CONCAT(id ORDER BY created_at ASC) as todos_ids,
                MAX(id) as id_manter
                FROM licitacoes
                GROUP BY pncp_id
                HAVING COUNT(*) > 1";

        $stmt = $db->query($sql);
        $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($duplicatas)) {
            $resultado = [
                'sucesso' => true,
                'mensagem' => 'Nenhuma duplicata encontrada.',
                'removidos' => 0
            ];
        } else {
            $idsParaRemover = [];
            foreach ($duplicatas as $dup) {
                $todosIds = explode(',', $dup['todos_ids']);
                $idManter = $dup['id_manter'];
                foreach ($todosIds as $id) {
                    if ($id !== $idManter) {
                        $idsParaRemover[] = $id;
                    }
                }
            }

            if (!empty($idsParaRemover)) {
                $placeholders = str_repeat('?,', count($idsParaRemover) - 1) . '?';
                $sqlDelete = "DELETE FROM licitacoes WHERE id IN ($placeholders)";
                $stmtDelete = $db->prepare($sqlDelete);
                $stmtDelete->execute($idsParaRemover);
                $removidos = $stmtDelete->rowCount();

                $db->commit();

                $resultado = [
                    'sucesso' => true,
                    'mensagem' => "Limpeza concluída com sucesso!",
                    'removidos' => $removidos
                ];
            }
        }

        $executado = true;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $resultado = [
            'sucesso' => false,
            'mensagem' => "Erro: " . $e->getMessage(),
            'removidos' => 0
        ];
        $executado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar Duplicatas - Licita.pub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .status-success { background: #d4edda; border-color: #28a745; color: #155724; }
        .status-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .status-error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .status-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }
        .btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .warning-box ul {
            list-style: none;
            padding-left: 20px;
        }
        .warning-box li {
            margin: 8px 0;
            color: #856404;
        }
        .warning-box li:before {
            content: "⚠️ ";
            margin-right: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        .checkbox-container {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .checkbox-container label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
        }
        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗑️ Limpar Duplicatas</h1>
            <p>⚠️ ATENÇÃO: Esta ação irá DELETAR dados permanentemente!</p>
        </div>

        <div class="content">
            <?php if ($executado && $resultado): ?>
                <?php if ($resultado['sucesso']): ?>
                    <div class="status-box status-success">
                        <h2 style="margin-bottom: 10px;">✅ <?php echo htmlspecialchars($resultado['mensagem']); ?></h2>
                        <p><strong>Registros removidos:</strong> <?php echo $resultado['removidos']; ?></p>
                    </div>
                    <div class="actions">
                        <a href="verificar_duplicatas_web.php" class="btn btn-secondary">Verificar Novamente</a>
                        <a href="?logout=1" class="btn btn-secondary" style="margin-left: 10px;">Sair</a>
                    </div>
                <?php else: ?>
                    <div class="status-box status-error">
                        <h2 style="margin-bottom: 10px;">❌ Erro na Limpeza</h2>
                        <p><?php echo htmlspecialchars($resultado['mensagem']); ?></p>
                    </div>
                    <div class="actions">
                        <a href="?" class="btn btn-secondary">Tentar Novamente</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php
                // Buscar duplicatas para exibir
                if (!defined('BASE_PATH')) {
                    define('BASE_PATH', __DIR__ . '/backend');
                }

                require_once BASE_PATH . '/src/Config/Config.php';
                require_once BASE_PATH . '/src/Config/Database.php';

                use App\Config\Config;
                use App\Config\Database;

                Config::load();

                try {
                    $db = Database::getConnection();
                    $sql = "SELECT pncp_id, COUNT(*) as duplicatas
                            FROM licitacoes
                            GROUP BY pncp_id
                            HAVING COUNT(*) > 1
                            ORDER BY duplicatas DESC";
                    $stmt = $db->query($sql);
                    $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicatas)): ?>
                        <div class="status-box status-success">
                            <h2 style="margin-bottom: 10px;">✅ Nenhuma Duplicata Encontrada!</h2>
                            <p>O banco de dados está limpo.</p>
                        </div>
                        <div class="actions">
                            <a href="verificar_duplicatas_web.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    <?php else:
                        $totalDuplicados = array_sum(array_column($duplicatas, 'duplicatas')) - count($duplicatas);
                    ?>
                        <div class="status-box status-warning">
                            <h2 style="margin-bottom: 10px;">⚠️ Duplicatas Encontradas</h2>
                            <p><strong><?php echo count($duplicatas); ?></strong> PNCP IDs com duplicatas</p>
                            <p><strong><?php echo $totalDuplicados; ?></strong> registros serão removidos</p>
                        </div>

                        <div class="warning-box">
                            <h3>⚠️ LEIA COM ATENÇÃO ANTES DE CONTINUAR:</h3>
                            <ul>
                                <li>Esta ação NÃO PODE SER DESFEITA!</li>
                                <li>Será mantido apenas o registro mais RECENTE de cada PNCP ID</li>
                                <li>Os registros mais ANTIGOS serão DELETADOS permanentemente</li>
                                <li>Recomendamos fazer BACKUP do banco antes de continuar</li>
                            </ul>
                        </div>

                        <h3 style="margin: 20px 0;">Duplicatas a serem limpas:</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>PNCP ID</th>
                                    <th>Total de Duplicatas</th>
                                    <th>Serão Removidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($duplicatas as $dup): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($dup['pncp_id']); ?></code></td>
                                        <td><?php echo $dup['duplicatas']; ?> registros</td>
                                        <td style="color: #dc3545; font-weight: bold;">
                                            <?php echo ($dup['duplicatas'] - 1); ?> registros
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <form method="POST" onsubmit="return confirm('⚠️ ATENÇÃO: Você tem CERTEZA que deseja deletar ' + <?php echo $totalDuplicados; ?> + ' registros?\n\nEsta ação NÃO pode ser desfeita!\n\nClique OK para confirmar ou Cancelar para voltar.');">
                            <div class="checkbox-container">
                                <label>
                                    <input type="checkbox" name="confirmar" value="SIM" required>
                                    <span><strong>Eu entendo que esta ação irá deletar <?php echo $totalDuplicados; ?> registros permanentemente e não pode ser desfeita.</strong></span>
                                </label>
                            </div>

                            <div class="actions">
                                <button type="submit" class="btn">🗑️ Limpar Duplicatas AGORA</button>
                                <a href="verificar_duplicatas_web.php" class="btn btn-secondary" style="margin-left: 10px;">Cancelar</a>
                            </div>
                        </form>
                    <?php endif;
                } catch (Exception $e) {
                    echo '<div class="status-box status-error">';
                    echo '<h2 style="margin-bottom: 10px;">❌ Erro de Conexão</h2>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '</div>';
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}
?>
