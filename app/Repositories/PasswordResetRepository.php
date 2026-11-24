<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Tokens de redefinição de senha.
 * O token vai só no e-mail; no banco fica apenas o hash SHA-256 dele,
 * então quem ler o banco não consegue usar os links.
 */
final class PasswordResetRepository extends Repository
{
    /** Cria um token novo (invalidando os anteriores do usuário) e devolve o token em texto. */
    public function create(int $userId, int $minutes): string
    {
        $token = bin2hex(random_bytes(32));

        $this->transaction(function () use ($userId, $minutes, $token): void {
            $this->execute('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL', [$userId]);
            $this->execute(
                'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
                [$userId, hash('sha256', $token), date('Y-m-d H:i:s', time() + $minutes * 60)]
            );
        });

        return $token;
    }

    /** Quantos links o usuário pediu na última hora (limite contra abuso). */
    public function countRecent(int $userId): int
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at >= ?',
            [$userId, date('Y-m-d H:i:s', time() - 3600)]
        );
    }

    /** Token válido (existe, não usado, não expirado) com os dados do usuário. */
    public function findValid(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return $this->fetchOne(
            'SELECT r.id AS reset_id, u.id, u.name, u.email
               FROM password_resets r
               JOIN user u ON u.id = r.user_id
              WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?',
            [hash('sha256', $token), date('Y-m-d H:i:s')]
        );
    }

    /** Marca como usado e apaga os demais links do usuário. */
    public function consume(int $resetId, int $userId): void
    {
        $this->transaction(function () use ($resetId, $userId): void {
            $this->execute('UPDATE password_resets SET used_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $resetId]);
            $this->execute('DELETE FROM password_resets WHERE user_id = ? AND id <> ?', [$userId, $resetId]);
        });
    }
}
