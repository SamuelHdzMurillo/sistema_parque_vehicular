<?php

declare(strict_types=1);

if (defined('PDF_HELPERS_LOADED')) {
    return;
}
define('PDF_HELPERS_LOADED', true);

function pdf_val(?string $value, string $placeholder = ''): string
{
    $value = trim((string) $value);
    if ($value === '' || $value === '—') {
        return $placeholder;
    }
    return $value;
}

function pdf_date(?string $date): string
{
    $formatted = format_date($date);
    return $formatted !== '—' ? $formatted : '';
}

function pdf_time(?string $time): string
{
    if ($time === null || $time === '') {
        return '';
    }
    return substr($time, 0, 5);
}

function pdf_money(float|int|string|null $amount): string
{
    if ($amount === null || $amount === '') {
        return '';
    }
    return format_money($amount);
}

function pdf_brand(string $key, string $default = ''): string
{
    return (string) config('app', 'branding.' . $key, $default);
}

function pdf_image_file_to_data_uri(string $full): string
{
    if (!is_file($full)) {
        return '';
    }

    $mime = mime_content_type($full) ?: '';
    if (str_contains($mime, 'svg')) {
        return 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($full));
    }

    if (!extension_loaded('gd')) {
        return '';
    }

    $image = match ($mime) {
        'image/png' => @imagecreatefrompng($full),
        'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($full),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : false,
        default => false,
    };

    if ($image === false) {
        return '';
    }

    ob_start();
    imagejpeg($image, null, 92);
    imagedestroy($image);
    $jpeg = ob_get_clean();

    return is_string($jpeg) && $jpeg !== ''
        ? 'data:image/jpeg;base64,' . base64_encode($jpeg)
        : '';
}

function pdf_logo_data_uri(): string
{
    $configured = pdf_brand('logo_horizontal');
    $candidates = array_values(array_unique(array_filter([
        $configured !== '' ? public_path($configured) : null,
        public_path('assets/images/logo-cecyte-horizontal.png'),
        public_path('assets/images/logo-cecyte-horizontal.svg'),
        public_path('assets/images/logo-cecyte.png'),
    ])));

    foreach ($candidates as $path) {
        $uri = pdf_image_file_to_data_uri($path);
        if ($uri !== '') {
            return $uri;
        }
    }

    return '';
}

function pdf_firma_data_uri(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }

    return pdf_image_file_to_data_uri(storage_path('uploads/' . ltrim($path, '/')));
}

/**
 * @param list<array{label: string, nombre?: string, firma?: string|null}> $firmas
 */
function pdf_render_firmas(array $firmas): void
{
    require view_path('pdf/partials/firmas.php');
}

/**
 * @param list<array{label: string, value: string}> $fields
 */
function pdf_render_fields(array $fields, int $cols = 2): void
{
    $chunks = array_chunk($fields, $cols);
    foreach ($chunks as $row) {
        echo '<table class="fields-row"><tr>';
        foreach ($row as $field) {
            echo '<td class="field-cell"><span class="field-label">' . e($field['label']) . '</span>';
            echo '<span class="field-value">' . ($field['value'] !== '' ? e($field['value']) : '&nbsp;') . '</span></td>';
        }
        if (count($row) < $cols) {
            echo str_repeat('<td class="field-cell"></td>', $cols - count($row));
        }
        echo '</tr></table>';
    }
}
