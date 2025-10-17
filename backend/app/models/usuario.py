"""
Model de Usuário
"""
from sqlalchemy import Column, String, Boolean, DateTime, Enum
from sqlalchemy.sql import func
from app.core.database import Base
import uuid
import enum


class PlanoEnum(str, enum.Enum):
    """Tipos de plano de assinatura"""
    GRATUITO = "GRATUITO"
    BASICO = "BASICO"
    INTERMEDIARIO = "INTERMEDIARIO"
    PREMIUM = "PREMIUM"


class Usuario(Base):
    """Tabela de usuários"""
    __tablename__ = "usuarios"

    # Usando CHAR(36) para UUID no MySQL
    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))

    # Dados básicos
    email = Column(String(255), unique=True, nullable=False, index=True)
    senha = Column(String(255), nullable=False)  # Hash bcrypt
    nome = Column(String(255), nullable=False)
    telefone = Column(String(20), nullable=True)
    cpf_cnpj = Column(String(18), nullable=True, unique=True)

    # Verificação de email
    email_verificado = Column(Boolean, default=False, nullable=False)
    token_verificacao = Column(String(255), nullable=True)
    token_verificacao_expira = Column(DateTime, nullable=True)

    # Token para reset de senha
    token_reset_senha = Column(String(255), nullable=True)
    token_reset_senha_expira = Column(DateTime, nullable=True)

    # Status e plano
    ativo = Column(Boolean, default=True, nullable=False)
    plano = Column(
        Enum(PlanoEnum),
        default=PlanoEnum.GRATUITO,
        nullable=False
    )

    # Timestamps
    created_at = Column(DateTime, server_default=func.now(), nullable=False)
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now(), nullable=False)

    def __repr__(self):
        return f"<Usuario(id={self.id}, email={self.email}, nome={self.nome})>"
