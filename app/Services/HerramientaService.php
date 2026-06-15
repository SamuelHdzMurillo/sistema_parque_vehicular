<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Services\AuditService;

final class HerramientaService extends BaseRepository
{
    public function listByVehiculo(int $vehiculoId): ?array
    {
        $vehiculo = $this->fetchOne(
            'SELECT id, numero_economico, placas FROM vehiculos WHERE id = ? AND deleted_at IS NULL',
            [$vehiculoId]
        );
        if ($vehiculo === null) {
            return null;
        }
        return [
            'vehiculo' => $vehiculo,
            'herramientas' => $this->fetchAll(
                'SELECT * FROM herramientas_vehiculo WHERE vehiculo_id = ? ORDER BY tipo',
                [$vehiculoId]
            ),
        ];
    }

    public function updateByVehiculo(int $vehiculoId, array $items, int $userId): void
    {
        foreach ($items as $tipo => $estado) {
            if (!is_string($tipo) || !is_string($estado)) {
                continue;
            }
            $actual = $this->fetchOne(
                'SELECT id, estado FROM herramientas_vehiculo WHERE vehiculo_id = ? AND tipo = ?',
                [$vehiculoId, $tipo]
            );
            if ($actual === null) {
                $this->execute(
                    'INSERT INTO herramientas_vehiculo (vehiculo_id, tipo, estado) VALUES (?, ?, ?)',
                    [$vehiculoId, $tipo, $estado]
                );
                continue;
            }
            if ($actual['estado'] === $estado) {
                continue;
            }
            $this->execute(
                'UPDATE herramientas_vehiculo SET estado = ? WHERE id = ?',
                [$estado, $actual['id']]
            );
            $this->execute(
                'INSERT INTO herramienta_reposiciones (herramienta_id, fecha, estado_anterior, estado_nuevo, user_id)
                 VALUES (?, CURDATE(), ?, ?, ?)',
                [$actual['id'], $actual['estado'], $estado, $userId]
            );
        }
        AuditService::log('UPDATE', 'herramientas_vehiculo', $vehiculoId, null, $items);
    }
}
