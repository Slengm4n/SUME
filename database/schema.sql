-- SUME - Sistema Único de Merenda Escolar
-- Schema completo para uma instalação nova (MySQL 5.7+ / MariaDB 10.3+).
-- Uso: mysql -u root < database/schema.sql
-- Para atualizar um banco antigo, use database/migrations/001_atualiza_banco_legado.sql

CREATE DATABASE IF NOT EXISTS sume_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sume_db;

-- Usuários (alunos e administradores)
CREATE TABLE IF NOT EXISTS user (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    RM              VARCHAR(20)   NULL,
    codigo          VARCHAR(20)   NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    profile_picture VARCHAR(255)  NULL,
    is_master       TINYINT(1)    NOT NULL DEFAULT 0,
    points          INT           NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_email (email),
    UNIQUE KEY uq_user_rm (RM)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cardápio: uma refeição por dia + registro de desperdício (preenchido após servir)
CREATE TABLE IF NOT EXISTS menu (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    menu_date         DATE          NOT NULL,
    dish              VARCHAR(255)  NOT NULL,
    prepared_portions INT UNSIGNED  NULL,
    leftover_portions INT UNSIGNED  NULL,
    waste_kg          DECIMAL(7,2) UNSIGNED NULL,
    waste_recorded_at TIMESTAMP     NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_menu_date (menu_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Confirmações de presença
CREATE TABLE IF NOT EXISTS confirmations (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    menu_id       INT UNSIGNED NOT NULL,
    confirmed_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_confirmation (user_id, menu_id),
    KEY idx_confirmation_menu (menu_id),
    CONSTRAINT fk_confirmation_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
    CONSTRAINT fk_confirmation_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avaliações das refeições
CREATE TABLE IF NOT EXISTS feedbacks (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED     NOT NULL,
    meal_id     INT UNSIGNED     NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    comment     TEXT             NULL,
    created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_feedback (user_id, meal_id),
    KEY idx_feedback_meal (meal_id),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_menu FOREIGN KEY (meal_id) REFERENCES menu (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
