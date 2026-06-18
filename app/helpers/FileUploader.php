<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

final class FileUploader
{
    public static function validateImageUpload(array $file, int $index = 0): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $label = $index > 0 ? ' (imagen ' . ($index + 1) . ')' : '';

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen' . $label . ' excede el tamaño máximo permitido (' . self::formatMaxSize() . ').',
                UPLOAD_ERR_PARTIAL => 'La imagen' . $label . ' se subió de forma incompleta. Intente de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: no hay carpeta temporal para subir la imagen' . $label . '.',
                UPLOAD_ERR_CANT_WRITE => 'Error del servidor: no se pudo guardar la imagen' . $label . '.',
                UPLOAD_ERR_EXTENSION => 'La extensión del servidor bloqueó la imagen' . $label . '.',
                default => 'No se pudo subir la imagen' . $label . ' (código de error ' . $error . ').',
            };
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'La imagen' . $label . ' no es válida o no se recibió correctamente.';
        }

        $allowed = config('app', 'upload.allowed_images');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            return 'La imagen' . $label . ' no es un formato permitido. Use JPG, PNG o WebP.';
        }

        if (($file['size'] ?? 0) > (int) config('app', 'upload.max_size')) {
            return 'La imagen' . $label . ' excede el tamaño máximo permitido (' . self::formatMaxSize() . ').';
        }

        return null;
    }

    public static function uploadImage(array $file, string $subdir): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadError = self::validateImageUpload($file);
        if ($uploadError !== null) {
            throw new RuntimeException($uploadError);
        }

        $allowed = config('app', 'upload.allowed_images');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Tipo de archivo no permitido.');
        }
        if ($file['size'] > (int) config('app', 'upload.max_size')) {
            throw new RuntimeException('Archivo demasiado grande.');
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
        $dir = storage_path('uploads/' . trim($subdir, '/'));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Error al guardar archivo.');
        }
        return trim($subdir, '/') . '/' . $filename;
    }

    public static function uploadDocument(array $file, string $subdir): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $allowed = config('app', 'upload.allowed_docs');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Tipo de documento no permitido.');
        }
        $dir = storage_path('uploads/' . trim($subdir, '/'));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);
        return trim($subdir, '/') . '/' . $filename;
    }

    public static function saveBase64Signature(string $base64, string $subdir): ?string
    {
        if (!str_contains($base64, 'base64,')) {
            return null;
        }

        [$meta, $data] = explode('base64,', $base64, 2);
        $binary = base64_decode($data, true);
        if ($binary === false) {
            return null;
        }

        $dir = storage_path('uploads/' . trim($subdir, '/'));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.jpg';
        $dest = $dir . '/' . $filename;

        if (str_contains(strtolower($meta), 'image/jpeg') || str_contains(strtolower($meta), 'image/jpg')) {
            file_put_contents($dest, $binary);
            return trim($subdir, '/') . '/' . $filename;
        }

        if (extension_loaded('gd')) {
            $image = @imagecreatefromstring($binary);
            if ($image !== false) {
                imagejpeg($image, $dest, 92);
                imagedestroy($image);
                return trim($subdir, '/') . '/' . $filename;
            }
        }

        return null;
    }

    private static function formatMaxSize(): string
    {
        $bytes = (int) config('app', 'upload.max_size');
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1024) . ' KB';
    }
}
