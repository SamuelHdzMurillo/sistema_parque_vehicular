<?php

declare(strict_types=1);

namespace App\Repositories;

final class HerramientaRepository extends BaseRepository
{
    public function getByVehiculo(int $vehiculoId): array
    {
        return $this->fetchAll(
            'SELECT * FROM herramientas_vehiculo WHERE vehiculo_id = ? ORDER BY FIELD(tipo,
                "gato","cruceta","extintor","botiquin","triangulos","linterna","llanta_refaccion")',
            [$vehiculoId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT h.*, v.numero_economico
             FROM herramientas_vehiculo h
             JOIN vehiculos v ON v.id = h.vehiculo_id
             WHERE h.id = ?',
            [$id]
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE herramientas_vehiculo SET
                estado = ?, foto_ruta = ?, fecha_vencimiento = ?, observaciones = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['estado'],
                $data['foto_ruta'] ?? null,
                $data['fecha_vencimiento'] ?? null,
                $data['observaciones'] ?? null,
                $id,
            ]
        );
    }

    public function reposicion(int $id, string $estadoNuevo, ?string $observaciones, int $userId): bool
    {
        $actual = $this->findById($id);
        if ($actual === null) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->execute(
                'UPDATE herramientas_vehiculo SET estado = ?, observaciones = ?, updated_at = NOW() WHERE id = ?',
                [$estadoNuevo, $observaciones, $id]
            );
            $this->execute(
                'INSERT INTO herramienta_reposiciones (herramienta_id, fecha, estado_anterior, estado_nuevo, observaciones, user_id)
                 VALUES (?, CURDATE(), ?, ?, ?, ?)',
                [$id, $actual['estado'], $estadoNuevo, $observaciones, $userId]
            );
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getReposiciones(int $herramientaId): array
    {
        return $this->fetchAll(
            'SELECT r.*, CONCAT(u.nombre, " ", u.apellido_paterno) AS usuario_nombre
             FROM herramienta_reposiciones r
             JOIN users u ON u.id = r.user_id
             WHERE r.herramienta_id = ?
             ORDER BY r.created_at DESC',
            [$herramientaId]
        );
    }

    public function ensureDefaultsForVehiculo(int $vehiculoId): void
    {
        foreach (herramienta_catalogo_codigos() as $tipo) {
            $exists = $this->fetchOne(
                'SELECT id FROM herramientas_vehiculo WHERE vehiculo_id = ? AND tipo = ?',
                [$vehiculoId, $tipo]
            );
            if ($exists === null) {
                $this->execute(
                    'INSERT INTO herramientas_vehiculo (vehiculo_id, tipo, estado) VALUES (?, ?, "presente")',
                    [$vehiculoId, $tipo]
                );
            }
        }
    }

    public function getProblematicas(int $vehiculoId): array
    {
        return $this->fetchAll(
            'SELECT * FROM herramientas_vehiculo
             WHERE vehiculo_id = ? AND estado IN ("ausente", "danado", "vencido")',
            [$vehiculoId]
        );
    }
}
