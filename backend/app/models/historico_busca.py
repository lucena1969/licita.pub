"""
Model de Histórico de Buscas
"""
from sqlalchemy import Column, String, DateTime, ForeignKey, JSON
from sqlalchemy.sql import func
from app.core.database import Base
import uuid


class HistoricoBusca(Base):
    """Tabela de histórico de buscas dos usuários"""
    __tablename__ = "historico_buscas"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    usuario_id = Column(String(36), ForeignKey('usuarios.id', ondelete='CASCADE'), nullable=False, index=True)
    termo_busca = Column(String(500), nullable=False)
    filtros = Column(JSON, nullable=True)
    created_at = Column(DateTime, server_default=func.now(), nullable=False, index=True)

    def __repr__(self):
        return f"<HistoricoBusca(id={self.id}, termo={self.termo_busca})>"
