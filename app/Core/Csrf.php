<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }
        return (string) Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        $sessionToken = Session::get(self::TOKEN_KEY);
        if (!is_string($sessionToken) || $token === null || $token === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    public static function regenerate(): void
    {
        Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
    }
}
