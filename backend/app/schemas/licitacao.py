"""
Schemas Pydantic para validação de dados de licitações/contratos
"""
from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import datetime
from decimal import Decimal


# ========== Schemas de Consulta (Filtros) ==========

class LicitacaoFiltros(BaseModel):
    """Schema para filtros de busca de licitações"""
    data_inicial: Optional[str] = Field(None, description="Data inicial YYYYMMDD")
    data_final: Optional[str] = Field(None, description="Data final YYYYMMDD")
    uf: Optional[str] = Field(None, max_length=2, description="Sigla da UF")
    municipio: Optional[str] = Field(None, description="Nome do município")
    modalidade: Optional[str] = Field(None, description="Modalidade da licitação")
    situacao: Optional[str] = Field(None, description="Situação da licitação")
    valor_min: Optional[Decimal] = Field(None, description="Valor mínimo")
    valor_max: Optional[Decimal] = Field(None, description="Valor máximo")
    cnpj_orgao: Optional[str] = Field(None, description="CNPJ do órgão")
    termo_busca: Optional[str] = Field(None, description="Termo para busca no objeto")
    pagina: int = Field(1, ge=1, description="Número da página")
    tamanho_pagina: int = Field(20, ge=10, le=100, description="Itens por página")


# ========== Schemas de Resposta ==========

class OrgaoEntidadeResponse(BaseModel):
    """Schema de órgão/entidade"""
    cnpj: str
    razaoSocial: str
    poderId: Optional[str] = None
    esferaId: Optional[str] = None

    class Config:
        from_attributes = True


class UnidadeOrgaoResponse(BaseModel):
    """Schema de unidade do órgão"""
    codigoUnidade: Optional[str] = None
    nomeUnidade: Optional[str] = None
    ufSigla: Optional[str] = None
    ufNome: Optional[str] = None
    municipioNome: Optional[str] = None
    codigoIbge: Optional[str] = None

    class Config:
        from_attributes = True


class CategoriaProcessoResponse(BaseModel):
    """Schema de categoria de processo"""
    id: int
    nome: str

    class Config:
        from_attributes = True


class TipoContratoResponse(BaseModel):
    """Schema de tipo de contrato"""
    id: int
    nome: str

    class Config:
        from_attributes = True


class ContratoResponse(BaseModel):
    """Schema de resposta de contrato do PNCP"""
    numeroControlePNCP: str
    numeroControlePncpCompra: Optional[str] = None
    anoContrato: int
    sequencialContrato: int
    numeroContratoEmpenho: str

    # Datas
    dataAssinatura: str
    dataVigenciaInicio: str
    dataVigenciaFim: Optional[str] = None
    dataPublicacaoPncp: str
    dataAtualizacao: str

    # Fornecedor
    niFornecedor: str
    tipoPessoa: str
    nomeRazaoSocialFornecedor: str

    # Valores
    valorInicial: Decimal
    valorGlobal: Decimal
    valorParcela: Optional[Decimal] = None

    # Dados do processo
    objetoContrato: str
    processo: Optional[str] = None

    # Relacionamentos
    tipoContrato: TipoContratoResponse
    orgaoEntidade: OrgaoEntidadeResponse
    unidadeOrgao: Optional[UnidadeOrgaoResponse] = None
    categoriaProcesso: Optional[CategoriaProcessoResponse] = None

    # Outros
    informacaoComplementar: Optional[str] = None
    numeroParcelas: Optional[int] = None
    numeroRetificacao: int = 0

    class Config:
        from_attributes = True


class ItemLicitacaoResponse(BaseModel):
    """Schema de resposta de item de licitação"""
    id: str
    numero_item: int
    descricao: str
    quantidade: Decimal
    unidade: str
    valor_unitario: Optional[Decimal] = None
    valor_total: Optional[Decimal] = None

    class Config:
        from_attributes = True


class LicitacaoResponse(BaseModel):
    """Schema de resposta de licitação completa"""
    id: str
    pncp_id: str
    numero: str
    objeto: str
    modalidade: str
    situacao: str

    # Valores
    valor_estimado: Optional[Decimal] = None

    # Datas
    data_publicacao: datetime
    data_abertura: Optional[datetime] = None
    data_encerramento: Optional[datetime] = None

    # Localização
    uf: str
    municipio: str

    # Órgão
    nome_orgao: str
    cnpj_orgao: str

    # Links
    url_edital: Optional[str] = None
    url_pncp: str

    # Metadados
    sincronizado_em: datetime
    atualizado_em: datetime

    # Relacionamentos (opcional)
    itens: Optional[List[ItemLicitacaoResponse]] = None

    class Config:
        from_attributes = True


class LicitacaoListResponse(BaseModel):
    """Schema de resposta de lista de licitações com paginação"""
    data: List[LicitacaoResponse]
    total: int
    pagina: int
    tamanho_pagina: int
    total_paginas: int


class LicitacaoDetalhesResponse(LicitacaoResponse):
    """Schema de resposta de detalhes completos de licitação"""
    itens: List[ItemLicitacaoResponse] = []

    class Config:
        from_attributes = True


# ========== Schemas para criação/atualização (interno) ==========

class LicitacaoCreate(BaseModel):
    """Schema para criar licitação no banco"""
    pncp_id: str
    orgao_id: str
    numero: str
    objeto: str
    modalidade: str
    situacao: str
    valor_estimado: Optional[Decimal] = None
    data_publicacao: datetime
    data_abertura: Optional[datetime] = None
    data_encerramento: Optional[datetime] = None
    uf: str
    municipio: str
    url_edital: Optional[str] = None
    url_pncp: str
    nome_orgao: str
    cnpj_orgao: str


class ItemLicitacaoCreate(BaseModel):
    """Schema para criar item de licitação"""
    licitacao_id: str
    numero_item: int
    descricao: str
    quantidade: Decimal
    unidade: str
    valor_unitario: Optional[Decimal] = None
    valor_total: Optional[Decimal] = None


# ========== Schemas de estatísticas ==========

class EstatisticasResponse(BaseModel):
    """Schema de estatísticas de licitações"""
    total_licitacoes: int
    total_valor: Decimal
    por_uf: dict
    por_modalidade: dict
    por_situacao: dict
