"""
Job para sincronização automática com PNCP
"""
from datetime import datetime, timedelta
from app.services.licitacao_service import LicitacaoService
from app.services.pncp_service import PNCPService
from app.models.log_sincronizacao import LogSincronizacao
from app.core.database import SessionLocal
import logging
import asyncio

logger = logging.getLogger(__name__)


async def sincronizar_licitacoes_diarias():
    """
    Job para sincronizar licitações do dia anterior

    Pode ser executado diariamente via cron ou scheduler
    """
    inicio = datetime.now()
    db = SessionLocal()

    try:
        logger.info("=" * 60)
        logger.info("Iniciando sincronização diária do PNCP")
        logger.info("=" * 60)

        # Buscar dados das últimas 24 horas
        hoje = datetime.now()
        ontem = hoje - timedelta(days=1)

        data_inicial = ontem.strftime("%Y%m%d")
        data_final = hoje.strftime("%Y%m%d")

        logger.info(f"Período: {data_inicial} a {data_final}")

        # Executar sincronização
        service = LicitacaoService(db)
        resultado = await service.sincronizar_do_pncp(
            data_inicial=data_inicial,
            data_final=data_final,
            max_paginas=20  # Limitar para não sobrecarregar
        )

        # Calcular duração
        duracao = int((datetime.now() - inicio).total_seconds())

        # Registrar log de sucesso
        log = LogSincronizacao(
            fonte="PNCP",
            tipo="contratos",
            status="sucesso",
            registros_novos=resultado["novos"],
            registros_atualizados=resultado["atualizados"],
            registros_erro=resultado["erros"],
            mensagem=f"Sincronização concluída com sucesso",
            detalhes=resultado,
            iniciado=inicio,
            finalizado=datetime.now(),
            duracao=duracao
        )
        db.add(log)
        db.commit()

        logger.info("=" * 60)
        logger.info(f"Sincronização concluída em {duracao}s")
        logger.info(f"Novos: {resultado['novos']}")
        logger.info(f"Atualizados: {resultado['atualizados']}")
        logger.info(f"Erros: {resultado['erros']}")
        logger.info("=" * 60)

        return resultado

    except Exception as e:
        logger.error(f"Erro na sincronização: {e}", exc_info=True)

        # Registrar log de erro
        duracao = int((datetime.now() - inicio).total_seconds())
        log = LogSincronizacao(
            fonte="PNCP",
            tipo="contratos",
            status="erro",
            registros_novos=0,
            registros_atualizados=0,
            registros_erro=0,
            mensagem=f"Erro na sincronização: {str(e)}",
            iniciado=inicio,
            finalizado=datetime.now(),
            duracao=duracao
        )
        db.add(log)
        db.commit()

        raise

    finally:
        db.close()


async def sincronizar_licitacoes_semanais():
    """
    Job para sincronização semanal (últimos 7 dias)

    Útil para garantir que nada foi perdido
    """
    inicio = datetime.now()
    db = SessionLocal()

    try:
        logger.info("=" * 60)
        logger.info("Iniciando sincronização semanal do PNCP")
        logger.info("=" * 60)

        # Buscar dados dos últimos 7 dias
        hoje = datetime.now()
        semana_atras = hoje - timedelta(days=7)

        data_inicial = semana_atras.strftime("%Y%m%d")
        data_final = hoje.strftime("%Y%m%d")

        logger.info(f"Período: {data_inicial} a {data_final}")

        # Executar sincronização
        service = LicitacaoService(db)
        resultado = await service.sincronizar_do_pncp(
            data_inicial=data_inicial,
            data_final=data_final,
            max_paginas=50  # Mais páginas para sincronização semanal
        )

        # Calcular duração
        duracao = int((datetime.now() - inicio).total_seconds())

        # Registrar log
        log = LogSincronizacao(
            fonte="PNCP",
            tipo="contratos_semanal",
            status="sucesso",
            registros_novos=resultado["novos"],
            registros_atualizados=resultado["atualizados"],
            registros_erro=resultado["erros"],
            mensagem=f"Sincronização semanal concluída",
            detalhes=resultado,
            iniciado=inicio,
            finalizado=datetime.now(),
            duracao=duracao
        )
        db.add(log)
        db.commit()

        logger.info("=" * 60)
        logger.info(f"Sincronização semanal concluída em {duracao}s")
        logger.info(f"Total processado: {resultado['total_processados']}")
        logger.info("=" * 60)

        return resultado

    except Exception as e:
        logger.error(f"Erro na sincronização semanal: {e}", exc_info=True)

        duracao = int((datetime.now() - inicio).total_seconds())
        log = LogSincronizacao(
            fonte="PNCP",
            tipo="contratos_semanal",
            status="erro",
            registros_novos=0,
            registros_atualizados=0,
            registros_erro=0,
            mensagem=f"Erro: {str(e)}",
            iniciado=inicio,
            finalizado=datetime.now(),
            duracao=duracao
        )
        db.add(log)
        db.commit()

        raise

    finally:
        db.close()


def executar_sincronizacao_diaria():
    """Wrapper síncrono para executar o job assíncrono"""
    return asyncio.run(sincronizar_licitacoes_diarias())


def executar_sincronizacao_semanal():
    """Wrapper síncrono para executar o job assíncrono"""
    return asyncio.run(sincronizar_licitacoes_semanais())


if __name__ == "__main__":
    # Permite executar o job manualmente
    import sys

    if len(sys.argv) > 1 and sys.argv[1] == "semanal":
        print("Executando sincronização semanal...")
        executar_sincronizacao_semanal()
    else:
        print("Executando sincronização diária...")
        executar_sincronizacao_diaria()
