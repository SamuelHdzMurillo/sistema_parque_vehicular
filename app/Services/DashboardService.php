<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $repo = new DashboardRepository()
    ) {
    }

    public function getDashboardData(): array
    {
        $vehiculos = $this->repo->getResumenVehiculos();
        $alertas = $this->repo->getResumenAlertas();

        return [
            'kpis' => [
                'vehiculos_total' => (int) ($vehiculos['total'] ?? 0),
                'vehiculos_operativos' => (int) ($vehiculos['operativos'] ?? 0),
                'vehiculos_en_comision' => (int) ($vehiculos['en_comision'] ?? 0),
                'vehiculos_en_mantenimiento' => (int) ($vehiculos['en_mantenimiento'] ?? 0),
                'vehiculos_en_taller' => (int) ($vehiculos['en_taller'] ?? 0),
                'alertas_rojas' => (int) ($alertas['rojas'] ?? 0),
                'alertas_amarillas' => (int) ($alertas['amarillas'] ?? 0),
                'alertas_pendientes' => (int) ($alertas['pendientes'] ?? 0),
                'comisiones_activas' => $this->repo->getComisionesActivasCount(),
                'servicios_pendientes' => $this->repo->getServiciosPendientes(),
                'docs_por_vencer' => $this->repo->getDocsPorVencer(),
                'danios_abiertos' => $this->repo->countDaniosAbiertos(),
            ],
            'proximos_servicios' => $this->getProximosServicios(12),
            'alertas' => $this->repo->getAlertasPendientes(8),
            'documentos' => $this->repo->getDocumentosPorVencer(8),
            'mantenimientos' => $this->repo->getMantenimientosActivos(8),
            'comisiones' => $this->repo->getComisionesEnCurso(8),
            'danios' => $this->repo->getDaniosAbiertos(6),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function getProximosServicios(int $limit = 12): array
    {
        $configs = $this->repo->getAlertaConfigsKm();
        $vehiculos = $this->repo->getVehiculosOperativos();
        $items = [];

        foreach ($vehiculos as $vehiculo) {
            $vehiculoId = (int) $vehiculo['id'];
            $kmActual = (int) $vehiculo['kilometraje_actual'];

            foreach ($configs as $config) {
                $busqueda = match ($config['tipo']) {
                    'cambio_aceite' => 'aceite',
                    'afinacion' => 'afinacion',
                    default => (string) $config['tipo'],
                };

                $ultimo = $this->repo->getUltimoServicioPreventivo($vehiculoId, $busqueda);
                $kmBase = $ultimo !== null ? (int) $ultimo['kilometraje'] : 0;
                $kmDesde = $kmActual - $kmBase;
                $kmLimite = (int) $config['umbral_verde'];
                $kmRestante = $kmLimite - $kmDesde;

                if ($kmDesde < (int) $config['umbral_rojo']) {
                    continue;
                }

                $nivel = $kmDesde >= $kmLimite ? 'rojo'
                    : ($kmDesde >= (int) $config['umbral_amarillo'] ? 'amarillo' : 'verde');

                $items[] = [
                    'vehiculo_id' => $vehiculoId,
                    'numero_economico' => $vehiculo['numero_economico'],
                    'servicio' => $config['nombre'],
                    'tipo' => $config['tipo'],
                    'km_desde_servicio' => $kmDesde,
                    'km_limite' => $kmLimite,
                    'km_restante' => max(0, $kmRestante),
                    'km_vencido' => $kmRestante < 0 ? abs($kmRestante) : 0,
                    'ultimo_servicio' => $ultimo['fecha'] ?? null,
                    'nivel' => $nivel,
                    'estado_vehiculo' => $vehiculo['estado'],
                ];
            }
        }

        usort($items, static function (array $a, array $b): int {
            $order = ['rojo' => 0, 'amarillo' => 1, 'verde' => 2];
            $cmp = ($order[$a['nivel']] ?? 3) <=> ($order[$b['nivel']] ?? 3);
            if ($cmp !== 0) {
                return $cmp;
            }
            if ($a['km_vencido'] !== $b['km_vencido']) {
                return $b['km_vencido'] <=> $a['km_vencido'];
            }

            return $a['km_restante'] <=> $b['km_restante'];
        });

        return array_slice($items, 0, $limit);
    }
}
