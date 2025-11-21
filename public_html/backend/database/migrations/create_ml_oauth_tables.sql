-- Tabela para armazenar states temporários (PKCE)
CREATE TABLE IF NOT EXISTS ml_oauth_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    state VARCHAR(255) NOT NULL,
    code_verifier VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY unique_user_state (user_id, state),
    INDEX idx_oauth_states_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar tokens OAuth
CREATE TABLE IF NOT EXISTS ml_oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    expires_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_oauth_tokens_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
