<?php

declare(strict_types=1);

namespace App\Repositories;

final class InspeccionRepository extends BaseRepository
{
    public const INSPECCION_ITEMS = [
        ['codigo' => 'aceite', 'nombre' => 'Aceite'],
        ['codigo' => 'anticongelante', 'nombre' => 'Anticongelante'],
        ['codigo' => 'frenos', 'nombre' => 'Frenos'],
        ['codigo' => 'direccion_hidraulica', 'nombre' => 'Dirección hidráulica'],
        ['codigo' => 'bateria', 'nombre' => 'Batería'],
        ['codigo' => 'luces', 'nombre' => 'Luces'],
        ['codigo' => 'direccionales', 'nombre' => 'Direccionales'],
        ['codigo' => 'llantas', 'nombre' => 'Llantas'],
        ['codigo' => 'suspension', 'nombre' => 'Suspensión'],
        ['codigo' => 'herramientas', 'nombre' => 'Herramientas'],
        ['codigo' => 'equipo_emergencia', 'nombre' => 'Equipo de emergencia'],
    ];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT i.*, v.numero_economico, CONCAT(u.nombre, " ", u.apellido_paterno) AS responsable_nombre
             FROM inspecciones i
             JOIN vehiculos v ON v.id = i.vehiculo_id
             JOIN users u ON u.id = i.responsable_id
             WHERE i.id = ?',
            [$id]
        );
    }

    public function findWithItems(int $id): ?array
    {
        $inspeccion = $this->findById($id);
        if ($inspeccion === null) {
            return null;
        }

        $inspeccion['items'] = $this->fetchAll(
            'SELECT * FROM inspeccion_items WHERE inspeccion_id = ? ORDER BY id ASC',
            [$id]
        );
        $inspeccion['fotos'] = $this->fetchAll(
            'SELECT * FROM inspeccion_fotos WHERE inspeccion_id = ? ORDER BY id ASC',
            [$id]
        );

        return $inspeccion;
    }

    public function createWithItems(array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $this->execute(
                'INSERT INTO inspecciones (
                    vehiculo_id, responsable_id, kilometraje, fecha, observaciones_generales,
                    firma_digital, resultado_general
                 ) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    (int) $data['vehiculo_id'],
                    (int) $data['responsable_id'],
                    (int) $data['kilometraje'],
                    $data['fecha'],
                    $data['observaciones_generales'] ?? null,
                    $data['firma_digital'] ?? null,
                    $data['resultado_general'] ?? 'aprobada',
                ]
            );

            $inspeccionId = (int) $this->lastInsertId();

            foreach ($items as $item) {
                $this->execute(
                    'INSERT INTO inspeccion_items (inspeccion_id, item_codigo, item_nombre, calificacion, observaciones)
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        $inspeccionId,
                        $item['item_codigo'],
                        $item['item_nombre'],
                        $item['calificacion'],
                        $item['observaciones'] ?? null,
                    ]
                );
            }

            $this->db->commit();
            return $inspeccionId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND i.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['resultado'])) {
            $where .= ' AND i.resultado_general = ?';
            $params[] = $filters['resultado'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where .= ' AND i.fecha >= ?';
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where .= ' AND i.fecha <= ?';
            $params[] = $filters['fecha_hasta'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM inspecciones i {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT i.id, i.fecha, i.kilometraje, i.resultado_general,
                    v.numero_economico,
                    CONCAT(u.nombre, ' ', u.apellido_paterno) AS responsable_nombre,
                    (SELECT COUNT(*) FROM inspeccion_items ii WHERE ii.inspeccion_id = i.id AND ii.calificacion = 'malo') AS items_malo
             FROM inspecciones i
             JOIN vehiculos v ON v.id = i.vehiculo_id
             JOIN users u ON u.id = i.responsable_id
             {$where}
             ORDER BY i.fecha DESC, i.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function countConsecutiveRegular(int $vehiculoId, string $itemCodigo, int $limit = 2): int
    {
        $rows = $this->fetchAll(
            'SELECT ii.calificacion
             FROM inspecciones i
             JOIN inspeccion_items ii ON ii.inspeccion_id = i.id
             WHERE i.vehiculo_id = ? AND ii.item_codigo = ?
             ORDER BY i.fecha DESC, i.id DESC
             LIMIT ?',
            [$vehiculoId, $itemCodigo, $limit]
        );

        $count = 0;
        foreach ($rows as $row) {
            if ($row['calificacion'] === 'regular') {
                $count++;
            } else {
                break;
            }
        }

        return $count;
    }

    public function getItemsMalos(int $inspeccionId): array
    {
        return $this->fetchAll(
            'SELECT * FROM inspeccion_items WHERE inspeccion_id = ? AND calificacion = "malo"',
            [$inspeccionId]
        );
    }
}
