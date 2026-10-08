<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    private const PUBLIC_ACTIONS = [
        \App\Modules\Auth\LoginController::class => ['showLogin', 'handleLogin', 'logout'],
    ];

    public function get(string $uri, array $action): void
    {
        $this->routes[] = ['method' => 'GET', 'uri' => $uri, 'action' => $action];
    }

    public function post(string $uri, array $action): void
    {
        $this->routes[] = ['method' => 'POST', 'uri' => $uri, 'action' => $action];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri    = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->matchUri($route['uri'], $uri);
            if ($params === false) {
                continue;
            }

            [$class, $action] = $route['action'];

            $isPublic = isset(self::PUBLIC_ACTIONS[$class])
                && in_array($action, self::PUBLIC_ACTIONS[$class], true);

            if (!$isPublic) {
                Auth::guard();
            }

            if ($method === 'POST') {
                CSRF::validateOrFail($request);
            }

            $controller = new $class();
            if (method_exists($controller, 'callAction')) {
                $controller->callAction($action, $request, $params);
            } else {
                $controller->$action($request, $params);
            }
            return;
        }

        http_response_code(404);
        echo '<h1>404 — Page Not Found</h1>';
    }

    private function matchUri(string $routeUri, string $requestUri): array|false
    {
        $routeParts   = explode('/', trim($routeUri, '/'));
        $requestParts = explode('/', trim($requestUri, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return false;
        }

        $params = [];
        foreach ($routeParts as $i => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $params[trim($segment, '{}')] = $requestParts[$i];
            } elseif ($segment !== $requestParts[$i]) {
                return false;
            }
        }
        return $params;
    }
}
