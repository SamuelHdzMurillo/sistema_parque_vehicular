<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class StorageController
{
    public function serve(Request $request): never
    {
        if (auth_id() === null) {
            http_response_code(403);
            exit('Acceso denegado');
        }

        $relative = ltrim(substr($request->uri(), strlen('/storage/uploads/')), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            http_response_code(404);
            exit('Archivo no encontrado');
        }

        $base = realpath(storage_path('uploads'));
        if ($base === false) {
            http_response_code(404);
            exit('Archivo no encontrado');
        }

        $full = storage_path('uploads/' . $relative);
        $real = realpath($full);
        $basePrefix = $base . DIRECTORY_SEPARATOR;
        if ($real === false || !str_starts_with($real, $basePrefix)) {
            http_response_code(404);
            exit('Archivo no encontrado');
        }

        if (!is_file($real)) {
            http_response_code(404);
            exit('Archivo no encontrado');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? finfo_file($finfo, $real) : false;
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($real));
        header('Cache-Control: private, max-age=3600');
        readfile($real);
        exit;
    }
}
