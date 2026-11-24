<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

/**
 * Autenticação: quem está logado e se é administrador.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');

        return $id === null ? null : (int) $id;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = self::id();
            self::$user = $id ? (new UserRepository())->find($id) : null;

            // Usuário foi excluído enquanto estava logado
            if ($id && self::$user === null) {
                Session::forget('user_id');
            }
        }

        return self::$user;
    }

    public static function isAdmin(): bool
    {
        return (bool) (self::user()['is_master'] ?? false);
    }

    /** Recarrega os dados do usuário (ex.: depois de editar o perfil). */
    public static function refresh(): void
    {
        self::$loaded = false;
    }
}
