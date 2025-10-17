"""
Script para criar todas as tabelas no banco de dados
Execute: python init_db.py
"""
import sys
from pathlib import Path

# Adicionar o diretório app ao path
sys.path.append(str(Path(__file__).parent))

from app.core.database import engine, Base
from app.models import (
    Usuario,
    Licitacao,
    ItemLicitacao,
    Favorito,
    Alerta,
    HistoricoBusca,
    LogSincronizacao
)


def create_tables():
    """Cria todas as tabelas no banco de dados"""
    print("=" * 60)
    print("Criando tabelas no banco de dados...")
    print("=" * 60)
    print()

    try:
        # Importa todos os models para garantir que estão registrados
        print("Models carregados:")
        print("  - Usuario")
        print("  - Licitacao")
        print("  - ItemLicitacao")
        print("  - Favorito")
        print("  - Alerta")
        print("  - HistoricoBusca")
        print("  - LogSincronizacao")
        print()

        # Cria todas as tabelas
        Base.metadata.create_all(bind=engine)

        print("✓ Tabelas criadas com sucesso!")
        print()
        print("=" * 60)
        print("Banco de dados inicializado!")
        print("=" * 60)

    except Exception as e:
        print(f"✗ Erro ao criar tabelas: {e}")
        sys.exit(1)


def drop_tables():
    """Remove todas as tabelas (cuidado!)"""
    print("=" * 60)
    print("ATENÇÃO: Removendo todas as tabelas...")
    print("=" * 60)
    Base.metadata.drop_all(bind=engine)
    print("✓ Tabelas removidas!")


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="Gerenciar banco de dados")
    parser.add_argument(
        "--drop",
        action="store_true",
        help="Remove todas as tabelas antes de criar (CUIDADO!)"
    )

    args = parser.parse_args()

    if args.drop:
        resposta = input("Tem certeza que deseja REMOVER todas as tabelas? (sim/não): ")
        if resposta.lower() == "sim":
            drop_tables()
            print()
        else:
            print("Operação cancelada.")
            sys.exit(0)

    create_tables()
