<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Repositories\ConfirmationRepository;
use App\Repositories\FeedbackRepository;
use App\Repositories\MenuRepository;
use App\Repositories\UserRepository;
use App\Services\DemandForecast;
use App\Services\WasteReport;

final class DashboardController
{
    public function index(): void
    {
        Auth::isAdmin() ? $this->admin() : $this->student();
    }

    private function student(): void
    {
        $user = Auth::user();
        $menus = new MenuRepository();
        $confirmations = new ConfirmationRepository();

        $feedbacks = new FeedbackRepository();
        $userId = (int) $user['id'];

        $today = $menus->forDate(today());
        $upcoming = $menus->upcoming(6);
        $confirmedIds = $confirmations->menuIdsConfirmedBy($userId, array_column($upcoming, 'id'));

        // Refeições (hoje + últimos 7 dias) que o aluno confirmou e ainda não avaliou
        $recent = array_merge($today ? [$today] : [], $menus->history(7));
        $recentConfirmed = $confirmations->menuIdsConfirmedBy($userId, array_column($recent, 'id'));
        $pendingFeedback = array_values(array_filter(
            $recent,
            fn (array $m) => in_array((int) $m['id'], $recentConfirmed, true)
                && !$feedbacks->findByUserAndMeal($userId, (int) $m['id'])
        ));

        view('pages/dashboard/student', [
            'title'           => 'Painel',
            'user'            => $user,
            'today'           => $today,
            'todayConfirmed'  => $today && in_array((int) $today['id'], $confirmedIds, true),
            'upcoming'        => array_values(array_filter($upcoming, fn ($m) => $m['menu_date'] !== today())),
            'confirmedIds'    => $confirmedIds,
            'pendingFeedback' => array_slice($pendingFeedback, 0, 3),
            'rank'            => (new UserRepository())->rankPosition($userId),
            'totalMeals'      => $confirmations->countForUser($userId),
        ]);
    }

    private function admin(): void
    {
        $menus = new MenuRepository();
        $today = $menus->forDate(today());
        $confirmations = $today ? (new ConfirmationRepository())->countForMenu((int) $today['id']) : 0;

        view('pages/dashboard/admin', [
            'title'         => 'Painel',
            'user'          => Auth::user(),
            'today'         => $today,
            'confirmations' => $confirmations,
            'forecast'      => $today ? (new DemandForecast())->suggest(today(), $confirmations) : null,
            'waste'         => WasteReport::summarize($menus->wasteRows(date('Y-m-d', strtotime('-29 days')), today())),
            'wastePending'  => $menus->pendingWasteCount(),
            'students'      => (new UserRepository())->countStudents(),
            'feedback'      => (new FeedbackRepository())->summary(),
            'upcoming'      => $menus->upcoming(7),
            'history'       => $menus->history(5),
        ]);
    }
}
