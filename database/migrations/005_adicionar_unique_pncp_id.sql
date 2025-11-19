-- ============================================================================
-- Migration 005: Adicionar índice UNIQUE em pncp_id
-- ============================================================================
-- Data: 28/10/2025
-- Descrição: Garante que não haja duplicatas de licitações do PNCP
--            Necessário para o funcionamento do UPSERT (INSERT ... ON DUPLICATE KEY UPDATE)
--
-- ⚠️  IMPORTANTE: Execute primeiro o script de verificação/limpeza de duplicatas
--     php backend/database/verificar_duplicatas.php
--     php backend/database/limpar_duplicatas.php (se houver duplicatas)
--
-- Execução:
--   mysql -u u590097272_neto -p u590097272_licitapub < database/migrations/005_adicionar_unique_pncp_id.sql
-- ============================================================================

-- Usar o banco correto
USE u590097272_licitapub;

-- ============================================================================
-- ETAPA 1: Verificar duplicatas existentes
-- ============================================================================

SELECT '============================================================' AS '';
SELECT '🔍 VERIFICANDO DUPLICATAS ANTES DE CRIAR ÍNDICE UNIQUE' AS '';
SELECT '============================================================' AS '';

SELECT
    pncp_id,
    COUNT(*) as duplicatas,
    MIN(sincronizado_em) as primeira_criacao,
    MAX(sincronizado_em) as ultima_criacao
FROM licitacoes
GROUP BY pncp_id
HAVING COUNT(*) > 1
ORDER BY duplicatas DESC
LIMIT 10;

-- ============================================================================
-- ETAPA 2: Limpar duplicatas (manter apenas o mais recente)
-- ============================================================================

SELECT '' AS '';
SELECT '🧹 REMOVENDO DUPLICATAS (mantendo apenas o registro mais recente)' AS '';
SELECT '============================================================' AS '';

-- Criar tabela temporária com IDs para manter
CREATE TEMPORARY TABLE IF NOT EXISTS keep_ids AS
SELECT MAX(id) as id_manter
FROM licitacoes
GROUP BY pncp_id;

-- Contar quantos registros serão removidos
SELECT COUNT(*) as 'Registros que serão removidos'
FROM licitacoes
WHERE id NOT IN (SELECT id_manter FROM keep_ids);

-- Remover duplicatas
DELETE FROM licitacoes
WHERE id NOT IN (SELECT id_manter FROM keep_ids);

-- Limpar tabela temporária
DROP TEMPORARY TABLE IF EXISTS keep_ids;

SELECT '' AS '';
SELECT '✅ Duplicatas removidas com sucesso!' AS '';

-- ============================================================================
-- ETAPA 3: Verificar se o índice já existe
-- ============================================================================

SELECT '' AS '';
SELECT '🔍 VERIFICANDO SE ÍNDICE JÁ EXISTE' AS '';
SELECT '============================================================' AS '';

SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '⚠️  Índice idx_pncp_id_unique já existe'
        ELSE '✅ Índice não existe, pode ser criado'
    END as status
FROM information_schema.statistics
WHERE table_schema = 'u590097272_licitapub'
  AND table_name = 'licitacoes'
  AND index_name = 'idx_pncp_id_unique';

-- ============================================================================
-- ETAPA 4: Criar índice UNIQUE em pncp_id
-- ============================================================================

SELECT '' AS '';
SELECT '🔧 CRIANDO ÍNDICE UNIQUE EM pncp_id' AS '';
SELECT '============================================================' AS '';

-- Remover índice se já existir (para garantir)
SET @exist := (SELECT COUNT(*)
               FROM information_schema.statistics
               WHERE table_schema = 'u590097272_licitapub'
                 AND table_name = 'licitacoes'
                 AND index_name = 'idx_pncp_id_unique');

SET @sqlstmt := IF(@exist > 0,
    'SELECT "⚠️  Removendo índice existente..." AS status',
    'SELECT "✅ Nenhum índice existente para remover" AS status'
);

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remover se existir
DROP INDEX IF EXISTS idx_pncp_id_unique ON licitacoes;

-- Criar índice UNIQUE
ALTER TABLE licitacoes
ADD UNIQUE KEY idx_pncp_id_unique (pncp_id);

SELECT '✅ Índice UNIQUE criado com sucesso!' AS '';

-- ============================================================================
-- ETAPA 5: Verificar índice criado
-- ============================================================================

SELECT '' AS '';
SELECT '✅ VERIFICANDO ÍNDICE CRIADO' AS '';
SELECT '============================================================' AS '';

SHOW INDEXES FROM licitacoes WHERE Key_name = 'idx_pncp_id_unique';

-- ============================================================================
-- ETAPA 6: Estatísticas finais
-- ============================================================================

SELECT '' AS '';
SELECT '📊 ESTATÍSTICAS FINAIS' AS '';
SELECT '============================================================' AS '';

SELECT
    COUNT(*) as 'Total de licitações',
    COUNT(DISTINCT pncp_id) as 'PNCP IDs únicos',
    CASE
        WHEN COUNT(*) = COUNT(DISTINCT pncp_id) THEN '✅ SEM DUPLICATAS'
        ELSE '⚠️  AINDA HÁ DUPLICATAS'
    END as 'Status'
FROM licitacoes;

SELECT '' AS '';
SELECT '============================================================' AS '';
SELECT '✅ MIGRATION 005 CONCLUÍDA COM SUCESSO!' AS '';
SELECT '============================================================' AS '';
SELECT '' AS '';
SELECT '📝 Próximos passos:' AS '';
SELECT '   1. Verificar se índice está ativo: SHOW INDEXES FROM licitacoes;' AS '';
SELECT '   2. Testar sincronização com UPSERT: php backend/cron/sincronizar_pncp.php --ultimos-dias=1' AS '';
SELECT '   3. Verificar logs de sincronização em: /home/u590097272/logs/pncp_sync.log' AS '';
SELECT '' AS '';
