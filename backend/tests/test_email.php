<?php

/**
 * Script de teste para envio de email
 *
 * Como usar:
 * php backend/tests/test_email.php seu-email@example.com
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\EmailService;

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Verificar se foi passado um email como argumento
if ($argc < 2) {
    echo "❌ Uso: php " . $argv[0] . " seu-email@example.com\n";
    exit(1);
}

$emailDestino = $argv[1];

// Validar email
if (!filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Email inválido: $emailDestino\n";
    exit(1);
}

echo "\n🚀 Testando envio de email para: $emailDestino\n\n";

echo "📋 Configurações SMTP:\n";
echo "   Host: " . ($_ENV['SMTP_HOST'] ?? 'não configurado') . "\n";
echo "   Port: " . ($_ENV['SMTP_PORT'] ?? 'não configurado') . "\n";
echo "   User: " . ($_ENV['SMTP_USER'] ?? 'não configurado') . "\n";
echo "   From: " . ($_ENV['EMAIL_FROM'] ?? 'não configurado') . "\n";
echo "   Encryption: " . ($_ENV['SMTP_ENCRYPTION'] ?? 'não configurado') . "\n\n";

// Criar serviço de email
$emailService = new EmailService();

// Gerar token de teste
$tokenTeste = bin2hex(random_bytes(32));

echo "📧 Enviando email de verificação de teste...\n";

// Tentar enviar
try {
    $resultado = $emailService->enviarEmailVerificacao(
        $emailDestino,
        'Usuário Teste',
        $tokenTeste
    );

    if ($resultado) {
        echo "\n✅ Email enviado com sucesso!\n";
        echo "📬 Verifique a caixa de entrada de: $emailDestino\n";
        echo "🔗 Token de teste: $tokenTeste\n\n";
        echo "💡 Dica: Se não receber o email, verifique a pasta de spam.\n\n";
        exit(0);
    } else {
        echo "\n❌ Falha ao enviar email.\n";
        echo "📝 Verifique os logs para mais detalhes.\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "\n❌ Erro ao enviar email: " . $e->getMessage() . "\n\n";
    exit(1);
}
