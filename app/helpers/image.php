<?php

declare(strict_types=1);

/**
 * Data URI JPEG para PDFs (Dompdf procesa JPEG sin extensión GD).
 */
function image_to_jpeg_data_uri(string $full): string
{
    if (!is_file($full)) {
        return '';
    }

    $mime = mime_content_type($full) ?: '';

    if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
        return jpeg_file_data_uri($full);
    }

    if (extension_loaded('gd')) {
        $image = image_from_file($full, $mime);
        if ($image !== false) {
            $image = flatten_image_on_white($image);
            ob_start();
            imagejpeg($image, null, 92);
            imagedestroy($image);
            $jpeg = ob_get_clean();

            if (is_string($jpeg) && $jpeg !== '') {
                return 'data:image/jpeg;base64,' . base64_encode($jpeg);
            }
        }
    }

    $jpegPath = preg_replace('/\.(png|webp|gif)$/i', '.jpg', $full);
    if ($jpegPath !== null && $jpegPath !== $full && is_file($jpegPath)) {
        return jpeg_file_data_uri($jpegPath);
    }

    return '';
}

function jpeg_file_data_uri(string $full): string
{
    if (!is_file($full)) {
        return '';
    }

    return 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($full));
}

function image_from_file(string $full, string $mime = ''): \GdImage|false
{
    if ($mime === '') {
        $mime = mime_content_type($full) ?: '';
    }

    $image = match (true) {
        str_contains($mime, 'png') => @imagecreatefrompng($full),
        str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => @imagecreatefromjpeg($full),
        str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : false,
        default => false,
    };

    if ($image !== false) {
        return $image;
    }

    if (function_exists('imagecreatefromwebp')) {
        $image = @imagecreatefromwebp($full);
        if ($image !== false) {
            return $image;
        }
    }

    return @imagecreatefrompng($full) ?: @imagecreatefromjpeg($full) ?: false;
}

/**
 * Logo para la interfaz web (PNG/JPEG estático local).
 */
function brand_logo_web_src(): string
{
    foreach ([
        'images/logo_Cecyte_vertical_sin_fondo.png',
        'images/logo_Cecyte_vertical_sin_fondo.jpg',
    ] as $candidate) {
        $path = public_path('assets/' . $candidate);
        if (is_file($path)) {
            return asset($candidate);
        }
    }

    return asset('images/logo_Cecyte_vertical_sin_fondo.png');
}

function flatten_image_on_white(\GdImage $image): \GdImage
{
    $w = imagesx($image);
    $h = imagesy($image);
    $flat = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($flat, 255, 255, 255);
    imagefill($flat, 0, 0, $white);
    imagecopy($flat, $image, 0, 0, 0, 0, $w, $h);
    imagedestroy($image);

    return $flat;
}

function image_save_as_jpeg(string $src, string $dest, int $quality = 92): bool
{
    if (!is_file($src) || !extension_loaded('gd')) {
        return false;
    }

    $mime = mime_content_type($src) ?: '';
    $image = image_from_file($src, $mime);
    if ($image === false) {
        return false;
    }

    $image = flatten_image_on_white($image);
    $ok = imagejpeg($image, $dest, $quality);
    imagedestroy($image);

    return $ok;
}
