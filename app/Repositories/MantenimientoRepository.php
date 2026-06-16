<?php

declare(strict_types=1);

namespace App\Repositories;

final class MantenimientoRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT m.*, v.numero_economico, v.placas,
                    p.razon_social AS proveedor_nombre,
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
                folio, vehiculo_id, tipo, fecha, kilometraje, proveedor_id, descripcion, costo,
                factura_ruta, xml_ruta, pdf_ruta, responsable_id, observaciones, estado, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['folio'],
                (int) $data['vehiculo_id'],
                $data['tipo'],
                $data['fecha'],
                (int) $data['kilometraje'],
                $data['proveedor_id'] ?? null,
                $data['descripcion'],
                (float) ($data['costo'] ?? 0),
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
                tipo = ?, fecha = ?, kilometraje = ?, proveedor_id = ?, descripcion = ?, costo = ?,
                factura_ruta = ?, xml_ruta = ?, pdf_ruta = ?, responsable_id = ?,
                observaciones = ?, estado = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['tipo'],
                $data['fecha'],
                (int) $data['kilometraje'],
                $data['proveedor_id'] ?? null,
                $data['descripcion'],
                (float) ($data['costo'] ?? 0),
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
            "SELECT m.id, m.folio, m.tipo, m.fecha, m.costo, m.estado, v.numero_economico, p.razon_social AS proveedor
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

    public function getUltimoPreventivo(int $vehiculoId, string $tipoDescripcion = 'aceite'): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM mantenimientos
             WHERE vehiculo_id = ? AND tipo = "preventivo" AND estado = "finalizado"
               AND descripcion LIKE ?
             ORDER BY fecha DESC LIMIT 1',
            [$vehiculoId, '%' . $tipoDescripcion . '%']
        );
    }

    public function getUltimoFinalizado(int $vehiculoId): ?array
    {
        return $this->fetchOne(
            'SELECT m.id, m.folio, m.tipo, m.fecha, m.kilometraje, m.descripcion, m.costo,
                    p.razon_social AS proveedor_nombre
             FROM mantenimientos m
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             WHERE m.vehiculo_id = ? AND m.estado = "finalizado"
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT 1',
            [$vehiculoId]
        );
    }
}
