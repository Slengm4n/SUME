<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MenuRepository;

/**
 * Sugere quantas porções preparar a partir das confirmações e do histórico.
 *
 * Ideia: nem todo aluno que confirma aparece (e alguns aparecem sem confirmar).
 * Olhamos as refeições anteriores e medimos a "taxa de comparecimento":
 *
 *     servidas = preparadas − sobras
 *     taxa     = servidas ÷ confirmadas
 *
 * Usamos a MEDIANA das taxas (resiste a dias atípicos), preferindo o mesmo dia
 * da semana quando há amostras suficientes. A sugestão é:
 *
 *     ceil(confirmadas × taxa × (1 + margem de segurança))
 */
final class DemandForecast
{
    public const SAFETY_MARGIN = 0.05;
    private const MIN_SAMPLES = 3;
    private const WEEKDAYS = ['domingos', 'segundas-feiras', 'terças-feiras', 'quartas-feiras', 'quintas-feiras', 'sextas-feiras', 'sábados'];

    /** @var array<int, array{weekday: int, ratio: float}>|null */
    private ?array $samples = null;

    public function __construct(private ?MenuRepository $menus = null)
    {
        $this->menus ??= new MenuRepository();
    }

    /**
     * @return array{portions: int, ratio: float, samples: int, basis: string}
     */
    public function suggest(string $menuDate, int $confirmations): array
    {
        $weekday = (int) date('w', strtotime($menuDate));
        $all = $this->samples();
        $sameDay = array_values(array_filter($all, fn ($s) => $s['weekday'] === $weekday));

        if (count($sameDay) >= self::MIN_SAMPLES) {
            $ratio = self::median(array_column($sameDay, 'ratio'));
            $basis = 'comparecimento típico das ' . self::WEEKDAYS[$weekday] . ' (' . count($sameDay) . ' registros)';
            $samples = count($sameDay);
        } elseif (count($all) >= self::MIN_SAMPLES) {
            $ratio = self::median(array_column($all, 'ratio'));
            $basis = 'comparecimento típico das últimas ' . count($all) . ' refeições registradas';
            $samples = count($all);
        } else {
            $ratio = 1.0;
            $basis = 'ainda sem histórico: confirmados + margem de segurança';
            $samples = 0;
        }

        return [
            'portions' => $confirmations > 0 ? (int) ceil($confirmations * $ratio * (1 + self::SAFETY_MARGIN)) : 0,
            'ratio'    => $ratio,
            'samples'  => $samples,
            'basis'    => $basis,
        ];
    }

    /** Taxas de comparecimento das refeições registradas (ignora dias sem confirmação). */
    private function samples(): array
    {
        if ($this->samples === null) {
            $this->samples = [];
            foreach ($this->menus->forecastHistory() as $row) {
                $confirmed = (int) $row['confirmations'];
                if ($confirmed === 0) {
                    continue;
                }
                $served = max(0, (int) $row['prepared_portions'] - (int) $row['leftover_portions']);
                $this->samples[] = [
                    'weekday' => (int) date('w', strtotime($row['menu_date'])),
                    // Limita a valores plausíveis para um erro de digitação não distorcer tudo
                    'ratio'   => min(2.0, max(0.3, $served / $confirmed)),
                ];
            }
        }

        return $this->samples;
    }

    private static function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }
}
