<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class AlertaService
{
    public function __construct(
        private readonly AlertaRepository $repo = new AlertaRepository(),
        private readonly MantenimientoRepository $mantenimientos = new MantenimientoRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
    ) {
    }

    public function runDailyCron(): array
    {
        $result = ['documentos' => $this->repo->generarFromDocumentos()];

        foreach ($this->repo->getServiciosKm() as $cfg) {
            $tipo = (string) $cfg['tipo'];
            $nombre = (string) ($cfg['nombre'] ?? $tipo);
            $result[$tipo] = $this->generarAlertasKm($tipo, $nombre . ' pendiente');
        }

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

    public function getConfigPageData(?int $vehiculoId = null): array
    {
        $data = [
            'config' => alerta_config_sort($this->repo->getAllConfig()),
            'vehiculos' => $this->catalogos->getVehiculosCatalogo(),
            'vehiculo_id' => $vehiculoId,
            'vehiculo_config' => [],
        ];

        if ($vehiculoId !== null && $vehiculoId > 0) {
            $data['vehiculo_config'] = alerta_config_sort(
                $this->buildVehiculoConfigForm($vehiculoId, $data['config'])
            );
        }

        return $data;
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

    public function updateVehiculoConfig(int $vehiculoId, array $config): void
    {
        foreach ($config as $tipo => $row) {
            if (!is_array($row) || $tipo === '') {
                continue;
            }

            if (empty($row['personalizado'])) {
                $this->repo->deleteVehiculoConfig($vehiculoId, (string) $tipo);
                continue;
            }

            $this->repo->upsertVehiculoConfig($vehiculoId, (string) $tipo, $row);
        }
    }

    public function getDashboardCounts(): array
    {
        return $this->repo->getDashboardCounts();
    }

    /** Al finalizar un mantenimiento, cierra alertas del mismo servicio y recalcula. */
    public function registrarMantenimientoFinalizado(array $mantenimiento, int $userId): void
    {
        $servicio = (string) ($mantenimiento['servicio'] ?? '');
        if ($servicio === '') {
            $servicio = mantenimiento_inferir_servicio((string) ($mantenimiento['descripcion'] ?? '')) ?? '';
        }
        if ($servicio === '') {
            return;
        }

        $vehiculoId = (int) ($mantenimiento['vehiculo_id'] ?? 0);
        if ($vehiculoId <= 0) {
            return;
        }

        $this->repo->atenderActivasPorServicio($vehiculoId, $servicio, $userId);
        $this->sincronizar();
    }

    private function buildVehiculoConfigForm(int $vehiculoId, array $globalConfig): array
    {
        $custom = $this->repo->getVehiculoConfigAll($vehiculoId);
        $rows = [];

        foreach ($globalConfig as $global) {
            $tipo = (string) $global['tipo'];
            $override = $custom[$tipo] ?? null;
            $rows[] = [
                'tipo' => $tipo,
                'nombre' => $global['nombre'],
                'unidad' => $global['unidad'],
                'personalizado' => $override !== null,
                'umbral_verde' => $override['umbral_verde'] ?? $global['umbral_verde'],
                'umbral_amarillo' => $override['umbral_amarillo'] ?? $global['umbral_amarillo'],
                'umbral_rojo' => $override['umbral_rojo'] ?? $global['umbral_rojo'],
                'umbral_verde_dias' => $override['umbral_verde_dias'] ?? $global['umbral_verde_dias'] ?? '',
                'umbral_amarillo_dias' => $override['umbral_amarillo_dias'] ?? $global['umbral_amarillo_dias'] ?? '',
                'umbral_rojo_dias' => $override['umbral_rojo_dias'] ?? $global['umbral_rojo_dias'] ?? '',
                'activo' => $override !== null ? (int) ($override['activo'] ?? 1) : (int) ($global['activo'] ?? 1),
            ];
        }

        return $rows;
    }

    private function generarAlertasKm(string $tipoConfig, string $tituloBase): int
    {
        $global = $this->repo->getAlertaConfig($tipoConfig);
        if ($global === null) {
            return 0;
        }

        $vehiculos = $this->vehiculos->paginate(1, 1000)['data'];
        $generadas = 0;

        foreach ($vehiculos as $vehiculo) {
            $vehiculoId = (int) $vehiculo['id'];
            $config = $this->repo->getEffectiveConfig($vehiculoId, $tipoConfig);
            if ($config === null) {
                continue;
            }

            $ultimo = $this->mantenimientos->getUltimoPorServicio($vehiculoId, $tipoConfig);

            $kmBase = $ultimo !== null ? (int) $ultimo['kilometraje'] : 0;
            $kmActual = (int) $vehiculo['kilometraje_actual'];
            $kmDesde = $kmActual - $kmBase;

            $diasDesde = 0;
            if ($ultimo !== null && !empty($ultimo['fecha'])) {
                $diasDesde = (int) ((strtotime(date('Y-m-d')) - strtotime((string) $ultimo['fecha'])) / 86400);
            }

            $evaluacion = $this->evaluarUmbralesMantenimiento($kmDesde, $diasDesde, $config);
            if ($evaluacion === null) {
                continue;
            }

            if ($this->repo->existsActive($vehiculoId, $tipoConfig)) {
                continue;
            }

            $this->repo->create([
                'vehiculo_id' => $vehiculoId,
                'tipo' => $tipoConfig,
                'titulo' => $tituloBase . ' — ' . $vehiculo['numero_economico'],
                'mensaje' => $this->buildMensajeMantenimiento(
                    $vehiculo['numero_economico'],
                    $config['nombre'],
                    $kmDesde,
                    $diasDesde,
                    $evaluacion['motivo']
                ),
                'nivel' => $evaluacion['nivel'],
            ]);
            $generadas++;
        }

        return $generadas;
    }

    /**
     * Evalúa km y/o días con lógica OR: basta que se cumpla uno para generar alerta.
     *
     * @return array{nivel: string, motivo: string}|null
     */
    private function evaluarUmbralesMantenimiento(int $kmDesde, int $diasDesde, array $config): ?array
    {
        $nivelKm = $this->calcularNivelAcumulado($kmDesde, $config, 'km');
        $nivelDias = null;

        if ($this->tieneUmbralesDias($config)) {
            $nivelDias = $this->calcularNivelAcumulado($diasDesde, $config, 'dias');
        }

        $nivel = $this->nivelMasGrave($nivelKm, $nivelDias);
        if ($nivel === null) {
            return null;
        }

        $motivos = [];
        if ($nivelKm !== null) {
            $motivos[] = sprintf('%d km desde el último servicio', $kmDesde);
        }
        if ($nivelDias !== null) {
            $motivos[] = sprintf('%d día(s) desde el último servicio', $diasDesde);
        }

        return [
            'nivel' => $nivel,
            'motivo' => implode(' o ', $motivos),
        ];
    }

    private function calcularNivelAcumulado(int $valor, array $config, string $modo): ?string
    {
        $prefix = $modo === 'dias' ? 'umbral_verde_dias' : 'umbral_verde';
        $verde = $config[$prefix] ?? null;
        $amarillo = $config[$modo === 'dias' ? 'umbral_amarillo_dias' : 'umbral_amarillo'] ?? null;
        $rojo = $config[$modo === 'dias' ? 'umbral_rojo_dias' : 'umbral_rojo'] ?? null;

        if ($verde === null && $amarillo === null && $rojo === null) {
            return null;
        }

        if ($verde !== null && $valor >= (int) $verde) {
            return 'rojo';
        }
        if ($amarillo !== null && $valor >= (int) $amarillo) {
            return 'amarillo';
        }
        if ($rojo !== null && $valor >= (int) $rojo) {
            return 'verde';
        }

        return null;
    }

    private function tieneUmbralesDias(array $config): bool
    {
        return ($config['umbral_verde_dias'] ?? null) !== null
            || ($config['umbral_amarillo_dias'] ?? null) !== null
            || ($config['umbral_rojo_dias'] ?? null) !== null;
    }

    private function nivelMasGrave(?string $a, ?string $b): ?string
    {
        $peso = ['verde' => 1, 'amarillo' => 2, 'rojo' => 3];
        $pa = $a !== null ? ($peso[$a] ?? 0) : 0;
        $pb = $b !== null ? ($peso[$b] ?? 0) : 0;

        if ($pa === 0 && $pb === 0) {
            return null;
        }

        return $pa >= $pb ? $a : $b;
    }

    private function buildMensajeMantenimiento(
        string $numeroEconomico,
        string $nombreServicio,
        int $kmDesde,
        int $diasDesde,
        string $motivo
    ): string {
        return sprintf(
            'El vehículo %s requiere %s (%s). Registro: %d km y %d día(s) transcurridos.',
            $numeroEconomico,
            $nombreServicio,
            $motivo,
            $kmDesde,
            $diasDesde
        );
    }
}
