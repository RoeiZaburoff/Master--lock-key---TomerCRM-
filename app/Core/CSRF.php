<?php
declare(strict_types=1);

namespace App\Core;

class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function verify(string $token): bool
    {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function validateOrFail(Request $request): void
    {
        if (!self::verify((string) $request->post('_csrf', ''))) {
            Response::abort(403, 'Invalid CSRF token.');
        }
    }
}
