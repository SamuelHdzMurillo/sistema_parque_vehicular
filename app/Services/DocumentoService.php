<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\BaseRepository;
use App\Services\AuditService;

final class DocumentoService extends BaseRepository
{
    public function __construct(
        private readonly \App\Repositories\CatalogoRepository $catalogos = new \App\Repositories\CatalogoRepository(),
    ) {
        parent::__construct();
    }

    public function paginate(int $page = 1, ?int $vehiculoId = null): array
    {
        return $this->paginateGrouped($page, $vehiculoId);
    }

    public function paginateGrouped(int $page = 1, ?int $vehiculoId = null): array
    {
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE d.activo = 1 AND d.vehiculo_id IS NOT NULL';

        if ($vehiculoId) {
            $where .= ' AND d.vehiculo_id = ?';
            $params[] = $vehiculoId;
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(DISTINCT d.vehiculo_id) AS c FROM documentos d {$where}",
            $params
        )['c'] ?? 0);

        $vehicleParams = array_merge($params, [$perPage, $offset]);
        $vehiculos = $this->fetchAll(
            "SELECT v.id AS vehiculo_id, v.numero_economico, v.placas, v.marca, v.modelo
             FROM vehiculos v
             INNER JOIN documentos d ON d.vehiculo_id = v.id AND d.activo = 1
             {$where}
             GROUP BY v.id, v.numero_economico, v.placas, v.marca, v.modelo
             ORDER BY v.numero_economico ASC
             LIMIT ? OFFSET ?",
            $vehicleParams
        );

        $grupos = [];
        $vehicleIds = array_map(static fn (array $v): int => (int) $v['vehiculo_id'], $vehiculos);

        if ($vehicleIds !== []) {
            $placeholders = implode(',', array_fill(0, count($vehicleIds), '?'));
            $docs = $this->fetchAll(
                "SELECT d.id, d.vehiculo_id, d.tipo, d.titulo, d.numero_documento,
                        d.fecha_vencimiento, d.version, d.archivo_ruta, d.archivo_tipo,
                        DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias_restantes
                 FROM documentos d
                 WHERE d.activo = 1 AND d.vehiculo_id IN ({$placeholders})
                 ORDER BY d.fecha_vencimiento IS NULL, d.fecha_vencimiento ASC, d.titulo ASC",
                $vehicleIds
            );

            $docsByVehicle = [];
            foreach ($docs as $doc) {
                $docsByVehicle[(int) $doc['vehiculo_id']][] = $doc;
            }

            foreach ($vehiculos as $vehiculo) {
                $id = (int) $vehiculo['vehiculo_id'];
                $grupos[] = [
                    'vehiculo_id' => $id,
                    'numero_economico' => $vehiculo['numero_economico'],
                    'placas' => $vehiculo['placas'],
                    'marca' => $vehiculo['marca'],
                    'modelo' => $vehiculo['modelo'],
                    'documentos' => $docsByVehicle[$id] ?? [],
                ];
            }
        }

        $vehiculosFiltro = $this->catalogos->getVehiculosCatalogo();

        return [
            'grupos' => $grupos,
            'vehiculos' => $vehiculosFiltro,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
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
            'vehiculos' => $this->catalogos->getVehiculosCatalogo(),
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
