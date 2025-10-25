-- ============================================================
-- Migração 002: Criar Tabela CONTRATOS
-- Data: 2025-10-25
-- Descrição: Tabela para armazenar contratos públicos do PNCP
-- ============================================================

-- Criar tabela contratos
CREATE TABLE IF NOT EXISTS `contratos` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `pncp_id` VARCHAR(100) NOT NULL COMMENT 'ID único do contrato no PNCP',
  `licitacao_id` CHAR(36) DEFAULT NULL COMMENT 'ID da licitação origem (pode ser NULL para contratação direta)',
  `orgao_id` VARCHAR(50) NOT NULL COMMENT 'ID do órgão contratante',
  `numero` VARCHAR(50) NOT NULL COMMENT 'Número do contrato',
  `objeto` TEXT NOT NULL COMMENT 'Descrição do objeto contratado',

  -- Dados do contratado
  `contratado_nome` VARCHAR(255) NOT NULL COMMENT 'Nome/Razão social do contratado',
  `contratado_cnpj` VARCHAR(18) NOT NULL COMMENT 'CNPJ do contratado',

  -- Valores
  `valor_inicial` DECIMAL(15,2) NOT NULL COMMENT 'Valor original do contrato',
  `valor_atual` DECIMAL(15,2) NOT NULL COMMENT 'Valor atual (com aditivos)',

  -- Datas
  `data_assinatura` DATE NOT NULL COMMENT 'Data de assinatura do contrato',
  `data_inicio` DATE NOT NULL COMMENT 'Data de início de vigência',
  `data_fim` DATE NOT NULL COMMENT 'Data prevista de encerramento',

  -- Status e localização
  `situacao` VARCHAR(30) NOT NULL COMMENT 'ATIVO, ENCERRADO, SUSPENSO, RESCINDIDO',
  `uf` CHAR(2) NOT NULL COMMENT 'Estado do órgão contratante',
  `municipio` VARCHAR(100) NOT NULL COMMENT 'Município do órgão contratante',

  -- URLs
  `url_contrato` TEXT DEFAULT NULL COMMENT 'URL do documento do contrato',
  `url_pncp` TEXT NOT NULL COMMENT 'URL do contrato no PNCP',

  -- Metadados
  `sincronizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de sincronização com PNCP',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pncp_id` (`pncp_id`),
  KEY `idx_pncp_id` (`pncp_id`),
  KEY `idx_licitacao_id` (`licitacao_id`),
  KEY `idx_orgao_id` (`orgao_id`),
  KEY `idx_contratado_cnpj` (`contratado_cnpj`),
  KEY `idx_situacao` (`situacao`),
  KEY `idx_data_fim` (`data_fim`),
  KEY `idx_data_assinatura` (`data_assinatura`),
  KEY `idx_uf` (`uf`),
  KEY `idx_uf_municipio` (`uf`, `municipio`),
  KEY `idx_situacao_data_fim` (`situacao`, `data_fim`),
  FULLTEXT KEY `idx_objeto` (`objeto`),
  FULLTEXT KEY `idx_contratado_nome` (`contratado_nome`),

  CONSTRAINT `fk_contratos_licitacao`
    FOREIGN KEY (`licitacao_id`) REFERENCES `licitacoes` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_contratos_orgao`
    FOREIGN KEY (`orgao_id`) REFERENCES `orgaos` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contratos públicos do PNCP';

-- ============================================================
-- Criar tabela de aditivos contratuais
-- ============================================================

CREATE TABLE IF NOT EXISTS `aditivos_contratuais` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `contrato_id` CHAR(36) NOT NULL COMMENT 'ID do contrato',
  `numero` VARCHAR(50) NOT NULL COMMENT 'Número do aditivo',
  `tipo` VARCHAR(50) NOT NULL COMMENT 'PRAZO, VALOR, QUANTITATIVO, QUALITATIVO',
  `descricao` TEXT NOT NULL COMMENT 'Descrição das alterações',
  `valor_aditado` DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor acrescido/suprimido',
  `data_assinatura` DATE NOT NULL COMMENT 'Data de assinatura do aditivo',
  `nova_data_fim` DATE DEFAULT NULL COMMENT 'Nova data de fim (se aplicável)',
  `url_documento` TEXT DEFAULT NULL COMMENT 'URL do documento do aditivo',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_contrato_id` (`contrato_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_data_assinatura` (`data_assinatura`),

  CONSTRAINT `fk_aditivos_contrato`
    FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aditivos/Termos Aditivos de contratos';

-- ============================================================
-- Inserir dados de exemplo (opcional - apenas para testes)
-- ============================================================

-- Exemplo de contrato vinculado à licitação de teste
-- NOTA: Só insere se o órgão de exemplo existir
INSERT INTO `contratos` (
  `id`, `pncp_id`, `licitacao_id`, `orgao_id`, `numero`, `objeto`,
  `contratado_nome`, `contratado_cnpj`,
  `valor_inicial`, `valor_atual`,
  `data_assinatura`, `data_inicio`, `data_fim`,
  `situacao`, `uf`, `municipio`, `url_pncp`
)
SELECT
  UUID() AS id,
  'CONTRATO-EXEMPLO-001-2025' AS pncp_id,
  'af1b572e-b03d-11f0-816d-2025649166b9' AS licitacao_id,
  '00000000000001' AS orgao_id,
  'CT-001/2025' AS numero,
  'Fornecimento de material de escritório conforme licitação CT-001/2025' AS objeto,
  'EMPRESA EXEMPLO LTDA' AS contratado_nome,
  '12345678000190' AS contratado_cnpj,
  50000.00 AS valor_inicial,
  50000.00 AS valor_atual,
  '2025-10-25' AS data_assinatura,
  '2025-11-01' AS data_inicio,
  '2026-10-31' AS data_fim,
  'ATIVO' AS situacao,
  'SP' AS uf,
  'São Paulo' AS municipio,
  'https://pncp.gov.br/app/contratos/CONTRATO-EXEMPLO-001-2025' AS url_pncp
FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM orgaos WHERE id = '00000000000001'
)
AND NOT EXISTS (
  SELECT 1 FROM contratos WHERE pncp_id = 'CONTRATO-EXEMPLO-001-2025'
);

-- ============================================================
-- Verificação
-- ============================================================
SELECT 'Tabelas contratos e aditivos_contratuais criadas com sucesso!' AS status;
SELECT COUNT(*) AS total_contratos FROM contratos;
SELECT COUNT(*) AS total_aditivos FROM aditivos_contratuais;
