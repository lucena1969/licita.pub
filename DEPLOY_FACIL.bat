@echo off
REM ========================================
REM Deploy Facil - Licita.pub
REM Envia arquivos para producao via FTP
REM ========================================

title Licita.pub - Deploy Automatico

echo.
echo ╔════════════════════════════════════════╗
echo ║   LICITA.PUB - DEPLOY AUTOMATICO      ║
echo ╚════════════════════════════════════════╝
echo.

REM Verificar se PHP existe
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERRO] PHP nao encontrado!
    echo.
    echo Por favor, instale o PHP ou adicione ao PATH
    echo Ou use o FileZilla para enviar os arquivos.
    echo.
    pause
    exit /b 1
)

echo [OK] PHP encontrado!
echo.

REM Menu
echo Escolha uma opcao:
echo.
echo 1. Fazer BACKUP do banco antes de tudo
echo 2. Enviar APENAS migracoes SQL
echo 3. Enviar BACKEND completo
echo 4. Enviar TUDO (migrações + backend)
echo 5. Listar backups disponiveis
echo 6. Sair
echo.

set /p opcao="Digite o numero da opcao: "

if "%opcao%"=="1" goto backup
if "%opcao%"=="2" goto migrations
if "%opcao%"=="3" goto backend
if "%opcao%"=="4" goto full
if "%opcao%"=="5" goto list_backups
if "%opcao%"=="6" goto sair

echo.
echo [ERRO] Opcao invalida!
pause
exit /b 1

:backup
echo.
echo ========================================
echo  CRIANDO BACKUP DO BANCO DE DADOS
echo ========================================
echo.
php backup_antes_deploy.php
if %errorlevel% neq 0 (
    echo.
    echo [ERRO] Falha ao criar backup!
    pause
    exit /b 1
)
echo.
echo [OK] Backup criado com sucesso!
echo.
pause
exit /b 0

:migrations
echo.
echo ========================================
echo  ENVIANDO APENAS MIGRACOES
echo ========================================
echo.
php deploy.php --migrations
if %errorlevel% neq 0 (
    echo.
    echo [ERRO] Falha no deploy!
    pause
    exit /b 1
)
goto sucesso

:backend
echo.
echo ========================================
echo  ENVIANDO BACKEND COMPLETO
echo ========================================
echo.
php deploy.php --backend
if %errorlevel% neq 0 (
    echo.
    echo [ERRO] Falha no deploy!
    pause
    exit /b 1
)
goto sucesso

:full
echo.
echo ========================================
echo  ENVIANDO TUDO
echo ========================================
echo.
php deploy.php
if %errorlevel% neq 0 (
    echo.
    echo [ERRO] Falha no deploy!
    pause
    exit /b 1
)
goto sucesso

:list_backups
echo.
echo ========================================
echo  BACKUPS DISPONIVEIS
echo ========================================
echo.
php backup_antes_deploy.php --listar
echo.
pause
exit /b 0

:sucesso
echo.
echo ========================================
echo  DEPLOY CONCLUIDO COM SUCESSO!
echo ========================================
echo.
echo Proximos passos:
echo 1. Acesse o phpMyAdmin
echo 2. Execute as migracoes SQL
echo 3. Verifique se as tabelas foram criadas
echo.
echo [IMPORTANTE] Delete o arquivo deploy.php por seguranca!
echo.
pause
exit /b 0

:sair
echo.
echo Ate logo!
exit /b 0
