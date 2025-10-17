"""
Configurações centralizadas da aplicação
"""
from pydantic_settings import BaseSettings
from typing import Optional


class Settings(BaseSettings):
    """Configurações da aplicação carregadas de variáveis de ambiente"""

    # Ambiente
    environment: str = "development"

    # Database
    database_url: str

    # Segurança
    secret_key: str
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 10080  # 7 dias
    email_verification_expire_hours: int = 168  # 7 dias

    # Email
    smtp_host: str
    smtp_port: int = 587
    smtp_user: str
    smtp_password: str
    smtp_from: str
    smtp_from_name: str = "Licita.pub"

    # URLs
    frontend_url: str = "http://localhost:5173"
    backend_url: str = "http://localhost:8000"

    # PNCP
    pncp_api_url: str = "https://pncp.gov.br/api/pncp/v1"
    pncp_username: Optional[str] = None
    pncp_password: Optional[str] = None

    # Redis
    redis_url: Optional[str] = None

    # AdSense
    adsense_client_id: Optional[str] = None

    # Logs
    log_level: str = "INFO"

    class Config:
        env_file = ".env"
        case_sensitive = False


# Instância global de settings
settings = Settings()
