<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\FeedbackRepository;

final class FeedbackController
{
    public function index(): void
    {
        $rating = (int) input('nota');
        $rating = $rating >= 1 && $rating <= 5 ? $rating : null;
        $feedbacks = new FeedbackRepository();

        view('pages/admin/feedbacks', [
            'title'     => 'Avaliações',
            'feedbacks' => $feedbacks->latest($rating),
            'summary'   => $feedbacks->summary(),
            'filter'    => $rating,
        ]);
    }
}
