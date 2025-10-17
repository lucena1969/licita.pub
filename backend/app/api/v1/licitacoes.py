"""
Endpoints de licitações/contratos
"""
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.schemas.licitacao import (
    LicitacaoResponse,
    LicitacaoListResponse,
    LicitacaoDetalhesResponse,
    LicitacaoFiltros,
    EstatisticasResponse,
    ItemLicitacaoResponse
)
from app.services.licitacao_service import LicitacaoService
from app.api.deps import get_current_user_optional
from app.models.usuario import Usuario
from typing import Optional, List
import math

router = APIRouter(prefix="/licitacoes", tags=["Licitações"])


@router.get("", response_model=LicitacaoListResponse)
async def listar_licitacoes(
    # Filtros de busca
    data_inicial: Optional[str] = Query(None, description="Data inicial YYYYMMDD"),
    data_final: Optional[str] = Query(None, description="Data final YYYYMMDD"),
    uf: Optional[str] = Query(None, max_length=2, description="Sigla da UF"),
    municipio: Optional[str] = Query(None, description="Nome do município"),
    modalidade: Optional[str] = Query(None, description="Modalidade"),
    situacao: Optional[str] = Query(None, description="Situação"),
    valor_min: Optional[float] = Query(None, description="Valor mínimo"),
    valor_max: Optional[float] = Query(None, description="Valor máximo"),
    cnpj_orgao: Optional[str] = Query(None, description="CNPJ do órgão"),
    termo_busca: Optional[str] = Query(None, description="Termo para busca"),

    # Paginação
    pagina: int = Query(1, ge=1, description="Número da página"),
    tamanho_pagina: int = Query(20, ge=10, le=100, description="Itens por página"),

    # Dependências
    db: Session = Depends(get_db),
    current_user: Optional[Usuario] = Depends(get_current_user_optional)
):
    """
    Lista licitações com filtros e paginação

    **Filtros disponíveis:**
    - data_inicial/data_final: Período de publicação (formato YYYYMMDD)
    - uf: Sigla da UF (ex: SP, RJ)
    - municipio: Nome do município
    - modalidade: Modalidade da licitação
    - situacao: Situação (ATIVO, CONCLUIDO, etc)
    - valor_min/valor_max: Faixa de valores
    - cnpj_orgao: CNPJ do órgão
    - termo_busca: Busca no objeto, número ou nome do órgão

    **Acesso público** (não requer autenticação)
    """
    filtros = LicitacaoFiltros(
        data_inicial=data_inicial,
        data_final=data_final,
        uf=uf,
        municipio=municipio,
        modalidade=modalidade,
        situacao=situacao,
        valor_min=valor_min,
        valor_max=valor_max,
        cnpj_orgao=cnpj_orgao,
        termo_busca=termo_busca,
        pagina=pagina,
        tamanho_pagina=tamanho_pagina
    )

    service = LicitacaoService(db)
    licitacoes, total, total_paginas = service.listar_licitacoes(filtros)

    return LicitacaoListResponse(
        data=[LicitacaoResponse.model_validate(lic) for lic in licitacoes],
        total=total,
        pagina=pagina,
        tamanho_pagina=tamanho_pagina,
        total_paginas=total_paginas
    )


@router.get("/recentes", response_model=List[LicitacaoResponse])
async def listar_recentes(
    limit: int = Query(10, ge=1, le=50, description="Quantidade de resultados"),
    db: Session = Depends(get_db)
):
    """
    Lista licitações mais recentes

    **Acesso público** (não requer autenticação)
    """
    service = LicitacaoService(db)
    licitacoes = service.buscar_recentes(limit)

    return [LicitacaoResponse.model_validate(lic) for lic in licitacoes]


@router.get("/estatisticas", response_model=EstatisticasResponse)
async def obter_estatisticas(
    db: Session = Depends(get_db)
):
    """
    Obtém estatísticas gerais de licitações

    Retorna:
    - Total de licitações
    - Valor total
    - Distribuição por UF
    - Distribuição por modalidade
    - Distribuição por situação

    **Acesso público** (não requer autenticação)
    """
    service = LicitacaoService(db)
    stats = service.obter_estatisticas()

    return EstatisticasResponse(**stats)


@router.get("/filtros", response_model=dict)
async def obter_filtros_disponiveis(
    db: Session = Depends(get_db)
):
    """
    Retorna opções disponíveis para filtros

    Útil para popular selects/dropdowns no frontend

    **Acesso público** (não requer autenticação)
    """
    service = LicitacaoService(db)
    return service.obter_filtros_disponiveis()


@router.get("/municipios/{uf}", response_model=List[str])
async def listar_municipios_por_uf(
    uf: str,
    db: Session = Depends(get_db)
):
    """
    Lista municípios de uma UF específica

    **Acesso público** (não requer autenticação)
    """
    service = LicitacaoService(db)
    return service.obter_municipios_por_uf(uf.upper())


@router.get("/{licitacao_id}", response_model=LicitacaoDetalhesResponse)
async def obter_detalhes(
    licitacao_id: str,
    db: Session = Depends(get_db)
):
    """
    Obtém detalhes completos de uma licitação

    Inclui itens da licitação

    **Acesso público** (não requer autenticação)
    """
    service = LicitacaoService(db)
    licitacao = service.buscar_por_id(licitacao_id)

    if not licitacao:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Licitação não encontrada"
        )

    # Buscar itens
    itens = service.buscar_itens_licitacao(licitacao_id)

    # Montar resposta
    response_data = LicitacaoResponse.model_validate(licitacao)

    return LicitacaoDetalhesResponse(
        **response_data.model_dump(),
        itens=[ItemLicitacaoResponse.model_validate(item) for item in itens]
    )


@router.post("/sincronizar", response_model=dict)
async def sincronizar_pncp(
    data_inicial: str = Query(..., description="Data inicial YYYYMMDD"),
    data_final: str = Query(..., description="Data final YYYYMMDD"),
    max_paginas: int = Query(10, ge=1, le=50, description="Máximo de páginas"),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user_optional)  # Pode ser público ou autenticado
):
    """
    Sincroniza licitações do PNCP para o banco local

    **Parâmetros:**
    - data_inicial: Data inicial no formato YYYYMMDD (ex: 20250101)
    - data_final: Data final no formato YYYYMMDD
    - max_paginas: Máximo de páginas a buscar (proteção, padrão 10)

    **Exemplo:**
    ```
    POST /api/v1/licitacoes/sincronizar?data_inicial=20250101&data_final=20250117&max_paginas=5
    ```

    **Nota:** Este endpoint pode demorar dependendo da quantidade de dados
    """
    service = LicitacaoService(db)

    resultado = await service.sincronizar_do_pncp(
        data_inicial=data_inicial,
        data_final=data_final,
        max_paginas=max_paginas
    )

    return {
        "message": "Sincronização concluída",
        "resultado": resultado
    }
