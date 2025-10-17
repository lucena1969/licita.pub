"""
Repository para operações de banco de dados relacionadas a licitações
"""
from sqlalchemy.orm import Session
from sqlalchemy import func, desc, and_, or_
from app.models.licitacao import Licitacao
from app.models.item_licitacao import ItemLicitacao
from typing import Optional, List, Tuple
from datetime import datetime
from decimal import Decimal


class LicitacaoRepository:
    """Repository para acesso a dados de licitações"""

    def __init__(self, db: Session):
        self.db = db

    def buscar_por_id(self, licitacao_id: str) -> Optional[Licitacao]:
        """Busca licitação por ID"""
        return self.db.query(Licitacao).filter(Licitacao.id == licitacao_id).first()

    def buscar_por_pncp_id(self, pncp_id: str) -> Optional[Licitacao]:
        """Busca licitação por ID do PNCP"""
        return self.db.query(Licitacao).filter(Licitacao.pncp_id == pncp_id).first()

    def criar(self, licitacao: Licitacao) -> Licitacao:
        """Cria uma nova licitação"""
        self.db.add(licitacao)
        self.db.commit()
        self.db.refresh(licitacao)
        return licitacao

    def atualizar(self, licitacao: Licitacao) -> Licitacao:
        """Atualiza uma licitação existente"""
        licitacao.atualizado_em = datetime.utcnow()
        self.db.commit()
        self.db.refresh(licitacao)
        return licitacao

    def deletar(self, licitacao: Licitacao) -> None:
        """Deleta uma licitação"""
        self.db.delete(licitacao)
        self.db.commit()

    def listar(
        self,
        skip: int = 0,
        limit: int = 20,
        uf: Optional[str] = None,
        municipio: Optional[str] = None,
        modalidade: Optional[str] = None,
        situacao: Optional[str] = None,
        valor_min: Optional[Decimal] = None,
        valor_max: Optional[Decimal] = None,
        cnpj_orgao: Optional[str] = None,
        data_inicial: Optional[datetime] = None,
        data_final: Optional[datetime] = None,
        termo_busca: Optional[str] = None
    ) -> Tuple[List[Licitacao], int]:
        """
        Lista licitações com filtros e paginação

        Returns:
            Tupla (lista de licitações, total de registros)
        """
        query = self.db.query(Licitacao)

        # Aplicar filtros
        if uf:
            query = query.filter(Licitacao.uf == uf)

        if municipio:
            query = query.filter(Licitacao.municipio.ilike(f"%{municipio}%"))

        if modalidade:
            query = query.filter(Licitacao.modalidade == modalidade)

        if situacao:
            query = query.filter(Licitacao.situacao == situacao)

        if valor_min is not None:
            query = query.filter(Licitacao.valor_estimado >= valor_min)

        if valor_max is not None:
            query = query.filter(Licitacao.valor_estimado <= valor_max)

        if cnpj_orgao:
            query = query.filter(Licitacao.cnpj_orgao == cnpj_orgao)

        if data_inicial:
            query = query.filter(Licitacao.data_publicacao >= data_inicial)

        if data_final:
            query = query.filter(Licitacao.data_publicacao <= data_final)

        if termo_busca:
            query = query.filter(
                or_(
                    Licitacao.objeto.ilike(f"%{termo_busca}%"),
                    Licitacao.numero.ilike(f"%{termo_busca}%"),
                    Licitacao.nome_orgao.ilike(f"%{termo_busca}%")
                )
            )

        # Contar total
        total = query.count()

        # Ordenar e paginar
        licitacoes = query.order_by(desc(Licitacao.data_publicacao)).offset(skip).limit(limit).all()

        return licitacoes, total

    def buscar_por_periodo(
        self,
        data_inicial: datetime,
        data_final: datetime,
        limit: Optional[int] = None
    ) -> List[Licitacao]:
        """Busca licitações em um período específico"""
        query = self.db.query(Licitacao).filter(
            and_(
                Licitacao.data_publicacao >= data_inicial,
                Licitacao.data_publicacao <= data_final
            )
        ).order_by(desc(Licitacao.data_publicacao))

        if limit:
            query = query.limit(limit)

        return query.all()

    def buscar_recentes(self, limit: int = 10) -> List[Licitacao]:
        """Busca licitações mais recentes"""
        return self.db.query(Licitacao).order_by(
            desc(Licitacao.data_publicacao)
        ).limit(limit).all()

    def contar_total(self) -> int:
        """Conta total de licitações"""
        return self.db.query(Licitacao).count()

    def contar_por_uf(self) -> List[Tuple[str, int]]:
        """Conta licitações agrupadas por UF"""
        return self.db.query(
            Licitacao.uf,
            func.count(Licitacao.id)
        ).group_by(Licitacao.uf).all()

    def contar_por_modalidade(self) -> List[Tuple[str, int]]:
        """Conta licitações agrupadas por modalidade"""
        return self.db.query(
            Licitacao.modalidade,
            func.count(Licitacao.id)
        ).group_by(Licitacao.modalidade).all()

    def contar_por_situacao(self) -> List[Tuple[str, int]]:
        """Conta licitações agrupadas por situação"""
        return self.db.query(
            Licitacao.situacao,
            func.count(Licitacao.id)
        ).group_by(Licitacao.situacao).all()

    def somar_valores_total(self) -> Decimal:
        """Soma o valor total estimado de todas as licitações"""
        resultado = self.db.query(func.sum(Licitacao.valor_estimado)).scalar()
        return resultado or Decimal(0)

    def buscar_modalidades_disponiveis(self) -> List[str]:
        """Lista modalidades únicas disponíveis"""
        modalidades = self.db.query(Licitacao.modalidade).distinct().all()
        return [m[0] for m in modalidades if m[0]]

    def buscar_ufs_disponiveis(self) -> List[str]:
        """Lista UFs únicas disponíveis"""
        ufs = self.db.query(Licitacao.uf).distinct().order_by(Licitacao.uf).all()
        return [uf[0] for uf in ufs if uf[0]]

    def buscar_municipios_por_uf(self, uf: str) -> List[str]:
        """Lista municípios de uma UF específica"""
        municipios = self.db.query(Licitacao.municipio).filter(
            Licitacao.uf == uf
        ).distinct().order_by(Licitacao.municipio).all()
        return [m[0] for m in municipios if m[0]]


class ItemLicitacaoRepository:
    """Repository para itens de licitação"""

    def __init__(self, db: Session):
        self.db = db

    def criar(self, item: ItemLicitacao) -> ItemLicitacao:
        """Cria um novo item de licitação"""
        self.db.add(item)
        self.db.commit()
        self.db.refresh(item)
        return item

    def criar_varios(self, itens: List[ItemLicitacao]) -> List[ItemLicitacao]:
        """Cria vários itens de uma vez"""
        self.db.add_all(itens)
        self.db.commit()
        return itens

    def buscar_por_licitacao(self, licitacao_id: str) -> List[ItemLicitacao]:
        """Busca todos os itens de uma licitação"""
        return self.db.query(ItemLicitacao).filter(
            ItemLicitacao.licitacao_id == licitacao_id
        ).order_by(ItemLicitacao.numero_item).all()

    def deletar_por_licitacao(self, licitacao_id: str) -> None:
        """Deleta todos os itens de uma licitação"""
        self.db.query(ItemLicitacao).filter(
            ItemLicitacao.licitacao_id == licitacao_id
        ).delete()
        self.db.commit()
