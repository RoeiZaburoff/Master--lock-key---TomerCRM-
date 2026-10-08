<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class LoginController
{
    public function showLogin(Request $request, array $params = []): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }
        View::render('auth/login', [], 'auth');
    }

    public function handleLogin(Request $request, array $params = []): void
    {
        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');
        $errors   = [];

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if (empty($errors)) {
            $service = new AuthService();
            $result  = $service->attempt($username, $password);
            if ($result !== false) {
                [$userId, $role] = $result;
                Auth::login($userId, $role);
                Response::redirect('/');
            }
            $errors['login'] = 'Invalid username or password.';
        }

        View::render('auth/login', ['errors' => $errors, 'old_username' => $username], 'auth');
    }

    public function logout(Request $request, array $params = []): void
    {
        Auth::logout();
        Response::redirect('/login');
    }
}
