<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed
    {
        AuthService::loadPermissions();
        if (Session::has('user')) {
            return $next();
        }
        $auth = new AuthService();
        if ($auth->tryRememberLogin()) {
            return $next();
        }
        flash('error', 'Debe iniciar sesión para continuar.');
        redirect('login');
    }
}
