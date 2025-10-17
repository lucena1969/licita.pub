"""
Utilitários para envio de emails
"""
import aiosmtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from jinja2 import Template
from app.config import settings
import logging

logger = logging.getLogger(__name__)


async def enviar_email(
    destinatario: str,
    assunto: str,
    corpo_html: str,
    corpo_texto: str = None
) -> bool:
    """
    Envia um email

    Args:
        destinatario: Email do destinatário
        assunto: Assunto do email
        corpo_html: Corpo do email em HTML
        corpo_texto: Corpo alternativo em texto plano (opcional)

    Returns:
        True se enviado com sucesso, False caso contrário
    """
    try:
        # Criar mensagem
        message = MIMEMultipart("alternative")
        message["From"] = f"{settings.smtp_from_name} <{settings.smtp_from}>"
        message["To"] = destinatario
        message["Subject"] = assunto

        # Adicionar corpo texto plano
        if corpo_texto:
            part1 = MIMEText(corpo_texto, "plain")
            message.attach(part1)

        # Adicionar corpo HTML
        part2 = MIMEText(corpo_html, "html")
        message.attach(part2)

        # Enviar email
        await aiosmtplib.send(
            message,
            hostname=settings.smtp_host,
            port=settings.smtp_port,
            username=settings.smtp_user,
            password=settings.smtp_password,
            start_tls=True,
        )

        logger.info(f"Email enviado com sucesso para {destinatario}")
        return True

    except Exception as e:
        logger.error(f"Erro ao enviar email para {destinatario}: {e}")
        return False


async def enviar_email_verificacao(email: str, nome: str, token: str) -> bool:
    """
    Envia email de verificação de conta

    Args:
        email: Email do usuário
        nome: Nome do usuário
        token: Token de verificação

    Returns:
        True se enviado com sucesso
    """
    link_verificacao = f"{settings.frontend_url}/verificar-email?token={token}"

    corpo_html = f"""
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body {{
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }}
            .container {{
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }}
            .header {{
                background-color: #2563EB;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }}
            .content {{
                background-color: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 5px 5px;
            }}
            .button {{
                display: inline-block;
                background-color: #2563EB;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }}
            .footer {{
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 12px;
            }}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Licita.pub</h1>
            </div>
            <div class="content">
                <h2>Olá, {nome}!</h2>
                <p>Bem-vindo ao Licita.pub, sua plataforma de licitações públicas do Brasil.</p>
                <p>Para ativar sua conta, por favor clique no botão abaixo:</p>
                <center>
                    <a href="{link_verificacao}" class="button">Verificar Email</a>
                </center>
                <p>Ou copie e cole este link no seu navegador:</p>
                <p style="word-break: break-all; color: #666;">{link_verificacao}</p>
                <p><strong>Este link expira em 7 dias.</strong></p>
                <p>Se você não criou uma conta no Licita.pub, por favor ignore este email.</p>
            </div>
            <div class="footer">
                <p>© 2025 Licita.pub - Todos os direitos reservados</p>
            </div>
        </div>
    </body>
    </html>
    """

    corpo_texto = f"""
    Olá, {nome}!

    Bem-vindo ao Licita.pub!

    Para ativar sua conta, acesse o link abaixo:
    {link_verificacao}

    Este link expira em 7 dias.

    Se você não criou uma conta no Licita.pub, ignore este email.

    ---
    Licita.pub - Licitações Públicas do Brasil
    """

    return await enviar_email(
        destinatario=email,
        assunto="Confirme seu email - Licita.pub",
        corpo_html=corpo_html,
        corpo_texto=corpo_texto
    )


async def enviar_email_reset_senha(email: str, nome: str, token: str) -> bool:
    """
    Envia email de reset de senha

    Args:
        email: Email do usuário
        nome: Nome do usuário
        token: Token de reset

    Returns:
        True se enviado com sucesso
    """
    link_reset = f"{settings.frontend_url}/resetar-senha?token={token}"

    corpo_html = f"""
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body {{
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }}
            .container {{
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }}
            .header {{
                background-color: #2563EB;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }}
            .content {{
                background-color: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 5px 5px;
            }}
            .button {{
                display: inline-block;
                background-color: #EF4444;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }}
            .footer {{
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 12px;
            }}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Licita.pub</h1>
            </div>
            <div class="content">
                <h2>Olá, {nome}!</h2>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <center>
                    <a href="{link_reset}" class="button">Redefinir Senha</a>
                </center>
                <p>Ou copie e cole este link no seu navegador:</p>
                <p style="word-break: break-all; color: #666;">{link_reset}</p>
                <p><strong>Este link expira em 1 hora.</strong></p>
                <p>Se você não solicitou a redefinição de senha, ignore este email e sua senha permanecerá inalterada.</p>
            </div>
            <div class="footer">
                <p>© 2025 Licita.pub - Todos os direitos reservados</p>
            </div>
        </div>
    </body>
    </html>
    """

    corpo_texto = f"""
    Olá, {nome}!

    Recebemos uma solicitação para redefinir sua senha.

    Para criar uma nova senha, acesse o link abaixo:
    {link_reset}

    Este link expira em 1 hora.

    Se você não solicitou este reset, ignore este email.

    ---
    Licita.pub - Licitações Públicas do Brasil
    """

    return await enviar_email(
        destinatario=email,
        assunto="Redefinir senha - Licita.pub",
        corpo_html=corpo_html,
        corpo_texto=corpo_texto
    )
