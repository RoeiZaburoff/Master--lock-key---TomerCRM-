<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function redirect(string $uri): never
    {
        $base = defined('APP_BASE') ? rtrim(APP_BASE, '/') : '';
        header('Location: ' . $base . $uri);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function abort(int $code = 404, string $message = 'Not Found'): never
    {
        http_response_code($code);
        echo $message;
        exit;
    }
}
