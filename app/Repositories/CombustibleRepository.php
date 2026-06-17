<?php

declare(strict_types=1);

namespace App\Repositories;

final class CombustibleRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.*, v.numero_economico, v.placas, p.razon_social AS proveedor_nombre,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS registrado_por_nombre
             FROM combustible_cargas c
             JOIN vehiculos v ON v.id = c.vehiculo_id
             LEFT JOIN proveedores p ON p.id = c.proveedor_id
             JOIN users u ON u.id = c.registrado_por
             WHERE c.id = ?',
            [$id]
        );
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO combustible_cargas (
                vehiculo_id, proveedor_id, fecha, litros, importe, kilometraje,
                folio_ticket, factura_ruta, observaciones, rendimiento, costo_por_km, registrado_por
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $data['vehiculo_id'],
                $data['proveedor_id'] ?? null,
                $data['fecha'],
                (float) $data['litros'],
                (float) $data['importe'],
                (int) $data['kilometraje'],
                $data['folio_ticket'] ?? null,
                $data['factura_ruta'] ?? null,
                $data['observaciones'] ?? null,
                $data['rendimiento'] ?? null,
                $data['costo_por_km'] ?? null,
                (int) $data['registrado_por'],
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE combustible_cargas SET
                proveedor_id = ?, fecha = ?, litros = ?, importe = ?, kilometraje = ?,
                folio_ticket = ?, factura_ruta = ?, observaciones = ?,
                rendimiento = ?, costo_por_km = ?
             WHERE id = ?',
            [
                $data['proveedor_id'] ?? null,
                $data['fecha'],
                (float) $data['litros'],
                (float) $data['importe'],
                (int) $data['kilometraje'],
                $data['folio_ticket'] ?? null,
                $data['factura_ruta'] ?? null,
                $data['observaciones'] ?? null,
                $data['rendimiento'] ?? null,
                $data['costo_por_km'] ?? null,
                $id,
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM combustible_cargas WHERE id = ?', [$id]);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND c.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where .= ' AND c.fecha >= ?';
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where .= ' AND c.fecha <= ?';
            $params[] = $filters['fecha_hasta'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM combustible_cargas c {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT c.id, c.fecha, c.litros, c.importe, c.kilometraje, c.rendimiento, c.costo_por_km,
                    c.folio_ticket, c.factura_ruta,
                    v.numero_economico
             FROM combustible_cargas c
             JOIN vehiculos v ON v.id = c.vehiculo_id
             {$where}
             ORDER BY c.fecha DESC, c.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function getAnteriorCarga(int $vehiculoId, int $beforeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM combustible_cargas
             WHERE vehiculo_id = ? AND id < ?
             ORDER BY fecha DESC, id DESC LIMIT 1',
            [$vehiculoId, $beforeId]
        );
    }

    public function getUltimaCarga(int $vehiculoId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM combustible_cargas WHERE vehiculo_id = ? ORDER BY fecha DESC, id DESC LIMIT 1',
            [$vehiculoId]
        );
    }

    public function calcularRendimiento(int $vehiculoId, int $kilometrajeActual, float $litros, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT kilometraje FROM combustible_cargas WHERE vehiculo_id = ?';
        $params = [$vehiculoId];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY fecha DESC, id DESC LIMIT 1';

        $anterior = $this->fetchOne($sql, $params);
        if ($anterior === null || $litros <= 0) {
            return null;
        }

        $kmAnterior = (int) $anterior['kilometraje'];
        if ($kilometrajeActual <= $kmAnterior) {
            return null;
        }

        $kmRecorridos = $kilometrajeActual - $kmAnterior;
        $rendimiento = round($kmRecorridos / $litros, 2);
        $costoPorKm = null;

        return [
            'km_recorridos' => $kmRecorridos,
            'rendimiento' => $rendimiento,
            'costo_por_km' => $costoPorKm,
        ];
    }

    public function statsByVehiculo(int $vehiculoId, ?string $desde = null, ?string $hasta = null): array
    {
        $params = [$vehiculoId];
        $where = 'WHERE vehiculo_id = ?';

        if ($desde !== null) {
            $where .= ' AND fecha >= ?';
            $params[] = $desde;
        }
        if ($hasta !== null) {
            $where .= ' AND fecha <= ?';
            $params[] = $hasta;
        }

        $stats = $this->fetchOne(
            "SELECT COUNT(*) AS total_cargas,
                    COALESCE(SUM(litros), 0) AS total_litros,
                    COALESCE(SUM(importe), 0) AS total_importe,
                    COALESCE(AVG(rendimiento), 0) AS rendimiento_promedio,
                    COALESCE(AVG(costo_por_km), 0) AS costo_promedio_km
             FROM combustible_cargas {$where}",
            $params
        );

        $mensual = $this->fetchAll(
            "SELECT DATE_FORMAT(fecha, '%Y-%m') AS periodo,
                    SUM(litros) AS litros, SUM(importe) AS importe
             FROM combustible_cargas {$where}
             GROUP BY DATE_FORMAT(fecha, '%Y-%m')
             ORDER BY periodo DESC
             LIMIT 12",
            $params
        );

        return [
            'resumen' => $stats ?? [],
            'mensual' => $mensual,
        ];
    }

    public function getPromedioRendimiento(int $vehiculoId, int $limit = 10): ?float
    {
        $row = $this->fetchOne(
            'SELECT AVG(rendimiento) AS promedio FROM (
                SELECT rendimiento FROM combustible_cargas
                WHERE vehiculo_id = ? AND rendimiento IS NOT NULL
                ORDER BY fecha DESC LIMIT ?
             ) sub',
            [$vehiculoId, $limit]
        );
        return $row !== null && $row['promedio'] !== null ? (float) $row['promedio'] : null;
    }
}
