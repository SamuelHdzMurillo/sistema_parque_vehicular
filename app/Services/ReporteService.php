<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\ExporterFactory;
use App\Repositories\CombustibleRepository;
use App\Repositories\ComisionRepository;
use App\Repositories\DanioRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class ReporteService
{
    public function __construct(
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly ComisionRepository $comisiones = new ComisionRepository(),
        private readonly MantenimientoRepository $mantenimientos = new MantenimientoRepository(),
        private readonly CombustibleRepository $combustible = new CombustibleRepository(),
        private readonly DanioRepository $danios = new DanioRepository(),
        private readonly DocumentoRepository $documentos = new DocumentoRepository(),
        private readonly DashboardRepository $dashboard = new DashboardRepository(),
    ) {
    }

    public function getAvailableTypes(): array
    {
        return [
            'vehiculos' => 'Inventario vehicular',
            'comisiones' => 'Comisiones',
            'mantenimiento' => 'Mantenimiento',
            'combustible' => 'Combustible',
            'danios' => 'Daños',
            'documentacion' => 'Documentación',
            'costos' => 'Costos consolidados',
            'kpi' => 'Indicadores KPI',
        ];
    }

    public function getReportData(string $tipo): array
    {
        $internal = $tipo === 'vehiculos' ? 'inventario' : $tipo;
        [, $headers, $rows] = $this->buildReportData($internal, []);
        return ['headers' => $headers, 'rows' => $rows];
    }

    public function export(string $tipo, string $format, array $filters = []): never
    {
        $internal = $tipo === 'vehiculos' ? 'inventario' : $tipo;
        [$title, $headers, $rows] = $this->buildReportData($internal, $filters);
        $exporter = ExporterFactory::make($format);
        $filename = $tipo . '_' . date('Ymd_His');
        $path = $exporter->export($title, $headers, $rows, $filename);

        AuditService::log('EXPORT', 'reportes', null, null, [
            'tipo' => $tipo,
            'formato' => $format,
            'registros' => count($rows),
        ]);

        header('Content-Type: ' . $exporter->contentType());
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * @return array{0: string, 1: list<string>, 2: list<array<string, mixed>>}
     */
    private function buildReportData(string $tipo, array $filters): array
    {
        return match ($tipo) {
            'inventario' => $this->reporteInventario($filters),
            'comisiones' => $this->reporteComisiones($filters),
            'mantenimiento' => $this->reporteMantenimiento($filters),
            'combustible' => $this->reporteCombustible($filters),
            'danios' => $this->reporteDanios($filters),
            'documentacion' => $this->reporteDocumentacion($filters),
            'costos' => $this->reporteCostos($filters),
            'kpi' => $this->reporteKpi($filters),
            default => throw new \InvalidArgumentException("Tipo de reporte no soportado: {$tipo}"),
        };
    }

    private function reporteInventario(array $filters): array
    {
        $headers = ['numero_economico', 'marca', 'modelo', 'placas', 'estado', 'area_nombre', 'responsable_nombre', 'kilometraje_actual'];
        $result = $this->vehiculos->paginate(1, 5000, $filters);
        return ['Inventario Vehicular', $headers, $result['data']];
    }

    private function reporteComisiones(array $filters): array
    {
        $headers = ['folio', 'fecha', 'numero_economico', 'destino', 'estado', 'km_recorridos', 'rendimiento', 'area_nombre'];
        $result = $this->comisiones->paginate(1, 5000, $filters);
        return ['Reporte de Comisiones', $headers, $result['data']];
    }

    private function reporteMantenimiento(array $filters): array
    {
        $headers = ['folio', 'tipo', 'fecha', 'numero_economico', 'costo', 'estado', 'proveedor'];
        $result = $this->mantenimientos->paginate(1, 5000, $filters);
        return ['Reporte de Mantenimiento', $headers, $result['data']];
    }

    private function reporteCombustible(array $filters): array
    {
        $headers = ['fecha', 'numero_economico', 'litros', 'importe', 'kilometraje', 'rendimiento', 'costo_por_km'];
        $result = $this->combustible->paginate(1, 5000, $filters);
        return ['Reporte de Combustible', $headers, $result['data']];
    }

    private function reporteDanios(array $filters): array
    {
        $headers = ['tipo_dano', 'ubicacion', 'estado', 'numero_economico', 'placas', 'created_at'];
        $result = $this->danios->paginate(1, 5000, $filters);
        return ['Reporte de Daños', $headers, $result['data']];
    }

    private function reporteDocumentacion(array $filters): array
    {
        $headers = ['tipo', 'titulo', 'numero_documento', 'fecha_vencimiento', 'numero_economico', 'dias_restantes', 'version'];
        $result = $this->documentos->paginate(1, 5000, $filters);
        return ['Reporte de Documentación', $headers, $result['data']];
    }

    private function reporteCostos(array $filters): array
    {
        $headers = ['numero_economico', 'costo_mantenimiento', 'costo_combustible', 'costo_total'];
        $rows = $this->dashboard->getTopVehiculosCostosos(100);
        if (!empty($filters['vehiculo_id'])) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $r) => (int) $r['vehiculo_id'] === (int) $filters['vehiculo_id']
            ));
        }
        return ['Costos Consolidados por Vehículo', $headers, $rows];
    }

    private function reporteKpi(array $filters): array
    {
        $year = isset($filters['year']) ? (int) $filters['year'] : null;
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $kpis = $this->dashboard->getAllKpis($year, $month);

        $headers = ['indicador', 'valor'];
        $rows = [
            ['indicador' => 'Vehículos activos', 'valor' => $kpis['vehiculos_activos']],
            ['indicador' => 'En taller', 'valor' => $kpis['en_taller']],
            ['indicador' => 'En mantenimiento', 'valor' => $kpis['en_mantenimiento']],
            ['indicador' => 'Gastos mantenimiento', 'valor' => $kpis['gastos']['mantenimiento']],
            ['indicador' => 'Gastos combustible', 'valor' => $kpis['gastos']['combustible']],
            ['indicador' => 'Gastos totales', 'valor' => $kpis['gastos']['total']],
            ['indicador' => 'Litros consumidos', 'valor' => $kpis['combustible']['litros']],
            ['indicador' => 'Servicios pendientes', 'valor' => $kpis['servicios_pendientes']],
            ['indicador' => 'Docs por vencer', 'valor' => $kpis['docs_por_vencer']],
            ['indicador' => 'Alertas rojas', 'valor' => $kpis['alertas']['rojo']],
            ['indicador' => 'Alertas amarillas', 'valor' => $kpis['alertas']['amarillo']],
        ];

        return ['Indicadores KPI', $headers, $rows];
    }
}
