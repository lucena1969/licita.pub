-- ============================================================
-- DIAGNÓSTICO COMPLETO - BUSCA POR PALAVRA-CHAVE
-- Execute este script no phpMyAdmin ou via MySQL CLI
-- ============================================================

-- 1. Verificar estrutura da tabela licitacoes
SHOW CREATE TABLE licitacoes\G

-- 2. Verificar índices existentes
SHOW INDEXES FROM licitacoes;

-- 3. Verificar se índices FULLTEXT existem
SELECT
    TABLE_NAME,
    INDEX_NAME,
    INDEX_TYPE,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'u590097272_licitapub'
  AND TABLE_NAME = 'licitacoes'
  AND INDEX_TYPE = 'FULLTEXT';

-- 4. Testar busca atual (LIKE - LENTA)
EXPLAIN SELECT * FROM licitacoes
WHERE LOWER(objeto) LIKE LOWER('%computador%')
   OR LOWER(numero) LIKE LOWER('%computador%')
LIMIT 10;

-- 5. Testar busca com FULLTEXT (RÁPIDA)
-- Se os índices existirem, este deve ser muito mais rápido
EXPLAIN SELECT * FROM licitacoes
WHERE MATCH(objeto) AGAINST('computador' IN BOOLEAN MODE)
LIMIT 10;

-- 6. Contar registros na tabela
SELECT
    COUNT(*) as total_licitacoes,
    COUNT(DISTINCT pncp_id) as total_pncp_ids,
    COUNT(objeto) as total_com_objeto,
    COUNT(nome_orgao) as total_com_nome_orgao
FROM licitacoes;

-- 7. Verificar alguns objetos para teste
SELECT
    pncp_id,
    LEFT(objeto, 100) as objeto_preview,
    modalidade,
    uf
FROM licitacoes
LIMIT 10;

-- 8. Testar busca real com diferentes termos
SELECT COUNT(*) as resultados_computador
FROM licitacoes
WHERE LOWER(objeto) LIKE '%computador%';

SELECT COUNT(*) as resultados_servico
FROM licitacoes
WHERE LOWER(objeto) LIKE '%serviço%';

SELECT COUNT(*) as resultados_material
FROM licitacoes
WHERE LOWER(objeto) LIKE '%material%';

-- 9. Performance: comparar LIKE vs FULLTEXT
-- (Execute separadamente e compare o tempo)
SET profiling = 1;

-- Busca com LIKE (devagar)
SELECT COUNT(*) FROM licitacoes
WHERE LOWER(objeto) LIKE '%computador%';

-- Busca com FULLTEXT (rápido)
SELECT COUNT(*) FROM licitacoes
WHERE MATCH(objeto) AGAINST('computador' IN BOOLEAN MODE);

SHOW PROFILES;

-- 10. Verificar charset/collation (pode afetar buscas)
SELECT
    TABLE_NAME,
    TABLE_COLLATION,
    ENGINE
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'u590097272_licitapub'
  AND TABLE_NAME = 'licitacoes';

-- ============================================================
-- RESULTADOS ESPERADOS:
--
-- - Índices FULLTEXT devem existir em `objeto` e `nome_orgao`
-- - Busca com MATCH() deve ser 10-100x mais rápida que LIKE
-- - Se índices não existirem, execute o script de correção
-- ============================================================
