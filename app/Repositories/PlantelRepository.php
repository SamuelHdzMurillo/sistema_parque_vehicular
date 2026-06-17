<?php

declare(strict_types=1);

namespace App\Repositories;

final class PlantelRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM planteles WHERE id = ?', [$id]);
    }

    public function findByClave(string $clave, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM planteles WHERE clave = ?';
        $params = [$clave];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        return $this->fetchOne($sql, $params);
    }

    public function listForSelect(bool $soloActivos = true): array
    {
        $sql = 'SELECT id, clave, nombre FROM planteles';
        if ($soloActivos) {
            $sql .= ' WHERE activo = 1';
        }
        $sql .= ' ORDER BY clave ASC';
        return $this->fetchAll($sql);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['q'])) {
            $where .= ' AND (clave LIKE ? OR nombre LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term);
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $where .= ' AND activo = ?';
            $params[] = (int) $filters['activo'];
        }

        $total = (int) ($this->fetchOne("SELECT COUNT(*) AS c FROM planteles {$where}", $params)['c'] ?? 0);
        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT id, clave, nombre, activo, created_at
             FROM planteles {$where}
             ORDER BY clave ASC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO planteles (clave, nombre, activo) VALUES (?, ?, ?)',
            [$data['clave'], $data['nombre'], (int) ($data['activo'] ?? 1)]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE planteles SET clave = ?, nombre = ?, activo = ? WHERE id = ?',
            [$data['clave'], $data['nombre'], (int) ($data['activo'] ?? 1), $id]
        );
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->execute('UPDATE planteles SET activo = ? WHERE id = ?', [$activo ? 1 : 0, $id]);
    }

    public function countAreas(int $id): int
    {
        return (int) ($this->fetchOne('SELECT COUNT(*) AS c FROM areas WHERE plantel_id = ?', [$id])['c'] ?? 0);
    }
}
