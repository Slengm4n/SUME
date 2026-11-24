<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Validator;
use App\Repositories\UserRepository;

final class ProfileController
{
    private const ALLOWED_IMAGES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public function show(): void
    {
        view('pages/profile', ['title' => 'Meu perfil', 'user' => Auth::user()]);
    }

    public function update(): void
    {
        $users = new UserRepository();
        $v = new Validator($_POST);
        $v->required('name', 'Informe seu nome.')->max('name', 100)
          ->required('email', 'Informe seu e-mail.')->email('email')->max('email', 150);

        if (!$v->has('email') && $users->emailTaken(mb_strtolower($v->value('email')), Auth::id())) {
            $v->add('email', 'Este e-mail já está em uso por outra conta.');
        }
        if ($v->fails()) {
            with_input($v->errors());
            redirect('/perfil');
        }

        $users->updateProfile(Auth::id(), $v->value('name'), mb_strtolower($v->value('email')));
        flash('success', 'Dados atualizados.');
        redirect('/perfil');
    }

    public function updatePassword(): void
    {
        $users = new UserRepository();
        $v = new Validator($_POST);
        $v->password('new_password')
          ->same('new_password', 'new_password_confirmation', 'As senhas não conferem.');

        if (!password_verify((string) ($_POST['current_password'] ?? ''), (string) $users->passwordHash(Auth::id()))) {
            $v->add('current_password', 'Senha atual incorreta.');
        }
        if ($v->fails()) {
            with_input($v->errors());
            redirect('/perfil#senha');
        }

        $users->updatePassword(Auth::id(), (string) $_POST['new_password']);
        flash('success', 'Senha alterada com sucesso.');
        redirect('/perfil');
    }

    public function updateAvatar(): void
    {
        $file = $_FILES['avatar'] ?? null;
        $error = $this->validateUpload($file);
        if ($error) {
            flash('error', $error);
            redirect('/perfil');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_IMAGES[$mime];
        $dir = config('uploads.avatars_dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            flash('error', 'Não foi possível salvar a imagem. Tente novamente.');
            redirect('/perfil');
        }

        $this->deleteCurrentAvatar();
        (new UserRepository())->updateAvatar(Auth::id(), $filename);
        flash('success', 'Foto atualizada.');
        redirect('/perfil');
    }

    public function removeAvatar(): void
    {
        $this->deleteCurrentAvatar();
        (new UserRepository())->updateAvatar(Auth::id(), null);
        flash('success', 'Foto removida.');
        redirect('/perfil');
    }

    private function validateUpload(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Escolha uma imagem.';
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            || $file['size'] > config('uploads.max_bytes')) {
            return 'A imagem deve ter no máximo 2 MB.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return 'Falha no envio da imagem. Tente novamente.';
        }

        // Confere o conteúdo real do arquivo, não a extensão enviada pelo navegador
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::ALLOWED_IMAGES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return 'Formato não suportado. Use JPG, PNG, WEBP ou GIF.';
        }

        return null;
    }

    private function deleteCurrentAvatar(): void
    {
        $current = basename((string) (Auth::user()['profile_picture'] ?? ''));
        $path = config('uploads.avatars_dir') . '/' . $current;
        if ($current !== '' && is_file($path)) {
            @unlink($path);
        }
    }
}
