<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ConfirmationRepository;
use App\Repositories\MenuRepository;
use App\Services\DemandForecast;

/** Acompanhamento das presenças confirmadas (atualiza sozinho a cada 10s). */
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
        $data = $this->data($this->requestedDate());

        json_response([
            'date'  => $data['date'],
            'menu'  => $data['menu'] ? ['id' => (int) $data['menu']['id'], 'dish' => $data['menu']['dish']] : null,
            'total' => count($data['people']),
            'forecast' => $data['forecast']['portions'] ?? null,
            'people' => array_map(static fn (array $p) => [
                'id'     => (int) $p['id'],
                'name'   => $p['name'],
                'rm'     => $p['RM'],
                'avatar' => avatar_url($p),
                'initials' => initials($p['name']),
                'time'   => date('H:i', strtotime($p['confirmed_at'])),
            ], $data['people']),
        ]);
    }

    /** Lista de confirmados do dia em CSV (abre no Excel). */
    public function export(): void
    {
        $data = $this->data($this->requestedDate());
        $rows = array_map(static fn (array $p) => [
            $p['name'],
            $p['RM'] ?? '',
            date('H:i', strtotime($p['confirmed_at'])),
            '',
        ], array_reverse($data['people']));

        csv_download("sume-confirmados-{$data['date']}.csv", ['Aluno', 'RM', 'Confirmou às', 'Retirou (marcar)'], $rows);
    }

    private function requestedDate(): string
    {
        $date = input('data', today());
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date ? $date : today();
    }

    private function data(string $date): array
    {
        $menu = (new MenuRepository())->forDate($date);
        $people = $menu ? (new ConfirmationRepository())->usersForMenu((int) $menu['id']) : [];

        return [
            'date'     => $date,
            'menu'     => $menu,
            'people'   => $people,
            'forecast' => $menu ? (new DemandForecast())->suggest($date, count($people)) : null,
        ];
    }
}
