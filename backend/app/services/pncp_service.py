"""
Serviço para integração com API do PNCP (Portal Nacional de Contratações Públicas)
"""
import httpx
from typing import List, Optional, Dict, Any
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)


class PNCPService:
    """
    Serviço para integração com API pública do PNCP
    Documentação: https://pncp.gov.br/api/pncp/swagger-ui/index.html
    """

    BASE_URL = "https://pncp.gov.br/api/consulta/v1"

    def __init__(self):
        self.client = httpx.AsyncClient(
            base_url=self.BASE_URL,
            timeout=30.0,
            limits=httpx.Limits(
                max_keepalive_connections=5,
                max_connections=10
            )
        )

    async def buscar_contratos(
        self,
        data_inicial: str,
        data_final: str,
        pagina: int = 1,
        tamanho_pagina: int = 500,
        cnpj_orgao: Optional[str] = None,
        uf: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Busca contratos no PNCP

        Args:
            data_inicial: Data inicial no formato YYYYMMDD (ex: 20250101)
            data_final: Data final no formato YYYYMMDD
            pagina: Número da página (padrão: 1)
            tamanho_pagina: Quantidade de registros por página (10-500)
            cnpj_orgao: CNPJ do órgão (opcional)
            uf: Sigla da UF (opcional)

        Returns:
            Dict com dados de contratos e paginação
        """
        try:
            params = {
                "dataInicial": data_inicial,
                "dataFinal": data_final,
                "pagina": pagina,
                "tamanhoPagina": min(tamanho_pagina, 500)  # Máximo 500
            }

            if cnpj_orgao:
                params["cnpjOrgao"] = cnpj_orgao
            if uf:
                params["uf"] = uf

            logger.info(f"Buscando contratos PNCP: {params}")

            response = await self.client.get("/contratos", params=params)
            response.raise_for_status()

            data = response.json()
            return data

        except httpx.HTTPStatusError as e:
            logger.error(f"Erro HTTP ao buscar contratos PNCP: {e.response.status_code} - {e.response.text}")
            raise
        except Exception as e:
            logger.error(f"Erro ao buscar contratos PNCP: {e}")
            raise

    async def buscar_contrato_detalhes(
        self,
        cnpj: str,
        ano: int,
        sequencial: int
    ) -> Dict[str, Any]:
        """
        Busca detalhes de um contrato específico

        Args:
            cnpj: CNPJ do órgão
            ano: Ano do contrato
            sequencial: Número sequencial do contrato

        Returns:
            Dict com detalhes do contrato
        """
        try:
            url = f"/contratos/{cnpj}/{ano}/{sequencial}"
            logger.info(f"Buscando detalhes do contrato: {url}")

            response = await self.client.get(url)
            response.raise_for_status()

            return response.json()

        except httpx.HTTPStatusError as e:
            logger.error(f"Erro ao buscar detalhes do contrato: {e.response.status_code}")
            raise
        except Exception as e:
            logger.error(f"Erro ao buscar detalhes do contrato: {e}")
            raise

    async def buscar_itens_contrato(
        self,
        cnpj: str,
        ano: int,
        sequencial: int
    ) -> List[Dict[str, Any]]:
        """
        Busca itens de um contrato específico

        Args:
            cnpj: CNPJ do órgão
            ano: Ano do contrato
            sequencial: Número sequencial do contrato

        Returns:
            Lista de itens do contrato
        """
        try:
            url = f"/contratos/{cnpj}/{ano}/{sequencial}/itens"
            logger.info(f"Buscando itens do contrato: {url}")

            response = await self.client.get(url)
            response.raise_for_status()

            data = response.json()
            return data.get("data", [])

        except httpx.HTTPStatusError as e:
            if e.response.status_code == 404:
                logger.warning(f"Itens não encontrados para contrato {cnpj}/{ano}/{sequencial}")
                return []
            logger.error(f"Erro ao buscar itens: {e.response.status_code}")
            raise
        except Exception as e:
            logger.error(f"Erro ao buscar itens: {e}")
            raise

    async def buscar_compras(
        self,
        data_inicial: str,
        data_final: str,
        pagina: int = 1,
        tamanho_pagina: int = 500,
        modalidade: Optional[str] = None,
        uf: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Busca processos de compra/licitações no PNCP

        Args:
            data_inicial: Data inicial no formato YYYYMMDD
            data_final: Data final no formato YYYYMMDD
            pagina: Número da página
            tamanho_pagina: Quantidade de registros (10-500)
            modalidade: Código da modalidade (opcional)
            uf: Sigla da UF (opcional)

        Returns:
            Dict com dados de compras e paginação
        """
        try:
            params = {
                "dataInicial": data_inicial,
                "dataFinal": data_final,
                "pagina": pagina,
                "tamanhoPagina": min(tamanho_pagina, 500)
            }

            if modalidade:
                params["codigoModalidade"] = modalidade
            if uf:
                params["uf"] = uf

            logger.info(f"Buscando compras PNCP: {params}")

            response = await self.client.get("/compras", params=params)
            response.raise_for_status()

            return response.json()

        except httpx.HTTPStatusError as e:
            logger.error(f"Erro ao buscar compras: {e.response.status_code} - {e.response.text}")
            raise
        except Exception as e:
            logger.error(f"Erro ao buscar compras: {e}")
            raise

    async def buscar_compra_detalhes(
        self,
        cnpj: str,
        ano: int,
        sequencial: int
    ) -> Dict[str, Any]:
        """
        Busca detalhes de uma compra específica

        Args:
            cnpj: CNPJ do órgão
            ano: Ano da compra
            sequencial: Número sequencial da compra

        Returns:
            Dict com detalhes da compra
        """
        try:
            url = f"/compras/{cnpj}/{ano}/{sequencial}"
            logger.info(f"Buscando detalhes da compra: {url}")

            response = await self.client.get(url)
            response.raise_for_status()

            return response.json()

        except httpx.HTTPStatusError as e:
            logger.error(f"Erro ao buscar detalhes da compra: {e.response.status_code}")
            raise
        except Exception as e:
            logger.error(f"Erro ao buscar detalhes da compra: {e}")
            raise

    def formatar_data_pncp(self, data: datetime) -> str:
        """
        Formata datetime para o formato aceito pelo PNCP (YYYYMMDD)

        Args:
            data: Objeto datetime

        Returns:
            String no formato YYYYMMDD
        """
        return data.strftime("%Y%m%d")

    def obter_periodo_ultima_semana(self) -> tuple[str, str]:
        """
        Retorna datas (inicial e final) da última semana no formato PNCP

        Returns:
            Tupla (data_inicial, data_final) em formato YYYYMMDD
        """
        hoje = datetime.now()
        semana_atras = hoje - timedelta(days=7)
        return (
            self.formatar_data_pncp(semana_atras),
            self.formatar_data_pncp(hoje)
        )

    def obter_periodo_ultimo_mes(self) -> tuple[str, str]:
        """
        Retorna datas (inicial e final) do último mês no formato PNCP

        Returns:
            Tupla (data_inicial, data_final) em formato YYYYMMDD
        """
        hoje = datetime.now()
        mes_atras = hoje - timedelta(days=30)
        return (
            self.formatar_data_pncp(mes_atras),
            self.formatar_data_pncp(hoje)
        )

    async def close(self):
        """Fecha a conexão HTTP"""
        await self.client.aclose()

    async def __aenter__(self):
        """Context manager async enter"""
        return self

    async def __aexit__(self, exc_type, exc_val, exc_tb):
        """Context manager async exit"""
        await self.close()
