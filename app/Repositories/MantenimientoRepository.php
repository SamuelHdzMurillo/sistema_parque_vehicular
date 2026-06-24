<?php

declare(strict_types=1);

namespace App\Repositories;

final class MantenimientoRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        $row = $this->fetchOne(
            'SELECT m.*, v.numero_economico, v.placas, v.kilometraje_actual,
                    p.razon_social AS proveedor_nombre,
                    p.rfc AS proveedor_rfc,
                    p.telefono AS proveedor_telefono,
                    p.email AS proveedor_email,
                    p.direccion AS proveedor_direccion,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS responsable_nombre,
                    CONCAT(au.nombre, " ", au.apellido_paterno) AS autorizado_por_nombre
             FROM mantenimientos m
             JOIN vehiculos v ON v.id = m.vehiculo_id
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             JOIN users u ON u.id = m.responsable_id
             LEFT JOIN users au ON au.id = m.autorizado_por
             WHERE m.id = ?',
            [$id]
        );

        if ($row === null) {
            return null;
        }

        $row['servicios'] = $this->getServicios($id);

        return $row;
    }

    /** @return list<string> */
    public function getServicios(int $mantenimientoId): array
    {
        $rows = $this->fetchAll(
            'SELECT servicio FROM mantenimiento_servicios WHERE mantenimiento_id = ? ORDER BY servicio ASC',
            [$mantenimientoId]
        );

        if ($rows !== []) {
            return array_column($rows, 'servicio');
        }

        $legacy = $this->fetchOne(
            'SELECT servicio FROM mantenimientos WHERE id = ? AND servicio IS NOT NULL AND servicio != ""',
            [$mantenimientoId]
        );

        return $legacy !== null && !empty($legacy['servicio'])
            ? [(string) $legacy['servicio']]
            : [];
    }

    /** @param list<string> $servicios */
    public function syncServicios(int $mantenimientoId, array $servicios): void
    {
        $this->execute('DELETE FROM mantenimiento_servicios WHERE mantenimiento_id = ?', [$mantenimientoId]);

        foreach ($servicios as $servicio) {
            $servicio = trim((string) $servicio);
            if ($servicio === '') {
                continue;
            }
            $this->execute(
                'INSERT IGNORE INTO mantenimiento_servicios (mantenimiento_id, servicio) VALUES (?, ?)',
                [$mantenimientoId, $servicio]
            );
        }
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<string>>
     */
    public function getServiciosByMantenimientoIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->fetchAll(
            "SELECT mantenimiento_id, servicio FROM mantenimiento_servicios
             WHERE mantenimiento_id IN ({$placeholders})
             ORDER BY mantenimiento_id ASC, servicio ASC",
            $ids
        );

        $map = [];
        foreach ($rows as $row) {
            $mid = (int) $row['mantenimiento_id'];
            $map[$mid][] = (string) $row['servicio'];
        }

        return $map;
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO mantenimientos (
                folio, vehiculo_id, tipo, servicio, fecha, kilometraje, es_historico, proveedor_id, descripcion, costo,
                factura_folio, factura_uuid, factura_fecha, factura_subtotal, factura_iva, factura_total,
                factura_ruta, xml_ruta, pdf_ruta, responsable_id, observaciones, estado, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['folio'],
                (int) $data['vehiculo_id'],
                $data['tipo'],
                $this->nullableStr($data['servicio'] ?? null),
                $data['fecha'],
                (int) $data['kilometraje'],
                !empty($data['es_historico']) ? 1 : 0,
                $this->nullableInt($data['proveedor_id'] ?? null),
                $data['descripcion'],
                (float) ($data['costo'] ?? 0),
                $this->nullableStr($data['factura_folio'] ?? null),
                $this->nullableStr($data['factura_uuid'] ?? null),
                $this->nullableStr($data['factura_fecha'] ?? null),
                $this->nullableDecimal($data['factura_subtotal'] ?? null),
                $this->nullableDecimal($data['factura_iva'] ?? null),
                $this->nullableDecimal($data['factura_total'] ?? null),
                $data['factura_ruta'] ?? null,
                $data['xml_ruta'] ?? null,
                $data['pdf_ruta'] ?? null,
                (int) $data['responsable_id'],
                $data['observaciones'] ?? null,
                $data['estado'] ?? 'pendiente',
                $data['created_by'] ?? null,
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE mantenimientos SET
                tipo = ?, servicio = ?, fecha = ?, kilometraje = ?, es_historico = ?, proveedor_id = ?, descripcion = ?, costo = ?,
                factura_folio = ?, factura_uuid = ?, factura_fecha = ?,
                factura_subtotal = ?, factura_iva = ?, factura_total = ?,
                factura_ruta = ?, xml_ruta = ?, pdf_ruta = ?, responsable_id = ?,
                observaciones = ?, estado = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['tipo'],
                $this->nullableStr($data['servicio'] ?? null),
                $data['fecha'],
                (int) $data['kilometraje'],
                !empty($data['es_historico']) ? 1 : 0,
                $this->nullableInt($data['proveedor_id'] ?? null),
                $data['descripcion'],
                (float) ($data['costo'] ?? 0),
                $this->nullableStr($data['factura_folio'] ?? null),
                $this->nullableStr($data['factura_uuid'] ?? null),
                $this->nullableStr($data['factura_fecha'] ?? null),
                $this->nullableDecimal($data['factura_subtotal'] ?? null),
                $this->nullableDecimal($data['factura_iva'] ?? null),
                $this->nullableDecimal($data['factura_total'] ?? null),
                $data['factura_ruta'] ?? null,
                $data['xml_ruta'] ?? null,
                $data['pdf_ruta'] ?? null,
                (int) $data['responsable_id'],
                $data['observaciones'] ?? null,
                $data['estado'],
                $id,
            ]
        );
    }

    private function nullableStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (int) $value === 0) {
            return null;
        }
        return (int) $value;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM mantenimientos WHERE id = ?', [$id]);
    }

    public function getMaxKmOperativoExcluding(int $vehiculoId, int $excludeMantenimientoId): int
    {
        $params = [$vehiculoId, $excludeMantenimientoId];
        $row = $this->fetchOne(
            'SELECT GREATEST(
                COALESCE((
                    SELECT MAX(kilometraje) FROM mantenimientos
                    WHERE vehiculo_id = ? AND id != ? AND estado = "finalizado" AND es_historico = 0
                ), 0),
                COALESCE((
                    SELECT MAX(km_regreso) FROM comisiones
                    WHERE vehiculo_id = ? AND estado = "finalizada" AND km_regreso IS NOT NULL
                ), 0),
                COALESCE((
                    SELECT MAX(kilometraje) FROM combustible_cargas WHERE vehiculo_id = ?
                ), 0),
                COALESCE((
                    SELECT MAX(kilometraje) FROM inspecciones WHERE vehiculo_id = ?
                ), 0)
            ) AS km_max',
            array_merge($params, [$vehiculoId, $vehiculoId, $vehiculoId])
        );

        return (int) ($row['km_max'] ?? 0);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND m.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND m.tipo = ?';
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['estado'])) {
            $where .= ' AND m.estado = ?';
            $params[] = $filters['estado'];
        }
        if (!empty($filters['proveedor_id'])) {
            $where .= ' AND m.proveedor_id = ?';
            $params[] = (int) $filters['proveedor_id'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM mantenimientos m {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT m.id, m.folio, m.tipo, m.servicio, m.fecha, m.costo, m.estado, v.numero_economico, p.razon_social AS proveedor
             FROM mantenimientos m
             JOIN vehiculos v ON v.id = m.vehiculo_id
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             {$where}
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $serviciosMap = $this->getServiciosByMantenimientoIds($ids);
        foreach ($rows as &$row) {
            $mid = (int) $row['id'];
            $row['servicios'] = $serviciosMap[$mid] ?? (
                !empty($row['servicio']) ? [(string) $row['servicio']] : []
            );
        }
        unset($row);

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function generateFolio(): string
    {
        $year = date('Y');
        $prefix = "MNT-{$year}-";
        $rows = $this->fetchAll(
            'SELECT folio FROM mantenimientos WHERE folio LIKE ?',
            ["{$prefix}%"]
        );
        $maxSeq = 0;
        foreach ($rows as $row) {
            if (preg_match('/(\d+)$/', (string) $row['folio'], $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }

        return $prefix . str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);
    }

    public function authorize(int $id, int $autorizadoPor): bool
    {
        return $this->execute(
            'UPDATE mantenimientos SET estado = "autorizado", autorizado_por = ?, updated_at = NOW()
             WHERE id = ? AND estado IN ("pendiente", "programado")',
            [$autorizadoPor, $id]
        );
    }

    public function getUltimoPreventivo(int $vehiculoId, string $servicio): ?array
    {
        return $this->getUltimoPorServicio($vehiculoId, $servicio);
    }

    public function getUltimoPorServicio(int $vehiculoId, string $servicio): ?array
    {
        $keyword = $this->legacyBusquedaDescripcion($servicio);
        $params = [$vehiculoId, $servicio, $servicio];
        $match = '(ms.servicio = ? OR m.servicio = ?';

        if ($keyword !== null) {
            $match .= ' OR m.descripcion LIKE ?';
            $params[] = '%' . $keyword . '%';
        }

        $match .= ')';

        return $this->fetchOne(
            "SELECT m.* FROM mantenimientos m
             LEFT JOIN mantenimiento_servicios ms ON ms.mantenimiento_id = m.id
             WHERE m.vehiculo_id = ? AND m.estado = 'finalizado' AND {$match}
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT 1",
            $params
        );
    }

    public function findAbiertoPorServicio(int $vehiculoId, string $servicio): ?array
    {
        $keyword = $this->legacyBusquedaDescripcion($servicio);
        $params = [$vehiculoId, $servicio, $servicio];
        $match = '(ms.servicio = ? OR m.servicio = ?';

        if ($keyword !== null) {
            $match .= ' OR (m.servicio IS NULL AND m.descripcion LIKE ?)';
            $params[] = '%' . $keyword . '%';
        }

        $match .= ')';

        return $this->fetchOne(
            "SELECT m.id, m.folio, m.estado, m.servicio FROM mantenimientos m
             LEFT JOIN mantenimiento_servicios ms ON ms.mantenimiento_id = m.id
             WHERE m.vehiculo_id = ? AND m.estado NOT IN ('finalizado', 'cancelado') AND {$match}
             ORDER BY m.id DESC
             LIMIT 1",
            $params
        );
    }

    private function legacyBusquedaDescripcion(string $servicio): ?string
    {
        return match ($servicio) {
            'cambio_aceite' => 'aceite',
            'afinacion' => 'afinaci',
            'llantas' => 'llanta',
            default => str_contains($servicio, '_') ? null : $servicio,
        };
    }

    public function getUltimoFinalizado(int $vehiculoId): ?array
    {
        return $this->fetchOne(
            'SELECT m.id, m.folio, m.tipo, m.fecha, m.kilometraje, m.descripcion, m.costo,
                    p.razon_social AS proveedor_nombre
             FROM mantenimientos m
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             WHERE m.vehiculo_id = ? AND m.estado = "finalizado" AND m.es_historico = 0
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT 1',
            [$vehiculoId]
        );
    }
}
