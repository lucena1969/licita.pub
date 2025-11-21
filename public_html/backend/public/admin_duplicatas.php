<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gerenciar Duplicatas - Licita.pub</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .card h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .card h2 .icon {
            font-size: 32px;
            margin-right: 15px;
        }
        .card p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 20px;
            border-radius: 6px;
            margin-top: 30px;
        }
        .info-box h3 {
            color: #0c5460;
            margin-bottom: 10px;
        }
        .info-box ul {
            color: #0c5460;
            padding-left: 20px;
        }
        .info-box li {
            margin: 8px 0;
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
            <h1>🔧 Admin - Gerenciar Duplicatas</h1>
            <p>Ferramentas para verificar e limpar duplicatas no banco de dados</p>
        </div>

        <div class="content">
            <div class="card">
                <h2>
                    <span class="icon">🔍</span>
                    Verificar Duplicatas
                </h2>
                <p>
                    Analise o banco de dados para identificar registros duplicados por PNCP ID.
                    Este processo apenas <strong>verifica</strong>, sem fazer nenhuma alteração.
                </p>
                <a href="verificar_duplicatas_web.php" class="btn">Verificar Agora</a>
            </div>

            <div class="card">
                <h2>
                    <span class="icon">🗑️</span>
                    Limpar Duplicatas
                </h2>
                <p>
                    Remove registros duplicados do banco de dados, mantendo apenas o mais recente.
                    <strong style="color: #dc3545;">⚠️ Esta ação NÃO pode ser desfeita!</strong>
                </p>
                <a href="limpar_duplicatas_web.php" class="btn btn-danger">Limpar Duplicatas</a>
            </div>

            <div class="info-box">
                <h3>📋 Ordem Recomendada:</h3>
                <ul>
                    <li><strong>1.</strong> Faça backup do banco de dados</li>
                    <li><strong>2.</strong> Execute "Verificar Duplicatas" para ver o que será removido</li>
                    <li><strong>3.</strong> Se estiver tudo ok, execute "Limpar Duplicatas"</li>
                    <li><strong>4.</strong> Execute a migration para adicionar o índice UNIQUE</li>
                </ul>
            </div>

            <div class="info-box" style="margin-top: 20px; background: #fff3cd; border-color: #ffc107;">
                <h3 style="color: #856404;">⚠️ Próximo Passo:</h3>
                <p style="color: #856404; margin: 0;">
                    Após limpar as duplicatas, execute a migration via SSH:<br>
                    <code style="display: block; background: white; padding: 10px; margin-top: 10px; border-radius: 4px; font-size: 13px;">
                        mysql -u u590097272_neto -p u590097272_licitapub < backend/database/migrations/004_adicionar_unique_pncp_id.sql
                    </code>
                </p>
            </div>
        </div>

        <div class="footer">
            <p>Licita.pub - Sistema de Licitações Públicas</p>
            <p style="margin-top: 5px; font-size: 11px;">Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
