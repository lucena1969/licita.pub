"""
Service com lógica de negócio para licitações
"""
from sqlalchemy.orm import Session
from app.models.licitacao import Licitacao
from app.models.item_licitacao import ItemLicitacao
from app.repositories.licitacao_repository import LicitacaoRepository, ItemLicitacaoRepository
from app.services.pncp_service import PNCPService
from app.schemas.licitacao import LicitacaoFiltros
from datetime import datetime
from typing import List, Tuple, Dict, Any, Optional
from decimal import Decimal
import logging
import uuid

logger = logging.getLogger(__name__)


class LicitacaoService:
    """Service para lógica de negócio de licitações"""

    def __init__(self, db: Session):
        self.repository = LicitacaoRepository(db)
        self.item_repository = ItemLicitacaoRepository(db)
        self.db = db

    def listar_licitacoes(
        self,
        filtros: LicitacaoFiltros
    ) -> Tuple[List[Licitacao], int, int]:
        """
        Lista licitações com filtros e paginação

        Returns:
            Tupla (licitações, total_registros, total_paginas)
        """
        skip = (filtros.pagina - 1) * filtros.tamanho_pagina

        # Converter datas se fornecidas
        data_inicial = None
        data_final = None

        if filtros.data_inicial:
            try:
                data_inicial = datetime.strptime(filtros.data_inicial, "%Y%m%d")
            except ValueError:
                pass

        if filtros.data_final:
            try:
                data_final = datetime.strptime(filtros.data_final, "%Y%m%d")
            except ValueError:
                pass

        licitacoes, total = self.repository.listar(
            skip=skip,
            limit=filtros.tamanho_pagina,
            uf=filtros.uf,
            municipio=filtros.municipio,
            modalidade=filtros.modalidade,
            situacao=filtros.situacao,
            valor_min=filtros.valor_min,
            valor_max=filtros.valor_max,
            cnpj_orgao=filtros.cnpj_orgao,
            data_inicial=data_inicial,
            data_final=data_final,
            termo_busca=filtros.termo_busca
        )

        # Calcular total de páginas
        total_paginas = (total + filtros.tamanho_pagina - 1) // filtros.tamanho_pagina

        return licitacoes, total, total_paginas

    def buscar_por_id(self, licitacao_id: str) -> Optional[Licitacao]:
        """Busca licitação por ID"""
        return self.repository.buscar_por_id(licitacao_id)

    def buscar_recentes(self, limit: int = 10) -> List[Licitacao]:
        """Busca licitações mais recentes"""
        return self.repository.buscar_recentes(limit)

    def buscar_itens_licitacao(self, licitacao_id: str) -> List[ItemLicitacao]:
        """Busca itens de uma licitação"""
        return self.item_repository.buscar_por_licitacao(licitacao_id)

    def obter_estatisticas(self) -> Dict[str, Any]:
        """Obtém estatísticas gerais de licitações"""
        total = self.repository.contar_total()
        valor_total = self.repository.somar_valores_total()

        por_uf = dict(self.repository.contar_por_uf())
        por_modalidade = dict(self.repository.contar_por_modalidade())
        por_situacao = dict(self.repository.contar_por_situacao())

        return {
            "total_licitacoes": total,
            "total_valor": float(valor_total),
            "por_uf": por_uf,
            "por_modalidade": por_modalidade,
            "por_situacao": por_situacao
        }

    def obter_filtros_disponiveis(self) -> Dict[str, List[str]]:
        """Retorna opções disponíveis para filtros"""
        return {
            "modalidades": self.repository.buscar_modalidades_disponiveis(),
            "ufs": self.repository.buscar_ufs_disponiveis()
        }

    def obter_municipios_por_uf(self, uf: str) -> List[str]:
        """Lista municípios de uma UF"""
        return self.repository.buscar_municipios_por_uf(uf)

    async def sincronizar_do_pncp(
        self,
        data_inicial: str,
        data_final: str,
        max_paginas: int = 10
    ) -> Dict[str, int]:
        """
        Sincroniza licitações/contratos do PNCP para o banco local

        Args:
            data_inicial: Data inicial no formato YYYYMMDD
            data_final: Data final no formato YYYYMMDD
            max_paginas: Máximo de páginas a buscar (proteção)

        Returns:
            Dict com estatísticas da sincronização
        """
        novos = 0
        atualizados = 0
        erros = 0

        async with PNCPService() as pncp:
            try:
                pagina = 1

                while pagina <= max_paginas:
                    logger.info(f"Sincronizando página {pagina} do PNCP...")

                    # Buscar contratos do PNCP
                    resultado = await pncp.buscar_contratos(
                        data_inicial=data_inicial,
                        data_final=data_final,
                        pagina=pagina,
                        tamanho_pagina=500
                    )

                    contratos = resultado.get("data", [])

                    if not contratos:
                        logger.info("Nenhum contrato encontrado, finalizando.")
                        break

                    # Processar cada contrato
                    for contrato in contratos:
                        try:
                            resultado_save = await self._salvar_contrato_pncp(contrato)
                            if resultado_save == "novo":
                                novos += 1
                            elif resultado_save == "atualizado":
                                atualizados += 1
                        except Exception as e:
                            logger.error(f"Erro ao salvar contrato {contrato.get('numeroControlePNCP')}: {e}")
                            erros += 1
                            continue

                    pagina += 1

                logger.info(f"Sincronização concluída: {novos} novos, {atualizados} atualizados, {erros} erros")

                return {
                    "novos": novos,
                    "atualizados": atualizados,
                    "erros": erros,
                    "total_processados": novos + atualizados + erros
                }

            except Exception as e:
                logger.error(f"Erro na sincronização: {e}")
                raise

    async def _salvar_contrato_pncp(self, contrato_data: dict) -> str:
        """
        Salva ou atualiza um contrato do PNCP no banco

        Returns:
            "novo" ou "atualizado"
        """
        pncp_id = contrato_data.get("numeroControlePNCP")

        if not pncp_id:
            raise ValueError("numeroControlePNCP não encontrado")

        # Verificar se já existe
        licitacao_existente = self.repository.buscar_por_pncp_id(pncp_id)

        # Extrair dados
        unidade = contrato_data.get("unidadeOrgao", {})
        orgao = contrato_data.get("orgaoEntidade", {})

        # Converter datas
        data_publicacao = datetime.fromisoformat(contrato_data["dataPublicacaoPncp"].replace("Z", "+00:00"))

        data_abertura = None
        if contrato_data.get("dataAssinatura"):
            try:
                data_abertura = datetime.strptime(contrato_data["dataAssinatura"], "%Y-%m-%d")
            except:
                pass

        data_encerramento = None
        if contrato_data.get("dataVigenciaFim"):
            try:
                data_encerramento = datetime.strptime(contrato_data["dataVigenciaFim"], "%Y-%m-%d")
            except:
                pass

        if licitacao_existente:
            # Atualizar
            licitacao_existente.objeto = contrato_data.get("objetoContrato", "")
            licitacao_existente.valor_estimado = Decimal(str(contrato_data.get("valorGlobal", 0)))
            licitacao_existente.situacao = "ATIVO" if data_encerramento and data_encerramento > datetime.now() else "CONCLUIDO"
            licitacao_existente.data_encerramento = data_encerramento

            self.repository.atualizar(licitacao_existente)
            return "atualizado"
        else:
            # Criar novo
            nova_licitacao = Licitacao(
                id=str(uuid.uuid4()),
                pncp_id=pncp_id,
                orgao_id=orgao.get("cnpj", ""),
                numero=contrato_data.get("numeroContratoEmpenho", ""),
                objeto=contrato_data.get("objetoContrato", ""),
                modalidade=contrato_data.get("tipoContrato", {}).get("nome", "CONTRATO"),
                situacao="ATIVO" if data_encerramento and data_encerramento > datetime.now() else "CONCLUIDO",
                valor_estimado=Decimal(str(contrato_data.get("valorGlobal", 0))),
                data_publicacao=data_publicacao,
                data_abertura=data_abertura,
                data_encerramento=data_encerramento,
                uf=unidade.get("ufSigla", ""),
                municipio=unidade.get("municipioNome", ""),
                url_edital=None,
                url_pncp=f"https://pncp.gov.br/app/contratos/{pncp_id}",
                nome_orgao=orgao.get("razaoSocial", ""),
                cnpj_orgao=orgao.get("cnpj", "")
            )

            self.repository.criar(nova_licitacao)
            return "novo"
