<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Services\Mailer;

final class ContactController
{
    public function show(): void
    {
        view('pages/contact', [
            'title'      => 'Contato',
            'user'       => Auth::user(),
            'configured' => $this->isConfigured(),
        ], Auth::check() ? 'app' : 'guest');
    }

    public function send(): void
    {
        // Campo "armadilha": robôs preenchem, pessoas não veem
        if (input('website') !== '') {
            redirect('/contato');
        }

        // No máximo uma mensagem por minuto por sessão
        if (Session::get('contact_sent_at', 0) > time() - 60) {
            flash('error', 'Aguarde um minuto antes de enviar outra mensagem.');
            with_input();
            redirect('/contato');
        }

        $v = new Validator($_POST);
        $v->required('name', 'Informe seu nome.')->max('name', 100)
          ->required('email', 'Informe seu e-mail.')->email('email')
          ->required('subject', 'Informe o assunto.')->max('subject', 150)
          ->required('message', 'Escreva sua mensagem.')->max('message', 3000);

        if ($v->fails()) {
            with_input($v->errors());
            redirect('/contato');
        }

        if (!$this->isConfigured()) {
            flash('error', 'O envio de e-mails ainda não foi configurado pela escola.');
            with_input();
            redirect('/contato');
        }

        $text = "Nome: {$v->value('name')}
E-mail: {$v->value('email')}

{$v->value('message')}";
        $html = Mailer::layout(
            'Nova mensagem de contato',
            '<p><strong>' . e($v->value('name')) . '</strong> &lt;' . e($v->value('email')) . '&gt;</p>'
            . '<p><strong>Assunto:</strong> ' . e($v->value('subject')) . '</p>'
            . '<p style="white-space:pre-wrap">' . e($v->value('message')) . '</p>'
        );

        try {
            // O remetente é sempre a conta do sistema; o visitante vai no Reply-To.
            Mailer::send(
                (string) config('mail.support'),
                'Suporte SUME',
                '[SUME] ' . $v->value('subject'),
                $html,
                $text,
                [$v->value('email'), $v->value('name')]
            );
        } catch (\RuntimeException) {
            flash('error', 'Não foi possível enviar sua mensagem agora. Tente novamente mais tarde.');
            with_input();
            redirect('/contato');
        }

        Session::put('contact_sent_at', time());
        flash('success', 'Mensagem enviada! Responderemos no seu e-mail.');
        redirect('/contato');
    }

    private function isConfigured(): bool
    {
        return Mailer::isConfigured() && config('mail.support') !== '';
    }
}
