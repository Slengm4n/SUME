<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Services\GoogleAuth;

/**
 * Login com Google.
 *
 * Ao voltar do Google:
 *  1. conta já vinculada (google_id)      -> entra
 *  2. e-mail já cadastrado no SUME         -> vincula a conta Google e entra
 *  3. pessoa nova                          -> completa o cadastro (RM e código da ETEC)
 * Logado, o usuário também pode vincular/desvincular pelo perfil.
 */
final class GoogleController
{
    private const PENDING = 'google_pending';

    public function redirect(): void
    {
        if (!GoogleAuth::isConfigured()) {
            abort(404);
        }

        $intent = Auth::check() ? 'link' : 'login';
        header('Location: ' . GoogleAuth::authorizationUrl($intent), true, 302);
        exit;
    }

    public function callback(): void
    {
        if (!GoogleAuth::isConfigured()) {
            abort(404);
        }

        // Usuário clicou em "Cancelar" na tela do Google
        if (input('error') !== '') {
            Session::forget('google_oauth');
            flash('info', 'Login com Google cancelado.');
            redirect(Auth::check() ? '/perfil' : '/entrar');
        }

        try {
            $profile = GoogleAuth::handleCallback(input('state'), input('code'));
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect(Auth::check() ? '/perfil' : '/entrar');
        }

        $users = new UserRepository();

        // Vincular à conta já logada (pelo perfil)
        if ($profile['intent'] === 'link' && Auth::check()) {
            $owner = $users->findByGoogleId($profile['sub']);
            if ($owner && (int) $owner['id'] !== Auth::id()) {
                flash('error', 'Essa conta Google já está vinculada a outro usuário do SUME.');
            } else {
                $users->linkGoogle((int) Auth::id(), $profile['sub']);
                flash('success', 'Conta Google conectada (' . $profile['email'] . '). Agora você pode entrar com ela.');
            }
            redirect('/perfil');
        }

        // 1. Já vinculada
        $user = $users->findByGoogleId($profile['sub']);

        // 2. Mesmo e-mail (o Google confirmou que a pessoa é dona dele)
        if (!$user && ($user = $users->findByEmail($profile['email']))) {
            if ($user['google_id']) {
                // E-mail igual, mas outra conta Google já vinculada: não troca em silêncio
                flash('error', 'Seu cadastro já está vinculado a outra conta Google. Entre com ela ou com sua senha.');
                redirect('/entrar');
            }
            $users->linkGoogle((int) $user['id'], $profile['sub']);
        }

        if ($user) {
            Auth::login($user);
            Session::forget('login_attempts');
            flash('success', 'Olá, ' . explode(' ', $user['name'])[0] . '! Bom te ver por aqui.');
            redirect('/painel');
        }

        // 3. Pessoa nova: falta RM e código da ETEC
        Session::put(self::PENDING, $profile + ['at' => time()]);
        redirect('/cadastro/google');
    }

    public function showComplete(): void
    {
        $pending = $this->pending();
        view('pages/auth/google-complete', ['title' => 'Completar cadastro', 'pending' => $pending], 'auth');
    }

    public function complete(): void
    {
        $pending = $this->pending();
        $users = new UserRepository();

        $v = new Validator($_POST);
        $v->required('name', 'Informe seu nome.')->max('name', 100)
          ->required('rm', 'Informe seu RM.')->digits('rm', 'O RM deve conter apenas números.')->max('rm', 20)
          ->required('codigo', 'Informe o código da sua ETEC.')->max('codigo', 20);
        if (!isset($_POST['terms'])) {
            $v->add('terms', 'Você precisa aceitar os termos de uso.');
        }
        if (!$v->has('rm') && $users->rmTaken($v->value('rm'))) {
            $v->add('rm', 'Este RM já está cadastrado. Se a conta é sua, entre com a senha e conecte o Google pelo perfil.');
        }
        if ($v->fails()) {
            with_input($v->errors());
            redirect('/cadastro/google');
        }

        // Pode ter sido criado em outra aba enquanto isso
        if ($users->findByEmail($pending['email']) || $users->findByGoogleId($pending['sub'])) {
            Session::forget(self::PENDING);
            flash('info', 'Essa conta já existe. Entre com o Google novamente.');
            redirect('/entrar');
        }

        $id = $users->create([
            'name'      => $v->value('name'),
            'email'     => $pending['email'],
            'rm'        => $v->value('rm'),
            'codigo'    => $v->value('codigo'),
            'google_id' => $pending['sub'],
        ]);
        Session::forget(self::PENDING);

        Auth::login($users->find($id));
        flash('success', 'Conta criada! Confirme sua presença nas refeições e retire seu prato para ganhar pontos.');
        redirect('/painel');
    }

    public function unlink(): void
    {
        $user = Auth::user();
        if (!$user['google_id']) {
            redirect('/perfil');
        }
        if (!$user['has_password']) {
            flash('error', 'Crie uma senha antes de desconectar o Google, senão você não conseguirá entrar.');
            redirect('/perfil#senha');
        }

        (new UserRepository())->linkGoogle((int) $user['id'], null);
        flash('success', 'Conta Google desconectada. Use seu e-mail/RM e senha para entrar.');
        redirect('/perfil');
    }

    /** Dados vindos do Google aguardando o complemento do cadastro (válidos por 15 min). */
    private function pending(): array
    {
        $pending = Session::get(self::PENDING);
        if (!is_array($pending) || time() - $pending['at'] > 900) {
            Session::forget(self::PENDING);
            flash('info', 'Comece pelo botão "Entrar com Google".');
            redirect('/entrar');
        }

        return $pending;
    }
}
