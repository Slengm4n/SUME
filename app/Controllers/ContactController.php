<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

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

        $mail = config('mail');
        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->SMTPAuth = true;
            $mailer->Host = $mail['host'];
            $mailer->Port = $mail['port'];
            $mailer->SMTPSecure = $mail['port'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Username = $mail['username'];
            $mailer->Password = $mail['password'];
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            // O remetente é sempre o endereço do sistema; o visitante vai no Reply-To.
            $mailer->setFrom($mail['from'], $mail['from_name']);
            $mailer->addAddress($mail['support']);
            $mailer->addReplyTo($v->value('email'), $v->value('name'));

            $mailer->Subject = '[SUME] ' . $v->value('subject');
            $mailer->Body = "Nome: {$v->value('name')}\nE-mail: {$v->value('email')}\n\n{$v->value('message')}";
            $mailer->send();
        } catch (MailException $e) {
            error_log('Falha ao enviar contato: ' . $e->getMessage());
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
        $mail = config('mail');

        return $mail['host'] !== '' && $mail['from'] !== '' && $mail['support'] !== '';
    }
}
