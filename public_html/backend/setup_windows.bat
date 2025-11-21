@echo off
REM ====================================================================================================
REM LICITA.PUB - SETUP AUTOMÁTICO WINDOWS
REM ====================================================================================================
REM Descrição: Script para configurar automaticamente o ambiente de desenvolvimento no Windows
REM Requisitos: Python 3.11+ instalado e no PATH
REM ====================================================================================================

echo ====================================================================================================
echo   LICITA.PUB - SETUP AUTOMATICO
echo ====================================================================================================
echo.

REM Verificar se está na pasta backend
if not exist "app\" (
    echo ERRO: Execute este script dentro da pasta 'backend'
    echo Exemplo: cd backend
    echo          setup_windows.bat
    pause
    exit /b 1
)

echo [1/6] Verificando Python...
python --version >nul 2>&1
if errorlevel 1 (
    echo ERRO: Python não encontrado!
    echo Instale Python 3.11+ e adicione ao PATH
    echo Download: https://www.python.org/downloads/
    pause
    exit /b 1
)
python --version
echo OK - Python encontrado!
echo.

echo [2/6] Criando ambiente virtual (venv)...
if exist "venv\" (
    echo Ambiente virtual já existe. Pulando criação.
) else (
    python -m venv venv
    if errorlevel 1 (
        echo ERRO ao criar ambiente virtual!
        pause
        exit /b 1
    )
    echo OK - Ambiente virtual criado!
)
echo.

echo [3/6] Ativando ambiente virtual...
call venv\Scripts\activate.bat
if errorlevel 1 (
    echo ERRO ao ativar ambiente virtual!
    pause
    exit /b 1
)
echo OK - Ambiente virtual ativado!
echo.

echo [4/6] Instalando dependências (pode demorar alguns minutos)...
pip install --upgrade pip
pip install -r requirements.txt
if errorlevel 1 (
    echo ERRO ao instalar dependências!
    pause
    exit /b 1
)
echo OK - Dependências instaladas!
echo.

echo [5/6] Configurando arquivo .env...
if exist ".env" (
    echo Arquivo .env já existe. Pulando criação.
) else (
    copy .env.example .env
    echo OK - Arquivo .env criado!
    echo.
    echo IMPORTANTE: Edite o arquivo .env e configure:
    echo   - DATABASE_URL (conexão MySQL)
    echo   - SECRET_KEY (gere uma chave forte)
    echo   - Outras configurações necessárias
    echo.
)
echo.

echo [6/6] Gerando SECRET_KEY...
echo.
echo Cole esta SECRET_KEY no arquivo .env:
python -c "import secrets; print(secrets.token_hex(32))"
echo.

echo ====================================================================================================
echo   SETUP CONCLUÍDO!
echo ====================================================================================================
echo.
echo Próximos passos:
echo.
echo 1. Edite o arquivo .env com suas configurações
echo    - DATABASE_URL=mysql+pymysql://root:@localhost:3306/licitapub
echo    - SECRET_KEY=[cole a chave gerada acima]
echo.
echo 2. Certifique-se que o MySQL está rodando no XAMPP
echo.
echo 3. Execute os scripts SQL no phpMyAdmin:
echo    - backend\sql\01_criar_banco.sql
echo    - backend\sql\02_criar_tabelas_simples.sql
echo.
echo 4. Teste a instalação:
echo    venv\Scripts\activate
echo    python test_pncp.py
echo.
echo 5. Inicie o servidor:
echo    uvicorn app.main:app --reload
echo.
echo 6. Acesse: http://localhost:8000/docs
echo.
echo ====================================================================================================
echo.

pause
