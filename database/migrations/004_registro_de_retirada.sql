-- Registro de retirada: a cozinha marca quem realmente pegou a refeição.
-- Os pontos passam a valer na retirada (e quem confirma e não aparece perde pontos
-- quando a cozinha encerra a retirada do dia).
-- Uso: mysql -u root sume_db < database/migrations/004_registro_de_retirada.sql
-- Feito para MariaDB (XAMPP). No MySQL 8, remova os "IF NOT EXISTS".

ALTER TABLE confirmations ADD COLUMN IF NOT EXISTS picked_up_at TIMESTAMP NULL;
ALTER TABLE confirmations ADD COLUMN IF NOT EXISTS points_awarded INT NOT NULL DEFAULT 0;
ALTER TABLE menu ADD COLUMN IF NOT EXISTS pickup_closed_at TIMESTAMP NULL;

-- Confirmações antigas já tinham rendido 10 pontos na regra anterior:
-- registramos isso para que um cancelamento continue devolvendo o valor certo.
UPDATE confirmations SET points_awarded = 10 WHERE points_awarded = 0 AND picked_up_at IS NULL;
