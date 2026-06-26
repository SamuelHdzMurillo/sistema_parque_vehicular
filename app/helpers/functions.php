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

/** URL de la pantalla de alertas conservando filtros activos. */
function alerta_index_url(array $overrides = []): string
{
    $params = array_merge($_GET ?? [], $overrides);
    $query = [];

    if (!empty($params['vehiculo_id'])) {
        $query['vehiculo_id'] = (int) $params['vehiculo_id'];
    }
    if (!empty($params['pendientes'])) {
        $query['pendientes'] = 1;
    }
    if (!empty($params['historial']) || !empty($params['todas'])) {
        $query['historial'] = 1;
    }
    if (!empty($params['page']) && (int) $params['page'] > 1) {
        $query['page'] = (int) $params['page'];
    }

    return $query === [] ? url('alertas') : url('alertas?' . http_build_query($query));
}

/** Estado visible en la matriz de mantenimiento por vehículo. */
function alerta_estado_mantenimiento(array $fila): array
{
    if (!empty($fila['sin_alta'])) {
        return ['label' => 'Sin alta', 'class' => 'badge-secondary'];
    }

    $nivel = isset($fila['nivel']) ? (string) $fila['nivel'] : null;
    if ($nivel === null || $nivel === '') {
        return ['label' => 'En orden', 'class' => 'badge-info'];
    }

    return ['label' => alerta_nivel_label($nivel), 'class' => semaforo_class($nivel)];
}

/** Estado visible en la matriz para documentos por vehículo. */
function alerta_estado_documento(array $fila): array
{
    if (!empty($fila['sin_alta'])) {
        return ['label' => 'Sin alta', 'class' => 'badge-secondary'];
    }

    $nivel = isset($fila['nivel']) ? (string) $fila['nivel'] : null;
    if ($nivel === null || $nivel === '') {
        return ['label' => 'En orden', 'class' => 'badge-info'];
    }

    return ['label' => alerta_nivel_label($nivel), 'class' => semaforo_class($nivel)];
}

/** Texto del último mantenimiento registrado o «Sin alta». */
function alerta_ultimo_mantenimiento_display(array $fila): string
{
    if (!empty($fila['sin_alta'])) {
        return 'Sin alta';
    }

    $partes = [];
    if (!empty($fila['fecha_ultimo_mantenimiento'])) {
        $partes[] = format_date($fila['fecha_ultimo_mantenimiento']);
    }
    if (isset($fila['ultimo_km']) && $fila['ultimo_km'] !== null) {
        $partes[] = number_format((int) $fila['ultimo_km'], 0, '.', ',') . ' km';
    }

    return $partes !== [] ? implode(' · ', $partes) : '—';
}

/** Texto de «próximo toca» o indicación cuando no hay alta. */
function alerta_proximo_mantenimiento_display(array $fila): string
{
    if (!empty($fila['sin_alta'])) {
        return 'Registre el primer servicio';
    }

    $partes = array_filter(alerta_proximo_partes($fila));

    return $partes !== [] ? implode(' · ', $partes) : '—';
}

/** Nivel más grave entre filas de un vehículo (ignora sin alta). */
function alerta_nivel_max_filas(array $filas): ?string
{
    $max = null;
    foreach ($filas as $fila) {
        if (!empty($fila['sin_alta'])) {
            continue;
        }

        $nivel = isset($fila['nivel']) ? (string) $fila['nivel'] : null;
        if ($nivel !== null && $nivel !== '' && alerta_nivel_peso($nivel) > alerta_nivel_peso($max)) {
            $max = $nivel;
        }
    }

    return $max;
}

/** Ordena filas: urgentes primero, sin alta al final. */
function alerta_ordenar_filas(array &$filas): void
{
    usort($filas, static function (array $a, array $b): int {
        $pesoA = !empty($a['sin_alta']) ? -1 : alerta_nivel_peso($a['nivel'] ?? null);
        $pesoB = !empty($b['sin_alta']) ? -1 : alerta_nivel_peso($b['nivel'] ?? null);
        $cmp = $pesoB <=> $pesoA;
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcasecmp(
            (string) ($a['servicio_nombre'] ?? $a['titulo'] ?? ''),
            (string) ($b['servicio_nombre'] ?? $b['titulo'] ?? '')
        );
    });
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

/** Genera un código interno (slug) para un tipo de servicio de alerta. */
function alerta_servicio_slug(string $text): string
{
    static $replacements = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ñ' => 'n', 'ü' => 'u',
    ];

    $text = mb_strtolower(trim($text));
    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9]+/', '_', $text) ?? '';
    $text = trim($text, '_');

    return substr($text, 0, 50);
}

/** Orden lógico de tipos de alerta (documentos primero, luego mantenimiento). */
function alerta_config_tipo_orden(string $tipo): int
{
    return match ($tipo) {
        'seguro' => 10,
        'tenencia' => 20,
        'verificacion' => 30,
        'licencia' => 40,
        'tarjeta_circulacion' => 45,
        'factura' => 48,
        'otro' => 49,
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

/** Normaliza el tipo de documento al catálogo de la base de datos. */
function documento_tipo_normalizado(string $tipo, string $titulo = ''): string
{
    $tipo = strtolower(trim($tipo));
    $tituloLower = strtolower($titulo);

    if ($tipo === 'poliza_seguro' || $tipo === 'seguro') {
        return 'poliza';
    }

    if ($tipo === '') {
        if (str_contains($tituloLower, 'poliza') || str_contains($tituloLower, 'seguro')) {
            return 'poliza';
        }
        if (str_contains($tituloLower, 'verificacion')) {
            return 'verificacion';
        }
        if (str_contains($tituloLower, 'tenencia')) {
            return 'tenencia';
        }
        if (str_contains($tituloLower, 'licencia')) {
            return 'licencia';
        }
        if (str_contains($tituloLower, 'tarjeta') || str_contains($tituloLower, 'circulacion')) {
            return 'tarjeta_circulacion';
        }

        return 'otro';
    }

    return $tipo;
}

/** @return array<string, string> */
function documento_tipos_opciones(): array
{
    return [
        'poliza' => 'Póliza de seguro',
        'verificacion' => 'Verificación vehicular',
        'tarjeta_circulacion' => 'Tarjeta de circulación',
        'tenencia' => 'Tenencia',
        'licencia' => 'Licencia de conductor',
        'factura' => 'Factura',
        'otro' => 'Otro',
    ];
}

function documento_tipo_label(string $tipo): string
{
    $tipo = documento_tipo_normalizado($tipo);

    return documento_tipos_opciones()[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
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

/** @param list<string> $servicios */
function mantenimiento_servicios_labels(array $servicios): string
{
    if ($servicios === []) {
        return '—';
    }

    return implode(', ', array_map(
        static fn (string $tipo): string => mantenimiento_servicio_label($tipo),
        $servicios
    ));
}

/** Valida ruta de retorno tras agregar un servicio desde mantenimiento. */
function mantenimiento_safe_return_to(string $returnTo): string
{
    $returnTo = trim($returnTo);
    if ($returnTo === '') {
        return 'mantenimiento/create';
    }

    $path = parse_url($returnTo, PHP_URL_PATH);
    $query = parse_url($returnTo, PHP_URL_QUERY);
    if (!is_string($path) || $path === '') {
        return 'mantenimiento/create';
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (!preg_match('#^mantenimiento(?:/create|/\d+/edit)$#', $path)) {
        return 'mantenimiento/create';
    }

    return $query !== null && $query !== '' ? $path . '?' . $query : $path;
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

/** Intervalo en km guardado en el último mantenimiento. */
function mantenimiento_intervalo_km(?array $ultimo): ?int
{
    if ($ultimo === null) {
        return null;
    }

    $km = $ultimo['intervalo_km'] ?? null;

    return ($km !== null && (int) $km > 0) ? (int) $km : null;
}

/** Intervalo en días guardado en el último mantenimiento. */
function mantenimiento_intervalo_dias(?array $ultimo): ?int
{
    if ($ultimo === null) {
        return null;
    }

    $dias = $ultimo['intervalo_dias'] ?? null;

    return ($dias !== null && (int) $dias > 0) ? (int) $dias : null;
}

/** Meses equivalentes al intervalo en días (para formularios). */
function mantenimiento_intervalo_meses(?array $ultimo): ?int
{
    $dias = mantenimiento_intervalo_dias($ultimo);
    if ($dias === null) {
        return null;
    }

    return (int) round($dias / 30);
}

/** Texto legible del próximo servicio programado. */
function mantenimiento_intervalo_display(?int $intervaloKm, ?int $intervaloDias): string
{
    $partes = [];
    if ($intervaloKm !== null && $intervaloKm > 0) {
        $partes[] = number_format($intervaloKm, 0, '.', ',') . ' km';
    }
    if ($intervaloDias !== null && $intervaloDias > 0) {
        $meses = (int) round($intervaloDias / 30);
        $partes[] = $meses . ' mes' . ($meses === 1 ? '' : 'es');
    }

    return $partes !== [] ? implode(' · ', $partes) : '—';
}

/**
 * Calcula la fecha y el kilometraje del próximo servicio a partir de un mantenimiento.
 *
 * @param array<string, mixed> $mant
 * @param array{servicio?: string, intervalo_km?: ?int, intervalo_dias?: ?int} $intervalo
 * @return array{fecha: ?string, km: ?int}
 */
function mantenimiento_proximo_servicio(array $mant, array $intervalo): array
{
    $fechaBase = !empty($mant['fecha']) ? substr((string) $mant['fecha'], 0, 10) : null;
    $kmBase = (int) ($mant['kilometraje'] ?? 0);

    $proximaFecha = null;
    $intervaloDias = isset($intervalo['intervalo_dias']) && (int) $intervalo['intervalo_dias'] > 0
        ? (int) $intervalo['intervalo_dias'] : null;
    if ($fechaBase !== null && $intervaloDias !== null) {
        $proximaFecha = date('Y-m-d', strtotime($fechaBase . ' + ' . $intervaloDias . ' days'));
    }

    $proximoKm = null;
    $intervaloKm = isset($intervalo['intervalo_km']) && (int) $intervalo['intervalo_km'] > 0
        ? (int) $intervalo['intervalo_km'] : null;
    if ($intervaloKm !== null) {
        $proximoKm = $kmBase + $intervaloKm;
    }

    return ['fecha' => $proximaFecha, 'km' => $proximoKm];
}

/**
 * Texto legible de cuándo toca el próximo servicio (fecha y/o kilometraje).
 *
 * @param array<string, mixed> $mant
 * @param array{servicio?: string, intervalo_km?: ?int, intervalo_dias?: ?int} $intervalo
 */
function mantenimiento_proximo_servicio_display(array $mant, array $intervalo): string
{
    $partes = mantenimiento_proximo_servicio_partes($mant, $intervalo);

    return $partes !== [] ? implode(' · ', $partes) : '—';
}

/**
 * @param array<string, mixed> $mant
 * @param array{servicio?: string, intervalo_km?: ?int, intervalo_dias?: ?int} $intervalo
 * @return list<string>
 */
function mantenimiento_proximo_servicio_partes(array $mant, array $intervalo): array
{
    $calc = mantenimiento_proximo_servicio($mant, $intervalo);
    $partes = [];
    if ($calc['fecha'] !== null) {
        $partes[] = 'Próxima fecha: ' . format_date($calc['fecha']);
    }
    if ($calc['km'] !== null) {
        $partes[] = 'Próximo kilometraje: ' . number_format($calc['km'], 0, '.', ',') . ' km';
    }

    return $partes;
}

/**
 * Campos etiquetados para PDF / detalle del próximo servicio por tipo.
 *
 * @param array<string, mixed> $mant
 * @param array{servicio?: string, intervalo_km?: ?int, intervalo_dias?: ?int} $intervalo
 * @return list<array{label: string, value: string}>
 */
function mantenimiento_proximo_servicio_campos(array $mant, array $intervalo): array
{
    $calc = mantenimiento_proximo_servicio($mant, $intervalo);
    $servicio = mantenimiento_servicio_label((string) ($intervalo['servicio'] ?? ''));
    $campos = [];
    if ($calc['fecha'] !== null) {
        $campos[] = [
            'label' => $servicio . ' — Próxima fecha',
            'value' => format_date($calc['fecha']),
        ];
    }
    if ($calc['km'] !== null) {
        $campos[] = [
            'label' => $servicio . ' — Próximo kilometraje',
            'value' => number_format($calc['km'], 0, '.', ',') . ' km',
        ];
    }

    return $campos;
}

/**
 * Evalúa alertas de mantenimiento según intervalos del último servicio.
 *
 * @return array{nivel: string, motivo: string}|null
 */
function alerta_evaluar_intervalos(int $kmDesde, int $diasDesde, ?int $intervaloKm, ?int $intervaloDias): ?array
{
    $pctMax = 0.0;
    $motivos = [];

    if ($intervaloKm !== null && $intervaloKm > 0) {
        $pct = ($kmDesde / $intervaloKm) * 100;
        $pctMax = max($pctMax, $pct);
        if ($pct >= 70) {
            $motivos[] = sprintf('%s km desde el último servicio', number_format($kmDesde, 0, '.', ','));
        }
    }

    if ($intervaloDias !== null && $intervaloDias > 0) {
        $pct = ($diasDesde / $intervaloDias) * 100;
        $pctMax = max($pctMax, $pct);
        if ($pct >= 70) {
            $motivos[] = sprintf('%d día(s) desde el último servicio', $diasDesde);
        }
    }

    if ($pctMax < 70 || $motivos === []) {
        return null;
    }

    $nivel = $pctMax >= 100 ? 'rojo' : ($pctMax >= 85 ? 'amarillo' : 'verde');

    return [
        'nivel' => $nivel,
        'motivo' => implode(' · ', $motivos),
    ];
}

/** @deprecated Usar mantenimiento_intervalo_dias() */
function alerta_intervalo_dias(array $config): ?int
{
    $intervalo = $config['intervalo_dias'] ?? null;
    if ($intervalo !== null && (int) $intervalo > 0) {
        return (int) $intervalo;
    }

    return null;
}

/** @deprecated Usar mantenimiento_intervalo_km() */
function alerta_intervalo_km(array $config): ?int
{
    $intervalo = $config['intervalo_km'] ?? null;
    if ($intervalo !== null && (int) $intervalo > 0) {
        return (int) $intervalo;
    }

    return null;
}

/**
 * Calcula la última fecha de servicio y la próxima fecha programada.
 *
 * @return array{ultima: ?string, proxima: ?string}
 */
function alerta_mantenimiento_fechas(?array $ultimo, ?array $vehiculo = null): array
{
    unset($vehiculo);
    $ultima = null;
    if ($ultimo !== null && !empty($ultimo['fecha'])) {
        $ultima = substr((string) $ultimo['fecha'], 0, 10);
    }

    $proxima = null;
    if ($ultima !== null) {
        $intervalDias = mantenimiento_intervalo_dias($ultimo);
        if ($intervalDias !== null) {
            $proxima = date('Y-m-d', strtotime($ultima . ' + ' . $intervalDias . ' days'));
        }
    }

    return ['ultima' => $ultima, 'proxima' => $proxima];
}

/** Kilometraje estimado del próximo servicio. */
function alerta_proximo_km(?array $ultimo): ?int
{
    $intervalo = mantenimiento_intervalo_km($ultimo);
    if ($intervalo === null) {
        return null;
    }

    $baseKm = $ultimo !== null ? (int) ($ultimo['kilometraje'] ?? 0) : 0;

    return $baseKm + $intervalo;
}

/** @return array{fecha: ?string, km: ?string} */
function alerta_proximo_partes(array $alerta): array
{
    return [
        'fecha' => !empty($alerta['fecha_proximo_mantenimiento'])
            ? format_date($alerta['fecha_proximo_mantenimiento'])
            : null,
        'km' => !empty($alerta['proximo_km'])
            ? number_format((int) $alerta['proximo_km'], 0, '.', ',') . ' km'
            : null,
    ];
}

/** Texto para la columna «Próximo toca». */
function alerta_proximo_display(array $alerta): string
{
    $partes = array_filter(alerta_proximo_partes($alerta));

    return implode(' · ', $partes);
}

/** URL para atender la alerta (registrar servicio o revisar documentación). */
function alerta_accion_url(array $alerta): string
{
    $vehiculoId = (int) ($alerta['vehiculo_id'] ?? 0);
    $tipo = (string) ($alerta['tipo'] ?? '');

    if (!empty($alerta['mantenimiento_abierto_id'])) {
        return url('mantenimiento/' . (int) $alerta['mantenimiento_abierto_id']);
    }

    if (($alerta['categoria'] ?? '') === 'mantenimiento' && $vehiculoId > 0 && $tipo !== '') {
        return url('mantenimiento/create?vehiculo_id=' . $vehiculoId . '&servicio=' . rawurlencode($tipo));
    }

    if (($alerta['categoria'] ?? '') === 'documento' && $vehiculoId > 0) {
        $documentoId = (int) ($alerta['documento_id'] ?? 0);
        if ($documentoId > 0) {
            return url('documentos/' . $documentoId . '/edit');
        }

        return url('documentos?vehiculo_id=' . $vehiculoId);
    }

    return url('alertas');
}

/** Resumen breve para la fila de alertas (sin párrafo largo). */
function alerta_resumen_fila(array $alerta): string
{
    if (($alerta['categoria'] ?? '') === 'mantenimiento') {
        $partes = [];
        if (isset($alerta['km_desde']) && $alerta['km_desde'] !== null) {
            $partes[] = number_format((int) $alerta['km_desde'], 0, '.', ',') . ' km recorridos';
        }
        if (isset($alerta['dias_desde']) && $alerta['dias_desde'] !== null && (int) $alerta['dias_desde'] > 0) {
            $partes[] = (int) $alerta['dias_desde'] . ' día(s)';
        }

        return implode(' · ', $partes);
    }

    if (($alerta['categoria'] ?? '') === 'documento') {
        $dias = $alerta['dias_restantes'] ?? null;
        if ($dias === null) {
            return 'Documento por revisar';
        }
        if ((int) $dias < 0) {
            return 'Vencido hace ' . number_format(abs((int) $dias)) . ' día(s)';
        }

        return 'Vence en ' . number_format((int) $dias) . ' día(s)';
    }

    return '';
}

/**
 * Agrupa alertas enriquecidas por vehículo (urgentes primero).
 *
 * @param list<array<string, mixed>> $alertas
 * @return list<array{vehiculo_id: int, numero_economico: string, nivel_max: ?string, alertas: list<array<string, mixed>>}>
 */
function alerta_agrupar_por_vehiculo(array $alertas): array
{
    $grupos = [];

    foreach ($alertas as $alerta) {
        $vehiculoId = (int) ($alerta['vehiculo_id'] ?? 0);
        if (!isset($grupos[$vehiculoId])) {
            $grupos[$vehiculoId] = [
                'vehiculo_id' => $vehiculoId,
                'numero_economico' => (string) ($alerta['numero_economico'] ?? '—'),
                'nivel_max' => null,
                'alertas' => [],
            ];
        }

        $grupos[$vehiculoId]['alertas'][] = $alerta;
        $nivel = isset($alerta['nivel']) ? (string) $alerta['nivel'] : null;
        if ($nivel !== null && alerta_nivel_peso($nivel) > alerta_nivel_peso($grupos[$vehiculoId]['nivel_max'])) {
            $grupos[$vehiculoId]['nivel_max'] = $nivel;
        }
    }

    $lista = array_values($grupos);

    usort($lista, static function (array $a, array $b): int {
        $cmp = alerta_nivel_peso($b['nivel_max']) <=> alerta_nivel_peso($a['nivel_max']);
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcasecmp($a['numero_economico'], $b['numero_economico']);
    });

    foreach ($lista as &$grupo) {
        usort($grupo['alertas'], static function (array $a, array $b): int {
            $cmp = alerta_nivel_peso($b['nivel'] ?? null) <=> alerta_nivel_peso($a['nivel'] ?? null);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp(
                (string) ($a['servicio_nombre'] ?? $a['titulo'] ?? ''),
                (string) ($b['servicio_nombre'] ?? $b['titulo'] ?? '')
            );
        });
    }
    unset($grupo);

    return $lista;
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

/** @return list<array{codigo: string, nombre: string}> */
function herramienta_catalogo(): array
{
    return \App\Repositories\ComisionRepository::HERRAMIENTAS;
}

function herramienta_nombre(string $codigo): string
{
    foreach (herramienta_catalogo() as $item) {
        if ($item['codigo'] === $codigo) {
            return $item['nombre'];
        }
    }
    return ucfirst(str_replace('_', ' ', $codigo));
}

function herramienta_slug(string $nombre): string
{
    $slug = strtolower(trim($nombre));
    $slug = (string) preg_replace('/[^a-z0-9]+/', '_', $slug);
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = 'otro_' . substr(md5($nombre), 0, 8);
    }
    return substr($slug, 0, 40);
}

/** @return list<string> */
function herramienta_catalogo_codigos(): array
{
    return array_column(herramienta_catalogo(), 'codigo');
}

function herramienta_es_codigo_valido(string $codigo): bool
{
    if (in_array($codigo, herramienta_catalogo_codigos(), true)) {
        return true;
    }
    return (bool) preg_match('/^[a-z][a-z0-9_]{1,38}$/', $codigo);
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

/**
 * Separa un folio tipo XXX-AAAA-NNN en prefijo y número consecutivo.
 *
 * @return array{prefix: string, num: int, num_padded: string, full: string}|null
 */
function folio_partes(string $folio, int $pad = 4): ?array
{
    $folio = trim($folio);
    if (!preg_match('/^([A-Z]{3}-\d{4}-)(\d+)$/i', $folio, $m)) {
        return null;
    }

    $num = (int) $m[2];
    $prefix = strtoupper($m[1]);
    $numPadded = str_pad((string) $num, $pad, '0', STR_PAD_LEFT);

    return [
        'prefix' => $prefix,
        'num' => $num,
        'num_padded' => $numPadded,
        'full' => $prefix . $numPadded,
    ];
}

/** Folio legible de una inspección (almacenado o derivado del id). */
function inspeccion_folio(array $inspeccion): string
{
    $folio = trim((string) ($inspeccion['folio'] ?? ''));
    if ($folio !== '') {
        return $folio;
    }

    $id = (int) ($inspeccion['id'] ?? 0);
    $fecha = (string) ($inspeccion['fecha'] ?? '');
    $year = $fecha !== '' ? date('Y', strtotime($fecha)) : date('Y');

    return 'INS-' . $year . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
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
