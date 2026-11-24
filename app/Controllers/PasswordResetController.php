<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\Validator;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Services\Mailer;

/**
 * "Esqueci minha senha": envia um link por e-mail que vale 60 minutos e
 * só pode ser usado uma vez.
 */
final class PasswordResetController
{
    private const MAX_PER_HOUR = 3;

    public function showRequest(): void
    {
        view('pages/auth/forgot', ['title' => 'Recuperar senha', 'available' => Mailer::isConfigured()], 'auth');
    }

    public function sendLink(): void
    {
        $v = new Validator($_POST);
        $v->required('email', 'Informe seu e-mail.')->email('email');
        if ($v->fails()) {
            with_input($v->errors());
            redirect('/esqueci-senha');
        }

        if (!Mailer::isConfigured()) {
            flash('error', 'A recuperação por e-mail ainda não foi configurada. Fale com a administração da escola.');
            redirect('/esqueci-senha');
        }

        // No máximo um pedido por minuto por sessão
        if (Session::get('reset_requested_at', 0) > time() - 60) {
            flash('error', 'Aguarde um minuto antes de pedir outro link.');
            with_input();
            redirect('/esqueci-senha');
        }
        Session::put('reset_requested_at', time());

        $email = mb_strtolower($v->value('email'));
        $user = (new UserRepository())->findByEmail($email);
        $resets = new PasswordResetRepository();

        if ($user && $resets->countRecent((int) $user['id']) < self::MAX_PER_HOUR) {
            $minutes = (int) config('password_reset_minutes');
            $token = $resets->create((int) $user['id'], $minutes);
            $link = absolute_url('/redefinir-senha', ['token' => $token]);
            $first = explode(' ', $user['name'])[0];

            try {
                Mailer::send(
                    $user['email'],
                    $user['name'],
                    'Redefinir sua senha do SUME',
                    Mailer::layout(
                        'Redefinir sua senha',
                        '<p>Olá, ' . e($first) . '! Recebemos um pedido para redefinir a senha da sua conta.</p>'
                        . '<p>O link vale por ' . $minutes . ' minutos e só pode ser usado uma vez.</p>',
                        $link,
                        'Criar nova senha'
                    ),
                    "Olá, {$first}!\n\nRecebemos um pedido para redefinir a senha da sua conta no SUME.\n"
                    . "Abra o link abaixo (vale por {$minutes} minutos e só pode ser usado uma vez):\n\n{$link}\n\n"
                    . "Se não foi você, ignore este e-mail: sua senha continua a mesma."
                );
            } catch (\RuntimeException) {
                flash('error', 'Não conseguimos enviar o e-mail agora. Tente novamente em alguns minutos.');
                redirect('/esqueci-senha');
            }
        }

        // Mesma resposta exista ou não a conta: não revela quem está cadastrado
        flash('success', 'Se existir uma conta com esse e-mail, enviamos um link para criar uma nova senha. Confira também o spam.');
        redirect('/entrar');
    }

    public function showReset(): void
    {
        $token = input('token');
        $reset = (new PasswordResetRepository())->findValid($token);

        view('pages/auth/reset', [
            'title' => 'Nova senha',
            'token' => $token,
            'reset' => $reset,
        ], 'auth');
    }

    public function reset(): void
    {
        $token = input('token');
        $resets = new PasswordResetRepository();
        $reset = $resets->findValid($token);
        if (!$reset) {
            flash('error', 'Este link é inválido ou expirou. Peça um novo.');
            redirect('/esqueci-senha');
        }

        $v = new Validator($_POST);
        $v->password('password')->same('password', 'password_confirmation', 'As senhas não conferem.');
        if ($v->fails()) {
            with_input($v->errors());
            redirect('/redefinir-senha?token=' . $token);
        }

        (new UserRepository())->updatePassword((int) $reset['id'], (string) $_POST['password']);
        $resets->consume((int) $reset['reset_id'], (int) $reset['id']);
        Session::forget('login_attempts');

        flash('success', 'Senha alterada! Agora é só entrar com a nova senha.');
        redirect('/entrar');
    }
}
