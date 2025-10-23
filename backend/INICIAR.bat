@echo off
REM ====================================================================================================
REM LICITA.PUB - INICIAR SERVIDOR
REM ====================================================================================================

title Licita.pub - Servidor API

echo ====================================================================================================
echo   LICITA.PUB - INICIANDO SERVIDOR
echo ====================================================================================================
echo.

REM Verificar se está na pasta backend
if not exist "app\" (
    echo ERRO: Execute este script dentro da pasta 'backend'
    pause
    exit /b 1
)

REM Verificar se venv existe
if not exist "venv\" (
    echo ERRO: Ambiente virtual não encontrado!
    echo Execute primeiro: setup_windows.bat
    pause
    exit /b 1
)

REM Verificar se .env existe
if not exist ".env" (
    echo ERRO: Arquivo .env não encontrado!
    echo Copie o arquivo .env.example para .env e configure
    pause
    exit /b 1
)

echo Ativando ambiente virtual...
call venv\Scripts\activate.bat

echo.
echo ====================================================================================================
echo   SERVIDOR INICIANDO...
echo ====================================================================================================
echo.
echo URL da API: http://localhost:8000
echo Documentação: http://localhost:8000/docs
echo.
echo Pressione Ctrl+C para parar o servidor
echo ====================================================================================================
echo.

REM Iniciar servidor com hot reload
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000

pause
