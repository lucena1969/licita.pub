#!/usr/bin/env php
<?php
/**
 * Script para Verificar Ambiente e Gerar Comando do Cron
 *
 * Execute este script PRIMEIRO para descobrir os caminhos corretos
 *
 * Uso:
 *   php verificar_ambiente.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   LICITA.PUB - VERIFICAÇÃO DE AMBIENTE            ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";

// 1. Caminho do PHP
echo "📍 1. CAMINHO DO PHP:\n";
echo "   " . PHP_BINARY . "\n";
echo "\n";

// 2. Versão do PHP
echo "📍 2. VERSÃO DO PHP:\n";
echo "   " . PHP_VERSION . "\n";
echo "\n";

// 3. Caminho do script
$scriptPath = __FILE__;
echo "📍 3. CAMINHO DO SCRIPT:\n";
echo "   " . $scriptPath . "\n";
echo "\n";

// 4. Caminho do diretório atual
$currentDir = dirname($scriptPath);
echo "📍 4. DIRETÓRIO ATUAL:\n";
echo "   " . $currentDir . "\n";
echo "\n";

// 5. Caminho do projeto (public_html)
$projectRoot = dirname(dirname($currentDir));
echo "📍 5. RAIZ DO PROJETO:\n";
echo "   " . $projectRoot . "\n";
echo "\n";

// 6. Verificar se arquivos necessários existem
echo "📍 6. VERIFICAÇÃO DE ARQUIVOS:\n";

$arquivosNecessarios = [
    '../src/Services/PNCPService.php' => 'PNCPService',
    '../src/Models/Licitacao.php' => 'Model Licitacao',
    '../src/Models/Orgao.php' => 'Model Orgao',
    '../src/Repositories/LicitacaoRepository.php' => 'Repository Licitacao',
    '../src/Repositories/OrgaoRepository.php' => 'Repository Orgao',
    '../src/Config/Database.php' => 'Database Config',
    'sincronizar_pncp.php' => 'Script de Sincronização',
];

$todosExistem = true;

foreach ($arquivosNecessarios as $arquivo => $descricao) {
    $caminhoCompleto = $currentDir . '/' . $arquivo;
    $existe = file_exists($caminhoCompleto);
    $status = $existe ? "✅ OK" : "❌ FALTA";

    if (!$existe) {
        $todosExistem = false;
    }

    echo "   {$status} - {$descricao}\n";
}

echo "\n";

// 7. Testar conexão com banco
echo "📍 7. TESTE DE CONEXÃO COM BANCO:\n";

try {
    require_once dirname($currentDir) . '/src/Config/Database.php';

    $db = App\Config\Database::getConnection();

    if ($db) {
        echo "   ✅ Conexão estabelecida com sucesso!\n";

        // Verificar se tabelas existem
        $stmt = $db->query("SHOW TABLES LIKE 'licitacoes'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Tabela 'licitacoes' existe\n";
        } else {
            echo "   ⚠️  Tabela 'licitacoes' NÃO existe (execute as migrações)\n";
        }

        $stmt = $db->query("SHOW TABLES LIKE 'orgaos'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Tabela 'orgaos' existe\n";
        } else {
            echo "   ⚠️  Tabela 'orgaos' NÃO existe (execute as migrações)\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao conectar: " . $e->getMessage() . "\n";
    echo "   💡 Verifique o arquivo .env com as credenciais do banco\n";
}

echo "\n";

// 8. Verificar permissões
echo "📍 8. PERMISSÕES DO SCRIPT:\n";
$scriptSync = $currentDir . '/sincronizar_pncp.php';
if (file_exists($scriptSync)) {
    $perms = substr(sprintf('%o', fileperms($scriptSync)), -4);
    $isExecutable = is_executable($scriptSync);

    echo "   Permissões: {$perms}\n";
    echo "   Executável: " . ($isExecutable ? "✅ SIM" : "❌ NÃO") . "\n";

    if (!$isExecutable) {
        echo "\n   💡 Execute: chmod +x {$scriptSync}\n";
    }
}

echo "\n";

// 9. Gerar comandos do cron
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   COMANDO PARA O CRON JOB                          ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";

$phpBinary = PHP_BINARY;
$syncScript = $currentDir . '/sincronizar_pncp.php';
$logDir = dirname($projectRoot) . '/logs';
$logFile = $logDir . '/pncp_sync.log';

echo "📋 COPIE E COLE NO cPanel:\n\n";

echo "┌─ Frequência (escolha uma):\n";
echo "│\n";
echo "│  Diário às 06:00:     0 6 * * *\n";
echo "│  A cada 12 horas:     0 */12 * * *\n";
echo "│  A cada 6 horas:      0 */6 * * *\n";
echo "│  Dias úteis às 06:00: 0 6 * * 1-5\n";
echo "│\n";
echo "└─────────────────────────────────────\n\n";

echo "┌─ Comando (SEM log):\n";
echo "│\n";
echo "│  {$phpBinary} {$syncScript}\n";
echo "│\n";
echo "└─────────────────────────────────────\n\n";

echo "┌─ Comando (COM log - RECOMENDADO):\n";
echo "│\n";
echo "│  {$phpBinary} {$syncScript} >> {$logFile} 2>&1\n";
echo "│\n";
echo "└─────────────────────────────────────\n\n";

echo "⚠️  ATENÇÃO: Antes de configurar o cron, crie a pasta de logs:\n";
echo "    mkdir -p {$logDir}\n";
echo "    chmod 755 {$logDir}\n";
echo "\n";

// 10. Teste manual
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   TESTE MANUAL                                     ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";

echo "🧪 Execute este comando para testar AGORA:\n\n";
echo "   cd {$currentDir}\n";
echo "   {$phpBinary} sincronizar_pncp.php --ultimos-dias=1\n";
echo "\n";

// 11. Resumo
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   RESUMO                                           ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";

if ($todosExistem) {
    echo "✅ Todos os arquivos necessários estão presentes\n";
} else {
    echo "❌ Alguns arquivos estão faltando (veja lista acima)\n";
}

echo "\n";
echo "📋 PRÓXIMOS PASSOS:\n";
echo "   1. Execute o teste manual (comando acima)\n";
echo "   2. Se funcionar, copie o comando do cron\n";
echo "   3. Acesse cPanel → Cron Jobs\n";
echo "   4. Cole o comando e configure a frequência\n";
echo "   5. Aguarde a próxima execução ou force manualmente\n";
echo "\n";

echo "📚 Documentação completa em:\n";
echo "   CONFIGURAR_CRON_HOSTINGER.md\n";
echo "\n";

exit(0);
