/*
====================================================================================================
LICITA.PUB - CRIAÇÃO DAS TABELAS
====================================================================================================
Descrição: Script para criar todas as tabelas do sistema
Autor: Licita.pub
Data: 2025-01-17
Versão: 1.0

INSTRUÇÕES:
1. Execute ANTES o script 01_criar_banco.sql
2. Abra o phpMyAdmin
3. Selecione o banco "licitapub" na sidebar
4. Clique em "SQL" no menu superior
5. Cole este script completo
6. Clique em "Executar"

OU via linha de comando MySQL:
mysql -u root -p licitapub < 02_criar_tabelas.sql
====================================================================================================
*/

USE licitapub;

-- ====================================================================================================
-- TABELA: usuarios
-- Descrição: Armazena dados dos usuários do sistema
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID do usuário',

    -- Dados básicos
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email do usuário (único)',
    senha VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt da senha',
    nome VARCHAR(255) NOT NULL COMMENT 'Nome completo',
    telefone VARCHAR(20) NULL COMMENT 'Telefone de contato',
    cpf_cnpj VARCHAR(18) NULL UNIQUE COMMENT 'CPF (11 dígitos) ou CNPJ (14 dígitos)',

    -- Verificação de email
    email_verificado BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Email já foi verificado?',
    token_verificacao VARCHAR(255) NULL COMMENT 'Token para verificação de email',
    token_verificacao_expira DATETIME NULL COMMENT 'Data de expiração do token de verificação',

    -- Reset de senha
    token_reset_senha VARCHAR(255) NULL COMMENT 'Token para reset de senha',
    token_reset_senha_expira DATETIME NULL COMMENT 'Data de expiração do token de reset',

    -- Status e plano
    ativo BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Usuário está ativo?',
    plano ENUM('GRATUITO', 'BASICO', 'INTERMEDIARIO', 'PREMIUM') NOT NULL DEFAULT 'GRATUITO' COMMENT 'Plano de assinatura',

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',

    -- Índices
    INDEX idx_email (email),
    INDEX idx_cpf_cnpj (cpf_cnpj),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuários do sistema';


-- ====================================================================================================
-- TABELA: licitacoes
-- Descrição: Armazena dados das licitações/contratos públicos
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS licitacoes (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID da licitação',

    -- IDs externos
    pncp_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID único do PNCP',
    orgao_id VARCHAR(50) NOT NULL COMMENT 'ID do órgão',

    -- Dados básicos
    numero VARCHAR(50) NOT NULL COMMENT 'Número do contrato/licitação',
    objeto TEXT NOT NULL COMMENT 'Descrição do objeto',
    modalidade VARCHAR(50) NOT NULL COMMENT 'Modalidade (PREGAO, DISPENSA, etc)',
    situacao VARCHAR(30) NOT NULL COMMENT 'Situação (ATIVO, CONCLUIDO, etc)',

    -- Valores
    valor_estimado DECIMAL(15, 2) NULL COMMENT 'Valor estimado/global',

    -- Datas
    data_publicacao DATETIME NOT NULL COMMENT 'Data de publicação no PNCP',
    data_abertura DATETIME NULL COMMENT 'Data de abertura/assinatura',
    data_encerramento DATETIME NULL COMMENT 'Data de encerramento/vigência fim',

    -- Localização
    uf CHAR(2) NOT NULL COMMENT 'Sigla da UF',
    municipio VARCHAR(100) NOT NULL COMMENT 'Nome do município',

    -- Links
    url_edital TEXT NULL COMMENT 'URL do edital (se disponível)',
    url_pncp TEXT NOT NULL COMMENT 'URL no portal PNCP',

    -- Dados do órgão
    nome_orgao VARCHAR(255) NOT NULL COMMENT 'Nome/razão social do órgão',
    cnpj_orgao VARCHAR(18) NOT NULL COMMENT 'CNPJ do órgão',

    -- Metadados
    sincronizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da sincronização',
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',

    -- Índices para performance
    INDEX idx_pncp_id (pncp_id),
    INDEX idx_uf (uf),
    INDEX idx_municipio (municipio),
    INDEX idx_uf_municipio (uf, municipio),
    INDEX idx_modalidade (modalidade),
    INDEX idx_situacao (situacao),
    INDEX idx_modalidade_situacao (modalidade, situacao),
    INDEX idx_data_abertura (data_abertura),
    INDEX idx_data_publicacao (data_publicacao),
    INDEX idx_valor (valor_estimado),
    INDEX idx_cnpj_orgao (cnpj_orgao),
    FULLTEXT INDEX idx_objeto (objeto),
    FULLTEXT INDEX idx_nome_orgao (nome_orgao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Licitações e contratos públicos';


-- ====================================================================================================
-- TABELA: itens_licitacao
-- Descrição: Armazena itens individuais de cada licitação
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS itens_licitacao (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID do item',
    licitacao_id CHAR(36) NOT NULL COMMENT 'ID da licitação',

    numero_item INT NOT NULL COMMENT 'Número sequencial do item',
    descricao TEXT NOT NULL COMMENT 'Descrição do item',
    quantidade DECIMAL(15, 3) NOT NULL COMMENT 'Quantidade',
    unidade VARCHAR(20) NOT NULL COMMENT 'Unidade de medida',
    valor_unitario DECIMAL(15, 2) NULL COMMENT 'Valor unitário',
    valor_total DECIMAL(15, 2) NULL COMMENT 'Valor total do item',

    -- Chave estrangeira
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,

    -- Índices
    INDEX idx_licitacao_id (licitacao_id),
    INDEX idx_numero_item (numero_item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens das licitações';


-- ====================================================================================================
-- TABELA: favoritos
-- Descrição: Licitações favoritadas pelos usuários
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS favoritos (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID do favorito',
    usuario_id CHAR(36) NOT NULL COMMENT 'ID do usuário',
    licitacao_id CHAR(36) NOT NULL COMMENT 'ID da licitação',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data que favoritou',

    -- Chaves estrangeiras
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,

    -- Garantir que não favorite duas vezes
    UNIQUE KEY uq_usuario_licitacao (usuario_id, licitacao_id),

    -- Índices
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Favoritos dos usuários';


-- ====================================================================================================
-- TABELA: alertas
-- Descrição: Alertas personalizados configurados pelos usuários
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS alertas (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID do alerta',
    usuario_id CHAR(36) NOT NULL COMMENT 'ID do usuário',

    nome VARCHAR(255) NOT NULL COMMENT 'Nome do alerta',
    ativo BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Alerta está ativo?',

    -- Filtros em JSON
    filtros JSON NOT NULL COMMENT 'Filtros do alerta (JSON)',

    -- Configurações
    frequencia ENUM('IMEDIATA', 'DIARIA', 'SEMANAL') NOT NULL DEFAULT 'DIARIA' COMMENT 'Frequência de envio',
    ultimo_envio DATETIME NULL COMMENT 'Data do último envio',

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',

    -- Chave estrangeira
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,

    -- Índices
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_frequencia (frequencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alertas personalizados';


-- ====================================================================================================
-- TABELA: historico_buscas
-- Descrição: Histórico de buscas realizadas pelos usuários
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS historico_buscas (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID do histórico',
    usuario_id CHAR(36) NOT NULL COMMENT 'ID do usuário',
    termo_busca VARCHAR(500) NOT NULL COMMENT 'Termo pesquisado',
    filtros JSON NULL COMMENT 'Filtros aplicados (JSON)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da busca',

    -- Chave estrangeira
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,

    -- Índices
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de buscas';


-- ====================================================================================================
-- TABELA: logs_sincronizacao
-- Descrição: Logs de sincronização com APIs externas (PNCP)
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS logs_sincronizacao (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID do log',
    fonte VARCHAR(50) NOT NULL COMMENT 'Fonte da sincronização (ex: PNCP)',
    tipo VARCHAR(50) NOT NULL COMMENT 'Tipo (ex: contratos, licitacoes)',
    status VARCHAR(20) NOT NULL COMMENT 'Status (sucesso, erro, parcial)',

    registros_novos INT NOT NULL DEFAULT 0 COMMENT 'Quantidade de registros novos',
    registros_atualizados INT NOT NULL DEFAULT 0 COMMENT 'Quantidade de registros atualizados',
    registros_erro INT NOT NULL DEFAULT 0 COMMENT 'Quantidade de erros',

    mensagem TEXT NULL COMMENT 'Mensagem descritiva',
    detalhes JSON NULL COMMENT 'Detalhes adicionais (JSON)',

    iniciado DATETIME NOT NULL COMMENT 'Data/hora de início',
    finalizado DATETIME NOT NULL COMMENT 'Data/hora de término',
    duracao INT NULL COMMENT 'Duração em segundos',

    -- Índices
    INDEX idx_fonte (fonte),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_iniciado (iniciado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de sincronização';


-- ====================================================================================================
-- VERIFICAÇÃO E RESUMO
-- ====================================================================================================

-- Exibir resumo das tabelas criadas
SELECT
    '✓ Tabelas criadas com sucesso!' AS Status,
    COUNT(*) AS Total_Tabelas
FROM information_schema.tables
WHERE table_schema = 'licitapub'
  AND table_type = 'BASE TABLE';

-- Listar todas as tabelas criadas
SELECT
    TABLE_NAME AS Tabela,
    TABLE_COMMENT AS Descrição,
    TABLE_ROWS AS Registros_Estimados
FROM information_schema.tables
WHERE table_schema = 'licitapub'
  AND table_type = 'BASE TABLE'
ORDER BY TABLE_NAME;
