<?php
/**
 * Script WEB para verificar duplicatas de pncp_id
 * Acesse via: https://licita.pub/verificar_duplicatas_web.php
 */

// Só permitir execução em ambiente de desenvolvimento/admin
// REMOVA ou proteja com senha em produção!

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Duplicatas - Licita.pub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .status-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .status-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            color: #495057;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
            text-align: center;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Verificação de Duplicatas</h1>
            <p>Sistema de Licitações - Licita.pub</p>
        </div>

        <div class="content">
<?php

// Definir caminho base (backend está dentro de public_html)
define('BASE_PATH', __DIR__ . '/backend');

// Carregar dependências
require_once BASE_PATH . '/src/Config/Config.php';
require_once BASE_PATH . '/src/Config/Database.php';

use App\Config\Config;
use App\Config\Database;

try {
    Config::load();
    $db = Database::getConnection();

    // Verificar duplicatas
    $sql = "SELECT pncp_id, COUNT(*) as duplicatas,
            GROUP_CONCAT(id ORDER BY created_at DESC SEPARATOR '|||') as ids,
            GROUP_CONCAT(created_at ORDER BY created_at DESC SEPARATOR '|||') as datas_criacao,
            MIN(created_at) as primeira,
            MAX(created_at) as ultima
            FROM licitacoes
            GROUP BY pncp_id
            HAVING COUNT(*) > 1
            ORDER BY duplicatas DESC";

    $stmt = $db->query($sql);
    $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas gerais
    $sqlTotal = "SELECT COUNT(*) as total FROM licitacoes";
    $stmtTotal = $db->query($sqlTotal);
    $total = $stmtTotal->fetch()['total'];

    $sqlUnicos = "SELECT COUNT(DISTINCT pncp_id) as unicos FROM licitacoes";
    $stmtUnicos = $db->query($sqlUnicos);
    $unicos = $stmtUnicos->fetch()['unicos'];

    $totalDuplicados = $total - $unicos;

    if (empty($duplicatas)) {
        echo '<div class="status-box status-success">';
        echo '<h2 style="margin-bottom: 10px;">✅ NENHUMA DUPLICATA ENCONTRADA!</h2>';
        echo '<p>O banco de dados está limpo e pronto para receber o índice UNIQUE.</p>';
        echo '</div>';

        echo '<div class="stats">';
        echo '<div class="stat-card">';
        echo '<div class="number">' . number_format($total, 0, ',', '.') . '</div>';
        echo '<div class="label">Total de Registros</div>';
        echo '</div>';
        echo '<div class="stat-card">';
        echo '<div class="number">' . number_format($unicos, 0, ',', '.') . '</div>';
        echo '<div class="label">PNCP IDs Únicos</div>';
        echo '</div>';
        echo '<div class="stat-card">';
        echo '<div class="number" style="color: #28a745;">0</div>';
        echo '<div class="label">Duplicatas</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="actions">';
        echo '<h3 style="margin-bottom: 15px;">📝 Próximo Passo:</h3>';
        echo '<p style="margin-bottom: 20px;">Execute a migration para adicionar o índice UNIQUE:</p>';
        echo '<code style="display: block; padding: 15px; margin: 15px 0;">mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/004_adicionar_unique_pncp_id.sql</code>';
        echo '</div>';

    } else {
        echo '<div class="status-box status-warning">';
        echo '<h2 style="margin-bottom: 10px;">⚠️ DUPLICATAS ENCONTRADAS!</h2>';
        echo '<p>Encontramos <strong>' . count($duplicatas) . '</strong> PNCP IDs com registros duplicados.</p>';
        echo '<p style="margin-top: 10px;">Total de registros que serão removidos: <strong>' . $totalDuplicados . '</strong></p>';
        echo '</div>';

        echo '<div class="stats">';
        echo '<div class="stat-card">';
        echo '<div class="number">' . number_format($total, 0, ',', '.') . '</div>';
        echo '<div class="label">Total de Registros</div>';
        echo '</div>';
        echo '<div class="stat-card">';
        echo '<div class="number">' . number_format($unicos, 0, ',', '.') . '</div>';
        echo '<div class="label">PNCP IDs Únicos</div>';
        echo '</div>';
        echo '<div class="stat-card">';
        echo '<div class="number" style="color: #dc3545;">' . number_format($totalDuplicados, 0, ',', '.') . '</div>';
        echo '<div class="label">Registros Duplicados</div>';
        echo '</div>';
        echo '</div>';

        echo '<h3 style="margin-top: 30px; margin-bottom: 15px;">📋 Detalhes das Duplicatas:</h3>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>PNCP ID</th>';
        echo '<th>Quantidade</th>';
        echo '<th>Primeira Criação</th>';
        echo '<th>Última Criação</th>';
        echo '<th>IDs no Banco</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($duplicatas as $dup) {
            $ids = explode('|||', $dup['ids']);
            $datas = explode('|||', $dup['datas_criacao']);

            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($dup['pncp_id']) . '</code></td>';
            echo '<td><span class="badge badge-danger">' . $dup['duplicatas'] . ' registros</span></td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($dup['primeira'])) . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($dup['ultima'])) . '</td>';
            echo '<td style="font-size: 11px;">';

            for ($i = 0; $i < count($ids); $i++) {
                $style = $i === 0 ? 'color: #28a745; font-weight: bold;' : 'color: #dc3545;';
                $label = $i === 0 ? ' (manter)' : ' (remover)';
                echo '<div style="' . $style . '">';
                echo htmlspecialchars(substr($ids[$i], 0, 8)) . '...' . $label;
                echo '</div>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        echo '<div class="actions">';
        echo '<h3 style="margin-bottom: 15px;">📝 Próximos Passos:</h3>';
        echo '<div class="status-box status-info" style="text-align: left;">';
        echo '<p><strong>1. Via Script PHP (Recomendado):</strong></p>';
        echo '<code style="display: block; padding: 10px; margin: 10px 0;">php backend/database/limpar_duplicatas.php</code>';
        echo '<p style="margin-top: 15px;"><strong>2. Via Migration SQL:</strong></p>';
        echo '<code style="display: block; padding: 10px; margin: 10px 0;">mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/004_adicionar_unique_pncp_id.sql</code>';
        echo '<p style="margin-top: 15px; font-size: 13px; color: #856404;"><strong>⚠️ ATENÇÃO:</strong> Faça backup do banco antes de executar!</p>';
        echo '</div>';
        echo '</div>';
    }

} catch (Exception $e) {
    echo '<div class="status-box status-error">';
    echo '<h2 style="margin-bottom: 10px;">❌ ERRO</h2>';
    echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="margin-top: 10px; font-size: 13px;">Verifique a conexão com o banco de dados e as configurações do arquivo .env</p>';
    echo '</div>';
}

?>
        </div>

        <div class="footer">
            <p>Licita.pub - Sistema de Licitações Públicas</p>
            <p style="margin-top: 5px; font-size: 11px;">Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
