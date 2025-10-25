-- ============================================================
-- Migração 004: Criar Tabela PLANOS DE CONTRATAÇÃO ANUAL (PCA)
-- Data: 2025-10-25
-- Descrição: Tabela para PCAs do PNCP (planejamento de compras)
-- ============================================================

-- Criar tabela planos_contratacao_anual
CREATE TABLE IF NOT EXISTS `planos_contratacao_anual` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID interno',
  `pncp_id` VARCHAR(100) NOT NULL COMMENT 'ID único do item do PCA no PNCP',
  `orgao_id` VARCHAR(50) NOT NULL COMMENT 'ID do órgão',
  `ano` INT NOT NULL COMMENT 'Ano do planejamento',
  `numero_item` INT NOT NULL COMMENT 'Número do item no PCA',

  -- Descrição
  `descricao` TEXT NOT NULL COMMENT 'Descrição do que será contratado',
  `categoria` VARCHAR(100) DEFAULT NULL COMMENT 'Categoria: OBRAS, SERVICOS, MATERIAIS, TI, etc',
  `justificativa` TEXT DEFAULT NULL COMMENT 'Justificativa da contratação',

  -- Valores e datas
  `valor_estimado` DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor estimado da contratação',
  `data_prevista` DATE DEFAULT NULL COMMENT 'Data prevista para licitação',
  `trimestre_previsto` TINYINT DEFAULT NULL COMMENT 'Trimestre previsto (1, 2, 3, 4)',

  -- Status
  `situacao` VARCHAR(30) NOT NULL COMMENT 'PLANEJADO, EM_LICITACAO, CONTRATADO, CANCELADO, ADIADO',
  `licitacao_id` CHAR(36) DEFAULT NULL COMMENT 'ID da licitação realizada (se houver)',

  -- Metadados
  `sincronizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de sincronização com PNCP',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pncp_id` (`pncp_id`),
  KEY `idx_pncp_id` (`pncp_id`),
  KEY `idx_orgao_id` (`orgao_id`),
  KEY `idx_ano` (`ano`),
  KEY `idx_orgao_ano` (`orgao_id`, `ano`),
  KEY `idx_situacao` (`situacao`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_data_prevista` (`data_prevista`),
  KEY `idx_trimestre` (`trimestre_previsto`),
  KEY `idx_licitacao_id` (`licitacao_id`),
  KEY `idx_situacao_data` (`situacao`, `data_prevista`),
  FULLTEXT KEY `idx_descricao` (`descricao`),

  CONSTRAINT `fk_pca_orgao`
    FOREIGN KEY (`orgao_id`) REFERENCES `orgaos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_pca_licitacao`
    FOREIGN KEY (`licitacao_id`) REFERENCES `licitacoes` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Planos de Contratação Anual (PCA) do PNCP';

-- ============================================================
-- Criar view para análise de PCAs
-- ============================================================

CREATE OR REPLACE VIEW `v_pca_resumo` AS
SELECT
  p.orgao_id,
  o.razao_social AS orgao_nome,
  o.uf,
  o.municipio,
  p.ano,
  p.categoria,
  p.situacao,
  COUNT(*) AS total_itens,
  SUM(p.valor_estimado) AS valor_total_estimado,
  COUNT(CASE WHEN p.situacao = 'PLANEJADO' THEN 1 END) AS itens_planejados,
  COUNT(CASE WHEN p.situacao = 'EM_LICITACAO' THEN 1 END) AS itens_em_licitacao,
  COUNT(CASE WHEN p.situacao = 'CONTRATADO' THEN 1 END) AS itens_contratados,
  COUNT(CASE WHEN p.situacao = 'CANCELADO' THEN 1 END) AS itens_cancelados
FROM planos_contratacao_anual p
INNER JOIN orgaos o ON p.orgao_id = o.id
GROUP BY p.orgao_id, o.razao_social, o.uf, o.municipio, p.ano, p.categoria, p.situacao;

-- ============================================================
-- Criar tabela de categorias do PCA (auxiliar)
-- ============================================================

CREATE TABLE IF NOT EXISTS `categorias_pca` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código da categoria',
  `nome` VARCHAR(100) NOT NULL COMMENT 'Nome da categoria',
  `descricao` TEXT DEFAULT NULL COMMENT 'Descrição da categoria',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,

  KEY `idx_codigo` (`codigo`),
  KEY `idx_ativo` (`ativo`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorias de itens do PCA';

-- ============================================================
-- Inserir categorias padrão
-- ============================================================

INSERT INTO `categorias_pca` (`codigo`, `nome`, `descricao`) VALUES
('OBRAS', 'Obras e Engenharia', 'Obras civis, reformas, construções'),
('SERVICOS', 'Serviços Gerais', 'Serviços de limpeza, segurança, manutenção'),
('TI', 'Tecnologia da Informação', 'Hardware, software, licenças, desenvolvimento'),
('SAUDE', 'Saúde', 'Medicamentos, equipamentos hospitalares, materiais médicos'),
('EDUCACAO', 'Educação', 'Material escolar, mobiliário, equipamentos educacionais'),
('MATERIAIS', 'Materiais e Consumo', 'Material de escritório, consumíveis diversos'),
('VEICULOS', 'Veículos e Transporte', 'Aquisição, locação e manutenção de veículos'),
('CONSULTORIA', 'Consultoria e Assessoria', 'Serviços especializados, consultorias'),
('TREINAMENTO', 'Treinamento e Capacitação', 'Cursos, treinamentos, capacitação de servidores'),
('COMUNICACAO', 'Comunicação', 'Publicidade, divulgação, eventos'),
('OUTROS', 'Outros', 'Categorias não especificadas')
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `descricao` = VALUES(`descricao`);

-- ============================================================
-- Inserir dados de exemplo (opcional - apenas para testes)
-- ============================================================

-- NOTA: Só insere se o órgão de exemplo existir
INSERT INTO `planos_contratacao_anual` (
  `id`, `pncp_id`, `orgao_id`, `ano`, `numero_item`,
  `descricao`, `categoria`, `justificativa`,
  `valor_estimado`, `data_prevista`, `trimestre_previsto`,
  `situacao`
)
SELECT * FROM (
  SELECT
    UUID() AS id,
    'PCA-EXEMPLO-001-2025' AS pncp_id,
    '00000000000001' AS orgao_id,
    2025 AS ano,
    1 AS numero_item,
    'Contratação de empresa para fornecimento de material de escritório' AS descricao,
    'MATERIAIS' AS categoria,
    'Necessidade de reposição de estoque de material de escritório para atendimento das unidades administrativas durante o exercício de 2025.' AS justificativa,
    50000.00 AS valor_estimado,
    '2025-11-01' AS data_prevista,
    4 AS trimestre_previsto,
    'EM_LICITACAO' AS situacao
  UNION ALL
  SELECT
    UUID(),
    'PCA-EXEMPLO-002-2025',
    '00000000000001',
    2025,
    2,
    'Aquisição de equipamentos de informática (computadores e notebooks)',
    'TI',
    'Modernização do parque tecnológico da prefeitura para melhorar o atendimento ao cidadão.',
    150000.00,
    '2026-02-01',
    1,
    'PLANEJADO'
  UNION ALL
  SELECT
    UUID(),
    'PCA-EXEMPLO-003-2025',
    '00000000000001',
    2025,
    3,
    'Contratação de serviços de limpeza e conservação',
    'SERVICOS',
    'Manutenção da limpeza dos prédios públicos municipais.',
    200000.00,
    '2026-01-15',
    1,
    'PLANEJADO'
) AS novos_pca
WHERE EXISTS (
  SELECT 1 FROM orgaos WHERE id = '00000000000001'
)
AND NOT EXISTS (
  SELECT 1 FROM planos_contratacao_anual WHERE pncp_id IN ('PCA-EXEMPLO-001-2025', 'PCA-EXEMPLO-002-2025', 'PCA-EXEMPLO-003-2025')
);

-- Vincular PCA já licitado (se existir)
UPDATE planos_contratacao_anual
SET licitacao_id = 'af1b572e-b03d-11f0-816d-2025649166b9'
WHERE pncp_id = 'PCA-EXEMPLO-001-2025'
  AND licitacao_id IS NULL
  AND EXISTS (SELECT 1 FROM licitacoes WHERE id = 'af1b572e-b03d-11f0-816d-2025649166b9');

-- ============================================================
-- Verificação
-- ============================================================
SELECT 'Tabelas de Planos de Contratação Anual criadas com sucesso!' AS status;
SELECT COUNT(*) AS total_pca FROM planos_contratacao_anual;
SELECT COUNT(*) AS total_categorias FROM categorias_pca;

-- Exibir resumo dos PCAs
SELECT * FROM v_pca_resumo;
