-- ============================================================
-- Migração 001: Criar Tabela ORGAOS
-- Data: 2025-10-25
-- Descrição: Tabela para armazenar órgãos públicos do PNCP
-- ============================================================

-- Criar tabela orgaos
CREATE TABLE IF NOT EXISTS `orgaos` (
  `id` VARCHAR(50) NOT NULL COMMENT 'ID do órgão no PNCP',
  `cnpj` VARCHAR(18) NOT NULL COMMENT 'CNPJ do órgão',
  `razao_social` VARCHAR(255) NOT NULL COMMENT 'Razão social oficial',
  `nome_fantasia` VARCHAR(255) DEFAULT NULL COMMENT 'Nome fantasia ou sigla',
  `esfera` VARCHAR(20) NOT NULL COMMENT 'FEDERAL, ESTADUAL, MUNICIPAL',
  `poder` VARCHAR(20) NOT NULL COMMENT 'EXECUTIVO, LEGISLATIVO, JUDICIARIO',
  `uf` CHAR(2) NOT NULL COMMENT 'Sigla do estado',
  `municipio` VARCHAR(100) DEFAULT NULL COMMENT 'Nome do município (se municipal)',
  `tipo` VARCHAR(100) DEFAULT NULL COMMENT 'Ministério, Prefeitura, Autarquia, etc',
  `email` VARCHAR(255) DEFAULT NULL COMMENT 'Email de contato',
  `telefone` VARCHAR(20) DEFAULT NULL COMMENT 'Telefone de contato',
  `total_licitacoes` INT NOT NULL DEFAULT 0 COMMENT 'Total de licitações (cache)',
  `total_contratos` INT NOT NULL DEFAULT 0 COMMENT 'Total de contratos (cache)',
  `sincronizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da primeira sincronização',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cnpj` (`cnpj`),
  KEY `idx_cnpj` (`cnpj`),
  KEY `idx_uf` (`uf`),
  KEY `idx_esfera` (`esfera`),
  KEY `idx_poder` (`poder`),
  KEY `idx_esfera_poder` (`esfera`, `poder`),
  KEY `idx_uf_municipio` (`uf`, `municipio`),
  FULLTEXT KEY `idx_busca` (`razao_social`, `nome_fantasia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Órgãos públicos cadastrados no PNCP';

-- Adicionar foreign key em licitacoes (se ainda não existir)
-- Verifica se a constraint já existe antes de tentar adicionar
SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'licitacoes'
    AND CONSTRAINT_NAME = 'fk_licitacoes_orgao'
);

SET @sql = IF(@constraint_exists = 0,
  'ALTER TABLE `licitacoes` ADD CONSTRAINT `fk_licitacoes_orgao`
   FOREIGN KEY (`orgao_id`) REFERENCES `orgaos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT "Constraint fk_licitacoes_orgao já existe" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Inserir dados de exemplo (opcional - apenas para testes)
-- ============================================================

INSERT INTO `orgaos` (
  `id`, `cnpj`, `razao_social`, `nome_fantasia`, `esfera`, `poder`,
  `uf`, `municipio`, `tipo`, `email`, `telefone`
) VALUES
(
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
  `razao_social` = VALUES(`razao_social`),
  `nome_fantasia` = VALUES(`nome_fantasia`),
  `atualizado_em` = CURRENT_TIMESTAMP;

-- ============================================================
-- Verificação
-- ============================================================
SELECT 'Tabela orgaos criada com sucesso!' AS status;
SELECT COUNT(*) AS total_orgaos FROM orgaos;
