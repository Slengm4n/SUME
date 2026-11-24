<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Validator;
use App\Repositories\MenuRepository;

/** Cadastro do cardápio (uma refeição por dia). */
final class MenuController
{
    public function index(): void
    {
        $menus = new MenuRepository();

        view('pages/admin/menu', [
            'title'    => 'Cardápio',
            'upcoming' => $menus->upcoming(30),
            'history'  => $menus->history(10),
            'editing'  => input('editar') !== '' ? $menus->find((int) input('editar')) : null,
        ]);
    }

    public function store(): void
    {
        $v = new Validator($_POST);
        $v->required('menu_date', 'Escolha a data.')->date('menu_date')
          ->required('dish', 'Descreva a refeição.')->max('dish', 255);

        if (!$v->fails() && $v->value('menu_date') < today()) {
            $v->add('menu_date', 'Não é possível cadastrar refeições em datas passadas.');
        }
        if ($v->fails()) {
            with_input($v->errors());
            redirect('/admin/cardapio');
        }

        $created = (new MenuRepository())->save($v->value('menu_date'), $v->value('dish'));
        $when = format_date($v->value('menu_date'), true);
        flash('success', $created ? "Refeição de {$when} cadastrada." : "Refeição de {$when} atualizada.");
        redirect('/admin/cardapio');
    }

    public function destroy(): void
    {
        $menus = new MenuRepository();
        $menu = $menus->find((int) input('id'));
        if (!$menu) {
            abort(404);
        }

        $menus->delete((int) $menu['id']);
        flash('success', 'Refeição de ' . format_date($menu['menu_date'], true) . ' removida.');
        redirect('/admin/cardapio');
    }
}
