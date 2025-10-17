"""
Model de Favoritos
"""
from sqlalchemy import Column, String, DateTime, ForeignKey, UniqueConstraint
from sqlalchemy.sql import func
from app.core.database import Base
import uuid


class Favorito(Base):
    """Tabela de favoritos"""
    __tablename__ = "favoritos"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    usuario_id = Column(String(36), ForeignKey('usuarios.id', ondelete='CASCADE'), nullable=False, index=True)
    licitacao_id = Column(String(36), ForeignKey('licitacoes.id', ondelete='CASCADE'), nullable=False, index=True)
    created_at = Column(DateTime, server_default=func.now(), nullable=False)

    # Garante que um usuário não favorite a mesma licitação duas vezes
    __table_args__ = (
        UniqueConstraint('usuario_id', 'licitacao_id', name='uq_usuario_licitacao'),
    )

    def __repr__(self):
        return f"<Favorito(usuario_id={self.usuario_id}, licitacao_id={self.licitacao_id})>"
