"""
Model de Log de Sincronização
"""
from sqlalchemy import Column, String, Integer, Text, DateTime, JSON
from app.core.database import Base
import uuid


class LogSincronizacao(Base):
    """Tabela de logs de sincronização com APIs externas"""
    __tablename__ = "logs_sincronizacao"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    fonte = Column(String(50), nullable=False)  # Ex: PNCP
    tipo = Column(String(50), nullable=False)  # Ex: licitacoes
    status = Column(String(20), nullable=False)  # sucesso, erro, parcial

    registros_novos = Column(Integer, default=0)
    registros_atualizados = Column(Integer, default=0)
    registros_erro = Column(Integer, default=0)

    mensagem = Column(Text, nullable=True)
    detalhes = Column(JSON, nullable=True)  # Detalhes adicionais

    iniciado = Column(DateTime, nullable=False)
    finalizado = Column(DateTime, nullable=False)
    duracao = Column(Integer, nullable=True)  # Em segundos

    def __repr__(self):
        return f"<LogSincronizacao(fonte={self.fonte}, tipo={self.tipo}, status={self.status})>"
