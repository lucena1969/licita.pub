/*
====================================================================================================
LICITA.PUB - CRIAÇÃO DO BANCO DE DADOS
====================================================================================================
Descrição: Script para criar o banco de dados inicial
Autor: Licita.pub
Data: 2025-01-17
Versão: 1.0

INSTRUÇÕES:
1. Abra o phpMyAdmin
2. Clique em "SQL" no menu superior
3. Cole este script completo
4. Clique em "Executar"

OU via linha de comando MySQL:
mysql -u root -p < 01_criar_banco.sql
====================================================================================================
*/

-- Remove o banco se já existir (CUIDADO: apaga todos os dados!)
-- Comente a linha abaixo se não quiser apagar o banco existente
-- DROP DATABASE IF EXISTS licitapub;

-- Cria o banco de dados com charset UTF-8
CREATE DATABASE IF NOT EXISTS licitapub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Seleciona o banco criado
USE licitapub;

-- Exibe mensagem de sucesso
SELECT 'Banco de dados LICITAPUB criado com sucesso!' AS Status;
