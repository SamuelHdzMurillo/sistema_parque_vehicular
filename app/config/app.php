<?php

declare(strict_types=1);

return [
    'name' => 'SICV - Control Vehicular',
    'institution' => 'CECYTE BCS',
    'url' => env('APP_URL', 'http://localhost/sistema_parque_vehicular/public'),
    'timezone' => 'America/Mazatlan',
    'locale' => 'es_MX',
    'debug' => env('APP_DEBUG', true),
    'session' => [
        'name' => 'SICV_SESSION',
        'lifetime' => 7200,
        'remember_days' => 30,
    ],
    'security' => [
        'max_login_attempts' => 5,
        'lockout_minutes' => 30,
        'attempt_window_minutes' => 15,
    ],
    'upload' => [
        'max_size' => 5 * 1024 * 1024,
        'allowed_images' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_docs' => ['application/pdf', 'application/xml', 'text/xml', 'image/jpeg', 'image/png'],
        'path' => BASE_PATH . '/storage/uploads',
    ],
];
