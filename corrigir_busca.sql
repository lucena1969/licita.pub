-- ============================================================
-- SCRIPT DE CORREÇÃO - BUSCA POR PALAVRA-CHAVE
-- Execute este script para corrigir os índices FULLTEXT
-- ============================================================

USE u590097272_licitapub;

-- 1. Remover índices FULLTEXT antigos (se existirem)
-- Ignora erro se não existirem
ALTER TABLE `licitacoes` DROP INDEX `idx_objeto`;
ALTER TABLE `licitacoes` DROP INDEX `idx_nome_orgao`;

-- 2. Criar índices FULLTEXT otimizados
-- Estes índices permitem buscas rápidas usando MATCH() AGAINST()
ALTER TABLE `licitacoes`
ADD FULLTEXT KEY `idx_objeto` (`objeto`);

ALTER TABLE `licitacoes`
ADD FULLTEXT KEY `idx_nome_orgao` (`nome_orgao`);

-- 3. Criar índice composto FULLTEXT (busca em múltiplos campos)
-- Permite buscar em objeto e nome_orgao simultaneamente
ALTER TABLE `licitacoes`
ADD FULLTEXT KEY `idx_busca_completa` (`objeto`, `nome_orgao`);

-- 4. Otimizar tabela após criar índices
OPTIMIZE TABLE `licitacoes`;

-- 5. Verificar índices criados
SHOW INDEXES FROM `licitacoes` WHERE Index_type = 'FULLTEXT';

-- 6. Testar busca com FULLTEXT
-- Deve retornar resultados rapidamente
SELECT
    pncp_id,
    numero,
    LEFT(objeto, 100) as objeto,
    uf,
    municipio
FROM licitacoes
WHERE MATCH(objeto) AGAINST('computador serviço' IN BOOLEAN MODE)
LIMIT 10;

-- 7. Testar busca com operadores booleanos
-- + = obrigatório, - = excluir, * = wildcard
SELECT COUNT(*) as total
FROM licitacoes
WHERE MATCH(objeto) AGAINST('+computador +notebook' IN BOOLEAN MODE);

-- 8. Verificar tamanho mínimo de palavra para FULLTEXT
-- (padrão é 3 caracteres no InnoDB)
SHOW VARIABLES LIKE 'innodb_ft_min_token_size';

-- ============================================================
-- RESULTADO ESPERADO:
--
-- Query OK, X rows affected - para cada ALTER TABLE
-- Índices criados com sucesso
-- Buscas devem retornar resultados instantaneamente
-- ============================================================

-- ============================================================
-- NOTAS IMPORTANTES:
--
-- 1. FULLTEXT SEARCH no MySQL/MariaDB:
--    - Palavras com menos de 3 caracteres são ignoradas
--    - Stopwords (palavras comuns) são ignoradas
--    - Use IN BOOLEAN MODE para mais controle
--
-- 2. Operadores Booleanos:
--    + : Palavra obrigatória (AND)
--    - : Excluir palavra (NOT)
--    * : Wildcard (comeput*)
--    "" : Frase exata ("material escritório")
--    () : Agrupar termos
--
-- 3. Exemplos de buscas:
--    - 'computador notebook' = qualquer uma
--    - '+computador +notebook' = ambas
--    - '+computador -notebook' = computador mas não notebook
--    - 'computador*' = computador, computadores, computação
--    - '"material de escritório"' = frase exata
-- ============================================================
