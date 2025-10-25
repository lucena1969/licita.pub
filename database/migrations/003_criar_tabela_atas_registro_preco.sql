-- ============================================================
-- Migração 003: Criar Tabelas ATAS DE REGISTRO DE PREÇO
-- Data: 2025-10-25
-- Descrição: Tabelas para ARPs e itens de ARPs do PNCP
-- ============================================================

-- Criar tabela atas_registro_preco
CREATE TABLE IF NOT EXISTS `atas_registro_preco` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `pncp_id` VARCHAR(100) NOT NULL COMMENT 'ID único da ARP no PNCP',
  `licitacao_id` CHAR(36) DEFAULT NULL COMMENT 'ID da licitação origem',
  `numero` VARCHAR(50) NOT NULL COMMENT 'Número da ARP',
  `objeto` TEXT NOT NULL COMMENT 'Descrição do objeto da ARP',

  -- Órgão gerenciador
  `orgao_gerenciador_id` VARCHAR(50) NOT NULL COMMENT 'ID do órgão gerenciador',
  `orgao_gerenciador_nome` VARCHAR(255) NOT NULL COMMENT 'Nome do órgão gerenciador',
  `orgao_gerenciador_cnpj` VARCHAR(18) NOT NULL COMMENT 'CNPJ do órgão gerenciador',

  -- Datas e vigência
  `data_assinatura` DATE NOT NULL COMMENT 'Data de assinatura da ARP',
  `data_vigencia_inicio` DATE NOT NULL COMMENT 'Início da vigência',
  `data_vigencia_fim` DATE NOT NULL COMMENT 'Fim da vigência',

  -- Status
  `situacao` VARCHAR(30) NOT NULL COMMENT 'ATIVA, ENCERRADA, SUSPENSA, CANCELADA',
  `permite_adesao` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Permite adesão de outros órgãos (carona)',

  -- Localização
  `uf` CHAR(2) NOT NULL COMMENT 'Estado do órgão gerenciador',
  `municipio` VARCHAR(100) DEFAULT NULL COMMENT 'Município do órgão gerenciador',

  -- URLs
  `url_ata` TEXT DEFAULT NULL COMMENT 'URL do documento da ARP',
  `url_pncp` TEXT NOT NULL COMMENT 'URL da ARP no PNCP',

  -- Metadados
  `sincronizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de sincronização com PNCP',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pncp_id` (`pncp_id`),
  KEY `idx_pncp_id` (`pncp_id`),
  KEY `idx_licitacao_id` (`licitacao_id`),
  KEY `idx_orgao_gerenciador_id` (`orgao_gerenciador_id`),
  KEY `idx_orgao_gerenciador_cnpj` (`orgao_gerenciador_cnpj`),
  KEY `idx_situacao` (`situacao`),
  KEY `idx_vigencia` (`data_vigencia_fim`),
  KEY `idx_vigencia_situacao` (`situacao`, `data_vigencia_fim`),
  KEY `idx_permite_adesao` (`permite_adesao`),
  KEY `idx_uf` (`uf`),
  FULLTEXT KEY `idx_objeto` (`objeto`),
  FULLTEXT KEY `idx_orgao_nome` (`orgao_gerenciador_nome`),

  CONSTRAINT `fk_atas_licitacao`
    FOREIGN KEY (`licitacao_id`) REFERENCES `licitacoes` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_atas_orgao`
    FOREIGN KEY (`orgao_gerenciador_id`) REFERENCES `orgaos` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Atas de Registro de Preço do PNCP';

-- ============================================================
-- Criar tabela itens_ata (itens das ARPs)
-- ============================================================

CREATE TABLE IF NOT EXISTS `itens_ata` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `ata_id` CHAR(36) NOT NULL COMMENT 'ID da ARP',
  `numero_item` INT NOT NULL COMMENT 'Número sequencial do item na ARP',

  -- Descrição do item
  `descricao` TEXT NOT NULL COMMENT 'Descrição do item/serviço',
  `unidade` VARCHAR(20) NOT NULL COMMENT 'Unidade de medida (UN, KG, M2, etc)',

  -- Fornecedor
  `fornecedor_nome` VARCHAR(255) NOT NULL COMMENT 'Nome do fornecedor vencedor',
  `fornecedor_cnpj` VARCHAR(18) NOT NULL COMMENT 'CNPJ do fornecedor',

  -- Valores e quantidades
  `valor_unitario` DECIMAL(15,2) NOT NULL COMMENT 'Preço unitário registrado',
  `quantidade_total` DECIMAL(15,3) NOT NULL COMMENT 'Quantidade total registrada',
  `quantidade_disponivel` DECIMAL(15,3) NOT NULL COMMENT 'Quantidade ainda disponível para adesão',
  `valor_total` DECIMAL(15,2) GENERATED ALWAYS AS (`valor_unitario` * `quantidade_total`) STORED COMMENT 'Valor total do item (calculado)',

  -- Metadados
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ata_id` (`ata_id`),
  KEY `idx_numero_item` (`numero_item`),
  KEY `idx_fornecedor_cnpj` (`fornecedor_cnpj`),
  KEY `idx_ata_fornecedor` (`ata_id`, `fornecedor_cnpj`),
  FULLTEXT KEY `idx_descricao` (`descricao`),
  FULLTEXT KEY `idx_fornecedor_nome` (`fornecedor_nome`),

  CONSTRAINT `fk_itens_ata`
    FOREIGN KEY (`ata_id`) REFERENCES `atas_registro_preco` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens das Atas de Registro de Preço';

-- ============================================================
-- Criar tabela adesoes_ata (órgãos que aderiram à ARP)
-- ============================================================

CREATE TABLE IF NOT EXISTS `adesoes_ata` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `ata_id` CHAR(36) NOT NULL COMMENT 'ID da ARP',
  `orgao_aderente_id` VARCHAR(50) NOT NULL COMMENT 'ID do órgão que aderiu (carona)',
  `orgao_aderente_nome` VARCHAR(255) NOT NULL COMMENT 'Nome do órgão aderente',
  `orgao_aderente_cnpj` VARCHAR(18) NOT NULL COMMENT 'CNPJ do órgão aderente',
  `data_adesao` DATE NOT NULL COMMENT 'Data da adesão',
  `valor_estimado` DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor estimado da adesão',
  `situacao` VARCHAR(30) NOT NULL COMMENT 'ATIVA, CANCELADA, CONCLUIDA',
  `url_documento` TEXT DEFAULT NULL COMMENT 'URL do termo de adesão',

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ata_id` (`ata_id`),
  KEY `idx_orgao_aderente_id` (`orgao_aderente_id`),
  KEY `idx_orgao_aderente_cnpj` (`orgao_aderente_cnpj`),
  KEY `idx_data_adesao` (`data_adesao`),
  KEY `idx_situacao` (`situacao`),

  CONSTRAINT `fk_adesoes_ata`
    FOREIGN KEY (`ata_id`) REFERENCES `atas_registro_preco` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_adesoes_orgao`
    FOREIGN KEY (`orgao_aderente_id`) REFERENCES `orgaos` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Adesões (caronas) de órgãos às ARPs';

-- ============================================================
-- Inserir dados de exemplo (opcional - apenas para testes)
-- ============================================================

-- Exemplo de ARP
-- NOTA: Só insere se o órgão de exemplo existir
INSERT INTO `atas_registro_preco` (
  `id`, `pncp_id`, `licitacao_id`, `numero`, `objeto`,
  `orgao_gerenciador_id`, `orgao_gerenciador_nome`, `orgao_gerenciador_cnpj`,
  `data_assinatura`, `data_vigencia_inicio`, `data_vigencia_fim`,
  `situacao`, `permite_adesao`, `uf`, `municipio`, `url_pncp`
)
SELECT
  UUID() AS id,
  'ARP-EXEMPLO-001-2025' AS pncp_id,
  'af1b572e-b03d-11f0-816d-2025649166b9' AS licitacao_id,
  'ARP-001/2025' AS numero,
  'Registro de preços para fornecimento de material de escritório' AS objeto,
  '00000000000001' AS orgao_gerenciador_id,
  'PREFEITURA MUNICIPAL DE EXEMPLO' AS orgao_gerenciador_nome,
  '00000000000001' AS orgao_gerenciador_cnpj,
  '2025-10-25' AS data_assinatura,
  '2025-11-01' AS data_vigencia_inicio,
  '2026-10-31' AS data_vigencia_fim,
  'ATIVA' AS situacao,
  1 AS permite_adesao,
  'SP' AS uf,
  'São Paulo' AS municipio,
  'https://pncp.gov.br/app/atas/ARP-EXEMPLO-001-2025' AS url_pncp
FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM orgaos WHERE id = '00000000000001'
)
AND NOT EXISTS (
  SELECT 1 FROM atas_registro_preco WHERE pncp_id = 'ARP-EXEMPLO-001-2025'
);

-- Exemplo de itens da ARP
-- NOTA: Só insere se a ARP foi criada
INSERT INTO `itens_ata` (
  `id`, `ata_id`, `numero_item`, `descricao`, `unidade`,
  `fornecedor_nome`, `fornecedor_cnpj`,
  `valor_unitario`, `quantidade_total`, `quantidade_disponivel`
)
SELECT
  UUID() AS id,
  arp.id AS ata_id,
  1 AS numero_item,
  'Papel A4 - Resma com 500 folhas' AS descricao,
  'RESMA' AS unidade,
  'EMPRESA EXEMPLO LTDA' AS fornecedor_nome,
  '12345678000190' AS fornecedor_cnpj,
  25.00 AS valor_unitario,
  1000.000 AS quantidade_total,
  800.000 AS quantidade_disponivel
FROM atas_registro_preco arp
WHERE arp.pncp_id = 'ARP-EXEMPLO-001-2025'
AND NOT EXISTS (
  SELECT 1 FROM itens_ata WHERE ata_id = arp.id AND numero_item = 1
)
UNION ALL
SELECT
  UUID() AS id,
  arp.id AS ata_id,
  2 AS numero_item,
  'Caneta esferográfica azul' AS descricao,
  'UNIDADE' AS unidade,
  'EMPRESA EXEMPLO LTDA' AS fornecedor_nome,
  '12345678000190' AS fornecedor_cnpj,
  1.50 AS valor_unitario,
  5000.000 AS quantidade_total,
  4500.000 AS quantidade_disponivel
FROM atas_registro_preco arp
WHERE arp.pncp_id = 'ARP-EXEMPLO-001-2025'
AND NOT EXISTS (
  SELECT 1 FROM itens_ata WHERE ata_id = arp.id AND numero_item = 2
);

-- ============================================================
-- Verificação
-- ============================================================
SELECT 'Tabelas de Atas de Registro de Preço criadas com sucesso!' AS status;
SELECT COUNT(*) AS total_atas FROM atas_registro_preco;
SELECT COUNT(*) AS total_itens_ata FROM itens_ata;
SELECT COUNT(*) AS total_adesoes FROM adesoes_ata;
