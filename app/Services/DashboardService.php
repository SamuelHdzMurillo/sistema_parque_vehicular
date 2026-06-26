<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $repo = new DashboardRepository(),
        private readonly AlertaService $alertas = new AlertaService(),
    ) {
    }

    public function getDashboardData(): array
    {
        $vehiculos = $this->repo->getResumenVehiculos();
        $avisosDashboard = $this->alertas->getAvisosDashboard();
        $conteos = $avisosDashboard['counts'];

        return [
            'kpis' => [
                'vehiculos_total' => (int) ($vehiculos['total'] ?? 0),
                'vehiculos_operativos' => (int) ($vehiculos['operativos'] ?? 0),
                'vehiculos_en_comision' => (int) ($vehiculos['en_comision'] ?? 0),
                'vehiculos_en_mantenimiento' => (int) ($vehiculos['en_mantenimiento'] ?? 0),
                'vehiculos_en_taller' => (int) ($vehiculos['en_taller'] ?? 0),
                'alertas_rojas' => (int) ($conteos['rojo'] ?? 0),
                'alertas_amarillas' => (int) ($conteos['amarillo'] ?? 0),
                'alertas_verdes' => (int) ($conteos['verde'] ?? 0),
                'alertas_pendientes' => (int) ($conteos['total'] ?? 0),
                'comisiones_activas' => $this->repo->getComisionesActivasCount(),
                'servicios_pendientes' => $this->repo->getServiciosPendientes(),
                'docs_por_vencer' => $this->repo->getDocsPorVencer(),
                'danios_abiertos' => $this->repo->countDaniosAbiertos(),
            ],
            'alertas_grupos' => $avisosDashboard['grupos'],
            'alertas_total_grupos' => (int) ($avisosDashboard['total_grupos'] ?? 0),
            'mantenimientos_por_vencer' => $avisosDashboard['mantenimientos_por_vencer'],
            'documentos' => $this->repo->getDocumentosPorVencer(8),
            'mantenimientos' => $this->repo->getMantenimientosActivos(8),
            'comisiones' => $this->repo->getComisionesEnCurso(8),
            'danios' => $this->repo->getDaniosAbiertos(6),
        ];
    }
}
