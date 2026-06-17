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
    return image_to_jpeg_data_uri($full);
}

function pdf_logo_data_uri(): string
{
    return brand_logo_data_uri();
}

function pdf_firma_data_uri(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }

    $full = storage_path('uploads/' . ltrim($path, '/'));
    if (!is_file($full)) {
        return '';
    }

    return image_to_jpeg_data_uri($full);
}

/**
 * Prepara la factura como imagen JPEG en base64, recortada para llenar una hoja A4.
 * Usa data URI (mismo método que las fotos de daños en PDF).
 *
 * @return array{src: string, width_mm: float, height_mm: float}|null
 */
function pdf_prepare_factura(?string $relativePath): ?array
{
    if ($relativePath === null || trim($relativePath) === '') {
        return null;
    }

    $full = storage_path('uploads/' . ltrim($relativePath, '/'));
    if (!is_file($full)) {
        return null;
    }

    $ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return null;
    }

    if (!extension_loaded('gd')) {
        $uri = image_to_jpeg_data_uri($full);
        if ($uri === '') {
            return null;
        }
        return ['src' => $uri, 'width_mm' => 190.0, 'height_mm' => 250.0];
    }

    $srcImg = image_from_file($full);
    if ($srcImg === false) {
        return null;
    }

    $srcW = imagesx($srcImg);
    $srcH = imagesy($srcImg);
    if ($srcW <= 0 || $srcH <= 0) {
        imagedestroy($srcImg);
        return null;
    }

    $pageW = 1500;
    $pageH = 1974;
    $srcRatio = $srcW / $srcH;
    $pageRatio = $pageW / $pageH;

    if ($srcRatio >= $pageRatio) {
        $cropH = $srcH;
        $cropW = (int) round($srcH * $pageRatio);
        $cropX = (int) (($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int) round($srcW / $pageRatio);
        $cropX = 0;
        $cropY = (int) (($srcH - $cropH) / 2);
    }

    $dst = imagecreatetruecolor($pageW, $pageH);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    imagecopyresampled($dst, $srcImg, 0, 0, $cropX, $cropY, $pageW, $pageH, $cropW, $cropH);
    imagedestroy($srcImg);

    ob_start();
    imagejpeg($dst, null, 88);
    imagedestroy($dst);
    $jpeg = ob_get_clean();

    if (!is_string($jpeg) || $jpeg === '') {
        return null;
    }

    return [
        'src' => 'data:image/jpeg;base64,' . base64_encode($jpeg),
        'width_mm' => 190.0,
        'height_mm' => 250.0,
    ];
}

function pdf_factura_is_pdf(?string $relativePath): bool
{
    if ($relativePath === null || trim($relativePath) === '') {
        return false;
    }

    return strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION)) === 'pdf';
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
