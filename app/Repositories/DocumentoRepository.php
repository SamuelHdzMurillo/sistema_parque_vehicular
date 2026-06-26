<?php

declare(strict_types=1);

namespace App\Repositories;

final class DocumentoRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT d.*, v.numero_economico,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS uploaded_by_nombre
             FROM documentos d
             LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
             LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE d.id = ?',
            [$id]
        );
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO documentos (
                vehiculo_id, user_id, tipo, titulo, numero_documento, fecha_emision,
                fecha_vencimiento, archivo_ruta, archivo_tipo, version, activo, uploaded_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)',
            [
                $data['vehiculo_id'] ?? null,
                $data['user_id'] ?? null,
                $data['tipo'],
                $data['titulo'],
                $data['numero_documento'] ?? null,
                $data['fecha_emision'] ?? null,
                $data['fecha_vencimiento'] ?? null,
                $data['archivo_ruta'],
                $data['archivo_tipo'],
                (int) ($data['version'] ?? 1),
                $data['uploaded_by'] ?? null,
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE documentos SET
                titulo = ?, numero_documento = ?, fecha_emision = ?, fecha_vencimiento = ?
             WHERE id = ? AND activo = 1',
            [
                $data['titulo'],
                $data['numero_documento'] ?? null,
                $data['fecha_emision'] ?? null,
                $data['fecha_vencimiento'] ?? null,
                $id,
            ]
        );
    }

    public function softDelete(int $id): bool
    {
        return $this->execute('UPDATE documentos SET activo = 0 WHERE id = ?', [$id]);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE d.activo = 1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND d.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND d.tipo = ?';
            $params[] = $filters['tipo'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM documentos d {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT d.id, d.tipo, d.titulo, d.numero_documento, d.fecha_vencimiento, d.version,
                    v.numero_economico,
                    DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM documentos d
             LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
             {$where}
             ORDER BY d.fecha_vencimiento ASC, d.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function porVencer(int $dias = 60): array
    {
        return $this->fetchAll(
            'SELECT d.*, v.numero_economico, v.placas,
                    DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM documentos d
             LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
             WHERE d.activo = 1
               AND d.fecha_vencimiento IS NOT NULL
               AND d.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY d.fecha_vencimiento ASC',
            [$dias]
        );
    }

    public function createVersion(int $documentoId, array $data): int
    {
        $actual = $this->findById($documentoId);
        if ($actual === null) {
            throw new \RuntimeException('Documento no encontrado');
        }

        $this->db->beginTransaction();
        try {
            $this->execute('UPDATE documentos SET activo = 0 WHERE id = ?', [$documentoId]);

            $nuevaVersion = (int) $actual['version'] + 1;
            $this->execute(
                'INSERT INTO documentos (
                    vehiculo_id, user_id, tipo, titulo, numero_documento, fecha_emision,
                    fecha_vencimiento, archivo_ruta, archivo_tipo, version, activo, uploaded_by
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)',
                [
                    $actual['vehiculo_id'],
                    $actual['user_id'],
                    $actual['tipo'],
                    $data['titulo'] ?? $actual['titulo'],
                    $data['numero_documento'] ?? $actual['numero_documento'],
                    $data['fecha_emision'] ?? $actual['fecha_emision'],
                    $data['fecha_vencimiento'] ?? $actual['fecha_vencimiento'],
                    $data['archivo_ruta'],
                    $data['archivo_tipo'],
                    $nuevaVersion,
                    $data['uploaded_by'] ?? null,
                ]
            );

            $newId = (int) $this->lastInsertId();
            $this->db->commit();
            return $newId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getHistorialVersiones(int $vehiculoId, string $tipo): array
    {
        return $this->fetchAll(
            'SELECT id, titulo, version, activo, fecha_vencimiento, created_at
             FROM documentos
             WHERE vehiculo_id = ? AND tipo = ?
             ORDER BY version DESC',
            [$vehiculoId, $tipo]
        );
    }

    public function hasDocumentosCriticosVencidos(int $vehiculoId): bool
    {
        return $this->fetchOne(
            'SELECT id FROM documentos
             WHERE vehiculo_id = ? AND activo = 1
               AND tipo IN ("poliza", "tarjeta_circulacion")
               AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE()
             LIMIT 1',
            [$vehiculoId]
        ) !== null;
    }

    /** @return array{tarjeta_circulacion: ?string, verificacion: ?string} */
    public function getVencimientosRevistaTarjeta(int $vehiculoId): array
    {
        $rows = $this->fetchAll(
            'SELECT tipo, fecha_vencimiento
             FROM documentos
             WHERE vehiculo_id = ? AND activo = 1
               AND tipo IN ("tarjeta_circulacion", "verificacion")
               AND fecha_vencimiento IS NOT NULL
             ORDER BY version DESC, id DESC',
            [$vehiculoId]
        );
        $result = ['tarjeta_circulacion' => null, 'verificacion' => null];
        foreach ($rows as $row) {
            $tipo = (string) $row['tipo'];
            if (array_key_exists($tipo, $result) && $result[$tipo] === null) {
                $result[$tipo] = (string) $row['fecha_vencimiento'];
            }
        }
        return $result;
    }
}
