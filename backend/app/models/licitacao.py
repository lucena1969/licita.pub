"""
Model de Licitação
"""
from sqlalchemy import Column, String, Text, DECIMAL, DateTime, Index
from sqlalchemy.sql import func
from app.core.database import Base
import uuid


class Licitacao(Base):
    """Tabela de licitações"""
    __tablename__ = "licitacoes"

    # ID
    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))

    # IDs externos
    pncp_id = Column(String(100), unique=True, nullable=False, index=True)
    orgao_id = Column(String(50), nullable=False)

    # Dados básicos
    numero = Column(String(50), nullable=False)
    objeto = Column(Text, nullable=False)
    modalidade = Column(String(50), nullable=False, index=True)
    situacao = Column(String(30), nullable=False, index=True)

    # Valores
    valor_estimado = Column(DECIMAL(15, 2), nullable=True, index=True)

    # Datas
    data_publicacao = Column(DateTime, nullable=False, index=True)
    data_abertura = Column(DateTime, nullable=True, index=True)
    data_encerramento = Column(DateTime, nullable=True)

    # Localização
    uf = Column(String(2), nullable=False, index=True)
    municipio = Column(String(100), nullable=False, index=True)

    # Links
    url_edital = Column(Text, nullable=True)
    url_pncp = Column(Text, nullable=False)

    # Dados do órgão
    nome_orgao = Column(String(255), nullable=False)
    cnpj_orgao = Column(String(18), nullable=False, index=True)

    # Metadados
    sincronizado_em = Column(DateTime, server_default=func.now(), nullable=False)
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now(), nullable=False)

    # Índices compostos para melhor performance
    __table_args__ = (
        Index('idx_uf_municipio', 'uf', 'municipio'),
        Index('idx_modalidade_situacao', 'modalidade', 'situacao'),
    )

    def __repr__(self):
        return f"<Licitacao(id={self.id}, numero={self.numero}, municipio={self.municipio})>"
