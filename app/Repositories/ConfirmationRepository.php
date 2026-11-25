<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Confirmações de presença e retirada das refeições.
 *
 * Pontos: cada confirmação guarda quanto já rendeu ao aluno (points_awarded).
 * O valor que ela DEVE render depende só do estado atual:
 *
 *     retirou                         → +points.pickup
 *     não retirou e retirada encerrada → −points.no_show
 *     caso contrário                   → 0
 *
 * Toda mudança (marcar/desmarcar retirada, encerrar/reabrir) chama settle(),
 * que aplica só a diferença. Assim qualquer ação pode ser desfeita sem
 * dar ou tirar pontos a mais.
 */
final class ConfirmationRepository extends Repository
{
    public function exists(int $userId, int $menuId): bool
    {
        return (bool) $this->value('SELECT 1 FROM confirmations WHERE user_id = ? AND menu_id = ?', [$userId, $menuId]);
    }

    public function pickedUp(int $userId, int $menuId): bool
    {
        return (bool) $this->value(
            'SELECT 1 FROM confirmations WHERE user_id = ? AND menu_id = ? AND picked_up_at IS NOT NULL',
            [$userId, $menuId]
        );
    }

    /** Confirma presença. Retorna false se o aluno já tinha confirmado. */
    public function confirm(int $userId, int $menuId): bool
    {
        return $this->transaction(function () use ($userId, $menuId): bool {
            try {
                $this->execute('INSERT INTO confirmations (user_id, menu_id) VALUES (?, ?)', [$userId, $menuId]);
            } catch (\PDOException $e) {
                if (self::isDuplicateKey($e)) {
                    return false;
                }
                throw $e;
            }
            $this->settle($menuId, $userId);

            return true;
        });
    }

    /**
     * Cancela a presença e desfaz os pontos que ela rendeu.
     *
     * @return 'cancelled'|'not_found'|'picked_up'
     */
    public function cancel(int $userId, int $menuId): string
    {
        return $this->transaction(function () use ($userId, $menuId): string {
            $row = $this->fetchOne(
                'SELECT id, picked_up_at, points_awarded FROM confirmations WHERE user_id = ? AND menu_id = ? FOR UPDATE',
                [$userId, $menuId]
            );
            if (!$row) {
                return 'not_found';
            }
            if ($row['picked_up_at'] !== null) {
                return 'picked_up';
            }
            $this->execute('DELETE FROM confirmations WHERE id = ?', [$row['id']]);
            $this->addPoints($userId, -(int) $row['points_awarded']);

            return 'cancelled';
        });
    }

    /** Marca (ou desmarca) que o aluno retirou a refeição. Retorna false se ele não tinha confirmado. */
    public function setPickedUp(int $menuId, int $userId, bool $pickedUp): bool
    {
        return $this->transaction(function () use ($menuId, $userId, $pickedUp): bool {
            $changed = $this->execute(
                'UPDATE confirmations SET picked_up_at = ' . ($pickedUp ? 'COALESCE(picked_up_at, NOW())' : 'NULL')
                . ' WHERE menu_id = ? AND user_id = ?',
                [$menuId, $userId]
            );
            if ($changed === 0 && !$this->exists($userId, $menuId)) {
                return false;
            }
            $this->settle($menuId, $userId);

            return true;
        });
    }

    /** Encerra (ou reabre) a retirada: quem confirmou e não retirou perde (ou recupera) os pontos. */
    public function setPickupClosed(int $menuId, bool $closed): void
    {
        $this->transaction(function () use ($menuId, $closed): void {
            $this->execute(
                'UPDATE menu SET pickup_closed_at = ' . ($closed ? 'COALESCE(pickup_closed_at, NOW())' : 'NULL') . ' WHERE id = ?',
                [$menuId]
            );
            $this->settle($menuId);
        });
    }

    public function countForMenu(int $menuId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM confirmations WHERE menu_id = ?', [$menuId]);
    }

    public function pickedUpCountForMenu(int $menuId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM confirmations WHERE menu_id = ? AND picked_up_at IS NOT NULL', [$menuId]);
    }

    /** Alunos confirmados numa refeição (uma única consulta, sem N+1). */
    public function usersForMenu(int $menuId): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.name, u.RM, u.profile_picture, c.confirmed_at, c.picked_up_at
               FROM confirmations c
               JOIN user u ON u.id = c.user_id
              WHERE c.menu_id = ?
              ORDER BY u.name',
            [$menuId]
        );
    }

    /** IDs das refeições que o aluno confirmou dentre as informadas. */
    public function menuIdsConfirmedBy(int $userId, array $menuIds): array
    {
        return $this->menuIdsFor($userId, $menuIds, false);
    }

    /** IDs das refeições que o aluno retirou dentre as informadas. */
    public function menuIdsPickedUpBy(int $userId, array $menuIds): array
    {
        return $this->menuIdsFor($userId, $menuIds, true);
    }

    /** Quantas refeições o aluno realmente retirou. */
    public function pickedUpCountForUser(int $userId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM confirmations WHERE user_id = ? AND picked_up_at IS NOT NULL', [$userId]);
    }

    /**
     * Comparecimento do aluno nas refeições com retirada encerrada.
     *
     * @return array{confirmed: int, picked_up: int}
     */
    public function attendanceForUser(int $userId): array
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS confirmed, COALESCE(SUM(c.picked_up_at IS NOT NULL), 0) AS picked_up
               FROM confirmations c
               JOIN menu m ON m.id = c.menu_id
              WHERE c.user_id = ? AND m.pickup_closed_at IS NOT NULL',
            [$userId]
        );

        return ['confirmed' => (int) $row['confirmed'], 'picked_up' => (int) $row['picked_up']];
    }

    private function menuIdsFor(int $userId, array $menuIds, bool $pickedUpOnly): array
    {
        if (!$menuIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
        $rows = $this->fetchAll(
            "SELECT menu_id FROM confirmations WHERE user_id = ? AND menu_id IN ($placeholders)"
            . ($pickedUpOnly ? ' AND picked_up_at IS NOT NULL' : ''),
            array_merge([$userId], array_values($menuIds))
        );

        return array_map('intval', array_column($rows, 'menu_id'));
    }

    /**
     * Ajusta os pontos das confirmações de uma refeição (ou de um aluno nela)
     * para o valor que o estado atual manda. Deve rodar dentro de transaction().
     */
    private function settle(int $menuId, ?int $userId = null): void
    {
        $rows = $this->fetchAll(
            'SELECT c.id, c.user_id, c.picked_up_at, c.points_awarded, m.pickup_closed_at
               FROM confirmations c
               JOIN menu m ON m.id = c.menu_id
              WHERE c.menu_id = ?' . ($userId !== null ? ' AND c.user_id = ?' : '') . '
              FOR UPDATE',
            $userId !== null ? [$menuId, $userId] : [$menuId]
        );

        foreach ($rows as $row) {
            $target = match (true) {
                $row['picked_up_at'] !== null     => (int) config('points.pickup'),
                $row['pickup_closed_at'] !== null => -(int) config('points.no_show'),
                default                           => 0,
            };
            $delta = $target - (int) $row['points_awarded'];
            if ($delta === 0) {
                continue;
            }
            // O saldo nunca fica negativo: guardamos só o que foi de fato aplicado,
            // para que desfazer devolva exatamente o mesmo valor.
            $applied = $this->addPoints((int) $row['user_id'], $delta);
            $this->execute('UPDATE confirmations SET points_awarded = points_awarded + ? WHERE id = ?', [$applied, $row['id']]);
        }
    }

    /** Soma (ou subtrai) pontos sem deixar o saldo negativo. Retorna o quanto realmente mudou. */
    private function addPoints(int $userId, int $delta): int
    {
        if ($delta === 0) {
            return 0;
        }
        $current = (int) $this->value('SELECT points FROM user WHERE id = ? FOR UPDATE', [$userId]);
        $new = max(0, $current + $delta);
        $this->execute('UPDATE user SET points = ? WHERE id = ?', [$new, $userId]);

        return $new - $current;
    }
}
