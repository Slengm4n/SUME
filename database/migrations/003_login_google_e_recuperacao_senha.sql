-- Login com Google + recuperação de senha por e-mail.
-- Uso: mysql -u root sume_db < database/migrations/003_login_google_e_recuperacao_senha.sql
-- Feito para MariaDB (XAMPP). No MySQL 8, remova os "IF NOT EXISTS".

-- Contas criadas pelo Google podem não ter senha
ALTER TABLE user MODIFY password_hash VARCHAR(255) NULL;

-- Vínculo com a conta Google (campo "sub" do Google, estável e único)
ALTER TABLE user ADD COLUMN IF NOT EXISTS google_id VARCHAR(64) NULL;
ALTER TABLE user ADD UNIQUE KEY IF NOT EXISTS uq_user_google (google_id);

-- Links de redefinição de senha (guardamos só o hash do token)
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64)     NOT NULL,
    expires_at  DATETIME     NOT NULL,
    used_at     DATETIME     NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reset_token (token_hash),
    KEY idx_reset_user (user_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
