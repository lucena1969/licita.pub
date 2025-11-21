@echo off
REM ====================================================================================================
REM LICITA.PUB - VERIFICAR INSTALAÇÃO
REM ====================================================================================================

echo ====================================================================================================
echo   LICITA.PUB - VERIFICAÇÃO DE INSTALAÇÃO
echo ====================================================================================================
echo.

REM Verificar se está na pasta backend
if not exist "app\" (
    echo [X] ERRO: Execute este script dentro da pasta 'backend'
    pause
    exit /b 1
)
echo [OK] Pasta backend encontrada
echo.

REM Verificar Python
echo Verificando Python...
python --version >nul 2>&1
if errorlevel 1 (
    echo [X] Python não encontrado
    echo     Instale Python 3.11+ em: https://www.python.org/downloads/
) else (
    python --version
    echo [OK] Python instalado
)
echo.

REM Verificar venv
if exist "venv\" (
    echo [OK] Ambiente virtual (venv) existe
) else (
    echo [X] Ambiente virtual não encontrado
    echo     Execute: setup_windows.bat
)
echo.

REM Verificar .env
if exist ".env" (
    echo [OK] Arquivo .env existe
) else (
    echo [X] Arquivo .env não encontrado
    echo     Copie .env.example para .env
)
echo.

REM Verificar requirements.txt
if exist "requirements.txt" (
    echo [OK] requirements.txt existe
) else (
    echo [X] requirements.txt não encontrado
)
echo.

REM Ativar venv e verificar dependências
if exist "venv\" (
    echo Verificando dependências instaladas...
    call venv\Scripts\activate.bat

    pip show fastapi >nul 2>&1
    if errorlevel 1 (
        echo [X] FastAPI não instalado
        echo     Execute: pip install -r requirements.txt
    ) else (
        echo [OK] FastAPI instalado
    )

    pip show uvicorn >nul 2>&1
    if errorlevel 1 (
        echo [X] Uvicorn não instalado
    ) else (
        echo [OK] Uvicorn instalado
    )

    pip show sqlalchemy >nul 2>&1
    if errorlevel 1 (
        echo [X] SQLAlchemy não instalado
    ) else (
        echo [OK] SQLAlchemy instalado
    )
    echo.

    REM Testar conexão com banco
    echo Testando conexão com banco de dados...
    python testar_conexao.py
)

echo.
echo ====================================================================================================
echo   VERIFICAÇÃO CONCLUÍDA
echo ====================================================================================================
echo.
echo Próximos passos:
echo   1. Se houver erros acima, corrija-os primeiro
echo   2. Se tudo estiver OK, execute: INICIAR.bat
echo   3. Acesse: http://localhost:8000/docs
echo.

pause
