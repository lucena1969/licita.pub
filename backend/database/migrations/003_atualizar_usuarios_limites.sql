-- ============================================================================
-- Migration 003: Atualizar tabela usuarios e criar tabelas de controle
-- ============================================================================
-- Descrição: Adiciona campos de controle de consultas na tabela usuarios
--            e cria tabelas para controle de limites por IP e histórico
-- Data: 2025-10-26
-- Autor: Sistema Licita.pub
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. ATUALIZAR TABELA USUARIOS
-- ----------------------------------------------------------------------------

-- Adicionar campos de controle de consultas
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS consultas_hoje INT DEFAULT 0 COMMENT 'Contador de consultas do dia atual' AFTER plano,
ADD COLUMN IF NOT EXISTS primeira_consulta_em TIMESTAMP NULL COMMENT 'Timestamp da primeira consulta (reset após 24h)' AFTER consultas_hoje,
ADD COLUMN IF NOT EXISTS limite_diario INT DEFAULT 10 COMMENT 'FREE=10, PREMIUM=99999' AFTER primeira_consulta_em;

-- Ajustar ENUM do plano para incluir FREE (manter compatibilidade)
ALTER TABLE usuarios
MODIFY COLUMN plano ENUM('GRATUITO','FREE','BASICO','INTERMEDIARIO','PREMIUM') DEFAULT 'FREE';

-- Adicionar índices para performance
ALTER TABLE usuarios
ADD INDEX IF NOT EXISTS idx_plano_ativo (plano, ativo),
ADD INDEX IF NOT EXISTS idx_primeira_consulta (primeira_consulta_em);

-- Atualizar usuários existentes GRATUITO para FREE
UPDATE usuarios SET plano = 'FREE' WHERE plano = 'GRATUITO';

-- ----------------------------------------------------------------------------
-- 2. CRIAR TABELA LIMITES_IP (Controle para usuários anônimos)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS limites_ip (
    ip VARCHAR(45) PRIMARY KEY COMMENT 'IPv4 ou IPv6',
    consultas_hoje INT DEFAULT 0 COMMENT 'Contador de consultas do dia',
    primeira_consulta_em TIMESTAMP NULL COMMENT 'Timestamp da primeira consulta (reset após 24h)',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_primeira_consulta (primeira_consulta_em),
    INDEX idx_atualizado (atualizado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Controle de limites para usuários anônimos por IP (5 consultas/dia)';

-- ----------------------------------------------------------------------------
-- 3. CRIAR TABELA HISTORICO_CONSULTAS (Analytics)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS historico_consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id CHAR(36) NULL COMMENT 'NULL se anônimo',
    ip VARCHAR(45) NOT NULL,
    tipo_usuario ENUM('ANONIMO','FREE','PREMIUM') NOT NULL,
    licitacao_pncp_id VARCHAR(255) NOT NULL,
    filtros JSON NULL COMMENT 'Filtros aplicados na busca',
    user_agent VARCHAR(500) NULL,
    consultado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario (usuario_id),
    INDEX idx_licitacao (licitacao_pncp_id),
    INDEX idx_consultado (consultado_em),
    INDEX idx_tipo (tipo_usuario),
    INDEX idx_ip (ip),

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de todas as consultas para analytics e métricas';

-- ----------------------------------------------------------------------------
-- 4. CRIAR TABELA SESSOES (Controle de login)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sessoes (
    id VARCHAR(64) PRIMARY KEY COMMENT 'Session ID ou JWT token hash',
    usuario_id CHAR(36) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500),
    expira_em TIMESTAMP NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario (usuario_id),
    INDEX idx_expira (expira_em),

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sessões ativas de usuários logados';

-- ----------------------------------------------------------------------------
-- 5. DADOS INICIAIS / CONFIGURAÇÕES
-- ----------------------------------------------------------------------------

-- Atualizar limite diário baseado no plano (para usuários existentes)
UPDATE usuarios SET limite_diario = 10 WHERE plano IN ('GRATUITO', 'FREE');
UPDATE usuarios SET limite_diario = 99999 WHERE plano IN ('PREMIUM', 'INTERMEDIARIO', 'BASICO');

-- ----------------------------------------------------------------------------
-- VERIFICAÇÕES
-- ----------------------------------------------------------------------------

-- Verificar se as tabelas foram criadas
SELECT
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('usuarios', 'limites_ip', 'historico_consultas', 'sessoes');

-- Verificar novos campos da tabela usuarios
DESCRIBE usuarios;

-- ============================================================================
-- FIM DA MIGRATION
-- ============================================================================
