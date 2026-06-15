<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Services\AuthService;
use Closure;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $permission)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (!AuthService::hasPermission($this->permission)) {
            http_response_code(403);
            flash('error', 'No tiene permisos para realizar esta acción.');
            redirect('dashboard');
        }
        return $next();
    }
}
