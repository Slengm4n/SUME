<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

/** Páginas públicas: início e termos de uso. */
final class PageController
{
    public function landing(): void
    {
        view('pages/landing', ['title' => 'Merenda sem desperdício', 'user' => Auth::user()], 'guest');
    }

    public function terms(): void
    {
        view('pages/terms', ['title' => 'Termos de uso'], Auth::check() ? 'app' : 'guest');
    }
}
