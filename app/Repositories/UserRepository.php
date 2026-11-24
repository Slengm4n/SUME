<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends Repository
{
    private const PUBLIC_COLUMNS = 'id, name, email, RM, codigo, profile_picture, is_master, points, google_id, created_at, (password_hash IS NOT NULL) AS has_password';

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT ' . self::PUBLIC_COLUMNS . ' FROM user WHERE id = ?', [$id]);
    }

    /** Busca para login: aceita e-mail ou RM. Inclui o hash da senha. */
    public function findForLogin(string $identifier): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::PUBLIC_COLUMNS . ', password_hash FROM user WHERE email = ? OR RM = ? LIMIT 1',
            [$identifier, $identifier]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT ' . self::PUBLIC_COLUMNS . ' FROM user WHERE email = ?', [$email]);
    }

    public function findByGoogleId(string $googleId): ?array
    {
        return $this->fetchOne('SELECT ' . self::PUBLIC_COLUMNS . ' FROM user WHERE google_id = ?', [$googleId]);
    }

    public function linkGoogle(int $id, ?string $googleId): void
    {
        $this->execute('UPDATE user SET google_id = ? WHERE id = ?', [$googleId, $id]);
    }

    public function passwordHash(int $id): ?string
    {
        return $this->value('SELECT password_hash FROM user WHERE id = ?', [$id]);
    }

    public function emailTaken(string $email, ?int $ignoreId = null): bool
    {
        return (bool) $this->value('SELECT 1 FROM user WHERE email = ? AND id <> ?', [$email, $ignoreId ?? 0]);
    }

    public function rmTaken(string $rm, ?int $ignoreId = null): bool
    {
        return (bool) $this->value('SELECT 1 FROM user WHERE RM = ? AND id <> ?', [$rm, $ignoreId ?? 0]);
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO user (name, email, RM, codigo, password_hash, google_id, is_master) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['email'],
                $data['rm'] !== '' ? $data['rm'] : null,
                $data['codigo'] !== '' ? $data['codigo'] : null,
                isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null,
                $data['google_id'] ?? null,
                (int) ($data['is_master'] ?? 0),
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $this->execute('UPDATE user SET name = ?, email = ? WHERE id = ?', [$name, $email, $id]);
    }

    public function updateByAdmin(int $id, array $data): void
    {
        $this->execute(
            'UPDATE user SET name = ?, email = ?, RM = ?, codigo = ?, is_master = ? WHERE id = ?',
            [
                $data['name'],
                $data['email'],
                $data['rm'] !== '' ? $data['rm'] : null,
                $data['codigo'] !== '' ? $data['codigo'] : null,
                (int) $data['is_master'],
                $id,
            ]
        );
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->execute('UPDATE user SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function updateAvatar(int $id, ?string $filename): void
    {
        $this->execute('UPDATE user SET profile_picture = ? WHERE id = ?', [$filename, $id]);
    }

    public function delete(int $id): void
    {
        // As FKs com ON DELETE CASCADE cuidam do resto; os DELETEs explícitos
        // garantem o mesmo comportamento em bancos antigos sem CASCADE.
        $this->transaction(function () use ($id): void {
            $this->execute('DELETE FROM feedbacks WHERE user_id = ?', [$id]);
            $this->execute('DELETE FROM confirmations WHERE user_id = ?', [$id]);
            $this->execute('DELETE FROM user WHERE id = ?', [$id]);
        });
    }

    public function search(string $term = ''): array
    {
        $sql = 'SELECT ' . self::PUBLIC_COLUMNS . ' FROM user';
        $params = [];
        if ($term !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ? OR RM LIKE ?';
            $like = '%' . $term . '%';
            $params = [$like, $like, $like];
        }

        return $this->fetchAll($sql . ' ORDER BY is_master DESC, name', $params);
    }

    public function countStudents(): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM user WHERE is_master = 0');
    }

    public function ranking(int $limit = 100): array
    {
        return $this->fetchAll(
            'SELECT id, name, profile_picture, points FROM user WHERE is_master = 0 ORDER BY points DESC, name LIMIT ' . max(1, $limit)
        );
    }

    /** Posição do aluno no ranking (1 = primeiro). */
    public function rankPosition(int $id): ?int
    {
        $points = $this->value('SELECT points FROM user WHERE id = ? AND is_master = 0', [$id]);
        if ($points === null) {
            return null;
        }

        return 1 + (int) $this->value('SELECT COUNT(*) FROM user WHERE is_master = 0 AND points > ?', [$points]);
    }

    public function addPoints(int $id, int $points): void
    {
        $this->execute('UPDATE user SET points = GREATEST(0, points + ?) WHERE id = ?', [$points, $id]);
    }

    public function deductFromAll(int $points): int
    {
        return $this->execute('UPDATE user SET points = GREATEST(0, points - ?) WHERE is_master = 0', [$points]);
    }

    public function resetAllPoints(): int
    {
        return $this->execute('UPDATE user SET points = 0');
    }
}
