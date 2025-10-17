/*
====================================================================================================
LICITA.PUB - CRIAÇÃO DAS TABELAS (VERSÃO SIMPLIFICADA)
====================================================================================================
Descrição: Script para criar todas as tabelas do sistema
Autor: Licita.pub
Data: 2025-01-17
Versão: 1.0 (Simplificada - sem queries de verificação)

INSTRUÇÕES PARA phpMyAdmin:
1. Execute ANTES o script 01_criar_banco.sql
2. Abra o phpMyAdmin
3. Selecione o banco "licitapub" na sidebar esquerda
4. Clique em "SQL" no menu superior
5. Cole TODO este script
6. Clique em "Executar"
====================================================================================================
*/

USE licitapub;

-- ====================================================================================================
-- TABELA: usuarios
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NULL,
    cpf_cnpj VARCHAR(18) NULL UNIQUE,
    email_verificado BOOLEAN NOT NULL DEFAULT FALSE,
    token_verificacao VARCHAR(255) NULL,
    token_verificacao_expira DATETIME NULL,
    token_reset_senha VARCHAR(255) NULL,
    token_reset_senha_expira DATETIME NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    plano ENUM('GRATUITO', 'BASICO', 'INTERMEDIARIO', 'PREMIUM') NOT NULL DEFAULT 'GRATUITO',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_cpf_cnpj (cpf_cnpj),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- TABELA: licitacoes
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS licitacoes (
    id CHAR(36) PRIMARY KEY,
    pncp_id VARCHAR(100) NOT NULL UNIQUE,
    orgao_id VARCHAR(50) NOT NULL,
    numero VARCHAR(50) NOT NULL,
    objeto TEXT NOT NULL,
    modalidade VARCHAR(50) NOT NULL,
    situacao VARCHAR(30) NOT NULL,
    valor_estimado DECIMAL(15, 2) NULL,
    data_publicacao DATETIME NOT NULL,
    data_abertura DATETIME NULL,
    data_encerramento DATETIME NULL,
    uf CHAR(2) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    url_edital TEXT NULL,
    url_pncp TEXT NOT NULL,
    nome_orgao VARCHAR(255) NOT NULL,
    cnpj_orgao VARCHAR(18) NOT NULL,
    sincronizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- TABELA: itens_licitacao
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS itens_licitacao (
    id CHAR(36) PRIMARY KEY,
    licitacao_id CHAR(36) NOT NULL,
    numero_item INT NOT NULL,
    descricao TEXT NOT NULL,
    quantidade DECIMAL(15, 3) NOT NULL,
    unidade VARCHAR(20) NOT NULL,
    valor_unitario DECIMAL(15, 2) NULL,
    valor_total DECIMAL(15, 2) NULL,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
    INDEX idx_licitacao_id (licitacao_id),
    INDEX idx_numero_item (numero_item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- TABELA: favoritos
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS favoritos (
    id CHAR(36) PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    licitacao_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_usuario_licitacao (usuario_id, licitacao_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- TABELA: alertas
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS alertas (
    id CHAR(36) PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    filtros JSON NOT NULL,
    frequencia ENUM('IMEDIATA', 'DIARIA', 'SEMANAL') NOT NULL DEFAULT 'DIARIA',
    ultimo_envio DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_frequencia (frequencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- TABELA: historico_buscas
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS historico_buscas (
    id CHAR(36) PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    termo_busca VARCHAR(500) NOT NULL,
    filtros JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- TABELA: logs_sincronizacao
-- ====================================================================================================
CREATE TABLE IF NOT EXISTS logs_sincronizacao (
    id CHAR(36) PRIMARY KEY,
    fonte VARCHAR(50) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    registros_novos INT NOT NULL DEFAULT 0,
    registros_atualizados INT NOT NULL DEFAULT 0,
    registros_erro INT NOT NULL DEFAULT 0,
    mensagem TEXT NULL,
    detalhes JSON NULL,
    iniciado DATETIME NOT NULL,
    finalizado DATETIME NOT NULL,
    duracao INT NULL,
    INDEX idx_fonte (fonte),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_iniciado (iniciado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================================================
-- FIM DO SCRIPT
-- ====================================================================================================
-- Tabelas criadas com sucesso!
-- Para verificar, execute: SHOW TABLES;
