-- ============================================================
-- TROUBLESHOOTING - Solução Rápida de Problemas
-- ============================================================

-- ============================================================
-- PROBLEMA 1: Erro de Foreign Key
-- Erro: #1452 - Cannot add or update a child row
-- ============================================================

-- SOLUÇÃO 1A: Verificar se a tabela orgaos existe e tem dados
SELECT
  'Verificando tabela orgaos...' AS etapa,
  COUNT(*) AS total_registros
FROM orgaos;

-- Se retornou 0, o órgão de exemplo não foi criado
-- Execute o INSERT abaixo:

INSERT INTO orgaos (
  id, cnpj, razao_social, nome_fantasia, esfera, poder,
  uf, municipio, tipo, email, telefone
) VALUES (
  '00000000000001',
  '00000000000001',
  'PREFEITURA MUNICIPAL DE EXEMPLO',
  'PMEXEMPLO',
  'MUNICIPAL',
  'EXECUTIVO',
  'SP',
  'São Paulo',
  'Prefeitura',
  'licitacoes@prefeitura-exemplo.sp.gov.br',
  '(11) 3000-0000'
) ON DUPLICATE KEY UPDATE
  razao_social = VALUES(razao_social);

-- ============================================================
-- PROBLEMA 2: Tabela já existe
-- Erro: Table 'xxx' already exists
-- ============================================================

-- SOLUÇÃO 2A: Verificar quais tabelas já existem
SELECT TABLE_NAME AS tabela_existente
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE'
  AND TABLE_NAME IN (
    'orgaos', 'contratos', 'aditivos_contratuais',
    'atas_registro_preco', 'itens_ata', 'adesoes_ata',
    'planos_contratacao_anual', 'categorias_pca'
  )
ORDER BY TABLE_NAME;

-- SOLUÇÃO 2B: Limpar tudo e recomeçar (CUIDADO: APAGA DADOS!)
-- Descomente as linhas abaixo se quiser limpar e recomeçar:

-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS adesoes_ata;
-- DROP TABLE IF EXISTS itens_ata;
-- DROP TABLE IF EXISTS atas_registro_preco;
-- DROP TABLE IF EXISTS planos_contratacao_anual;
-- DROP TABLE IF EXISTS categorias_pca;
-- DROP TABLE IF EXISTS aditivos_contratuais;
-- DROP TABLE IF EXISTS contratos;
-- DROP TABLE IF EXISTS orgaos;
-- SET FOREIGN_KEY_CHECKS = 1;

-- Depois execute os scripts na ordem: 001 → 002 → 003 → 004

-- ============================================================
-- PROBLEMA 3: Constraint já existe
-- Erro: Duplicate key name 'xxx'
-- ============================================================

-- SOLUÇÃO 3: Verificar constraints existentes
SELECT
  TABLE_NAME AS tabela,
  CONSTRAINT_NAME AS constraint,
  CONSTRAINT_TYPE AS tipo
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('contratos', 'atas_registro_preco', 'planos_contratacao_anual')
ORDER BY TABLE_NAME, CONSTRAINT_TYPE;

-- ============================================================
-- PROBLEMA 4: Dados duplicados
-- Erro: Duplicate entry 'xxx' for key 'PRIMARY'
-- ============================================================

-- SOLUÇÃO 4: Limpar apenas dados de exemplo (mantém estrutura)
DELETE FROM adesoes_ata;
DELETE FROM itens_ata WHERE ata_id IN (
  SELECT id FROM atas_registro_preco WHERE pncp_id LIKE '%EXEMPLO%'
);
DELETE FROM atas_registro_preco WHERE pncp_id LIKE '%EXEMPLO%';
DELETE FROM aditivos_contratuais WHERE contrato_id IN (
  SELECT id FROM contratos WHERE pncp_id LIKE '%EXEMPLO%'
);
DELETE FROM contratos WHERE pncp_id LIKE '%EXEMPLO%';
DELETE FROM planos_contratacao_anual WHERE pncp_id LIKE '%EXEMPLO%';
DELETE FROM orgaos WHERE id = '00000000000001';

-- ============================================================
-- VERIFICAÇÃO COMPLETA DO BANCO
-- ============================================================

-- 1. Listar todas as tabelas
SELECT
  'TABELAS' AS categoria,
  TABLE_NAME AS nome,
  TABLE_ROWS AS linhas_aprox,
  ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS tamanho_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- 2. Verificar foreign keys
SELECT
  'FOREIGN KEYS' AS categoria,
  TABLE_NAME AS tabela,
  CONSTRAINT_NAME AS constraint,
  REFERENCED_TABLE_NAME AS referencia
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- 3. Verificar índices FULLTEXT
SELECT
  'ÍNDICES FULLTEXT' AS categoria,
  TABLE_NAME AS tabela,
  INDEX_NAME AS indice,
  GROUP_CONCAT(COLUMN_NAME) AS colunas
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_TYPE = 'FULLTEXT'
GROUP BY TABLE_NAME, INDEX_NAME;

-- 4. Contar registros em todas as tabelas
SELECT 'orgaos' AS tabela, COUNT(*) AS registros FROM orgaos
UNION ALL SELECT 'licitacoes', COUNT(*) FROM licitacoes
UNION ALL SELECT 'itens_licitacao', COUNT(*) FROM itens_licitacao
UNION ALL SELECT 'contratos', COUNT(*) FROM contratos
UNION ALL SELECT 'aditivos_contratuais', COUNT(*) FROM aditivos_contratuais
UNION ALL SELECT 'atas_registro_preco', COUNT(*) FROM atas_registro_preco
UNION ALL SELECT 'itens_ata', COUNT(*) FROM itens_ata
UNION ALL SELECT 'adesoes_ata', COUNT(*) FROM adesoes_ata
UNION ALL SELECT 'planos_contratacao_anual', COUNT(*) FROM planos_contratacao_anual
UNION ALL SELECT 'categorias_pca', COUNT(*) FROM categorias_pca
UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios
UNION ALL SELECT 'alertas', COUNT(*) FROM alertas
UNION ALL SELECT 'favoritos', COUNT(*) FROM favoritos
UNION ALL SELECT 'historico_buscas', COUNT(*) FROM historico_buscas
UNION ALL SELECT 'logs_sincronizacao', COUNT(*) FROM logs_sincronizacao;

-- ============================================================
-- TESTE DE INTEGRIDADE REFERENCIAL
-- ============================================================

-- Verificar se há contratos sem órgão válido
SELECT
  'Contratos órfãos' AS problema,
  COUNT(*) AS total
FROM contratos c
LEFT JOIN orgaos o ON c.orgao_id = o.id
WHERE o.id IS NULL;

-- Verificar se há licitações sem órgão válido
SELECT
  'Licitações órfãs' AS problema,
  COUNT(*) AS total
FROM licitacoes l
LEFT JOIN orgaos o ON l.orgao_id = o.id
WHERE o.id IS NULL;

-- Verificar se há ARPs sem órgão válido
SELECT
  'ARPs órfãs' AS problema,
  COUNT(*) AS total
FROM atas_registro_preco a
LEFT JOIN orgaos o ON a.orgao_gerenciador_id = o.id
WHERE o.id IS NULL;

-- Se qualquer query acima retornar total > 0, há problemas!

-- ============================================================
-- ESTATÍSTICAS DO BANCO
-- ============================================================

SELECT
  'Banco de Dados' AS info,
  DATABASE() AS nome,
  COUNT(*) AS total_tabelas,
  SUM(TABLE_ROWS) AS total_linhas_aprox,
  ROUND(SUM((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS tamanho_total_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE';

-- ============================================================
-- FIM - Tudo OK!
-- ============================================================

SELECT '✅ Verificação completa! Analise os resultados acima.' AS status;
