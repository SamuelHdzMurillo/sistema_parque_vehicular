<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Session;
use Closure;

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Session::has('user')) {
            redirect('dashboard');
        }
        return $next();
    }
}
