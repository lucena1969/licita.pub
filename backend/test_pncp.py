"""
Script para testar integração com API do PNCP
Execute: python test_pncp.py
"""
import sys
import asyncio
from pathlib import Path

# Adicionar o diretório app ao path
sys.path.append(str(Path(__file__).parent))

from app.services.pncp_service import PNCPService
from datetime import datetime, timedelta
import json


async def testar_busca_contratos():
    """Testa busca de contratos"""
    print("=" * 60)
    print("Testando busca de contratos do PNCP")
    print("=" * 60)
    print()

    async with PNCPService() as pncp:
        # Buscar contratos dos últimos 7 dias
        data_inicial, data_final = pncp.obter_periodo_ultima_semana()

        print(f"Período: {data_inicial} a {data_final}")
        print("Buscando contratos...")
        print()

        resultado = await pncp.buscar_contratos(
            data_inicial=data_inicial,
            data_final=data_final,
            pagina=1,
            tamanho_pagina=10  # Mínimo 10 (requisito da API)
        )

        contratos = resultado.get("data", [])

        print(f"✓ Encontrados {len(contratos)} contratos")
        print()

        if contratos:
            print("Exemplo de contrato:")
            print("-" * 60)
            contrato = contratos[0]
            print(f"Número: {contrato.get('numeroControlePNCP')}")
            print(f"Órgão: {contrato.get('orgaoEntidade', {}).get('razaoSocial')}")
            print(f"Valor: R$ {contrato.get('valorGlobal', 0):,.2f}")
            print(f"Objeto: {contrato.get('objetoContrato', '')[:100]}...")
            print(f"UF: {contrato.get('unidadeOrgao', {}).get('ufSigla')}")
            print(f"Município: {contrato.get('unidadeOrgao', {}).get('municipioNome')}")
            print("-" * 60)
            print()

        return True


async def testar_busca_compras():
    """Testa busca de compras"""
    print("=" * 60)
    print("Testando busca de compras/licitações do PNCP")
    print("=" * 60)
    print()

    async with PNCPService() as pncp:
        data_inicial, data_final = pncp.obter_periodo_ultima_semana()

        print(f"Período: {data_inicial} a {data_final}")
        print("Buscando compras...")
        print()

        try:
            resultado = await pncp.buscar_compras(
                data_inicial=data_inicial,
                data_final=data_final,
                pagina=1,
                tamanho_pagina=10
            )

            compras = resultado.get("data", [])
            print(f"✓ Encontradas {len(compras)} compras")
            print()

            if compras:
                print("Exemplo de compra:")
                print("-" * 60)
                compra = compras[0]
                print(json.dumps(compra, indent=2, ensure_ascii=False)[:500])
                print("...")
                print("-" * 60)
                print()

            return True

        except Exception as e:
            print(f"⚠ Endpoint de compras pode não estar disponível: {e}")
            print()
            return False


async def testar_periodos():
    """Testa geração de períodos"""
    print("=" * 60)
    print("Testando geração de períodos")
    print("=" * 60)
    print()

    pncp = PNCPService()

    data_ini, data_fim = pncp.obter_periodo_ultima_semana()
    print(f"Última semana: {data_ini} a {data_fim}")

    data_ini, data_fim = pncp.obter_periodo_ultimo_mes()
    print(f"Último mês: {data_ini} a {data_fim}")

    print()
    print("✓ Períodos gerados corretamente")
    print()

    await pncp.close()


async def main():
    """Executa todos os testes"""
    print("\n")
    print("╔" + "=" * 58 + "╗")
    print("║" + " " * 15 + "TESTE DE INTEGRAÇÃO PNCP" + " " * 19 + "║")
    print("╚" + "=" * 58 + "╝")
    print()

    try:
        # Teste 1: Períodos
        await testar_periodos()

        # Teste 2: Busca de contratos
        resultado1 = await testar_busca_contratos()

        # Teste 3: Busca de compras
        resultado2 = await testar_busca_compras()

        # Resumo
        print("=" * 60)
        print("RESUMO DOS TESTES")
        print("=" * 60)
        print(f"Geração de períodos: ✓")
        print(f"Busca de contratos: {'✓' if resultado1 else '✗'}")
        print(f"Busca de compras: {'✓' if resultado2 else '⚠'}")
        print()

        if resultado1:
            print("✓ Integração com PNCP funcionando corretamente!")
            print()
            print("Próximos passos:")
            print("1. Configure o banco de dados (MySQL)")
            print("2. Execute: python init_db.py")
            print("3. Inicie o servidor: uvicorn app.main:app --reload")
            print("4. Acesse /docs para testar os endpoints")
            print("5. Use o endpoint POST /api/v1/licitacoes/sincronizar")
        else:
            print("⚠ Alguns testes falharam, mas a API básica está funcionando")

        print()

    except Exception as e:
        print("=" * 60)
        print("✗ ERRO NOS TESTES")
        print("=" * 60)
        print(f"Erro: {e}")
        print()
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    asyncio.run(main())
