<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

final class FileUploader
{
    public static function uploadImage(array $file, string $subdir): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
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
}
