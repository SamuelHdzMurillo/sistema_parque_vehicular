<?php

declare(strict_types=1);

require_once __DIR__ . '/image.php';

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;
    static $vars = [];

    if (!$loaded) {
        $path = BASE_PATH . '/.env';
        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
                $vars[trim($name)] = trim($value, " \t\"'");
            }
        }
        $loaded = true;
    }

    return $vars[$key] ?? $_ENV[$key] ?? $default;
}

function config(string $file, ?string $key = null, mixed $default = null): mixed
{
    static $cache = [];
    if (!isset($cache[$file])) {
        $cache[$file] = require BASE_PATH . '/app/config/' . $file . '.php';
    }
    if ($key === null) {
        return $cache[$file];
    }
    $segments = explode('.', $key);
    $value = $cache[$file];
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function public_path(string $path = ''): string
{
    return base_path('public' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

function view_path(string $path = ''): string
{
    return base_path('views' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app', 'url'), '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function brand_logo_path(): string
{
    $configured = (string) config('app', 'branding.logo', '');
    if ($configured !== '') {
        $path = public_path($configured);
        if (is_file($path)) {
            return $path;
        }
    }

    foreach ([
        'assets/images/logo_Cecyte_vertical_sin_fondo.jpg',
        'assets/images/logo_Cecyte_vertical_sin_fondo.png',
    ] as $candidate) {
        $path = public_path($candidate);
        if (is_file($path)) {
            return $path;
        }
    }

    return public_path('assets/images/logo_Cecyte_vertical_sin_fondo.png');
}

function brand_logo_data_uri(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $path = brand_logo_path();
    $cached = is_file($path) ? image_to_jpeg_data_uri($path) : '';
    return $cached;
}

function brand_logo_img(string $class = '', string $alt = 'CECYTE Baja California Sur'): string
{
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<img src="' . e(brand_logo_web_src()) . '" alt="' . e($alt) . '"' . $classAttr . '>';
}

/** @return array{0:int,1:int}|null */
function brand_logo_dimensions(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached ?: null;
    }

    $path = brand_logo_path();
    if (!is_file($path)) {
        $cached = [];
        return null;
    }

    $size = @getimagesize($path);
    if ($size === false) {
        $cached = [];
        return null;
    }

    $cached = [(int) $size[0], (int) $size[1]];
    return $cached;
}

function brand_logo_pdf_size(): array
{
    $height = (int) config('app', 'branding.logo_pdf_height', 42);
    $dims = brand_logo_dimensions();
    if ($dims === null || $dims[1] === 0) {
        return ['width' => 175, 'height' => $height];
    }

    return [
        'width' => (int) round($height * ($dims[0] / $dims[1])),
        'height' => $height,
    ];
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $key, mixed $value = null): mixed
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $dt ? $dt->format($format) : $date;
}

function format_datetime(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '—';
    }
    $dt = new DateTimeImmutable($datetime);
    return $dt->format('d/m/Y H:i');
}

function format_money(float|int|string|null $amount): string
{
    return '$' . number_format((float) $amount, 2, '.', ',');
}

function csrf_field(): string
{
    return App\Core\Csrf::field();
}

function csrf_token(): string
{
    return App\Core\Csrf::token();
}

function auth_user(): ?array
{
    return App\Core\Session::get('user');
}

function auth_id(): ?int
{
    $user = auth_user();
    return $user ? (int) $user['id'] : null;
}

function can(string $permission): bool
{
    return App\Services\AuthService::hasPermission($permission);
}

function semaforo_class(?string $nivel): string
{
    return match ($nivel) {
        'verde' => 'badge-success',
        'amarillo' => 'badge-warning',
        'rojo' => 'badge-danger',
        default => 'badge-secondary',
    };
}

function vehiculo_estado_badge(string $estado): string
{
    return match ($estado) {
        'activo' => 'badge-success',
        'disponible' => 'badge-info',
        'en_comision' => 'badge-primary',
        'en_mantenimiento' => 'badge-warning',
        'en_taller' => 'badge-warning',
        'fuera_servicio' => 'badge-secondary',
        'baja' => 'badge-danger',
        default => 'badge-secondary',
    };
}

function vehiculo_identificador_label(): string
{
    return 'Identificador';
}

function vehiculo_identificador_placeholder(): string
{
    return 'Ej. Patrulla 01, Unidad Norte, Camión 3…';
}
