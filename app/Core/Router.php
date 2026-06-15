<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use InvalidArgumentException;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, handler: callable|array, middlewares: array}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): self
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, callable|array $handler, array $middlewares): self
    {
        $pattern = $this->compilePattern($path);
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'path' => $path,
        ];
        return $this;
    }

    private function compilePattern(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return '#^/$#';
        }
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $uri = $request->uri();
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                fn ($key) => !is_int($key),
                ARRAY_FILTER_USE_KEY
            );

            $pipeline = array_reduce(
                array_reverse($route['middlewares']),
                fn ($next, $middleware) => fn () => $this->runMiddleware($middleware, $request, $next),
                fn () => $this->invokeHandler($route['handler'], $params, $request)
            );

            return $pipeline();
        }

        http_response_code(404);
        View::make('errors.404', [], 'layouts.guest');
    }

    private function runMiddleware(string|object $middleware, Request $request, Closure $next): mixed
    {
        if (is_string($middleware)) {
            $middleware = new $middleware();
        }
        if (!method_exists($middleware, 'handle')) {
            throw new InvalidArgumentException('Middleware inválido');
        }
        return $middleware->handle($request, $next);
    }

    private function invokeHandler(callable|array $handler, array $params, Request $request): mixed
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            return $controller->$method($request, ...array_values($params));
        }
        return $handler($request, ...array_values($params));
    }
}
