<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;

final class DashboardService extends BaseRepository
{
    public function getKpis(): array
    {
        $vehiculos = $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(estado NOT IN ('baja','fuera_servicio')) AS operativos,
                SUM(estado = 'en_comision') AS en_comision,
                SUM(estado = 'en_mantenimiento') AS en_mantenimiento
             FROM vehiculos WHERE deleted_at IS NULL"
        ) ?: [];

        $alertas = $this->fetchOne(
            "SELECT
                SUM(nivel = 'rojo' AND atendida = 0) AS rojas,
                SUM(nivel = 'amarillo' AND atendida = 0) AS amarillas,
                SUM(atendida = 0) AS pendientes
             FROM alertas"
        ) ?: [];

        $comisiones = $this->fetchOne(
            "SELECT COUNT(*) AS activas FROM comisiones WHERE estado = 'en_curso'"
        ) ?: [];

        $costos = $this->fetchOne(
            'SELECT COALESCE(SUM(costo_total), 0) AS total FROM v_costos_vehiculo'
        ) ?: [];

        return [
            'vehiculos_total' => (int) ($vehiculos['total'] ?? 0),
            'vehiculos_operativos' => (int) ($vehiculos['operativos'] ?? 0),
            'vehiculos_en_comision' => (int) ($vehiculos['en_comision'] ?? 0),
            'vehiculos_en_mantenimiento' => (int) ($vehiculos['en_mantenimiento'] ?? 0),
            'alertas_rojas' => (int) ($alertas['rojas'] ?? 0),
            'alertas_amarillas' => (int) ($alertas['amarillas'] ?? 0),
            'alertas_pendientes' => (int) ($alertas['pendientes'] ?? 0),
            'comisiones_activas' => (int) ($comisiones['activas'] ?? 0),
            'costo_total_parque' => (float) ($costos['total'] ?? 0),
        ];
    }
}
