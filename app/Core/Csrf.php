<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Proteção contra CSRF: todo POST precisa enviar o token da sessão.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }

        return $token;
    }

    public static function isValid(?string $token): bool
    {
        $expected = Session::get(self::KEY);

        return is_string($expected) && is_string($token) && hash_equals($expected, $token);
    }
}
