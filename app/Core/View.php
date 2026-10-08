<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $data['errors']        = $data['errors']  ?? [];
        $data['flash_success'] = Session::getFlash('success');
        $data['flash_error']   = Session::getFlash('error');
        $data['__is_admin']    = Auth::isAdmin();

        extract($data, EXTR_SKIP);

        ob_start();
        $templateFile = VIEW_PATH . '/' . $template . '.php';
        if (!file_exists($templateFile)) {
            Response::abort(404, 'View not found: ' . $template);
        }
        require $templateFile;
        $content = ob_get_clean();

        require VIEW_PATH . '/layouts/' . $layout . '.php';
    }
}
