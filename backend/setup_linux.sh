#!/bin/bash
# ====================================================================================================
# LICITA.PUB - SETUP AUTOMÁTICO LINUX/MAC
# ====================================================================================================
# Descrição: Script para configurar automaticamente o ambiente de desenvolvimento
# Requisitos: Python 3.11+ instalado
# ====================================================================================================

echo "===================================================================================================="
echo "  LICITA.PUB - SETUP AUTOMÁTICO"
echo "===================================================================================================="
echo ""

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se está na pasta backend
if [ ! -d "app" ]; then
    echo -e "${RED}ERRO: Execute este script dentro da pasta 'backend'${NC}"
    echo "Exemplo: cd backend"
    echo "         ./setup_linux.sh"
    exit 1
fi

echo "[1/6] Verificando Python..."
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}ERRO: Python3 não encontrado!${NC}"
    echo "Instale Python 3.11+ primeiro"
    exit 1
fi
python3 --version
echo -e "${GREEN}OK - Python encontrado!${NC}"
echo ""

echo "[2/6] Criando ambiente virtual (venv)..."
if [ -d "venv" ]; then
    echo -e "${YELLOW}Ambiente virtual já existe. Pulando criação.${NC}"
else
    python3 -m venv venv
    if [ $? -ne 0 ]; then
        echo -e "${RED}ERRO ao criar ambiente virtual!${NC}"
        exit 1
    fi
    echo -e "${GREEN}OK - Ambiente virtual criado!${NC}"
fi
echo ""

echo "[3/6] Ativando ambiente virtual..."
source venv/bin/activate
if [ $? -ne 0 ]; then
    echo -e "${RED}ERRO ao ativar ambiente virtual!${NC}"
    exit 1
fi
echo -e "${GREEN}OK - Ambiente virtual ativado!${NC}"
echo ""

echo "[4/6] Instalando dependências (pode demorar alguns minutos)..."
pip install --upgrade pip
pip install -r requirements.txt
if [ $? -ne 0 ]; then
    echo -e "${RED}ERRO ao instalar dependências!${NC}"
    exit 1
fi
echo -e "${GREEN}OK - Dependências instaladas!${NC}"
echo ""

echo "[5/6] Configurando arquivo .env..."
if [ -f ".env" ]; then
    echo -e "${YELLOW}Arquivo .env já existe. Pulando criação.${NC}"
else
    cp .env.example .env
    echo -e "${GREEN}OK - Arquivo .env criado!${NC}"
    echo ""
    echo -e "${YELLOW}IMPORTANTE: Edite o arquivo .env e configure:${NC}"
    echo "  - DATABASE_URL (conexão MySQL)"
    echo "  - SECRET_KEY (gere uma chave forte)"
    echo "  - Outras configurações necessárias"
    echo ""
fi
echo ""

echo "[6/6] Gerando SECRET_KEY..."
echo ""
echo "Cole esta SECRET_KEY no arquivo .env:"
python3 -c "import secrets; print(secrets.token_hex(32))"
echo ""

echo "===================================================================================================="
echo "  SETUP CONCLUÍDO!"
echo "===================================================================================================="
echo ""
echo "Próximos passos:"
echo ""
echo "1. Edite o arquivo .env com suas configurações"
echo "   - DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub"
echo "   - SECRET_KEY=[cole a chave gerada acima]"
echo ""
echo "2. Certifique-se que o MySQL está rodando"
echo ""
echo "3. Execute os scripts SQL:"
echo "   - backend/sql/01_criar_banco.sql"
echo "   - backend/sql/02_criar_tabelas_simples.sql"
echo ""
echo "4. Teste a instalação:"
echo "   source venv/bin/activate"
echo "   python test_pncp.py"
echo ""
echo "5. Inicie o servidor:"
echo "   uvicorn app.main:app --reload"
echo ""
echo "6. Acesse: http://localhost:8000/docs"
echo ""
echo "===================================================================================================="
echo ""
