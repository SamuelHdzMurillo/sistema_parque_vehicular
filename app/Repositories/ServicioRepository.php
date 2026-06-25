<?php

declare(strict_types=1);

namespace App\Repositories;

final class ServicioRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM alerta_config WHERE id = ? AND unidad = "km"',
            [$id]
        );
    }

    public function findByTipo(string $tipo, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM alerta_config WHERE tipo = ? AND unidad = "km"';
        $params = [$tipo];
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
        $where = 'WHERE unidad = "km"';

        if (!empty($filters['q'])) {
            $where .= ' AND (tipo LIKE ? OR nombre LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term);
        }
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $where .= ' AND activo = ?';
            $params[] = (int) $filters['activo'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM alerta_config {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT id, tipo, nombre, activo
             FROM alerta_config
             {$where}
             ORDER BY nombre ASC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO alerta_config (
                tipo, nombre, umbral_verde, umbral_amarillo, umbral_rojo, unidad,
                umbral_verde_dias, umbral_amarillo_dias, umbral_rojo_dias, activo
             ) VALUES (?, ?, 0, 0, 0, "km", NULL, NULL, NULL, ?)',
            [
                $data['tipo'],
                $data['nombre'],
                (int) ($data['activo'] ?? 1),
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE alerta_config SET nombre = ?, activo = ? WHERE id = ? AND unidad = "km"',
            [
                $data['nombre'],
                (int) ($data['activo'] ?? 1),
                $id,
            ]
        );
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->execute(
            'UPDATE alerta_config SET activo = ? WHERE id = ? AND unidad = "km"',
            [$activo ? 1 : 0, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute(
            'DELETE FROM alerta_config WHERE id = ? AND unidad = "km"',
            [$id]
        );
    }

    public function tipoExists(string $tipo): bool
    {
        return $this->fetchOne('SELECT id FROM alerta_config WHERE tipo = ?', [$tipo]) !== null;
    }

    public function countMantenimientos(string $tipo): int
    {
        $ms = (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM mantenimiento_servicios WHERE servicio = ?',
            [$tipo]
        )['c'] ?? 0);

        $legacy = (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM mantenimientos WHERE servicio = ?',
            [$tipo]
        )['c'] ?? 0);

        return $ms + $legacy;
    }

    public function countAlertas(string $tipo): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM alertas WHERE tipo = ?',
            [$tipo]
        )['c'] ?? 0);
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
