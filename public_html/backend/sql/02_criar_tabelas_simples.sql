/*
====================================================================================================
LICITA.PUB - CRIAÇÃO DAS TABELAS (VERSÃO SIMPLIFICADA PARA XAMPP)
====================================================================================================
INSTRUÇÕES:
1. Abra o phpMyAdmin (http://localhost/phpmyadmin)
2. Clique no banco "licitapub" na sidebar esquerda
3. Clique em "SQL" no menu superior
4. Cole TODO este script
5. Clique em "Executar"
====================================================================================================
*/

USE licitapub;

-- Remove tabelas existentes (cuidado: apaga dados!)
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS historico_buscas;
DROP TABLE IF EXISTS itens_licitacao;
DROP TABLE IF EXISTS licitacoes;
DROP TABLE IF EXISTS logs_sincronizacao;
DROP TABLE IF EXISTS usuarios;

-- TABELA: usuarios
CREATE TABLE usuarios (
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
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: licitacoes
CREATE TABLE licitacoes (
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
    INDEX idx_uf (uf),
    INDEX idx_municipio (municipio),
    INDEX idx_modalidade (modalidade),
    INDEX idx_situacao (situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: itens_licitacao
CREATE TABLE itens_licitacao (
    id CHAR(36) PRIMARY KEY,
    licitacao_id CHAR(36) NOT NULL,
    numero_item INT NOT NULL,
    descricao TEXT NOT NULL,
    quantidade DECIMAL(15, 3) NOT NULL,
    unidade VARCHAR(20) NOT NULL,
    valor_unitario DECIMAL(15, 2) NULL,
    valor_total DECIMAL(15, 2) NULL,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
    INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: favoritos
CREATE TABLE favoritos (
    id CHAR(36) PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    licitacao_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_usuario_licitacao (usuario_id, licitacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: alertas
CREATE TABLE alertas (
    id CHAR(36) PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    filtros JSON NOT NULL,
    frequencia ENUM('IMEDIATA', 'DIARIA', 'SEMANAL') NOT NULL DEFAULT 'DIARIA',
    ultimo_envio DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: historico_buscas
CREATE TABLE historico_buscas (
    id CHAR(36) PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    termo_busca VARCHAR(500) NOT NULL,
    filtros JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: logs_sincronizacao
CREATE TABLE logs_sincronizacao (
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
    duracao INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✓ 7 tabelas criadas com sucesso!' AS Status;
SHOW TABLES;
