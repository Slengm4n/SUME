<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConfirmationRepository extends Repository
{
    public function exists(int $userId, int $menuId): bool
    {
        return (bool) $this->value('SELECT 1 FROM confirmations WHERE user_id = ? AND menu_id = ?', [$userId, $menuId]);
    }

    /**
     * Confirma presença e soma os pontos numa única transação.
     * Retorna false se o aluno já tinha confirmado.
     */
    public function confirm(int $userId, int $menuId, int $points): bool
    {
        return $this->transaction(function () use ($userId, $menuId, $points): bool {
            try {
                $this->execute('INSERT INTO confirmations (user_id, menu_id) VALUES (?, ?)', [$userId, $menuId]);
            } catch (\PDOException $e) {
                if (self::isDuplicateKey($e)) {
                    return false;
                }
                throw $e;
            }
            $this->execute('UPDATE user SET points = points + ? WHERE id = ?', [$points, $userId]);

            return true;
        });
    }

    /** Cancela a presença e devolve os pontos ganhos. */
    public function cancel(int $userId, int $menuId, int $points): bool
    {
        return $this->transaction(function () use ($userId, $menuId, $points): bool {
            $deleted = $this->execute('DELETE FROM confirmations WHERE user_id = ? AND menu_id = ?', [$userId, $menuId]);
            if ($deleted === 0) {
                return false;
            }
            $this->execute('UPDATE user SET points = GREATEST(0, points - ?) WHERE id = ?', [$points, $userId]);

            return true;
        });
    }

    public function countForMenu(int $menuId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM confirmations WHERE menu_id = ?', [$menuId]);
    }

    /** Alunos confirmados numa refeição (uma única consulta, sem N+1). */
    public function usersForMenu(int $menuId): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.name, u.RM, u.profile_picture, c.confirmed_at
               FROM confirmations c
               JOIN user u ON u.id = c.user_id
              WHERE c.menu_id = ?
              ORDER BY c.confirmed_at DESC',
            [$menuId]
        );
    }

    /** IDs das refeições que o aluno confirmou dentre as informadas. */
    public function menuIdsConfirmedBy(int $userId, array $menuIds): array
    {
        if (!$menuIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
        $rows = $this->fetchAll(
            "SELECT menu_id FROM confirmations WHERE user_id = ? AND menu_id IN ($placeholders)",
            array_merge([$userId], array_values($menuIds))
        );

        return array_map('intval', array_column($rows, 'menu_id'));
    }

    public function countForUser(int $userId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM confirmations WHERE user_id = ?', [$userId]);
    }
}
