<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProveedorRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM proveedores WHERE id = ?', [$id]);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['q'])) {
            $where .= ' AND (razon_social LIKE ? OR rfc LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND tipo = ?';
            $params[] = $filters['tipo'];
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $where .= ' AND activo = ?';
            $params[] = (int) $filters['activo'];
        }

        $total = (int) ($this->fetchOne("SELECT COUNT(*) AS c FROM proveedores {$where}", $params)['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT id, razon_social, rfc, telefono, email, tipo, activo
             FROM proveedores {$where}
             ORDER BY razon_social ASC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO proveedores (razon_social, rfc, telefono, email, direccion, tipo, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['razon_social'],
                $this->nullable($data['rfc'] ?? null),
                $this->nullable($data['telefono'] ?? null),
                $this->nullable($data['email'] ?? null),
                $this->nullable($data['direccion'] ?? null),
                $data['tipo'] ?? 'ambos',
                (int) ($data['activo'] ?? 1),
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE proveedores SET razon_social = ?, rfc = ?, telefono = ?, email = ?, direccion = ?, tipo = ?, activo = ?
             WHERE id = ?',
            [
                $data['razon_social'],
                $this->nullable($data['rfc'] ?? null),
                $this->nullable($data['telefono'] ?? null),
                $this->nullable($data['email'] ?? null),
                $this->nullable($data['direccion'] ?? null),
                $data['tipo'] ?? 'ambos',
                (int) ($data['activo'] ?? 1),
                $id,
            ]
        );
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->execute('UPDATE proveedores SET activo = ? WHERE id = ?', [$activo ? 1 : 0, $id]);
    }

    private function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
