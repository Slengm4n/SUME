<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Repositories\UserRepository;

final class RankingController
{
    public function index(): void
    {
        view('pages/ranking', [
            'title'   => 'Ranking',
            'ranking' => (new UserRepository())->ranking(100),
            'me'      => Auth::id(),
        ]);
    }

    public function deduct(): void
    {
        $points = (int) input('points');
        if ($points < 1 || $points > 100000) {
            flash('error', 'Informe uma quantidade de pontos entre 1 e 100000.');
            redirect('/ranking');
        }

        $affected = (new UserRepository())->deductFromAll($points);
        flash('success', "{$points} pontos deduzidos de {$affected} aluno(s).");
        redirect('/ranking');
    }

    public function reset(): void
    {
        if (mb_strtoupper(input('confirmation')) !== 'ZERAR') {
            flash('error', 'Digite ZERAR para confirmar o reset dos pontos.');
            redirect('/ranking');
        }

        (new UserRepository())->resetAllPoints();
        flash('success', 'Os pontos de todos os usuários foram zerados.');
        redirect('/ranking');
    }
}
