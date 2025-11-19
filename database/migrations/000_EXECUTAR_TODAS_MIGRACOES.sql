-- ============================================================
-- SCRIPT MASTER: Executar TODAS as migrações do Licita.pub
-- Data: 2025-10-25
-- Versão: 1.0.0
-- Descrição: Cria todas as tabelas necessárias para o MVP
-- ============================================================
--
-- IMPORTANTE:
-- 1. Este script deve ser executado em um banco de dados MySQL/MariaDB
-- 2. O banco deve estar usando charset UTF-8 (utf8mb4)
-- 3. Execute este script como usuário com privilégios de CREATE TABLE
-- 4. As tabelas base (usuarios, licitacoes, etc) já devem existir
--
-- ORDEM DE EXECUÇÃO:
-- 001 → Órgãos (tabela independente)
-- 002 → Contratos (depende de órgãos e licitações)
-- 003 → Atas de Registro de Preço (depende de órgãos e licitações)
-- 004 → Planos de Contratação Anual (depende de órgãos)
-- 005 → Índice UNIQUE em pncp_id (CRÍTICO para UPSERT)
--
-- ============================================================

-- Configurações iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Verificar se o banco está correto
-- ============================================================

SELECT
  DATABASE() AS banco_atual,
  VERSION() AS versao_mysql,
  @@character_set_database AS charset,
  @@collation_database AS collation;

-- Pausa para conferência (aguardar 3 segundos)
DO SLEEP(3);

-- ============================================================
-- MIGRAÇÃO 001: ÓRGÃOS
-- ============================================================

SELECT '==================================================' AS '';
SELECT 'INICIANDO MIGRAÇÃO 001: ÓRGÃOS' AS status;
SELECT '==================================================' AS '';

SOURCE 001_criar_tabela_orgaos.sql;

SELECT 'MIGRAÇÃO 001 CONCLUÍDA!' AS status;
DO SLEEP(1);

-- ============================================================
-- MIGRAÇÃO 002: CONTRATOS
-- ============================================================

SELECT '==================================================' AS '';
SELECT 'INICIANDO MIGRAÇÃO 002: CONTRATOS' AS status;
SELECT '==================================================' AS '';

SOURCE 002_criar_tabela_contratos.sql;

SELECT 'MIGRAÇÃO 002 CONCLUÍDA!' AS status;
DO SLEEP(1);

-- ============================================================
-- MIGRAÇÃO 003: ATAS DE REGISTRO DE PREÇO
-- ============================================================

SELECT '==================================================' AS '';
SELECT 'INICIANDO MIGRAÇÃO 003: ATAS DE REGISTRO DE PREÇO' AS status;
SELECT '==================================================' AS '';

SOURCE 003_criar_tabela_atas_registro_preco.sql;

SELECT 'MIGRAÇÃO 003 CONCLUÍDA!' AS status;
DO SLEEP(1);

-- ============================================================
-- MIGRAÇÃO 004: PLANOS DE CONTRATAÇÃO ANUAL
-- ============================================================

SELECT '==================================================' AS '';
SELECT 'INICIANDO MIGRAÇÃO 004: PLANOS DE CONTRATAÇÃO ANUAL' AS status;
SELECT '==================================================' AS '';

SOURCE 004_criar_tabela_planos_contratacao_anual.sql;

SELECT 'MIGRAÇÃO 004 CONCLUÍDA!' AS status;
DO SLEEP(1);

-- ============================================================
-- MIGRAÇÃO 005: ÍNDICE UNIQUE EM PNCP_ID
-- ============================================================

SELECT '==================================================' AS '';
SELECT 'INICIANDO MIGRAÇÃO 005: ÍNDICE UNIQUE EM PNCP_ID' AS status;
SELECT '==================================================' AS '';

SOURCE 005_adicionar_unique_pncp_id.sql;

SELECT 'MIGRAÇÃO 005 CONCLUÍDA!' AS status;
DO SLEEP(1);

-- ============================================================
-- Reativar verificações de foreign key
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RESUMO FINAL
-- ============================================================

SELECT '==================================================' AS '';
SELECT 'TODAS AS MIGRAÇÕES FORAM EXECUTADAS COM SUCESSO!' AS status;
SELECT '==================================================' AS '';

-- Verificar tabelas criadas
SELECT
  'TABELAS CRIADAS' AS categoria,
  COUNT(*) AS total
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE';

-- Listar todas as tabelas
SELECT
  TABLE_NAME AS tabela,
  TABLE_ROWS AS linhas_estimadas,
  ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS tamanho_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- Verificar foreign keys
SELECT
  'FOREIGN KEYS' AS categoria,
  COUNT(*) AS total
FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Verificar índices FULLTEXT
SELECT
  'ÍNDICES FULLTEXT' AS categoria,
  COUNT(*) AS total
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_TYPE = 'FULLTEXT';

SELECT '==================================================' AS '';
SELECT 'PRÓXIMO PASSO: Desenvolver integração com PNCP' AS proxima_etapa;
SELECT '==================================================' AS '';
