<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConductorRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.*, a.nombre AS area_nombre, p.clave AS plantel_clave,
                    CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(" - ", p.clave), "")) AS area_label
             FROM conductores c
             JOIN areas a ON a.id = c.area_id
             LEFT JOIN planteles p ON p.id = a.plantel_id
             WHERE c.id = ?',
            [$id]
        );
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['q'])) {
            $where .= ' AND (c.nombre LIKE ? OR c.telefono LIKE ? OR a.nombre LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }
        if (!empty($filters['area_id'])) {
            $where .= ' AND c.area_id = ?';
            $params[] = (int) $filters['area_id'];
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $where .= ' AND c.activo = ?';
            $params[] = (int) $filters['activo'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c
             FROM conductores c
             JOIN areas a ON a.id = c.area_id
             {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT c.id, c.nombre, c.telefono, c.area_id, c.activo,
                    a.nombre AS area_nombre, p.clave AS plantel_clave,
                    CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(' - ', p.clave), '')) AS area_label
             FROM conductores c
             JOIN areas a ON a.id = c.area_id
             LEFT JOIN planteles p ON p.id = a.plantel_id
             {$where}
             ORDER BY c.nombre ASC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO conductores (nombre, area_id, telefono, activo) VALUES (?, ?, ?, ?)',
            [
                $data['nombre'],
                (int) $data['area_id'],
                $data['telefono'],
                (int) ($data['activo'] ?? 1),
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE conductores SET nombre = ?, area_id = ?, telefono = ?, activo = ? WHERE id = ?',
            [
                $data['nombre'],
                (int) $data['area_id'],
                $data['telefono'],
                (int) ($data['activo'] ?? 1),
                $id,
            ]
        );
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->execute('UPDATE conductores SET activo = ? WHERE id = ?', [$activo ? 1 : 0, $id]);
    }
}
