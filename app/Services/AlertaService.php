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

    public function paginate(int $page = 1, bool $soloPendientes = true, ?int $vehiculoId = null): array
    {
        if ($soloPendientes) {
            $this->sincronizar();
        }

        $filters = $soloPendientes ? ['atendida' => 0] : [];
        if ($vehiculoId !== null && $vehiculoId > 0) {
            $filters['vehiculo_id'] = $vehiculoId;
        }
        $result = $this->repo->paginate($page, 15, $filters);
        $result['data'] = array_map(fn (array $row): array => $this->enriquecerFila($row), $result['data']);
        $result['grupos'] = alerta_agrupar_por_vehiculo($result['data']);

        return $result;
    }

    /**
     * Vista por vehículo: todos los servicios de mantenimiento con último registro y próximo toque.
     *
     * @return array{grupos: list<array>, page: int, total: int, per_page: int, counts: array, solo_pendientes: bool, modo: string, vehiculo_id: ?int, vehiculos: list<array>}
     */
    public function getMatrizMantenimiento(int $page = 1, bool $soloConAvisos = false, ?int $vehiculoId = null): array
    {
        $this->sincronizar();

        $perPage = 10;
        $serviciosKm = $this->repo->getServiciosKm();
        $grupos = [];

        if ($vehiculoId !== null && $vehiculoId > 0) {
            $vehiculo = $this->vehiculos->findById($vehiculoId);
            $vehiculosData = $vehiculo !== null ? [$vehiculo] : [];
            $total = count($vehiculosData);
            $page = 1;
        } else {
            $vehiculosResult = $this->vehiculos->paginate($page, $perPage);
            $vehiculosData = $vehiculosResult['data'];
            $total = $vehiculosResult['total'];
        }

        foreach ($vehiculosData as $vehiculo) {
            $vehiculoId = (int) $vehiculo['id'];
            $filas = [];

            foreach ($serviciosKm as $cfg) {
                $tipo = (string) $cfg['tipo'];
                $config = $this->repo->getEffectiveConfig($vehiculoId, $tipo);
                if ($config === null) {
                    continue;
                }

                $fila = $this->buildFilaMantenimiento($vehiculo, $tipo, $config);
                if (!empty($fila['sin_alta'])) {
                    continue;
                }
                $filas[] = $fila;
            }

            foreach ($this->repo->findPendientesDocumentoPorVehiculo($vehiculoId) as $alerta) {
                $filas[] = $this->enriquecerFila($alerta);
            }

            if ($filas === []) {
                continue;
            }

            $nivelMax = alerta_nivel_max_filas($filas);

            if ($soloConAvisos && $nivelMax === null) {
                continue;
            }

            alerta_ordenar_filas($filas);

            $grupos[] = [
                'vehiculo_id' => $vehiculoId,
                'numero_economico' => (string) $vehiculo['numero_economico'],
                'kilometraje_actual' => (int) ($vehiculo['kilometraje_actual'] ?? 0),
                'nivel_max' => $nivelMax,
                'alertas' => $filas,
            ];
        }

        usort($grupos, static function (array $a, array $b): int {
            $cmp = alerta_nivel_peso($b['nivel_max']) <=> alerta_nivel_peso($a['nivel_max']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp($a['numero_economico'], $b['numero_economico']);
        });

        return [
            'grupos' => $grupos,
            'page' => $page,
            'total' => $total,
            'per_page' => $perPage,
            'counts' => $this->repo->getDashboardCounts(),
            'solo_pendientes' => $soloConAvisos,
            'modo' => 'matriz',
            'vehiculo_id' => $vehiculoId !== null && $vehiculoId > 0 ? $vehiculoId : null,
            'vehiculos' => $this->catalogos->getVehiculosCatalogo(),
        ];
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

    /** @return array{error: ?string, tipo: ?string} */
    public function createServicioKm(array $data): array
    {
        try {
            $nombre = trim((string) ($data['nombre'] ?? ''));
            if ($nombre === '') {
                return ['error' => 'Indique el nombre del servicio.', 'tipo' => null];
            }

            $tipo = trim((string) ($data['tipo'] ?? ''));
            $tipo = $tipo !== '' ? alerta_servicio_slug($tipo) : alerta_servicio_slug($nombre);

            if ($tipo === '' || !preg_match('/^[a-z][a-z0-9_]{1,48}$/', $tipo)) {
                return [
                    'error' => 'El código interno debe usar letras minúsculas, números y guión bajo (ej. revision_frenos).',
                    'tipo' => null,
                ];
            }

            if ($this->repo->tipoExists($tipo)) {
                return ['error' => 'Ya existe un servicio con ese código interno.', 'tipo' => null];
            }

            $umbralRojo = max(0, (int) ($data['umbral_rojo'] ?? 500));
            $umbralAmarillo = max(0, (int) ($data['umbral_amarillo'] ?? 2000));
            $umbralVerde = max(0, (int) ($data['umbral_verde'] ?? 5000));

            if ($umbralRojo > $umbralAmarillo || $umbralAmarillo > $umbralVerde) {
                return [
                    'error' => 'Los umbrales deben ir de menor a mayor: aviso ≤ atención ≤ urgente (km).',
                    'tipo' => null,
                ];
            }

            $id = $this->repo->createConfig([
                'tipo' => $tipo,
                'nombre' => $nombre,
                'umbral_verde' => $umbralVerde,
                'umbral_amarillo' => $umbralAmarillo,
                'umbral_rojo' => $umbralRojo,
                'unidad' => 'km',
                'umbral_verde_dias' => (int) ($data['umbral_verde_dias'] ?? 365),
                'umbral_amarillo_dias' => (int) ($data['umbral_amarillo_dias'] ?? 180),
                'umbral_rojo_dias' => (int) ($data['umbral_rojo_dias'] ?? 90),
                'activo' => 1,
            ]);

            AuditService::log('CREATE', 'alerta_config', $id, null, [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'unidad' => 'km',
            ]);

            return ['error' => null, 'tipo' => $tipo];
        } catch (\Throwable $e) {
            return ['error' => user_facing_error($e, 'No se pudo agregar el servicio.'), 'tipo' => null];
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

    /** @return list<array<string, mixed>> */
    public function getVehiculosCatalogo(): array
    {
        return $this->catalogos->getVehiculosCatalogo();
    }

    /** Al finalizar un mantenimiento, cierra alertas de cada servicio y recalcula. */
    public function registrarMantenimientoFinalizado(array $mantenimiento, int $userId): void
    {
        $vehiculoId = (int) ($mantenimiento['vehiculo_id'] ?? 0);
        if ($vehiculoId <= 0) {
            return;
        }

        $servicios = $mantenimiento['servicios'] ?? [];
        if (!is_array($servicios) || $servicios === []) {
            $servicio = (string) ($mantenimiento['servicio'] ?? '');
            if ($servicio === '') {
                $servicio = mantenimiento_inferir_servicio((string) ($mantenimiento['descripcion'] ?? '')) ?? '';
            }
            $servicios = $servicio !== '' ? [$servicio] : [];
        }

        if ($servicios === []) {
            return;
        }

        foreach ($servicios as $servicio) {
            $this->repo->atenderActivasPorServicio($vehiculoId, (string) $servicio, $userId);
        }
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

            if ($ultimo === null) {
                $this->repo->dismissActivasPorServicio(
                    $vehiculoId,
                    $tipoConfig,
                    'Sin mantenimiento registrado de este servicio.'
                );
                continue;
            }

            $kmBase = (int) $ultimo['kilometraje'];
            $kmActual = (int) $vehiculo['kilometraje_actual'];
            $kmDesde = $kmActual - $kmBase;

            $diasDesde = 0;
            if (!empty($ultimo['fecha'])) {
                $diasDesde = (int) ((strtotime(date('Y-m-d')) - strtotime((string) $ultimo['fecha'])) / 86400);
            }

            $evaluacion = $this->evaluarUmbralesMantenimiento(
                $kmDesde,
                $diasDesde,
                $config,
                $ultimo,
                $kmActual
            );
            if ($evaluacion === null) {
                continue;
            }

            $mensaje = $this->buildMensajeMantenimiento(
                (string) $config['nombre'],
                $evaluacion['motivo']
            );

            $activa = $this->repo->findActive($vehiculoId, $tipoConfig);
            if ($activa !== null) {
                $this->repo->updateMensaje((int) $activa['id'], $mensaje, $evaluacion['nivel']);
                continue;
            }

            $this->repo->create([
                'vehiculo_id' => $vehiculoId,
                'tipo' => $tipoConfig,
                'titulo' => $tituloBase . ' — ' . $vehiculo['numero_economico'],
                'mensaje' => $mensaje,
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
    private function evaluarUmbralesMantenimiento(
        int $kmDesde,
        int $diasDesde,
        array $config,
        ?array $ultimo,
        int $kmActual
    ): ?array {
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
            $motivos[] = sprintf('%s km desde el último servicio', number_format($kmDesde, 0, '.', ','));
        }
        if ($nivelDias !== null) {
            $motivos[] = sprintf('%d día(s) desde el último servicio', $diasDesde);
        }

        return [
            'nivel' => $nivel,
            'motivo' => implode(' · ', $motivos),
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

    private function buildMensajeMantenimiento(string $nombreServicio, string $motivo): string
    {
        return sprintf('%s · %s', $nombreServicio, $motivo);
    }

    /** @return array<string, mixed> */
    private function buildFilaMantenimiento(array $vehiculo, string $tipo, array $config): array
    {
        $vehiculoId = (int) $vehiculo['id'];
        $ultimo = $this->mantenimientos->getUltimoPorServicio($vehiculoId, $tipo);
        $fechas = alerta_mantenimiento_fechas($ultimo, $config, $vehiculo);

        $fila = [
            'vehiculo_id' => $vehiculoId,
            'numero_economico' => (string) ($vehiculo['numero_economico'] ?? ''),
            'tipo' => $tipo,
            'servicio_nombre' => (string) ($config['nombre'] ?? mantenimiento_servicio_label($tipo)),
            'categoria' => 'mantenimiento',
            'sin_alta' => $ultimo === null,
            'fecha_ultimo_mantenimiento' => $fechas['ultima'],
            'fecha_proximo_mantenimiento' => $fechas['proxima'],
            'proximo_km' => alerta_proximo_km($ultimo, $config),
            'ultimo_km' => $ultimo !== null ? (int) $ultimo['kilometraje'] : null,
            'mantenimiento_id' => $ultimo['id'] ?? null,
            'mantenimiento_folio' => $ultimo['folio'] ?? null,
            'atendida' => 0,
            'id' => null,
            'nivel' => null,
        ];

        $abierto = $this->mantenimientos->findAbiertoPorServicio($vehiculoId, $tipo);
        $fila['mantenimiento_abierto_id'] = $abierto['id'] ?? null;
        $fila['mantenimiento_abierto_folio'] = $abierto['folio'] ?? null;

        if ($ultimo !== null) {
            $kmActual = (int) ($vehiculo['kilometraje_actual'] ?? 0);
            $kmDesde = max(0, $kmActual - (int) $ultimo['kilometraje']);
            $diasDesde = 0;
            if (!empty($ultimo['fecha'])) {
                $diasDesde = (int) ((strtotime(date('Y-m-d')) - strtotime((string) $ultimo['fecha'])) / 86400);
            }

            $fila['km_desde'] = $kmDesde;
            $fila['dias_desde'] = $diasDesde;

            $evaluacion = $this->evaluarUmbralesMantenimiento(
                $kmDesde,
                $diasDesde,
                $config,
                $ultimo,
                $kmActual
            );
            $fila['nivel'] = $evaluacion['nivel'] ?? null;
        } else {
            $fila['km_desde'] = null;
            $fila['dias_desde'] = null;
        }

        $activa = $this->repo->findActive($vehiculoId, $tipo);
        if ($activa !== null) {
            $fila['id'] = (int) $activa['id'];
        }

        return $fila;
    }

    private function enriquecerFila(array $alerta): array
    {
        $tipo = (string) ($alerta['tipo'] ?? '');
        $vehiculoId = (int) ($alerta['vehiculo_id'] ?? 0);
        $config = $vehiculoId > 0 ? $this->repo->getEffectiveConfig($vehiculoId, $tipo) : null;
        $alerta['servicio_nombre'] = (string) ($config['nombre'] ?? mantenimiento_servicio_label($tipo));

        if ($config !== null && ($config['unidad'] ?? '') === 'km') {
            $vehiculo = $this->vehiculos->findById($vehiculoId);
            $ultimo = $this->mantenimientos->getUltimoPorServicio($vehiculoId, $tipo);
            $fechas = alerta_mantenimiento_fechas($ultimo, $config, $vehiculo);
            $alerta['categoria'] = 'mantenimiento';
            $alerta['fecha_ultimo_mantenimiento'] = $fechas['ultima'];
            $alerta['fecha_proximo_mantenimiento'] = $fechas['proxima'];
            $alerta['proximo_km'] = alerta_proximo_km($ultimo, $config);
            $alerta['mantenimiento_id'] = $ultimo['id'] ?? null;
            $alerta['mantenimiento_folio'] = $ultimo['folio'] ?? null;
            $alerta['ultimo_km'] = $ultimo !== null ? (int) $ultimo['kilometraje'] : null;
            $alerta['sin_alta'] = $ultimo === null;

            $abierto = $this->mantenimientos->findAbiertoPorServicio($vehiculoId, $tipo);
            $alerta['mantenimiento_abierto_id'] = $abierto['id'] ?? null;
            $alerta['mantenimiento_abierto_folio'] = $abierto['folio'] ?? null;

            if ($ultimo !== null) {
                $kmActual = (int) ($vehiculo['kilometraje_actual'] ?? 0);
                $alerta['km_desde'] = max(0, $kmActual - (int) $ultimo['kilometraje']);
                if (!empty($ultimo['fecha'])) {
                    $alerta['dias_desde'] = (int) ((strtotime(date('Y-m-d')) - strtotime((string) $ultimo['fecha'])) / 86400);
                }
            } else {
                $alerta['km_desde'] = null;
                $alerta['dias_desde'] = null;
            }

            return $alerta;
        }

        $alerta['categoria'] = 'documento';
        if (!empty($alerta['fecha_vencimiento'])) {
            $alerta['fecha_proximo_mantenimiento'] = substr((string) $alerta['fecha_vencimiento'], 0, 10);
            $alerta['dias_restantes'] = (int) ((strtotime($alerta['fecha_proximo_mantenimiento']) - strtotime(date('Y-m-d'))) / 86400);
        }

        return $alerta;
    }
}
