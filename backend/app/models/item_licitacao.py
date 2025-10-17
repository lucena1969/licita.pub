"""
Model de Itens da Licitação
"""
from sqlalchemy import Column, String, Integer, Text, DECIMAL, ForeignKey
from app.core.database import Base
import uuid


class ItemLicitacao(Base):
    """Tabela de itens da licitação"""
    __tablename__ = "itens_licitacao"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    licitacao_id = Column(String(36), ForeignKey('licitacoes.id', ondelete='CASCADE'), nullable=False, index=True)

    numero_item = Column(Integer, nullable=False)
    descricao = Column(Text, nullable=False)
    quantidade = Column(DECIMAL(15, 3), nullable=False)
    unidade = Column(String(20), nullable=False)
    valor_unitario = Column(DECIMAL(15, 2), nullable=True)
    valor_total = Column(DECIMAL(15, 2), nullable=True)

    def __repr__(self):
        return f"<ItemLicitacao(id={self.id}, licitacao_id={self.licitacao_id}, numero_item={self.numero_item})>"
