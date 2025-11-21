<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * EmailService
 *
 * Serviço para envio de emails do sistema usando SMTP da Hostinger
 */
class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $baseUrl;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $smtpEncryption;
    private bool $smtpDebug;

    public function __construct()
    {
        // Configurações gerais
        $this->fromEmail = $_ENV['EMAIL_FROM'] ?? 'contato@licita.pub';
        $this->fromName = $_ENV['EMAIL_FROM_NAME'] ?? 'Licita.pub';
        $this->baseUrl = $_ENV['APP_URL'] ?? 'https://licita.pub';

        // Configurações SMTP da Hostinger
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com';
        $this->smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->smtpUser = $_ENV['SMTP_USER'] ?? $this->fromEmail;
        $this->smtpPass = $_ENV['SMTP_PASS'] ?? '';
        $this->smtpEncryption = $_ENV['SMTP_ENCRYPTION'] ?? 'tls'; // 'tls' ou 'ssl'
        $this->smtpDebug = (bool)($_ENV['SMTP_DEBUG'] ?? false);
    }

    /**
     * Enviar email de verificação de conta
     */
    public function enviarEmailVerificacao(string $email, string $nome, string $token): bool
    {
        $linkVerificacao = $this->baseUrl . "/frontend/verificar-email.html?token=" . urlencode($token);

        $assunto = "Confirme seu cadastro no Licita.pub";

        $corpo = $this->getTemplateVerificacao($nome, $linkVerificacao);

        return $this->enviar($email, $assunto, $corpo);
    }

    /**
     * Enviar email de recuperação de senha
     */
    public function enviarEmailResetSenha(string $email, string $nome, string $token): bool
    {
        $linkReset = $this->baseUrl . "/frontend/reset-senha.html?token=" . urlencode($token);

        $assunto = "Redefinir sua senha - Licita.pub";

        $corpo = $this->getTemplateResetSenha($nome, $linkReset);

        return $this->enviar($email, $assunto, $corpo);
    }

    /**
     * Enviar email usando PHPMailer com SMTP
     */
    private function enviar(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpEncryption; // 'tls' ou 'ssl'
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';

            // Debug (apenas em desenvolvimento)
            if ($this->smtpDebug) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }

            // Remetente
            $mail->setFrom($this->fromEmail, $this->fromName);

            // Destinatário
            $mail->addAddress($to);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body); // Versão texto puro

            // Enviar
            $resultado = $mail->send();

            if ($resultado) {
                error_log("Email enviado com sucesso para: $to");
            } else {
                error_log("Falha ao enviar email para: $to");
            }

            return $resultado;

        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $mail->ErrorInfo);
            error_log("Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML - Email de Verificação
     */
    private function getTemplateVerificacao(string $nome, string $link): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirme seu cadastro</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #1351b4; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Licita.pub</h1>
                        </td>
                    </tr>

                    <!-- Corpo -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Olá, {$nome}!</h2>

                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Bem-vindo ao <strong>Licita.pub</strong> - sua plataforma de consulta de licitações públicas!
                            </p>

                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                Para concluir seu cadastro e começar a usar a plataforma, clique no botão abaixo para confirmar seu email:
                            </p>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{$link}" style="background-color: #1351b4; color: #ffffff; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: 600; display: inline-block;">
                                    Confirmar Email
                                </a>
                            </div>

                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; padding-top: 20px; border-top: 1px solid #eeeeee;">
                                Ou copie e cole este link no seu navegador:<br>
                                <a href="{$link}" style="color: #1351b4; word-break: break-all;">{$link}</a>
                            </p>

                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                <strong>Este link expira em 24 horas.</strong>
                            </p>

                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                Se você não criou uma conta no Licita.pub, ignore este email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f8f8; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="color: #999; font-size: 12px; margin: 0;">
                                © 2025 Licita.pub - Consulta de Licitações Públicas<br>
                                Este é um email automático, não responda.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Template HTML - Email de Reset de Senha
     */
    private function getTemplateResetSenha(string $nome, string $link): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #1351b4; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Licita.pub</h1>
                        </td>
                    </tr>

                    <!-- Corpo -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Olá, {$nome}!</h2>

                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Licita.pub</strong>.
                            </p>

                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                Clique no botão abaixo para criar uma nova senha:
                            </p>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{$link}" style="background-color: #1351b4; color: #ffffff; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: 600; display: inline-block;">
                                    Redefinir Senha
                                </a>
                            </div>

                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; padding-top: 20px; border-top: 1px solid #eeeeee;">
                                Ou copie e cole este link no seu navegador:<br>
                                <a href="{$link}" style="color: #1351b4; word-break: break-all;">{$link}</a>
                            </p>

                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                <strong>Este link expira em 1 hora.</strong>
                            </p>

                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                Se você não solicitou a redefinição de senha, ignore este email. Sua senha permanecerá inalterada.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f8f8; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="color: #999; font-size: 12px; margin: 0;">
                                © 2025 Licita.pub - Consulta de Licitações Públicas<br>
                                Este é um email automático, não responda.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
