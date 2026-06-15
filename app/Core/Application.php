<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Application
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        date_default_timezone_set((string) config('app', 'timezone'));
        Session::start();
        $this->request = new Request();
        $this->router = new Router();
        $router = $this->router;
        require base_path('routes/web.php');
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        try {
            $this->router->dispatch($this->request);
        } catch (Throwable $e) {
            if (config('app', 'debug')) {
                http_response_code(500);
                echo '<h1>Error del sistema</h1><pre>' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
            } else {
                http_response_code(500);
                View::make('errors.500', [], 'layouts.guest');
            }
        }
    }
}
