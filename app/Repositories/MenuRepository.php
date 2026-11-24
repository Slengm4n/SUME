<?php

declare(strict_types=1);

namespace App\Repositories;

final class MenuRepository extends Repository
{
    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM menu WHERE id = ?', [$id]);
    }

    public function forDate(string $date): ?array
    {
        return $this->fetchOne('SELECT * FROM menu WHERE menu_date = ? ORDER BY id DESC LIMIT 1', [$date]);
    }

    /** Refeições de hoje em diante, com total de confirmações. */
    public function upcoming(int $limit = 14): array
    {
        return $this->fetchAll(
            'SELECT m.*, COUNT(c.id) AS confirmations
               FROM menu m
               LEFT JOIN confirmations c ON c.menu_id = m.id
              WHERE m.menu_date >= ?
              GROUP BY m.id
              ORDER BY m.menu_date
              LIMIT ' . max(1, $limit),
            [today()]
        );
    }

    /** Últimas refeições servidas, com confirmações e média das avaliações. */
    public function history(int $limit = 14): array
    {
        return $this->fetchAll(
            'SELECT m.*,
                    (SELECT COUNT(*) FROM confirmations c WHERE c.menu_id = m.id) AS confirmations,
                    (SELECT AVG(f.rating) FROM feedbacks f WHERE f.meal_id = m.id) AS avg_rating,
                    (SELECT COUNT(*) FROM feedbacks f WHERE f.meal_id = m.id) AS feedbacks
               FROM menu m
              WHERE m.menu_date < ?
              ORDER BY m.menu_date DESC
              LIMIT ' . max(1, $limit),
            [today()]
        );
    }

    /** Cria ou atualiza a refeição de uma data. Retorna true se criou. */
    public function save(string $date, string $dish): bool
    {
        $existing = $this->forDate($date);
        if ($existing) {
            $this->execute('UPDATE menu SET dish = ? WHERE id = ?', [$dish, $existing['id']]);

            return false;
        }

        $this->execute('INSERT INTO menu (menu_date, dish) VALUES (?, ?)', [$date, $dish]);

        return true;
    }

    /** Registra porções preparadas/sobras de uma refeição já servida. */
    public function recordWaste(int $id, int $prepared, int $leftover, ?float $kg): void
    {
        $this->execute(
            'UPDATE menu SET prepared_portions = ?, leftover_portions = ?, waste_kg = ?, waste_recorded_at = NOW() WHERE id = ?',
            [$prepared, $leftover, $kg, $id]
        );
    }

    /** Refeições servidas no período (até hoje), com confirmações e registro de sobras. */
    public function wasteRows(string $from, string $to): array
    {
        return $this->fetchAll(
            'SELECT m.id, m.menu_date, m.dish, m.prepared_portions, m.leftover_portions, m.waste_kg,
                    (SELECT COUNT(*) FROM confirmations c WHERE c.menu_id = m.id) AS confirmations
               FROM menu m
              WHERE m.menu_date BETWEEN ? AND ? AND m.menu_date <= ?
              ORDER BY m.menu_date DESC',
            [$from, $to, today()]
        );
    }

    /** Histórico usado pela previsão: refeições com sobras registradas e confirmações. */
    public function forecastHistory(int $limit = 60): array
    {
        return $this->fetchAll(
            'SELECT m.menu_date, m.prepared_portions, m.leftover_portions,
                    (SELECT COUNT(*) FROM confirmations c WHERE c.menu_id = m.id) AS confirmations
               FROM menu m
              WHERE m.prepared_portions IS NOT NULL AND m.menu_date <= ?
              ORDER BY m.menu_date DESC
              LIMIT ' . max(1, $limit),
            [today()]
        );
    }

    /** Quantas refeições já servidas ainda não tiveram as sobras registradas. */
    public function pendingWasteCount(int $days = 30): int
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM menu WHERE prepared_portions IS NULL AND menu_date < ? AND menu_date >= ?',
            [today(), date('Y-m-d', strtotime("-{$days} days"))]
        );
    }

    public function delete(int $id): void
    {
        $this->transaction(function () use ($id): void {
            $this->execute('DELETE FROM feedbacks WHERE meal_id = ?', [$id]);
            $this->execute('DELETE FROM confirmations WHERE menu_id = ?', [$id]);
            $this->execute('DELETE FROM menu WHERE id = ?', [$id]);
        });
    }
}
