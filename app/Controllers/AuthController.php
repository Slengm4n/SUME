<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;

final class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public function showLogin(): void
    {
        view('pages/auth/login', ['title' => 'Entrar'], 'auth');
    }

    public function login(): void
    {
        $identifier = input('identifier');
        $password = (string) ($_POST['password'] ?? '');

        // Limite simples de tentativas por sessão (dificulta força bruta)
        $attempts = Session::get('login_attempts', ['count' => 0, 'until' => 0]);
        if ($attempts['until'] > time()) {
            $wait = (int) ceil(($attempts['until'] - time()) / 60);
            flash('error', "Muitas tentativas. Aguarde {$wait} minuto(s) e tente de novo.");
            with_input();
            redirect('/entrar');
        }

        $user = $identifier !== '' ? (new UserRepository())->findForLogin($identifier) : null;

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts['count']++;
            if ($attempts['count'] >= self::MAX_ATTEMPTS) {
                $attempts = ['count' => 0, 'until' => time() + self::LOCK_SECONDS];
            }
            Session::put('login_attempts', $attempts);

            flash('error', 'E-mail/RM ou senha incorretos.');
            with_input();
            redirect('/entrar');
        }

        // Atualiza o hash se o algoritmo padrão do PHP mudou
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            (new UserRepository())->updatePassword((int) $user['id'], $password);
        }

        Session::forget('login_attempts');
        Auth::login($user);
        flash('success', 'Olá, ' . explode(' ', $user['name'])[0] . '! Bom te ver por aqui.');
        redirect('/painel');
    }

    public function showRegister(): void
    {
        view('pages/auth/register', ['title' => 'Criar conta'], 'auth');
    }

    public function register(): void
    {
        $users = new UserRepository();
        $v = new Validator($_POST);
        $v->required('name', 'Informe seu nome.')->max('name', 100)
          ->required('email', 'Informe seu e-mail.')->email('email')->max('email', 150)
          ->required('rm', 'Informe seu RM.')->digits('rm', 'O RM deve conter apenas números.')->max('rm', 20)
          ->required('codigo', 'Informe o código da sua ETEC.')->max('codigo', 20)
          ->password('password')
          ->same('password', 'password_confirmation', 'As senhas não conferem.');

        if (!isset($_POST['terms'])) {
            $v->add('terms', 'Você precisa aceitar os termos de uso.');
        }
        if (!$v->has('email') && $users->emailTaken(mb_strtolower($v->value('email')))) {
            $v->add('email', 'Este e-mail já está cadastrado.');
        }
        if (!$v->has('rm') && $users->rmTaken($v->value('rm'))) {
            $v->add('rm', 'Este RM já está cadastrado.');
        }

        if ($v->fails()) {
            with_input($v->errors());
            redirect('/cadastro');
        }

        $id = $users->create([
            'name'     => $v->value('name'),
            'email'    => mb_strtolower($v->value('email')),
            'rm'       => $v->value('rm'),
            'codigo'   => $v->value('codigo'),
            'password' => (string) $_POST['password'],
        ]);

        Auth::login($users->find($id));
        flash('success', 'Conta criada! Confirme sua presença nas refeições para ganhar pontos.');
        redirect('/painel');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        flash('success', 'Você saiu da sua conta.');
        redirect('/entrar');
    }
}
