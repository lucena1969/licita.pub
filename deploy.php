<?php
/**
 * Script de Auto-Deploy para Produção
 *
 * IMPORTANTE:
 * 1. Configure as credenciais FTP abaixo
 * 2. Execute: php deploy.php
 * 3. DELETE este arquivo após usar (segurança)
 *
 * Uso:
 *   php deploy.php                 → Deploy completo
 *   php deploy.php --migrations    → Apenas migrações
 *   php deploy.php --backend       → Apenas backend
 */

// ============================================================
// CONFIGURAÇÕES (EDITE AQUI)
// ============================================================

$config = [
    'ftp_host' => 'ftp.licita.pub',  // ou IP do servidor
    'ftp_port' => 21,                 // 21 para FTP, 22 para SFTP
    'ftp_user' => 'u590097272',       // seu usuário
    'ftp_pass' => 'Numse!2020',   // ⚠️ COLOQUE SUA SENHA
    'ftp_ssl'  => true,               // true para FTPS

    'remote_dir' => '/public_html/',  // diretório remoto
    'local_dir'  => __DIR__,          // diretório local

    'pastas_enviar' => [
        'database/migrations' => 'database/migrations',
        'backend/src'         => 'backend/src',
        'backend/public'      => 'backend/public',
    ],
];

// ============================================================
// NÃO EDITE DAQUI PRA BAIXO
// ============================================================

class AutoDeploy {
    private $conn;
    private $config;
    private $stats = [
        'enviados' => 0,
        'erros' => 0,
        'pulados' => 0,
    ];

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function conectar(): bool {
        echo "🔌 Conectando ao servidor FTP...\n";

        if ($this->config['ftp_ssl']) {
            $this->conn = @ftp_ssl_connect(
                $this->config['ftp_host'],
                $this->config['ftp_port'],
                10 // timeout
            );
        } else {
            $this->conn = @ftp_connect(
                $this->config['ftp_host'],
                $this->config['ftp_port'],
                10
            );
        }

        if (!$this->conn) {
            echo "❌ Erro ao conectar ao servidor FTP\n";
            return false;
        }

        $login = @ftp_login(
            $this->conn,
            $this->config['ftp_user'],
            $this->config['ftp_pass']
        );

        if (!$login) {
            echo "❌ Erro ao fazer login (usuário/senha incorretos)\n";
            return false;
        }

        ftp_pasv($this->conn, true); // Modo passivo

        echo "✅ Conectado com sucesso!\n\n";
        return true;
    }

    public function deploy(array $pastas = null): void {
        if ($pastas === null) {
            $pastas = $this->config['pastas_enviar'];
        }

        foreach ($pastas as $local => $remoto) {
            echo "📁 Enviando: {$local} → {$remoto}\n";
            $this->enviarDiretorio($local, $remoto);
        }

        $this->exibirResumo();
    }

    private function enviarDiretorio(string $localDir, string $remoteDir): void {
        $fullLocalPath = $this->config['local_dir'] . '/' . $localDir;
        $fullRemotePath = $this->config['remote_dir'] . $remoteDir;

        if (!is_dir($fullLocalPath)) {
            echo "⚠️  Diretório local não encontrado: {$fullLocalPath}\n";
            return;
        }

        // Criar diretório remoto se não existir
        $this->criarDiretorioRemoto($fullRemotePath);

        // Listar arquivos locais
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullLocalPath),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }

            $relativePath = str_replace($fullLocalPath, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);
            $remoteFile = $fullRemotePath . $relativePath;
            $localFile = $file->getPathname();

            // Criar subdiretórios se necessário
            $remoteFileDir = dirname($remoteFile);
            $this->criarDiretorioRemoto($remoteFileDir);

            // Enviar arquivo
            echo "  ↑ {$relativePath} ... ";

            $result = @ftp_put(
                $this->conn,
                $remoteFile,
                $localFile,
                FTP_BINARY
            );

            if ($result) {
                echo "✅\n";
                $this->stats['enviados']++;
            } else {
                echo "❌\n";
                $this->stats['erros']++;
            }
        }
    }

    private function criarDiretorioRemoto(string $dir): void {
        $parts = explode('/', trim($dir, '/'));
        $path = '';

        foreach ($parts as $part) {
            if (empty($part)) continue;

            $path .= '/' . $part;

            // Tentar criar (ignora se já existe)
            @ftp_mkdir($this->conn, $path);
            @ftp_chmod($this->conn, 0755, $path);
        }
    }

    private function exibirResumo(): void {
        echo "\n";
        echo "═══════════════════════════════════════\n";
        echo "📊 RESUMO DO DEPLOY\n";
        echo "═══════════════════════════════════════\n";
        echo "✅ Arquivos enviados: {$this->stats['enviados']}\n";
        echo "❌ Erros:             {$this->stats['erros']}\n";
        echo "⏭️  Pulados:          {$this->stats['pulados']}\n";
        echo "═══════════════════════════════════════\n";

        if ($this->stats['erros'] === 0) {
            echo "🎉 DEPLOY CONCLUÍDO COM SUCESSO!\n";
        } else {
            echo "⚠️  DEPLOY CONCLUÍDO COM ERROS\n";
        }
    }

    public function desconectar(): void {
        if ($this->conn) {
            ftp_close($this->conn);
            echo "\n🔌 Desconectado do servidor FTP\n";
        }
    }
}

// ============================================================
// EXECUÇÃO
// ============================================================

try {
    echo "\n";
    echo "╔═══════════════════════════════════════╗\n";
    echo "║   LICITA.PUB - AUTO DEPLOY v1.0       ║\n";
    echo "╚═══════════════════════════════════════╝\n";
    echo "\n";

    // Verificar senha
    if ($config['ftp_pass'] === 'SUA_SENHA_AQUI') {
        die("❌ ERRO: Configure a senha FTP no arquivo deploy.php (linha 22)\n");
    }

    // Verificar argumentos
    $opcao = $argv[1] ?? null;
    $pastasEnviar = null;

    switch ($opcao) {
        case '--migrations':
            echo "📦 Modo: Apenas migrações\n\n";
            $pastasEnviar = ['database/migrations' => 'database/migrations'];
            break;

        case '--backend':
            echo "📦 Modo: Apenas backend\n\n";
            $pastasEnviar = [
                'backend/src' => 'backend/src',
                'backend/public' => 'backend/public',
            ];
            break;

        default:
            echo "📦 Modo: Deploy completo\n\n";
            break;
    }

    $deploy = new AutoDeploy($config);

    if (!$deploy->conectar()) {
        die("\n❌ Não foi possível conectar ao servidor\n");
    }

    $deploy->deploy($pastasEnviar);
    $deploy->desconectar();

    echo "\n✨ Processo finalizado!\n";
    echo "\n⚠️  IMPORTANTE: Delete o arquivo deploy.php por segurança!\n\n";

} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: {$e->getMessage()}\n";
    exit(1);
}
