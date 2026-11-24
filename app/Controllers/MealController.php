<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Repositories\ConfirmationRepository;
use App\Repositories\FeedbackRepository;
use App\Repositories\MenuRepository;

/** Ações do aluno sobre as refeições: confirmar, cancelar e avaliar. */
final class MealController
{
    public function confirm(): void
    {
        $menu = $this->menuFromRequest();
        $this->ensureOpen($menu);

        $ok = (new ConfirmationRepository())->confirm(Auth::id(), (int) $menu['id'], (int) config('points.confirmation'));
        flash(
            $ok ? 'success' : 'info',
            $ok ? 'Presença confirmada! +' . config('points.confirmation') . ' pontos.' : 'Você já tinha confirmado essa refeição.'
        );
        back();
    }

    public function cancel(): void
    {
        $menu = $this->menuFromRequest();
        $this->ensureOpen($menu);

        if ((new FeedbackRepository())->findByUserAndMeal(Auth::id(), (int) $menu['id'])) {
            flash('error', 'Você já avaliou essa refeição, então não é possível cancelar a presença.');
            back();
        }

        $ok = (new ConfirmationRepository())->cancel(Auth::id(), (int) $menu['id'], (int) config('points.confirmation'));
        flash($ok ? 'success' : 'info', $ok ? 'Presença cancelada. Obrigado por avisar!' : 'Você não tinha confirmado essa refeição.');
        back();
    }

    public function showFeedback(): void
    {
        $menu = $this->feedbackMenu();

        view('pages/feedback', [
            'title'    => 'Avaliar refeição',
            'menu'     => $menu,
            'existing' => (new FeedbackRepository())->findByUserAndMeal(Auth::id(), (int) $menu['id']),
        ]);
    }

    public function storeFeedback(): void
    {
        $menu = $this->feedbackMenu();
        $rating = (int) input('rating');
        $comment = mb_substr(input('comment'), 0, 500);

        if ($rating < 1 || $rating > 5) {
            with_input(['rating' => 'Escolha uma nota de 1 a 5 estrelas.']);
            redirect('/avaliar?refeicao=' . $menu['id']);
        }

        $ok = (new FeedbackRepository())->create(Auth::id(), (int) $menu['id'], $rating, $comment, (int) config('points.feedback'));
        flash(
            $ok ? 'success' : 'info',
            $ok ? 'Obrigado pela avaliação! +' . config('points.feedback') . ' pontos.' : 'Você já avaliou essa refeição.'
        );
        redirect('/painel');
    }

    private function menuFromRequest(): array
    {
        $menu = (new MenuRepository())->find((int) input('menu_id'));
        if (!$menu) {
            abort(404, 'Refeição não encontrada.');
        }

        return $menu;
    }

    private function ensureOpen(array $menu): void
    {
        if (confirmation_closed($menu['menu_date'])) {
            flash('error', 'O prazo para confirmar ou cancelar essa refeição já terminou.');
            back();
        }
    }

    /** Refeição que o aluno pode avaliar: já servida (até 7 dias) e confirmada por ele. */
    private function feedbackMenu(): array
    {
        $menus = new MenuRepository();
        $id = (int) input('refeicao');
        $menu = $id ? $menus->find($id) : $menus->forDate(today());

        if (!$menu) {
            flash('info', $id ? 'Refeição não encontrada.' : 'Não há refeição cadastrada para hoje.');
            redirect('/painel');
        }
        if ($menu['menu_date'] > today() || $menu['menu_date'] < date('Y-m-d', strtotime('-7 days'))) {
            flash('info', 'Só é possível avaliar refeições servidas nos últimos 7 dias.');
            redirect('/painel');
        }
        if (!(new ConfirmationRepository())->exists(Auth::id(), (int) $menu['id'])) {
            flash('info', 'Você só pode avaliar refeições em que confirmou presença.');
            redirect('/painel');
        }

        return $menu;
    }
}
