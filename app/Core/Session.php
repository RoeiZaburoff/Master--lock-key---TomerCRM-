<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => 0, 'path' => defined('APP_BASE') && APP_BASE !== '' ? APP_BASE . '/' : '/',
                'secure' => RuntimeConfig::enabled('REQUIRE_HTTPS', true),
                'httponly' => true, 'samesite' => 'Lax',
            ]);
            session_start();
            if (!empty($_SESSION['user_id']) && time() - (int) ($_SESSION['last_activity'] ?? 0) > 3600) {
                $_SESSION = [];
                session_regenerate_id(true);
            }
            $_SESSION['last_activity'] = time();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
