<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\BaseRepository;
use App\Services\AuditService;

final class DocumentoService extends BaseRepository
{
    public function paginate(int $page = 1, ?int $vehiculoId = null): array
    {
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE d.activo = 1';
        if ($vehiculoId) {
            $where .= ' AND d.vehiculo_id = ?';
            $params[] = $vehiculoId;
        }
        $total = (int) ($this->fetchOne("SELECT COUNT(*) AS c FROM documentos d {$where}", $params)['c'] ?? 0);
        $params[] = $perPage;
        $params[] = $offset;
        $rows = $this->fetchAll(
            "SELECT d.*, v.numero_economico, v.placas
             FROM documentos d LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
             {$where} ORDER BY d.fecha_vencimiento ASC LIMIT ? OFFSET ?",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT d.*, v.numero_economico FROM documentos d LEFT JOIN vehiculos v ON v.id = d.vehiculo_id WHERE d.id = ? AND d.activo = 1',
            [$id]
        );
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->fetchAll(
                'SELECT id, numero_economico, placas FROM vehiculos WHERE deleted_at IS NULL ORDER BY numero_economico'
            ),
        ];
    }

    public function create(array $data, array $file, int $userId): int
    {
        $ruta = FileUploader::uploadDocument($file, 'documentos');
        if ($ruta === null) {
            throw new \RuntimeException('No se pudo subir el archivo.');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, storage_path('uploads/' . $ruta));
        finfo_close($finfo);
        $this->execute(
            'INSERT INTO documentos (vehiculo_id, tipo, titulo, numero_documento, fecha_emision, fecha_vencimiento, archivo_ruta, archivo_tipo, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['vehiculo_id'] ? (int) $data['vehiculo_id'] : null, $data['tipo'], $data['titulo'],
                $data['numero_documento'] ?? null, $data['fecha_emision'] ?? null, $data['fecha_vencimiento'] ?? null,
                $ruta, $mime ?: 'application/octet-stream', $userId,
            ]
        );
        $id = (int) $this->lastInsertId();
        AuditService::log('INSERT', 'documentos', $id, null, $data);
        return $id;
    }

    public function getDownloadPath(int $id): ?array
    {
        $doc = $this->find($id);
        if ($doc === null) {
            return null;
        }
        $path = storage_path('uploads/' . $doc['archivo_ruta']);
        if (!is_file($path)) {
            return null;
        }
        return [
            'path' => $path,
            'filename' => basename($doc['archivo_ruta']),
            'content_type' => $doc['archivo_tipo'],
        ];
    }
}
