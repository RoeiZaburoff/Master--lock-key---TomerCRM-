<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        if (empty($_SESSION['user_id'])) { return false; }
        $q=Database::getInstance()->prepare('SELECT role FROM users WHERE user_id=?');
        $q->execute([(int) $_SESSION['user_id']]);
        $role=$q->fetchColumn();
        if ($role === false) { unset($_SESSION['user_id'], $_SESSION['user_role']); return false; }
        $_SESSION['user_role']=$role;
        return true;
    }

    public static function guard(): void
    {
        if (!self::check()) {
            Response::redirect('/login');
        }
    }

    public static function userId(): int|null
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): string
    {
        return (string) ($_SESSION['user_role'] ?? 'user');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function requireAdmin(): void
    {
        self::guard();
        if (!self::isAdmin()) {
            Response::abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /** $role should be passed from the DB row at login time. */
    public static function login(int $userId, string $role = 'user'): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_role'] = $role;
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
