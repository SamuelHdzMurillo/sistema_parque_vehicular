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
