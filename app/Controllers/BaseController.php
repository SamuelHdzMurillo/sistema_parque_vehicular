<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

abstract class BaseController
{
    protected function render(string $view, array $data = [], ?string $layout = 'layouts.app'): never
    {
        View::make($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function back(Request $request): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('dashboard');
        header('Location: ' . $referer);
        exit;
    }

    protected function validateCsrf(Request $request): void
    {
        $token = $request->input('_token');
        if (!\App\Core\Csrf::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            flash('error', 'Token de seguridad inválido. Intente de nuevo.');
            $this->back($request);
        }
    }
}
