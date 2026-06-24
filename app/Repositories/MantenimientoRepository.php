<?php

declare(strict_types=1);

namespace App\Repositories;

final class MantenimientoRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
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
        return $this->execute(
            'DELETE FROM mantenimientos WHERE id = ? AND estado IN ("pendiente", "cancelado")',
            [$id]
        );
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

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function generateFolio(): string
    {
        $year = date('Y');
        $prefix = "MNT-{$year}-";
        $last = $this->fetchOne(
            'SELECT folio FROM mantenimientos WHERE folio LIKE ? ORDER BY id DESC LIMIT 1',
            ["{$prefix}%"]
        );
        $seq = 1;
        if ($last !== null && preg_match('/(\d+)$/', $last['folio'], $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
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
        $params = [$vehiculoId, $servicio];
        $where = 'vehiculo_id = ? AND estado = "finalizado" AND es_historico = 0 AND (servicio = ?';

        if ($keyword !== null) {
            $where .= ' OR descripcion LIKE ?';
            $params[] = '%' . $keyword . '%';
        }

        $where .= ')';

        return $this->fetchOne(
            "SELECT * FROM mantenimientos WHERE {$where} ORDER BY fecha DESC, id DESC LIMIT 1",
            $params
        );
    }

    public function findAbiertoPorServicio(int $vehiculoId, string $servicio): ?array
    {
        $keyword = $this->legacyBusquedaDescripcion($servicio);
        $params = [$vehiculoId, $servicio];
        $where = 'vehiculo_id = ? AND estado NOT IN ("finalizado", "cancelado") AND (servicio = ?';

        if ($keyword !== null) {
            $where .= ' OR (servicio IS NULL AND descripcion LIKE ?)';
            $params[] = '%' . $keyword . '%';
        }

        $where .= ')';

        return $this->fetchOne(
            "SELECT id, folio, estado, servicio FROM mantenimientos WHERE {$where} ORDER BY id DESC LIMIT 1",
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
