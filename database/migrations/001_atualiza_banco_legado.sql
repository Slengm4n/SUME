-- Atualiza um banco criado pela versão antiga do SUME para a estrutura nova.
-- Feito para MariaDB (o banco que vem no XAMPP), que aceita "IF NOT EXISTS"
-- em ADD COLUMN / ADD KEY. No MySQL 8, remova esses "IF NOT EXISTS".
-- FAÇA BACKUP ANTES:  mysqldump -u root sume_db > backup.sql
-- Uso:               mysql -u root sume_db < database/migrations/001_atualiza_banco_legado.sql
--
-- Se algum passo falhar por dados duplicados (ex.: dois alunos com o mesmo RM
-- ou duas refeições no mesmo dia), corrija os dados e rode o passo de novo.

-- 1) Colunas que o código usa e podem não existir
ALTER TABLE user ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL;
ALTER TABLE user ADD COLUMN IF NOT EXISTS is_master TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE user ADD COLUMN IF NOT EXISTS points INT NOT NULL DEFAULT 0;
ALTER TABLE user ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 2) Fotos de perfil: a versão antiga guardava "../uploads/arquivo.png".
--    Agora guardamos só o nome do arquivo (que fica em public/uploads/avatars/).
UPDATE user
   SET profile_picture = SUBSTRING_INDEX(profile_picture, '/', -1)
 WHERE profile_picture LIKE '%/%';

-- 3) RM vazio vira NULL (necessário para o índice único)
UPDATE user SET RM = NULL WHERE RM = '';

-- 4) Remove confirmações/feedbacks duplicados (mantém o mais antigo)
DELETE c1 FROM confirmations c1
  JOIN confirmations c2
    ON c1.user_id = c2.user_id AND c1.menu_id = c2.menu_id AND c1.id > c2.id;

DELETE f1 FROM feedbacks f1
  JOIN feedbacks f2
    ON f1.user_id = f2.user_id AND f1.meal_id = f2.meal_id AND f1.id > f2.id;

-- 5) Índices únicos (garantem as regras de negócio no próprio banco)
ALTER TABLE user          ADD UNIQUE KEY IF NOT EXISTS uq_user_rm (RM);
ALTER TABLE menu          ADD UNIQUE KEY IF NOT EXISTS uq_menu_date (menu_date);
ALTER TABLE confirmations ADD UNIQUE KEY IF NOT EXISTS uq_confirmation (user_id, menu_id);
ALTER TABLE feedbacks     ADD UNIQUE KEY IF NOT EXISTS uq_feedback (user_id, meal_id);
