#!/bin/bash

echo "======================================"
echo "  Licita.pub - Setup do Projeto"
echo "======================================"
echo ""

# Cores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Criar estrutura de diretórios do backend
echo -e "${BLUE}Criando estrutura de diretórios...${NC}"

mkdir -p backend/app/{api/v1,core,services,repositories,models,schemas,jobs,utils}
mkdir -p backend/alembic/versions
mkdir -p backend/tests
mkdir -p frontend/src/{components/{layout,licitacao,ui,auth},pages,services,hooks,types,styles}
mkdir -p nginx
mkdir -p logs

echo -e "${GREEN}✓ Estrutura de diretórios criada${NC}"

# Criar arquivos __init__.py
echo -e "${BLUE}Criando arquivos __init__.py...${NC}"

touch backend/app/__init__.py
touch backend/app/api/__init__.py
touch backend/app/api/v1/__init__.py
touch backend/app/core/__init__.py
touch backend/app/services/__init__.py
touch backend/app/repositories/__init__.py
touch backend/app/models/__init__.py
touch backend/app/schemas/__init__.py
touch backend/app/jobs/__init__.py
touch backend/app/utils/__init__.py

echo -e "${GREEN}✓ Arquivos __init__.py criados${NC}"

# Criar .gitignore
echo -e "${BLUE}Criando .gitignore...${NC}"
cat > .gitignore << 'EOF'
# Python
__pycache__/
*.py[cod]
*$py.class
*.so
.Python
venv/
env/
ENV/
*.egg-info/
dist/
build/

# Environment
.env
.env.local

# IDEs
.vscode/
.idea/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Logs
*.log
logs/

# Database
*.db
*.sqlite3

# Frontend
frontend/node_modules/
frontend/dist/
frontend/build/
frontend/.vite/

# Tests
.pytest_cache/
.coverage
htmlcov/
EOF

echo -e "${GREEN}✓ .gitignore criado${NC}"

echo ""
echo -e "${GREEN}======================================"
echo "  Setup concluído com sucesso!"
echo "======================================${NC}"
echo ""
echo "Próximos passos:"
echo "1. cd backend"
echo "2. python3 -m venv venv"
echo "3. source venv/bin/activate  (Linux/Mac) ou venv\\Scripts\\activate (Windows)"
echo "4. pip install -r requirements.txt"
echo "5. cp .env.example .env"
echo "6. Configurar as variáveis no arquivo .env"
echo "7. python init_db.py"
echo ""
