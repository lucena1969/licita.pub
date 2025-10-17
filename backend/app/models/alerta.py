"""
Model de Alertas
"""
from sqlalchemy import Column, String, Boolean, DateTime, ForeignKey, JSON, Enum
from sqlalchemy.sql import func
from app.core.database import Base
import uuid
import enum


class FrequenciaEnum(str, enum.Enum):
    """Frequência de envio de alertas"""
    IMEDIATA = "IMEDIATA"
    DIARIA = "DIARIA"
    SEMANAL = "SEMANAL"


class Alerta(Base):
    """Tabela de alertas personalizados"""
    __tablename__ = "alertas"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    usuario_id = Column(String(36), ForeignKey('usuarios.id', ondelete='CASCADE'), nullable=False, index=True)

    nome = Column(String(255), nullable=False)
    ativo = Column(Boolean, default=True, nullable=False)

    # Filtros armazenados como JSON
    # Exemplo: {"uf": ["SP", "RJ"], "modalidade": ["PREGAO_ELETRONICO"], "valor_min": 10000}
    filtros = Column(JSON, nullable=False)

    # Configurações de envio
    frequencia = Column(
        Enum(FrequenciaEnum),
        default=FrequenciaEnum.DIARIA,
        nullable=False
    )
    ultimo_envio = Column(DateTime, nullable=True)

    # Timestamps
    created_at = Column(DateTime, server_default=func.now(), nullable=False)
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now(), nullable=False)

    def __repr__(self):
        return f"<Alerta(id={self.id}, nome={self.nome}, usuario_id={self.usuario_id})>"
