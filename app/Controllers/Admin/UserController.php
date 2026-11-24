<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Validator;
use App\Repositories\UserRepository;

final class UserController
{
    public function index(): void
    {
        $search = input('busca');

        view('pages/admin/users', [
            'title'  => 'Usuários',
            'users'  => (new UserRepository())->search($search),
            'search' => $search,
        ]);
    }

    public function edit(): void
    {
        view('pages/admin/user-edit', [
            'title' => 'Editar usuário',
            'edit'  => $this->findOrFail(),
        ]);
    }

    public function update(): void
    {
        $user = $this->findOrFail();
        $users = new UserRepository();
        $id = (int) $user['id'];

        $v = new Validator($_POST);
        $v->required('name', 'Informe o nome.')->max('name', 100)
          ->required('email', 'Informe o e-mail.')->email('email')->max('email', 150)
          ->digits('rm', 'O RM deve conter apenas números.')->max('rm', 20)
          ->max('codigo', 20);

        if (!$v->has('email') && $users->emailTaken(mb_strtolower($v->value('email')), $id)) {
            $v->add('email', 'Este e-mail já está em uso.');
        }
        if (!$v->has('rm') && $v->value('rm') !== '' && $users->rmTaken($v->value('rm'), $id)) {
            $v->add('rm', 'Este RM já está em uso.');
        }

        $isMaster = isset($_POST['is_master']) ? 1 : 0;
        if ($id === Auth::id() && !$isMaster) {
            $v->add('is_master', 'Você não pode remover seu próprio acesso de administrador.');
        }

        if ($v->fails()) {
            with_input($v->errors());
            redirect('/admin/usuarios/editar?id=' . $id);
        }

        $users->updateByAdmin($id, [
            'name'      => $v->value('name'),
            'email'     => mb_strtolower($v->value('email')),
            'rm'        => $v->value('rm'),
            'codigo'    => $v->value('codigo'),
            'is_master' => $isMaster,
        ]);

        flash('success', 'Usuário atualizado.');
        redirect('/admin/usuarios');
    }

    public function destroy(): void
    {
        $user = $this->findOrFail();
        if ((int) $user['id'] === Auth::id()) {
            flash('error', 'Você não pode excluir a própria conta.');
            redirect('/admin/usuarios');
        }

        $avatar = basename((string) $user['profile_picture']);
        (new UserRepository())->delete((int) $user['id']);
        if ($avatar !== '' && is_file(config('uploads.avatars_dir') . '/' . $avatar)) {
            @unlink(config('uploads.avatars_dir') . '/' . $avatar);
        }

        flash('success', $user['name'] . ' foi excluído(a).');
        redirect('/admin/usuarios');
    }

    private function findOrFail(): array
    {
        $user = (new UserRepository())->find((int) input('id'));
        if (!$user) {
            abort(404, 'Usuário não encontrado.');
        }

        return $user;
    }
}
