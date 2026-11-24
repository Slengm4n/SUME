<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Indicadores de desperdício a partir das linhas de MenuRepository::wasteRows().
 *
 *   taxa de desperdício = sobras ÷ preparadas
 *   comparecimento      = servidas ÷ confirmadas   (servidas = preparadas − sobras)
 */
final class WasteReport
{
    public static function summarize(array $rows): array
    {
        $registered = array_values(array_filter($rows, fn ($r) => $r['prepared_portions'] !== null));

        $prepared = $leftover = $confirmedRegistered = 0;
        $kg = null;
        foreach ($registered as $r) {
            $prepared += (int) $r['prepared_portions'];
            $leftover += (int) $r['leftover_portions'];
            $confirmedRegistered += (int) $r['confirmations'];
            if ($r['waste_kg'] !== null) {
                $kg = ($kg ?? 0) + (float) $r['waste_kg'];
            }
        }
        $served = $prepared - $leftover;

        return [
            'meals'      => count($registered),
            // Refeição de hoje ainda pode estar sendo servida: não conta como pendente
            'pending'    => count(array_filter($rows, fn ($r) => $r['prepared_portions'] === null && $r['menu_date'] < today())),
            'prepared'   => $prepared,
            'leftover'   => $leftover,
            'served'     => $served,
            'kg'         => $kg,
            'waste_rate' => $prepared > 0 ? $leftover / $prepared : null,
            'attendance' => $confirmedRegistered > 0 ? $served / $confirmedRegistered : null,
        ];
    }

    /**
     * Taxa de desperdício por semana (segunda-feira como início), em ordem cronológica.
     *
     * @return list<array{week: string, prepared: int, leftover: int, rate: float, meals: int}>
     */
    public static function weekly(array $rows): array
    {
        $weeks = [];
        foreach ($rows as $r) {
            if ($r['prepared_portions'] === null) {
                continue;
            }
            $monday = date('Y-m-d', strtotime('monday this week', strtotime($r['menu_date'])));
            $weeks[$monday] ??= ['week' => $monday, 'prepared' => 0, 'leftover' => 0, 'meals' => 0];
            $weeks[$monday]['prepared'] += (int) $r['prepared_portions'];
            $weeks[$monday]['leftover'] += (int) $r['leftover_portions'];
            $weeks[$monday]['meals']++;
        }
        ksort($weeks);

        return array_values(array_map(static function (array $w): array {
            $w['rate'] = $w['prepared'] > 0 ? $w['leftover'] / $w['prepared'] : 0.0;

            return $w;
        }, $weeks));
    }

    /** Taxa de desperdício de uma linha (ou null se não registrada). */
    public static function rowRate(array $row): ?float
    {
        if ($row['prepared_portions'] === null || (int) $row['prepared_portions'] === 0) {
            return null;
        }

        return (int) $row['leftover_portions'] / (int) $row['prepared_portions'];
    }
}
