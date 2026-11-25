<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ConfirmationRepository;
use App\Repositories\MenuRepository;
use App\Services\DemandForecast;

/**
 * Acompanhamento das presenças e registro de retirada na hora de servir.
 * A página do dia atualiza sozinha a cada 10s.
 */
final class ConfirmationController
{
    public function index(): void
    {
        $data = $this->data($this->requestedDate());
        view('pages/admin/confirmations', ['title' => 'Confirmações'] + $data);
    }

    /** Mesmo conteúdo em JSON para a atualização automática da página. */
    public function json(): void
    {
        json_response($this->payload($this->data($this->requestedDate())));
    }

    /** Marca/desmarca que um aluno retirou a refeição. */
    public function pickup(): void
    {
        $menu = $this->menuForPickup();
        $pickedUp = input('picked_up') === '1';
        $ok = (new ConfirmationRepository())->setPickedUp((int) $menu['id'], (int) input('user_id'), $pickedUp);

        if ($this->wantsJson()) {
            if (!$ok) {
                json_response(['error' => 'Esse aluno não confirmou presença nessa refeição.'], 404);
            }
            json_response($this->payload($this->data($menu['menu_date'])));
        }

        flash($ok ? 'success' : 'error', $ok
            ? ($pickedUp ? 'Retirada registrada.' : 'Retirada desmarcada.')
            : 'Esse aluno não confirmou presença nessa refeição.');
        redirect('/admin/confirmacoes?data=' . $menu['menu_date']);
    }

    /** Encerra (ou reabre) a retirada do dia: aplica (ou devolve) a perda de pontos de quem faltou. */
    public function close(): void
    {
        $menu = $this->menuForPickup();
        $close = input('closed') === '1';
        $repo = new ConfirmationRepository();
        $repo->setPickupClosed((int) $menu['id'], $close);

        if ($close) {
            $missing = $repo->countForMenu((int) $menu['id']) - $repo->pickedUpCountForMenu((int) $menu['id']);
            flash('success', $missing > 0
                ? "Retirada encerrada. {$missing} aluno(s) confirmaram e não retiraram (−" . config('points.no_show') . ' pontos cada).'
                : 'Retirada encerrada. Todos os confirmados retiraram!');
        } else {
            flash('success', 'Retirada reaberta. Os pontos perdidos por quem não retirou foram devolvidos.');
        }
        redirect('/admin/confirmacoes?data=' . $menu['menu_date']);
    }

    /** Lista de confirmados do dia em CSV (abre no Excel). */
    public function export(): void
    {
        $data = $this->data($this->requestedDate());
        $rows = array_map(static fn (array $p) => [
            $p['name'],
            $p['RM'] ?? '',
            date('H:i', strtotime($p['confirmed_at'])),
            $p['picked_up_at'] ? 'Sim (' . date('H:i', strtotime($p['picked_up_at'])) . ')' : '',
        ], $data['people']);

        csv_download("sume-confirmados-{$data['date']}.csv", ['Aluno', 'RM', 'Confirmou às', 'Retirou'], $rows);
    }

    private function requestedDate(): string
    {
        $date = input('data', today());
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date ? $date : today();
    }

    /** Refeição alvo de uma ação de retirada: precisa existir e não pode ser futura. */
    private function menuForPickup(): array
    {
        $menu = (new MenuRepository())->find((int) input('menu_id'));
        if (!$menu) {
            abort(404, 'Refeição não encontrada.');
        }
        if ($menu['menu_date'] > today()) {
            $this->wantsJson()
                ? json_response(['error' => 'A retirada só pode ser registrada no dia da refeição.'], 422)
                : flash('error', 'A retirada só pode ser registrada no dia da refeição.');
            redirect('/admin/confirmacoes?data=' . $menu['menu_date']);
        }

        return $menu;
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function data(string $date): array
    {
        $menu = (new MenuRepository())->forDate($date);
        $people = $menu ? (new ConfirmationRepository())->usersForMenu((int) $menu['id']) : [];

        return [
            'date'     => $date,
            'menu'     => $menu,
            'people'   => $people,
            'pickedUp' => count(array_filter($people, static fn (array $p) => $p['picked_up_at'] !== null)),
            'forecast' => $menu ? (new DemandForecast())->suggest($date, count($people)) : null,
        ];
    }

    private function payload(array $data): array
    {
        return [
            'date'     => $data['date'],
            'menu'     => $data['menu'] ? [
                'id'     => (int) $data['menu']['id'],
                'dish'   => $data['menu']['dish'],
                'closed' => $data['menu']['pickup_closed_at'] !== null,
            ] : null,
            'total'    => count($data['people']),
            'pickedUp' => $data['pickedUp'],
            'forecast' => $data['forecast']['portions'] ?? null,
            'people'   => array_map(static fn (array $p) => [
                'id'       => (int) $p['id'],
                'name'     => $p['name'],
                'rm'       => $p['RM'],
                'avatar'   => avatar_url($p),
                'initials' => initials($p['name']),
                'time'     => date('H:i', strtotime($p['confirmed_at'])),
                'pickedUp' => $p['picked_up_at'] !== null ? date('H:i', strtotime($p['picked_up_at'])) : null,
            ], $data['people']),
        ];
    }
}
