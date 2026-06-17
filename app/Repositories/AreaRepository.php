<?php

declare(strict_types=1);

namespace App\Repositories;

final class AreaRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT a.*, p.clave AS plantel_clave, p.nombre AS plantel_nombre
             FROM areas a
             LEFT JOIN planteles p ON p.id = a.plantel_id
             WHERE a.id = ?',
            [$id]
        );
    }

    public function findByClave(string $clave, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM areas WHERE clave = ?';
        $params = [$clave];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        return $this->fetchOne($sql, $params);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['q'])) {
            $where .= ' AND (a.clave LIKE ? OR a.nombre LIKE ? OR p.clave LIKE ? OR p.nombre LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if (!empty($filters['plantel_id'])) {
            $where .= ' AND a.plantel_id = ?';
            $params[] = (int) $filters['plantel_id'];
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $where .= ' AND a.activo = ?';
            $params[] = (int) $filters['activo'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM areas a LEFT JOIN planteles p ON p.id = a.plantel_id {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT a.id, a.clave, a.nombre, a.plantel_id, a.activo,
                    p.clave AS plantel_clave, p.nombre AS plantel_nombre,
                    CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(' - ', p.clave), '')) AS label
             FROM areas a
             LEFT JOIN planteles p ON p.id = a.plantel_id
             {$where}
             ORDER BY p.clave ASC, a.nombre ASC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO areas (clave, nombre, plantel_id, activo) VALUES (?, ?, ?, ?)',
            [
                $data['clave'],
                $data['nombre'],
                (int) $data['plantel_id'],
                (int) ($data['activo'] ?? 1),
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE areas SET clave = ?, nombre = ?, plantel_id = ?, activo = ? WHERE id = ?',
            [
                $data['clave'],
                $data['nombre'],
                (int) $data['plantel_id'],
                (int) ($data['activo'] ?? 1),
                $id,
            ]
        );
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->execute('UPDATE areas SET activo = ? WHERE id = ?', [$activo ? 1 : 0, $id]);
    }

    public function countConductores(int $id): int
    {
        return (int) ($this->fetchOne('SELECT COUNT(*) AS c FROM conductores WHERE area_id = ?', [$id])['c'] ?? 0);
    }
}
