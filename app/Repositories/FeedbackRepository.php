<?php

declare(strict_types=1);

namespace App\Repositories;

final class FeedbackRepository extends Repository
{
    public function findByUserAndMeal(int $userId, int $mealId): ?array
    {
        return $this->fetchOne('SELECT * FROM feedbacks WHERE user_id = ? AND meal_id = ?', [$userId, $mealId]);
    }

    /** Registra a avaliação e soma os pontos. Retorna false se já existia. */
    public function create(int $userId, int $mealId, int $rating, string $comment, int $points): bool
    {
        return $this->transaction(function () use ($userId, $mealId, $rating, $comment, $points): bool {
            try {
                $this->execute(
                    'INSERT INTO feedbacks (user_id, meal_id, rating, comment) VALUES (?, ?, ?, ?)',
                    [$userId, $mealId, $rating, $comment !== '' ? $comment : null]
                );
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

    public function latest(?int $rating = null, int $limit = 100): array
    {
        $sql = 'SELECT f.*, u.name, u.RM, u.profile_picture, m.dish, m.menu_date
                  FROM feedbacks f
                  JOIN user u ON u.id = f.user_id
                  JOIN menu m ON m.id = f.meal_id';
        $params = [];
        if ($rating !== null) {
            $sql .= ' WHERE f.rating = ?';
            $params[] = $rating;
        }

        return $this->fetchAll($sql . ' ORDER BY f.created_at DESC LIMIT ' . max(1, $limit), $params);
    }

    /** Média geral e distribuição de notas (1 a 5). */
    public function summary(): array
    {
        $rows = $this->fetchAll('SELECT rating, COUNT(*) AS total FROM feedbacks GROUP BY rating');
        $distribution = array_fill(1, 5, 0);
        $sum = 0;
        $count = 0;
        foreach ($rows as $row) {
            $r = (int) $row['rating'];
            if ($r >= 1 && $r <= 5) {
                $distribution[$r] = (int) $row['total'];
                $sum += $r * (int) $row['total'];
                $count += (int) $row['total'];
            }
        }

        return [
            'average'      => $count ? round($sum / $count, 1) : null,
            'count'        => $count,
            'distribution' => $distribution,
        ];
    }
}
