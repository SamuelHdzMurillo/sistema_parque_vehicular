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

/** Ruta relativa al host actual (ideal para fetch/AJAX en el mismo sitio). */
function url_path(string $path = ''): string
{
    $parsed = parse_url(url($path), PHP_URL_PATH);
    return $parsed ?: '/';
}

function asset(string $path): string
{
    $relative = 'assets/' . ltrim($path, '/');
    $url = url($relative);
    $file = public_path($relative);
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
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

/** Como old(), pero ignora cadenas vacías (útil para selects que no deben quedar en blanco tras un error). */
function old_nonempty(string $key, mixed $default = null): mixed
{
    if (!array_key_exists($key, $_SESSION['_old'] ?? [])) {
        return $default;
    }

    $value = $_SESSION['_old'][$key];
    if ($value === '' || $value === null) {
        return $default;
    }

    return $value;
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

/** Registra un error en storage/logs/app.log para diagnóstico. */
function log_app_error(\Throwable $e, array $context = []): void
{
    $dir = storage_path('logs');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $userId = auth_id();

    $line = sprintf(
        "[%s] %s: %s | %s %s | user:%s\n%s\n---\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $method,
        $requestUri,
        $userId ?? 'guest',
        $e->getTraceAsString()
    );

    if ($context !== []) {
        $line = str_replace("\n---\n", ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n---\n", $line);
    }

    file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/** Mensaje de error legible para el usuario; registra el detalle técnico en el log. */
function user_facing_error(\Throwable $e, string $fallback = 'Ocurrió un error inesperado.'): string
{
    log_app_error($e);

    if ($e instanceof \App\Exceptions\ValidationException) {
        $errors = $e->getErrors();
        return $errors !== [] ? implode(' ', $errors) : $fallback;
    }

    if ($e instanceof \InvalidArgumentException || $e instanceof \RuntimeException) {
        return $e->getMessage() !== '' ? $e->getMessage() : $fallback;
    }

    if ($e instanceof \PDOException) {
        if (config('app', 'debug')) {
            return 'Error de base de datos: ' . $e->getMessage();
        }
        return $fallback . ' Revise storage/logs/app.log para más detalle.';
    }

    if (config('app', 'debug')) {
        return $e->getMessage() !== '' ? $e->getMessage() : $fallback;
    }

    return $fallback . ' Revise storage/logs/app.log para más detalle.';
}

/** @return array<string, string> */
function field_errors(): array
{
    $errors = $_SESSION['_field_errors'] ?? [];
    unset($_SESSION['_field_errors']);
    return is_array($errors) ? $errors : [];
}

function field_error(string $field): ?string
{
    static $errors = null;
    if ($errors === null) {
        $errors = field_errors();
    }
    $message = $errors[$field] ?? null;
    return is_string($message) && $message !== '' ? $message : null;
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

function format_tiempo_restante(?string $fechaVencimiento, ?int $diasRestantes = null): string
{
    if ($fechaVencimiento === null || $fechaVencimiento === '') {
        return 'Sin vencimiento';
    }

    $dias = $diasRestantes;
    if ($dias === null) {
        $vence = DateTimeImmutable::createFromFormat('Y-m-d', substr($fechaVencimiento, 0, 10));
        if ($vence === false) {
            return '—';
        }
        $hoy = new DateTimeImmutable('today');
        $dias = (int) $hoy->diff($vence)->format('%r%a');
    }

    if ($dias < 0) {
        $abs = abs($dias);
        if ($abs >= 60) {
            $meses = intdiv($abs, 30);
            $resto = $abs % 30;
            $texto = $meses . ' mes' . ($meses !== 1 ? 'es' : '');
            if ($resto > 0) {
                $texto .= ', ' . $resto . ' día' . ($resto !== 1 ? 's' : '');
            }
            return 'Vencido hace ' . $texto;
        }
        return 'Vencido hace ' . $abs . ' día' . ($abs !== 1 ? 's' : '');
    }

    if ($dias === 0) {
        return 'Vence hoy';
    }

    if ($dias >= 60) {
        $meses = intdiv($dias, 30);
        $resto = $dias % 30;
        $texto = $meses . ' mes' . ($meses !== 1 ? 'es' : '');
        if ($resto > 0) {
            $texto .= ', ' . $resto . ' día' . ($resto !== 1 ? 's' : '');
        }
        return $texto;
    }

    return $dias . ' día' . ($dias !== 1 ? 's' : '');
}

function vencimiento_badge_class(?string $fechaVencimiento, ?int $diasRestantes = null): string
{
    if ($fechaVencimiento === null || $fechaVencimiento === '') {
        return 'badge-secondary';
    }

    $dias = $diasRestantes;
    if ($dias === null) {
        $vence = DateTimeImmutable::createFromFormat('Y-m-d', substr($fechaVencimiento, 0, 10));
        if ($vence === false) {
            return 'badge-secondary';
        }
        $hoy = new DateTimeImmutable('today');
        $dias = (int) $hoy->diff($vence)->format('%r%a');
    }

    if ($dias < 0) {
        return 'badge-danger';
    }
    if ($dias <= 30) {
        return 'badge-warning';
    }

    return 'badge-success';
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

/** Etiqueta legible del nivel de alerta (semáforo). */
function alerta_nivel_label(?string $nivel): string
{
    return match ($nivel) {
        'verde' => 'Aviso',
        'amarillo' => 'Atención',
        'rojo' => 'Urgente',
        default => 'Sin clasificar',
    };
}

/** Breve explicación de para qué sirve cada tipo de alerta. */
function alerta_tipo_descripcion(string $tipo, string $unidad): string
{
    $porTipo = [
        'cambio_aceite' => 'Recuerda cuándo toca cambiar el aceite del motor.',
        'afinacion' => 'Avisa cuando el vehículo necesita afinación.',
        'llantas' => 'Indica cuándo conviene revisar o cambiar llantas.',
        'bateria' => 'Controla la vida útil estimada de la batería.',
        'seguro' => 'Te avisa antes de que venza la póliza de seguro.',
        'tenencia' => 'Te avisa antes de que venza la tenencia.',
        'verificacion' => 'Te avisa antes de que venza la verificación vehicular.',
        'licencia' => 'Te avisa antes de que venza la licencia del conductor.',
    ];

    if (isset($porTipo[$tipo])) {
        return $porTipo[$tipo];
    }

    return $unidad === 'km'
        ? 'Avisa según los kilómetros recorridos desde el último servicio.'
        : 'Avisa según los días que faltan para vencer un documento.';
}

/** Orden lógico de tipos de alerta (documentos primero, luego mantenimiento). */
function alerta_config_tipo_orden(string $tipo): int
{
    return match ($tipo) {
        'seguro' => 10,
        'tenencia' => 20,
        'verificacion' => 30,
        'licencia' => 40,
        'bateria' => 50,
        'cambio_aceite' => 110,
        'afinacion' => 120,
        'llantas' => 130,
        default => 999,
    };
}

/** @param list<array<string, mixed>> $items */
function alerta_config_sort(array $items): array
{
    usort($items, static function (array $a, array $b): int {
        $grupoA = ($a['unidad'] ?? '') === 'km' ? 0 : 1;
        $grupoB = ($b['unidad'] ?? '') === 'km' ? 0 : 1;
        if ($grupoA !== $grupoB) {
            return $grupoA <=> $grupoB;
        }

        $orden = alerta_config_tipo_orden((string) ($a['tipo'] ?? ''))
            <=> alerta_config_tipo_orden((string) ($b['tipo'] ?? ''));
        if ($orden !== 0) {
            return $orden;
        }

        return strcmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });

    return $items;
}

/** Peso del nivel para ordenar listas (urgente primero). */
function alerta_nivel_peso(?string $nivel): int
{
    return match ($nivel) {
        'rojo' => 3,
        'amarillo' => 2,
        'verde' => 1,
        default => 0,
    };
}

/** @return array{aviso: int, atencion: int, urgente: int} Umbrales de mantenimiento en km (desde último servicio). */
function alerta_config_umbrales_km(array $row): array
{
    return [
        'aviso' => (int) ($row['umbral_rojo'] ?? 0),
        'atencion' => (int) ($row['umbral_amarillo'] ?? 0),
        'urgente' => (int) ($row['umbral_verde'] ?? 0),
    ];
}

/** @return array{aviso: int, atencion: int, urgente: int} Umbrales de documentos en días antes del vencimiento. */
function alerta_config_umbrales_dias_doc(array $row): array
{
    return [
        'aviso' => (int) ($row['umbral_verde'] ?? 0),
        'atencion' => (int) ($row['umbral_amarillo'] ?? 0),
        'urgente' => (int) ($row['umbral_rojo'] ?? 0),
    ];
}

function alerta_config_fmt_num(int $n): string
{
    return number_format($n, 0, '.', ',');
}

/** Texto corto para la columna «En la práctica» (mantenimiento). */
function alerta_config_resumen_km(array $row): string
{
    $u = alerta_config_umbrales_km($row);
    $nombre = (string) ($row['nombre'] ?? 'Servicio');

    return sprintf(
        '%s: avisa a los %s km, luego a los %s km y urgente a los %s km (desde el último servicio).',
        $nombre,
        alerta_config_fmt_num($u['aviso']),
        alerta_config_fmt_num($u['atencion']),
        alerta_config_fmt_num($u['urgente'])
    );
}

/** Texto corto para documentos. */
function alerta_config_resumen_doc(array $row): string
{
    $u = alerta_config_umbrales_dias_doc($row);
    $nombre = (string) ($row['nombre'] ?? 'Documento');

    return sprintf(
        '%s: avisa %s días antes, luego %s días antes y urgente %s días antes de vencer.',
        $nombre,
        alerta_config_fmt_num($u['aviso']),
        alerta_config_fmt_num($u['atencion']),
        alerta_config_fmt_num($u['urgente'])
    );
}

/** Busca una fila de config por tipo. */
function alerta_config_por_tipo(array $config, string $tipo): ?array
{
    foreach ($config as $row) {
        if (($row['tipo'] ?? '') === $tipo) {
            return $row;
        }
    }

    return null;
}

function mantenimiento_servicio_label(?string $tipo): string
{
    if ($tipo === null || $tipo === '') {
        return '—';
    }

    return match ($tipo) {
        'cambio_aceite' => 'Cambio de aceite',
        'afinacion' => 'Afinación',
        'llantas' => 'Revisión de llantas',
        default => ucfirst(str_replace('_', ' ', $tipo)),
    };
}

/** Intenta deducir el servicio desde la descripción (registros antiguos). */
function mantenimiento_inferir_servicio(string $descripcion): ?string
{
    $d = mb_strtolower($descripcion);
    if (str_contains($d, 'aceite')) {
        return 'cambio_aceite';
    }
    if (str_contains($d, 'afinaci')) {
        return 'afinacion';
    }
    if (str_contains($d, 'llanta')) {
        return 'llantas';
    }

    return null;
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

/** @param array<string, mixed> $area */
function catalogo_area_label(array $area): string
{
    if (!empty($area['label'])) {
        return (string) $area['label'];
    }
    $nombre = trim((string) ($area['nombre'] ?? ''));
    $plantel = trim((string) ($area['plantel_clave'] ?? $area['plantel_nombre'] ?? ''));
    if ($nombre !== '' && $plantel !== '') {
        return $nombre . ' - ' . $plantel;
    }
    return $nombre !== '' ? $nombre : '—';
}

/** @param array<string, mixed> $vehiculo */
function catalogo_vehiculo_label(array $vehiculo, bool $incluirEstado = true): string
{
    $partes = array_filter([
        trim((string) ($vehiculo['numero_economico'] ?? '')),
        trim(trim((string) ($vehiculo['marca'] ?? '')) . ' ' . trim((string) ($vehiculo['modelo'] ?? ''))),
        trim((string) ($vehiculo['placas'] ?? '')),
    ]);
    $etiqueta = implode(' — ', $partes);
    if ($incluirEstado && !empty($vehiculo['estado']) && !in_array($vehiculo['estado'], ['activo', 'disponible'], true)) {
        $etiqueta .= ' [' . ucfirst(str_replace('_', ' ', (string) $vehiculo['estado'])) . ']';
    }
    return $etiqueta !== '' ? $etiqueta : '—';
}

/** @return array<int, string> */
function combustible_cuartos_opciones(): array
{
    return [
        4 => 'Lleno (4/4)',
        3 => '3/4',
        2 => '1/2',
        1 => '1/4',
        0 => 'Vacío (0/4)',
    ];
}

/** @return array<string, string> */
function combustible_fracciones_opciones(): array
{
    return [
        '0/4' => 'Vacío (0/4)',
        '1/4' => '1/4',
        '1/2' => '1/2',
        '3/4' => '3/4',
        '4/4' => 'Lleno (4/4)',
    ];
}

function combustible_cuartos_a_fraccion(int $cuartos): string
{
    $cuartos = max(0, min(4, $cuartos));

    return match ($cuartos) {
        0 => '0/4',
        1 => '1/4',
        2 => '1/2',
        3 => '3/4',
        4 => '4/4',
        default => $cuartos . '/4',
    };
}

function combustible_porcentaje_a_fraccion(mixed $porcentaje): string
{
    if ($porcentaje === null || $porcentaje === '') {
        return '—';
    }

    $cuartos = (int) round(((float) $porcentaje / 100) * 4);

    return combustible_cuartos_a_fraccion($cuartos);
}

function combustible_fraccion_etiqueta(mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return '—';
    }

    $porcentaje = combustible_fraccion_a_porcentaje($valor);
    if ($porcentaje === null) {
        return '—';
    }

    return (int) round($porcentaje) . '%';
}

/** Convierte cualquier valor de combustible (cuartos, fracción o %) a cuartos 0–4. */
function combustible_valor_a_cuartos(mixed $valor): ?int
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $porcentaje = combustible_fraccion_a_porcentaje($valor);
    if ($porcentaje === null) {
        return null;
    }

    return (int) round(($porcentaje / 100) * 4);
}

function combustible_porcentaje_a_valor_formulario(mixed $porcentaje): string
{
    if ($porcentaje === null || $porcentaje === '') {
        return '';
    }

    return combustible_porcentaje_a_fraccion($porcentaje);
}

/** Normaliza un valor de formulario (fracción, porcentaje o legado) a fracción 0/4–4/4. */
function combustible_input_a_fraccion(mixed $valor): string
{
    if ($valor === null || $valor === '' || is_array($valor)) {
        return '';
    }

    $porcentaje = combustible_fraccion_a_porcentaje($valor);
    if ($porcentaje !== null) {
        return combustible_porcentaje_a_fraccion($porcentaje);
    }

    return trim((string) $valor);
}

/** Convierte fracción (1/4, 1/2, 3/4, 4/4) o porcentaje legado a valor 0–100. */
function combustible_fraccion_a_porcentaje(mixed $valor): ?float
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $s = strtolower(str_replace(' ', '', trim((string) $valor)));

    if (preg_match('/^(\d+)\/(\d+)$/', $s, $m)) {
        $num = (int) $m[1];
        $den = (int) $m[2];
        if ($den === 2) {
            $num *= 2;
            $den = 4;
        }
        if ($den !== 4 || $num < 0 || $num > 4) {
            return null;
        }

        return ($num / 4) * 100;
    }

    if ($s === 'lleno' || $s === 'full') {
        return 100.0;
    }

    if ($s === 'vacio' || $s === 'vacío' || $s === 'empty') {
        return 0.0;
    }

    // Valores del formulario: 0–4 = cuartos de tanque (más fiable que "3/4" en POST).
    if (preg_match('/^[0-4]$/', $s)) {
        return ((int) $s / 4) * 100;
    }

    if (is_numeric($s)) {
        $pct = (float) $s;
        if ($pct >= 0 && $pct <= 100) {
            $cuartos = (int) round(($pct / 100) * 4);

            return ($cuartos / 4) * 100;
        }
    }

    return null;
}

function rol_badge_class(string $slug): string
{
    return match ($slug) {
        'admin_general' => 'badge-primary',
        'admin_transporte' => 'badge-success',
        'supervisor' => 'badge-info',
        'responsable_vehiculo' => 'badge-warning',
        'consulta' => 'badge-secondary',
        default => 'badge-secondary',
    };
}

function rol_nivel_label(string $slug): string
{
    return match ($slug) {
        'admin_general' => 'Acceso total',
        'admin_transporte' => 'Operación del parque',
        'supervisor' => 'Supervisión y autorización',
        'responsable_vehiculo' => 'Operación en campo',
        'consulta' => 'Solo consulta',
        default => '',
    };
}

/** @return array<string, string> */
function auditoria_modulos(): array
{
    return [
        'vehiculos' => 'Vehículos',
        'comisiones' => 'Comisiones',
        'inspecciones' => 'Inspecciones',
        'danios' => 'Daños',
        'danio_fotos' => 'Daños (evidencia fotográfica)',
        'mantenimientos' => 'Mantenimiento',
        'combustible_cargas' => 'Combustible',
        'proveedores' => 'Proveedores',
        'documentos' => 'Documentos',
        'alertas' => 'Alertas',
        'herramientas_vehiculo' => 'Herramientas del vehículo',
        'users' => 'Usuarios del sistema',
        'reportes' => 'Reportes',
        'formatos_pdf' => 'Formatos PDF',
    ];
}

function auditoria_modulo_label(string $tabla): string
{
    return auditoria_modulos()[$tabla] ?? ucfirst(str_replace('_', ' ', $tabla));
}

/** @return array<string, string> */
function auditoria_acciones(): array
{
    return [
        'CREATE' => 'Registro nuevo',
        'INSERT' => 'Registro nuevo',
        'UPDATE' => 'Modificación',
        'DELETE' => 'Eliminación',
        'EXPORT' => 'Exportación / descarga',
    ];
}

/** @return array<string, string> */
function auditoria_filtros_accion(): array
{
    return [
        'nuevo' => 'Registro nuevo',
        'UPDATE' => 'Modificación',
        'DELETE' => 'Eliminación',
        'EXPORT' => 'Exportación / descarga',
    ];
}

function auditoria_accion_label(string $accion): string
{
    $key = strtoupper($accion);
    return auditoria_acciones()[$key] ?? ucfirst(strtolower($accion));
}

function auditoria_accion_badge(string $accion): string
{
    return match (strtoupper($accion)) {
        'CREATE', 'INSERT' => 'badge-success',
        'UPDATE' => 'badge-warning',
        'DELETE' => 'badge-danger',
        'EXPORT' => 'badge-info',
        default => 'badge-secondary',
    };
}

function auditoria_campo_label(string $campo): string
{
    return match ($campo) {
        'email' => 'Correo electrónico',
        'estado' => 'Estado',
        'vehiculo_id' => 'Vehículo',
        'password_changed' => 'Contraseña',
        'activo' => 'Activo',
        'atendida' => 'Atendida',
        'motivo' => 'Motivo',
        'fotos' => 'Fotos',
        'tipo' => 'Tipo',
        'formato' => 'Formato',
        'fecha_inicio' => 'Fecha de inicio',
        'fecha_fin' => 'Fecha de fin',
        default => ucfirst(str_replace('_', ' ', $campo)),
    };
}

/** @param mixed $valor */
function auditoria_valor_legible(string $campo, $valor): string
{
    if ($valor === null || $valor === '') {
        return '—';
    }
    if (is_bool($valor)) {
        return $valor ? 'Sí' : 'No';
    }
    if ($campo === 'password_changed' && $valor) {
        return 'Se actualizó la contraseña';
    }
    if ($campo === 'estado') {
        return ucfirst(str_replace('_', ' ', (string) $valor));
    }
    if (is_array($valor)) {
        return json_encode($valor, JSON_UNESCAPED_UNICODE) ?: '—';
    }
    return (string) $valor;
}

/** @param array<string, mixed>|null $datos */
function auditoria_formatear_datos(?array $datos, int $maxItems = 4): string
{
    if ($datos === null || $datos === []) {
        return '';
    }
    $partes = [];
    foreach (array_slice($datos, 0, $maxItems, true) as $campo => $valor) {
        $partes[] = auditoria_campo_label((string) $campo) . ': ' . auditoria_valor_legible((string) $campo, $valor);
    }
    if (count($datos) > $maxItems) {
        $partes[] = '… y ' . (count($datos) - $maxItems) . ' más';
    }
    return implode(' · ', $partes);
}

/** @param array<string, mixed> $log */
function auditoria_resumen(array $log): string
{
    $accion = strtoupper((string) ($log['accion'] ?? ''));
    $modulo = auditoria_modulo_label((string) ($log['tabla_afectada'] ?? ''));
    $registroId = $log['registro_id'] ?? null;
    $before = auditoria_decodificar_json($log['valores_anteriores'] ?? null);
    $after = auditoria_decodificar_json($log['valores_nuevos'] ?? null);
    $ref = $registroId ? ' (referencia #' . $registroId . ')' : '';

    return match ($accion) {
        'EXPORT' => 'Se exportó o descargó información de ' . mb_strtolower($modulo)
            . ($after !== null && $after !== [] ? ': ' . auditoria_formatear_datos($after) : ''),
        'DELETE' => 'Se eliminó un registro en ' . mb_strtolower($modulo) . $ref
            . ($before !== null && $before !== [] ? ': ' . auditoria_formatear_datos($before) : ''),
        'INSERT', 'CREATE' => 'Se creó un registro en ' . mb_strtolower($modulo) . $ref
            . ($after !== null && $after !== [] ? ': ' . auditoria_formatear_datos($after) : ''),
        'UPDATE' => auditoria_resumen_cambio($modulo, $before, $after, $ref),
        default => 'Se registró una acción en ' . mb_strtolower($modulo) . $ref,
    };
}

/** @return array<string, mixed>|null */
function auditoria_decodificar_json(mixed $valor): ?array
{
    if ($valor === null || $valor === '') {
        return null;
    }
    if (is_array($valor)) {
        return $valor;
    }
    if (!is_string($valor)) {
        return null;
    }
    $decoded = json_decode($valor, true);
    return is_array($decoded) ? $decoded : null;
}

/** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
function auditoria_resumen_cambio(string $modulo, ?array $before, ?array $after, string $ref = ''): string
{
    $cambios = [];
    $keys = array_unique(array_merge(array_keys($before ?? []), array_keys($after ?? [])));
    foreach ($keys as $campo) {
        $anterior = $before[$campo] ?? null;
        $nuevo = $after[$campo] ?? null;
        if ($anterior == $nuevo) {
            continue;
        }
        $etiqueta = auditoria_campo_label((string) $campo);
        if ($anterior === null) {
            $cambios[] = $etiqueta . ' → ' . auditoria_valor_legible((string) $campo, $nuevo);
        } elseif ($nuevo === null) {
            $cambios[] = $etiqueta . ' eliminado (antes: ' . auditoria_valor_legible((string) $campo, $anterior) . ')';
        } else {
            $cambios[] = $etiqueta . ': ' . auditoria_valor_legible((string) $campo, $anterior)
                . ' → ' . auditoria_valor_legible((string) $campo, $nuevo);
        }
        if (count($cambios) >= 3) {
            break;
        }
    }

    $base = 'Se modificó un registro en ' . mb_strtolower($modulo) . $ref;
    if ($cambios === []) {
        return $base;
    }
    return $base . ': ' . implode(' · ', $cambios);
}
