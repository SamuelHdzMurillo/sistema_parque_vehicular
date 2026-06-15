<?php

declare(strict_types=1);

namespace App\Repositories;

final class DanioRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT d.*, v.numero_economico, v.placas,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS reportado_por_nombre
             FROM danios d
             JOIN vehiculos v ON v.id = d.vehiculo_id
             JOIN users u ON u.id = d.reportado_por
             WHERE d.id = ?',
            [$id]
        );
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO danios (vehiculo_id, tipo_dano, ubicacion, descripcion, estado, reportado_por, mantenimiento_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $data['vehiculo_id'],
                $data['tipo_dano'],
                $data['ubicacion'],
                $data['descripcion'],
                $data['estado'] ?? 'reportado',
                (int) $data['reportado_por'],
                $data['mantenimiento_id'] ?? null,
            ]
        );

        $id = (int) $this->lastInsertId();
        $this->registrarSeguimiento($id, '', 'reportado', 'Reporte inicial del daño', (int) $data['reportado_por']);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE danios SET tipo_dano = ?, ubicacion = ?, descripcion = ?, mantenimiento_id = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['tipo_dano'],
                $data['ubicacion'],
                $data['descripcion'],
                $data['mantenimiento_id'] ?? null,
                $id,
            ]
        );
    }

    public function updateEstado(int $id, string $estado, ?string $comentario, int $userId): bool
    {
        $current = $this->fetchOne('SELECT estado FROM danios WHERE id = ?', [$id]);
        if ($current === null) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->execute(
                'UPDATE danios SET estado = ?, updated_at = NOW() WHERE id = ?',
                [$estado, $id]
            );
            $this->registrarSeguimiento($id, $current['estado'], $estado, $comentario, $userId);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function registrarSeguimiento(int $danioId, string $estadoAnterior, string $estadoNuevo, ?string $comentario, int $userId): void
    {
        $this->execute(
            'INSERT INTO danio_seguimiento (danio_id, estado_anterior, estado_nuevo, comentario, user_id)
             VALUES (?, ?, ?, ?, ?)',
            [$danioId, $estadoAnterior ?: 'reportado', $estadoNuevo, $comentario, $userId]
        );
    }

    public function getSeguimiento(int $danioId): array
    {
        return $this->fetchAll(
            'SELECT s.*, CONCAT(u.nombre, " ", u.apellido_paterno) AS usuario_nombre
             FROM danio_seguimiento s
             JOIN users u ON u.id = s.user_id
             WHERE s.danio_id = ?
             ORDER BY s.created_at ASC',
            [$danioId]
        );
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND d.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['estado'])) {
            $where .= ' AND d.estado = ?';
            $params[] = $filters['estado'];
        }
        if (!empty($filters['tipo_dano'])) {
            $where .= ' AND d.tipo_dano = ?';
            $params[] = $filters['tipo_dano'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM danios d {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT d.id, d.tipo_dano, d.ubicacion, d.estado, d.created_at,
                    v.numero_economico, v.placas
             FROM danios d
             JOIN vehiculos v ON v.id = d.vehiculo_id
             {$where}
             ORDER BY d.created_at DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM danios WHERE id = ? AND estado = "reportado"', [$id]);
    }
}
