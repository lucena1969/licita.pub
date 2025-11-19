@echo off
  echo ========================================
  echo  IMPORTANDO BANCO DE DADOS LICITAPUB
  echo ========================================
  echo.

  echo [1/3] Criando banco de dados...
  C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS licitapub; CREATE DATABASE licitapub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  if %errorlevel% neq 0 (
      echo ERRO: Nao foi possivel criar o banco!
      pause
      exit /b 1
  )
  echo OK - Banco criado!
  echo.

  echo [2/3] Importando arquivo SQL...
  C:\xampp\mysql\bin\mysql.exe -u root licitapub < database\u590097272_licitapub.sql
  if %errorlevel% neq 0 (
      echo ERRO: Nao foi possivel importar o SQL!
      pause
      exit /b 1
  )
  echo OK - Dados importados!
  echo.

  echo [3/3] Verificando tabelas criadas...
  C:\xampp\mysql\bin\mysql.exe -u root licitapub -e "SHOW TABLES;"
  echo.

  echo ========================================
  echo  IMPORTACAO CONCLUIDA COM SUCESSO!
  echo ========================================
  pause