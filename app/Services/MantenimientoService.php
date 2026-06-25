<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\AlertaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class MantenimientoService
{
    public function __construct(
        private readonly MantenimientoRepository $repo = new MantenimientoRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
        private readonly AlertaRepository $alertas = new AlertaRepository(),
        private readonly AlertaService $alertaService = new AlertaService(),
    ) {
    }

    public function paginate(int $page = 1, ?string $estado = null): array
    {
        return $this->repo->paginate($page, 15, array_filter(['estado' => $estado]));
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->catalogos->getVehiculosOperativos(),
            'proveedores' => $this->catalogos->getProveedores(),
            'responsables' => $this->catalogos->getUsersForSelect(),
            'areas' => $this->catalogos->getAreas(),
            'planteles' => $this->catalogos->getPlanteles(),
            'tipos' => ['preventivo', 'correctivo', 'predictivo'],
            'servicios' => $this->alertas->getServiciosKm(),
            'estados' => ['pendiente', 'programado', 'autorizado', 'en_proceso', 'finalizado', 'cancelado'],
            'folio_sugerido' => $this->repo->generateFolio(),
            'puede_agregar_servicio' => can('mantenimiento.create'),
        ];
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data, int $userId): int
    {
        $data = $this->normalizeHistorico($data);
        $data = $this->normalizeServicios($data);
        $this->assertKilometrajeValido($data);
        $files = $this->extractFiles($data);
        $servicios = $data['servicios'] ?? [];
        $intervalos = $this->parseIntervalosFromData($data);
        if (($data['tipo'] ?? '') === 'preventivo') {
            $this->assertIntervalosValidos($servicios, $intervalos);
        }
        $data['folio'] = $this->repo->generateFolio();
        $data['created_by'] = $userId;
        $data['responsable_id'] = (int) ($data['responsable_id'] ?? $userId);
        $data['estado'] = !empty($data['es_historico']) ? 'finalizado' : ($data['estado'] ?? 'pendiente');
        $id = $this->repo->create($data);
        $this->repo->syncServicios($id, $servicios, $intervalos);

        $rutas = $this->storeFacturaFiles($id, $files);
        if ($rutas !== []) {
            $mant = $this->repo->findById($id);
            if ($mant !== null) {
                $this->repo->update($id, array_merge($mant, $rutas));
            }
        }

        if (($data['estado'] ?? '') === 'finalizado') {
            $mant = $this->repo->findById($id);
            if ($mant !== null) {
                $this->alertaService->registrarMantenimientoFinalizado($mant, $userId);
            }
        }

        AuditService::log('CREATE', 'mantenimientos', $id, null, $data);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $data = $this->normalizeHistorico(array_merge($before, $data));
        $data = $this->normalizeServicios($data);
        $this->assertKilometrajeValido($data);
        $files = $this->extractFiles($data);
        $servicios = $data['servicios'] ?? [];
        $intervalos = $this->parseIntervalosFromData($data);
        if (($data['tipo'] ?? '') === 'preventivo') {
            $this->assertIntervalosValidos($servicios, $intervalos);
        }
        $rutas = $this->storeFacturaFiles($id, $files);
        $result = $this->repo->update($id, array_merge($before, $data, $rutas));
        if ($result) {
            $this->repo->syncServicios($id, $servicios, $intervalos);
            if (($data['estado'] ?? '') === 'finalizado') {
                $finalizado = $this->repo->findById($id);
                $userId = auth_id() ?? (int) ($before['responsable_id'] ?? 0);
                if ($finalizado !== null && $userId > 0) {
                    $this->alertaService->registrarMantenimientoFinalizado($finalizado, $userId);
                }
            }
            AuditService::log('UPDATE', 'mantenimientos', $id, $before, array_merge($data, $rutas));
        }
        return $result;
    }

    /**
     * Separa los archivos subidos del resto de datos del formulario.
     *
     * @return array{factura?: array, xml?: array}
     */
    private function extractFiles(array &$data): array
    {
        $files = [];
        foreach (['factura', 'xml'] as $key) {
            $file = $data['archivo_' . $key] ?? null;
            unset($data['archivo_' . $key]);
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $files[$key] = $file;
            }
        }
        return $files;
    }

    /**
     * Sube los archivos de factura (PDF/XML) y devuelve las rutas a persistir.
     *
     * @param array{factura?: array, xml?: array} $files
     * @return array{factura_ruta?: string, xml_ruta?: string}
     */
    private function storeFacturaFiles(int $id, array $files): array
    {
        $rutas = [];
        if (isset($files['factura'])) {
            $ruta = FileUploader::uploadDocument($files['factura'], 'mantenimientos/' . $id);
            if ($ruta !== null) {
                $rutas['factura_ruta'] = $ruta;
            }
        }
        if (isset($files['xml'])) {
            $ruta = FileUploader::uploadDocument($files['xml'], 'mantenimientos/' . $id);
            if ($ruta !== null) {
                $rutas['xml_ruta'] = $ruta;
            }
        }
        return $rutas;
    }

    public function autorizar(int $id, int $userId): ?string
    {
        try {
            $mant = $this->repo->findById($id);
            if ($mant === null) {
                return 'Mantenimiento no encontrado.';
            }
            if (!in_array($mant['estado'], ['pendiente', 'programado'], true)) {
                return 'Estado no válido para autorizar.';
            }
            $this->repo->authorize($id, $userId);
            return null;
        } catch (\Throwable $e) {
            return user_facing_error($e, 'No se pudo autorizar el mantenimiento.');
        }
    }

    public function finalizar(int $id): ?string
    {
        try {
            $mant = $this->repo->findById($id);
            if ($mant === null) {
                return 'Mantenimiento no encontrado.';
            }
            if (!in_array($mant['estado'], ['autorizado', 'en_proceso'], true)) {
                return 'No se puede finalizar en este estado.';
            }
            $this->repo->update($id, array_merge($mant, ['estado' => 'finalizado']));
            $userId = auth_id() ?? (int) ($mant['responsable_id'] ?? 0);
            if (empty($mant['es_historico'])) {
                $vehiculoId = (int) $mant['vehiculo_id'];
                $this->vehiculos->updateKilometraje($vehiculoId, (int) $mant['kilometraje'], auth_id());
                $this->vehiculos->updateEstado($vehiculoId, 'disponible', 'Fin mantenimiento ' . $mant['folio'], auth_id());
            }
            $finalizado = $this->repo->findById($id);
            if ($finalizado !== null && $userId > 0) {
                $this->alertaService->registrarMantenimientoFinalizado($finalizado, $userId);
            }
            return null;
        } catch (\Throwable $e) {
            return user_facing_error($e, 'No se pudo finalizar el mantenimiento.');
        }
    }

    public function eliminar(int $id): ?string
    {
        try {
            $mant = $this->repo->findById($id);
            if ($mant === null) {
                return 'Mantenimiento no encontrado.';
            }

            $this->repo->beginTransaction();

            if (($mant['estado'] ?? '') === 'finalizado' && empty($mant['es_historico'])) {
                $this->revertirEfectosFinalizado($mant);
            }

            $this->eliminarArchivosMantenimiento($mant);

            if (!$this->repo->delete($id)) {
                throw new \RuntimeException('No se pudo eliminar el mantenimiento.');
            }

            $this->repo->commit();

            if (($mant['estado'] ?? '') === 'finalizado') {
                $this->alertaService->sincronizar();
            }

            AuditService::log('DELETE', 'mantenimientos', $id, $mant, null);
            return null;
        } catch (\InvalidArgumentException $e) {
            $this->repo->rollBack();
            return $e->getMessage();
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            return user_facing_error($e, 'No se pudo eliminar el mantenimiento.');
        }
    }

    /** @param array<string, mixed> $mant */
    private function revertirEfectosFinalizado(array $mant): void
    {
        $vehiculoId = (int) ($mant['vehiculo_id'] ?? 0);
        $kmMant = (int) ($mant['kilometraje'] ?? 0);
        if ($vehiculoId <= 0 || $kmMant <= 0) {
            return;
        }

        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo === null) {
            throw new \InvalidArgumentException('Vehículo del mantenimiento no encontrado.');
        }

        $kmActual = (int) $vehiculo['kilometraje_actual'];
        if ($kmActual !== $kmMant) {
            return;
        }

        $kmAnterior = $this->repo->getMaxKmOperativoExcluding($vehiculoId, (int) $mant['id']);
        if (!$this->vehiculos->setKilometraje($vehiculoId, $kmAnterior, auth_id())) {
            throw new \RuntimeException('No se pudo revertir el kilometraje del vehículo.');
        }
    }

    /** @param array<string, mixed> $mant */
    private function eliminarArchivosMantenimiento(array $mant): void
    {
        foreach (['factura_ruta', 'xml_ruta', 'pdf_ruta'] as $campo) {
            $ruta = trim((string) ($mant[$campo] ?? ''));
            if ($ruta === '') {
                continue;
            }
            $path = storage_path('uploads/' . ltrim($ruta, '/'));
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $dir = storage_path('uploads/mantenimientos/' . (int) ($mant['id'] ?? 0));
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($dir);
        }
    }

    private function normalizeServicios(array $data): array
    {
        $serviciosRaw = $data['servicios'] ?? [];
        if (!is_array($serviciosRaw)) {
            $serviciosRaw = $serviciosRaw !== null && $serviciosRaw !== '' ? [(string) $serviciosRaw] : [];
        }
        if ($serviciosRaw === [] && !empty($data['servicio'])) {
            $serviciosRaw = [(string) $data['servicio']];
        }

        $validos = array_column($this->alertas->getServiciosKm(), 'tipo');
        $servicios = [];
        foreach ($serviciosRaw as $item) {
            $item = trim((string) $item);
            if ($item === '' || !in_array($item, $validos, true)) {
                continue;
            }
            if (!in_array($item, $servicios, true)) {
                $servicios[] = $item;
            }
        }

        if (($data['tipo'] ?? '') === 'preventivo') {
            if ($servicios === []) {
                throw new \RuntimeException(
                    'Seleccione al menos un servicio preventivo (cambio de aceite, afinación, llantas…).'
                );
            }
            $data['servicios'] = $servicios;
            $data['servicio'] = $servicios[0];
        } else {
            $data['servicios'] = [];
            $data['servicio'] = null;
        }

        return $data;
    }

    private function normalizeHistorico(array $data): array
    {
        $data['es_historico'] = !empty($data['es_historico']) ? 1 : 0;
        return $data;
    }

    private function assertKilometrajeValido(array $data): void
    {
        if (!empty($data['es_historico'])) {
            return;
        }

        $vehiculoId = (int) ($data['vehiculo_id'] ?? 0);
        if ($vehiculoId <= 0) {
            throw new \RuntimeException('Seleccione un vehículo.');
        }

        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo === null) {
            throw new \RuntimeException('Vehículo no encontrado.');
        }

        $km = (int) ($data['kilometraje'] ?? 0);
        $kmActual = (int) $vehiculo['kilometraje_actual'];
        if ($km < $kmActual) {
            throw new \RuntimeException(
                'El kilometraje (' . number_format($km) . ' km) no puede ser menor al actual del vehículo ('
                . number_format($kmActual) . ' km). Marque «Mantenimiento anterior al kilometraje actual» si el servicio fue con menor kilometraje.'
            );
        }
    }

    /**
     * @return array<string, array{intervalo_km: ?int, intervalo_dias: ?int}>
     */
    private function parseIntervalosFromData(array $data): array
    {
        $raw = $data['intervalos'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $servicio => $vals) {
            if (!is_array($vals)) {
                continue;
            }
            $servicio = trim((string) $servicio);
            if ($servicio === '') {
                continue;
            }

            $km = isset($vals['km']) && $vals['km'] !== '' ? (int) $vals['km'] : null;
            $meses = isset($vals['meses']) && $vals['meses'] !== '' ? (int) $vals['meses'] : null;
            $dias = ($meses !== null && $meses > 0) ? $meses * 30 : null;

            $result[$servicio] = [
                'intervalo_km' => ($km !== null && $km > 0) ? $km : null,
                'intervalo_dias' => $dias,
            ];
        }

        return $result;
    }

    /**
     * @param list<string> $servicios
     * @param array<string, array{intervalo_km: ?int, intervalo_dias: ?int}> $intervalos
     */
    private function assertIntervalosValidos(array $servicios, array $intervalos): void
    {
        foreach ($servicios as $servicio) {
            $cfg = $intervalos[$servicio] ?? [];
            $km = (int) ($cfg['intervalo_km'] ?? 0);
            $dias = (int) ($cfg['intervalo_dias'] ?? 0);
            if ($km <= 0 && $dias <= 0) {
                throw new \RuntimeException(
                    'Indique en cuántos kilómetros o meses toca el próximo servicio de «'
                    . mantenimiento_servicio_label($servicio) . '».'
                );
            }
        }
    }
}
