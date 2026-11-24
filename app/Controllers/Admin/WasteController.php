<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Validator;
use App\Repositories\ConfirmationRepository;
use App\Repositories\MenuRepository;
use App\Services\DemandForecast;
use App\Services\WasteReport;

/** Relatório de desperdício e registro das sobras de cada refeição. */
final class WasteController
{
    private const PERIODS = [30 => '30 dias', 90 => '90 dias', 365 => '12 meses'];

    public function index(): void
    {
        $days = $this->period();
        [$from, $to] = $this->range($days);
        $menus = new MenuRepository();

        $rows = $menus->wasteRows($from, $to);
        $previous = $menus->wasteRows(
            date('Y-m-d', strtotime($from . " -{$days} days")),
            date('Y-m-d', strtotime($from . ' -1 day'))
        );

        view('pages/admin/waste', [
            'title'    => 'Desperdício',
            'days'     => $days,
            'periods'  => self::PERIODS,
            'rows'     => $rows,
            'summary'  => WasteReport::summarize($rows),
            'previous' => WasteReport::summarize($previous),
            'weekly'   => WasteReport::weekly($rows),
        ]);
    }

    public function edit(): void
    {
        $menu = $this->servedMenuOrFail();
        $confirmations = (new ConfirmationRepository())->countForMenu((int) $menu['id']);

        view('pages/admin/waste-edit', [
            'title'         => 'Registrar sobras',
            'menu'          => $menu,
            'confirmations' => $confirmations,
            'forecast'      => (new DemandForecast())->suggest($menu['menu_date'], $confirmations),
        ]);
    }

    public function update(): void
    {
        $menu = $this->servedMenuOrFail();
        $v = new Validator($_POST);
        $v->required('prepared_portions', 'Informe quantas porções foram preparadas.')
          ->digits('prepared_portions', 'Use um número inteiro.')
          ->required('leftover_portions', 'Informe quantas porções sobraram (0 se nada sobrou).')
          ->digits('leftover_portions', 'Use um número inteiro.');

        $prepared = (int) $v->value('prepared_portions');
        $leftover = (int) $v->value('leftover_portions');
        if (!$v->has('prepared_portions') && ($prepared < 1 || $prepared > 100000)) {
            $v->add('prepared_portions', 'Informe um valor entre 1 e 100000.');
        }
        if (!$v->has('leftover_portions') && !$v->has('prepared_portions') && $leftover > $prepared) {
            $v->add('leftover_portions', 'As sobras não podem ser maiores que o total preparado.');
        }

        $kgRaw = str_replace(',', '.', $v->value('waste_kg'));
        $kg = null;
        if ($kgRaw !== '') {
            if (!is_numeric($kgRaw) || (float) $kgRaw < 0 || (float) $kgRaw > 99999) {
                $v->add('waste_kg', 'Informe o peso em kg (ex.: 3,5) ou deixe em branco.');
            } else {
                $kg = round((float) $kgRaw, 2);
            }
        }

        if ($v->fails()) {
            with_input($v->errors());
            redirect('/admin/desperdicio/registrar?id=' . $menu['id']);
        }

        (new MenuRepository())->recordWaste((int) $menu['id'], $prepared, $leftover, $kg);
        flash('success', 'Sobras de ' . format_date($menu['menu_date'], true) . ' registradas: '
            . percent($leftover / $prepared) . ' de desperdício.');
        redirect(input('voltar') === 'cardapio' ? '/admin/cardapio' : '/admin/desperdicio');
    }

    public function export(): void
    {
        $days = $this->period();
        [$from, $to] = $this->range($days);

        $rows = array_map(static function (array $r): array {
            $rate = WasteReport::rowRate($r);
            $registered = $r['prepared_portions'] !== null;

            return [
                date('d/m/Y', strtotime($r['menu_date'])),
                $r['dish'],
                (int) $r['confirmations'],
                $registered ? (int) $r['prepared_portions'] : '',
                $registered ? (int) $r['prepared_portions'] - (int) $r['leftover_portions'] : '',
                $registered ? (int) $r['leftover_portions'] : '',
                $rate !== null ? number_format($rate * 100, 1, ',', '') : '',
                $r['waste_kg'] !== null ? number_format((float) $r['waste_kg'], 2, ',', '') : '',
            ];
        }, (new MenuRepository())->wasteRows($from, $to));

        csv_download(
            "sume-desperdicio-{$from}-a-{$to}.csv",
            ['Data', 'Refeição', 'Confirmados', 'Preparadas', 'Servidas', 'Sobras', 'Desperdício (%)', 'Descartado (kg)'],
            $rows
        );
    }

    private function period(): int
    {
        $days = (int) input('periodo', '30');

        return isset(self::PERIODS[$days]) ? $days : 30;
    }

    /** @return array{0: string, 1: string} */
    private function range(int $days): array
    {
        return [date('Y-m-d', strtotime('-' . ($days - 1) . ' days')), today()];
    }

    private function servedMenuOrFail(): array
    {
        $menu = (new MenuRepository())->find((int) input('id'));
        if (!$menu) {
            abort(404, 'Refeição não encontrada.');
        }
        if ($menu['menu_date'] > today()) {
            flash('error', 'Só é possível registrar sobras de refeições que já foram servidas.');
            redirect('/admin/cardapio');
        }

        return $menu;
    }
}
