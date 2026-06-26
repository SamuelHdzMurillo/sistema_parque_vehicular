<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class AlertaService
{
    public function __construct(
        private readonly AlertaRepository $repo = new AlertaRepository(),
        private readonly MantenimientoRepository $mantenimientos = new MantenimientoRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
        private readonly DocumentoRepository $documentos = new DocumentoRepository(),
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
        $filtroVehiculoId = $vehiculoId !== null && $vehiculoId > 0 ? $vehiculoId : null;

        if ($filtroVehiculoId !== null) {
            $vehiculo = $this->vehiculos->findById($filtroVehiculoId);
            $vehiculosData = $vehiculo !== null ? [$vehiculo] : [];
            $total = count($vehiculosData);
            $page = 1;
        } else {
            $vehiculosResult = $this->vehiculos->paginate($page, $perPage);
            $vehiculosData = $vehiculosResult['data'];
            $total = $vehiculosResult['total'];
        }

        $grupos = $this->buildGruposFromVehiculos($vehiculosData, $soloConAvisos);

        return [
            'grupos' => $grupos,
            'page' => $page,
            'total' => $total,
            'per_page' => $perPage,
            'counts' => $this->getDashboardCounts(),
            'solo_pendientes' => $soloConAvisos,
            'modo' => 'matriz',
            'vehiculo_id' => $filtroVehiculoId,
            'vehiculos' => $this->catalogos->getVehiculosCatalogo(),
        ];
    }

    /** @return array{error: ?string, tipo: ?string} */
    public function createServicioKm(array $data): array
    {
        try {
            return (new ServicioService())->createWithTipo($data);
        } catch (\Throwable $e) {
            return ['error' => user_facing_error($e, 'No se pudo agregar el servicio.'), 'tipo' => null];
        }
    }

    public function getDashboardCounts(): array
    {
        return $this->getAvisosDashboard()['counts'];
    }

    /**
     * Grupos por vehículo con avisos activos (vista «Solo con avisos» de alertas).
     *
     * @return list<array<string, mixed>>
     */
    public function getGruposConAvisos(): array
    {
        $this->sincronizar();
        $vehiculos = $this->vehiculos->paginate(1, 1000)['data'];

        return $this->buildGruposFromVehiculos($vehiculos, true);
    }

    /**
     * Avisos calculados en vivo (misma lógica que la pantalla de alertas).
     *
     * @return array{
     *     counts: array{verde: int, amarillo: int, rojo: int, total: int},
     *     grupos: list<array<string, mixed>>,
     *     total_grupos: int,
     *     mantenimientos_por_vencer: list<array<string, mixed>>
     * }
     */
    public function getAvisosDashboard(int $limiteGrupos = 20): array
    {
        $this->sincronizar();

        $avisos = $this->collectAvisosActivos();
        alerta_ordenar_filas($avisos);

        $counts = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'total' => 0];
        foreach ($avisos as $aviso) {
            $nivel = isset($aviso['nivel']) ? (string) $aviso['nivel'] : '';
            if ($nivel !== '' && isset($counts[$nivel])) {
                $counts[$nivel]++;
                $counts['total']++;
            }
        }

        $vehiculos = $this->vehiculos->paginate(1, 1000)['data'];
        $grupos = alerta_filtrar_grupos_dashboard(
            $this->buildGruposFromVehiculos($vehiculos, false)
        );

        $mantenimientosPorVencer = [];
        foreach ($grupos as $grupo) {
            $kmActual = (int) ($grupo['kilometraje_actual'] ?? 0);
            foreach ($grupo['alertas'] ?? [] as $fila) {
                if (alerta_fila_mantenimiento_por_vencer($fila, $kmActual)) {
                    $mantenimientosPorVencer[] = $fila;
                }
            }
        }
        alerta_ordenar_filas($mantenimientosPorVencer);

        return [
            'counts' => $counts,
            'grupos' => array_slice($grupos, 0, $limiteGrupos),
            'total_grupos' => count($grupos),
            'mantenimientos_por_vencer' => $mantenimientosPorVencer,
        ];
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

    private function generarAlertasKm(string $tipoConfig, string $tituloBase): int
    {
        if ($this->repo->getAlertaConfig($tipoConfig) === null) {
            return 0;
        }

        $vehiculos = $this->vehiculos->paginate(1, 1000)['data'];
        $generadas = 0;

        foreach ($vehiculos as $vehiculo) {
            $vehiculoId = (int) $vehiculo['id'];
            $ultimo = $this->mantenimientos->getUltimoPorServicio($vehiculoId, $tipoConfig);

            if ($ultimo === null) {
                $this->repo->dismissActivasPorServicio(
                    $vehiculoId,
                    $tipoConfig,
                    'Sin mantenimiento registrado de este servicio.'
                );
                continue;
            }

            $intervaloKm = mantenimiento_intervalo_km($ultimo);
            $intervaloDias = mantenimiento_intervalo_dias($ultimo);
            if ($intervaloKm === null && $intervaloDias === null) {
                continue;
            }

            $kmDesde = max(0, (int) $vehiculo['kilometraje_actual'] - (int) $ultimo['kilometraje']);
            $diasDesde = 0;
            if (!empty($ultimo['fecha'])) {
                $diasDesde = (int) ((strtotime(date('Y-m-d')) - strtotime((string) $ultimo['fecha'])) / 86400);
            }

            $evaluacion = alerta_evaluar_intervalos($kmDesde, $diasDesde, $intervaloKm, $intervaloDias);
            if ($evaluacion === null) {
                $this->repo->dismissActivasPorServicio(
                    $vehiculoId,
                    $tipoConfig,
                    'Servicio dentro del intervalo permitido.'
                );
                continue;
            }

            $nombreServicio = mantenimiento_servicio_label($tipoConfig);
            $mensaje = $this->buildMensajeMantenimiento($nombreServicio, $evaluacion['motivo']);

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

    private function buildMensajeMantenimiento(string $nombreServicio, string $motivo): string
    {
        return sprintf('%s · %s', $nombreServicio, $motivo);
    }

    /** @return list<array<string, mixed>> */
    private function buildGruposFromVehiculos(array $vehiculosData, bool $soloConAvisos): array
    {
        $serviciosKm = $this->repo->getServiciosKm();
        $grupos = [];

        foreach ($vehiculosData as $vehiculo) {
            $vehiculoRowId = (int) $vehiculo['id'];
            $filas = [];

            foreach ($serviciosKm as $cfg) {
                $tipo = (string) $cfg['tipo'];
                $fila = $this->buildFilaMantenimiento($vehiculo, $tipo, $cfg);
                if (!empty($fila['sin_alta'])) {
                    continue;
                }
                $filas[] = $fila;
            }

            foreach ($this->documentos->getActivosConVencimientoPorVehiculo($vehiculoRowId) as $documento) {
                $filas[] = $this->buildFilaDocumentoDesdeRegistro($vehiculo, $documento);
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
                'vehiculo_id' => $vehiculoRowId,
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

        return $grupos;
    }

    /** @return list<array<string, mixed>> */
    private function collectAvisosActivos(): array
    {
        $serviciosKm = $this->repo->getServiciosKm();
        $vehiculos = $this->vehiculos->paginate(1, 1000)['data'];
        $avisos = [];

        foreach ($vehiculos as $vehiculo) {
            foreach ($serviciosKm as $cfg) {
                $tipo = (string) $cfg['tipo'];
                $fila = $this->buildFilaMantenimiento($vehiculo, $tipo, $cfg);
                if (!empty($fila['sin_alta']) || ($fila['nivel'] ?? null) === null) {
                    continue;
                }
                $avisos[] = $fila;
            }

            foreach ($this->documentos->getActivosConVencimientoPorVehiculo((int) $vehiculo['id']) as $documento) {
                $fila = $this->buildFilaDocumentoDesdeRegistro($vehiculo, $documento);
                if (($fila['nivel'] ?? null) === null) {
                    continue;
                }
                $avisos[] = $fila;
            }
        }

        return $avisos;
    }

    /** @return array<string, mixed> */
    private function buildFilaMantenimiento(array $vehiculo, string $tipo, array $catalogo): array
    {
        $vehiculoId = (int) $vehiculo['id'];
        $ultimo = $this->mantenimientos->getUltimoPorServicio($vehiculoId, $tipo);
        $fechas = alerta_mantenimiento_fechas($ultimo);

        $fila = [
            'vehiculo_id' => $vehiculoId,
            'numero_economico' => (string) ($vehiculo['numero_economico'] ?? ''),
            'tipo' => $tipo,
            'servicio_nombre' => (string) ($catalogo['nombre'] ?? mantenimiento_servicio_label($tipo)),
            'categoria' => 'mantenimiento',
            'sin_alta' => $ultimo === null,
            'fecha_ultimo_mantenimiento' => $fechas['ultima'],
            'fecha_proximo_mantenimiento' => $fechas['proxima'],
            'proximo_km' => alerta_proximo_km($ultimo),
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

            $evaluacion = alerta_evaluar_intervalos(
                $kmDesde,
                $diasDesde,
                mantenimiento_intervalo_km($ultimo),
                mantenimiento_intervalo_dias($ultimo)
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

    /** @return array<string, mixed> */
    private function buildFilaDocumentoDesdeRegistro(array $vehiculo, array $documento): array
    {
        $vehiculoId = (int) $vehiculo['id'];
        $titulo = (string) ($documento['titulo'] ?? '');
        $tipoDocumento = documento_tipo_normalizado((string) ($documento['tipo'] ?? ''), $titulo);
        $tipoAlerta = $this->repo->mapTipoDocumentoToAlerta($tipoDocumento, $titulo);
        $config = $this->repo->getAlertaConfig($tipoAlerta);
        $nombre = (string) ($config['nombre'] ?? documento_tipo_label($tipoDocumento));

        $fila = [
            'vehiculo_id' => $vehiculoId,
            'numero_economico' => (string) ($vehiculo['numero_economico'] ?? ''),
            'tipo' => $tipoAlerta,
            'servicio_nombre' => $nombre,
            'categoria' => 'documento',
            'sin_alta' => false,
            'documento_id' => (int) $documento['id'],
            'documento_titulo' => $titulo,
            'fecha_ultimo_mantenimiento' => !empty($documento['fecha_emision'])
                ? substr((string) $documento['fecha_emision'], 0, 10)
                : null,
            'fecha_proximo_mantenimiento' => substr((string) $documento['fecha_vencimiento'], 0, 10),
            'atendida' => 0,
            'id' => null,
            'nivel' => null,
            'dias_restantes' => null,
        ];

        $diasRestantes = (int) ((strtotime($fila['fecha_proximo_mantenimiento']) - strtotime(date('Y-m-d'))) / 86400);
        $fila['dias_restantes'] = $diasRestantes;
        $fila['nivel'] = $this->repo->calcularNivelDocumento($diasRestantes, $config);

        $activa = $this->repo->findActivePorDocumento((int) $documento['id']);
        if ($activa !== null) {
            $fila['id'] = (int) $activa['id'];
        }

        return $fila;
    }

    private function enriquecerFila(array $alerta): array
    {
        $tipo = (string) ($alerta['tipo'] ?? '');
        $vehiculoId = (int) ($alerta['vehiculo_id'] ?? 0);
        $catalogo = $this->repo->getAlertaConfig($tipo);
        $alerta['servicio_nombre'] = (string) ($catalogo['nombre'] ?? mantenimiento_servicio_label($tipo));

        if ($catalogo !== null && ($catalogo['unidad'] ?? '') === 'km') {
            $vehiculo = $this->vehiculos->findById($vehiculoId);
            $ultimo = $this->mantenimientos->getUltimoPorServicio($vehiculoId, $tipo);
            $fechas = alerta_mantenimiento_fechas($ultimo);
            $alerta['categoria'] = 'mantenimiento';
            $alerta['fecha_ultimo_mantenimiento'] = $fechas['ultima'];
            $alerta['fecha_proximo_mantenimiento'] = $fechas['proxima'];
            $alerta['proximo_km'] = alerta_proximo_km($ultimo);
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
