<?php
declare(strict_types=1);

namespace App\Core;

abstract class BaseController
{
    /**
     * @var array List of actions that require admin privileges. Use ['*'] for all actions.
     */
    protected array $adminActions = [];

    public function callAction(string $action, Request $request, array $params): void
    {
        if (in_array('*', $this->adminActions, true) || in_array($action, $this->adminActions, true)) {
            Auth::requireAdmin();
        }

        $this->$action($request, $params);
    }

    protected function redirectWithMessage(string $url, string $message, string $type = 'success'): void
    {
        Session::flash($type, $message);
        Response::redirect($url);
    }
}
