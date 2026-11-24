-- Controle de desperdício: a cozinha registra, após cada refeição,
-- quantas porções preparou, quantas sobraram e (opcional) o peso descartado.
-- Uso: mysql -u root sume_db < database/migrations/002_controle_desperdicio.sql
-- Feito para MariaDB (XAMPP). No MySQL 8, remova os "IF NOT EXISTS".

ALTER TABLE menu ADD COLUMN IF NOT EXISTS prepared_portions INT UNSIGNED NULL;
ALTER TABLE menu ADD COLUMN IF NOT EXISTS leftover_portions INT UNSIGNED NULL;
ALTER TABLE menu ADD COLUMN IF NOT EXISTS waste_kg DECIMAL(7,2) UNSIGNED NULL;
ALTER TABLE menu ADD COLUMN IF NOT EXISTS waste_recorded_at TIMESTAMP NULL;
