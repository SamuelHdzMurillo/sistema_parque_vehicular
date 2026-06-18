<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertaRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class AlertaService
{
    public function __construct(
        private readonly AlertaRepository $repo = new AlertaRepository(),
        private readonly MantenimientoRepository $mantenimientos = new MantenimientoRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
    ) {
    }

    public function runDailyCron(): array
    {
        $result = [
            'documentos' => $this->repo->generarFromDocumentos(),
            'aceite' => $this->generarAlertasAceite(),
            'afinacion' => $this->generarAlertasKm('afinacion', 'Afinación pendiente'),
        ];

        return $result;
    }

    /** Genera o actualiza alertas pendientes según documentos y kilometraje. */
    public function sincronizar(): void
    {
        $this->runDailyCron();
    }

    public function atender(int $id, int $userId, ?string $comentario): ?string
    {
        try {
            $alerta = $this->repo->findById($id);
            if ($alerta === null) {
                return 'Alerta no encontrada.';
            }
            if (!$this->repo->atender($id, $userId, $comentario)) {
                return 'La alerta ya fue atendida.';
            }
            AuditService::log('UPDATE', 'alertas', $id, ['atendida' => 0], ['atendida' => 1]);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function paginate(int $page = 1, bool $soloPendientes = true): array
    {
        if ($soloPendientes) {
            $this->sincronizar();
        }

        $filters = $soloPendientes ? ['atendida' => 0] : [];
        return $this->repo->paginate($page, 15, $filters);
    }

    public function getConfig(): array
    {
        return $this->repo->getAllConfig();
    }

    public function updateConfig(array $config): void
    {
        foreach ($config as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $this->repo->updateConfig((int) $id, $row);
        }
    }

    public function getDashboardCounts(): array
    {
        return $this->repo->getDashboardCounts();
    }

    private function generarAlertasAceite(): int
    {
        return $this->generarAlertasKm('cambio_aceite', 'Cambio de aceite pendiente', 'aceite');
    }

    private function generarAlertasKm(string $tipoConfig, string $tituloBase, string $busquedaDesc = ''): int
    {
        $config = $this->repo->getAlertaConfig($tipoConfig);
        if ($config === null) {
            return 0;
        }

        $vehiculos = $this->vehiculos->paginate(1, 1000)['data'];
        $generadas = 0;

        foreach ($vehiculos as $vehiculo) {
            $vehiculoId = (int) $vehiculo['id'];
            $ultimo = $this->mantenimientos->getUltimoPreventivo(
                $vehiculoId,
                $busquedaDesc !== '' ? $busquedaDesc : $tipoConfig
            );

            $kmBase = $ultimo !== null ? (int) $ultimo['kilometraje'] : 0;
            $kmActual = (int) $vehiculo['kilometraje_actual'];
            $kmDesde = $kmActual - $kmBase;

            $nivel = $this->calcularNivelKm($kmDesde, $config);
            if ($nivel === null) {
                continue;
            }

            if ($this->repo->existsActive($vehiculoId, $tipoConfig)) {
                continue;
            }

            $this->repo->create([
                'vehiculo_id' => $vehiculoId,
                'tipo' => $tipoConfig,
                'titulo' => $tituloBase . ' — ' . $vehiculo['numero_economico'],
                'mensaje' => sprintf(
                    'El vehículo %s ha recorrido %d km desde el último servicio de %s.',
                    $vehiculo['numero_economico'],
                    $kmDesde,
                    $config['nombre']
                ),
                'nivel' => $nivel,
            ]);
            $generadas++;
        }

        return $generadas;
    }

    private function calcularNivelKm(int $kmDesde, array $config): ?string
    {
        if ($kmDesde >= (int) $config['umbral_verde']) {
            return 'rojo';
        }
        if ($kmDesde >= (int) $config['umbral_amarillo']) {
            return 'amarillo';
        }
        if ($kmDesde >= (int) $config['umbral_rojo']) {
            return 'verde';
        }

        return null;
    }
}
