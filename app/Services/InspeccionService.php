<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\AlertaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\DanioRepository;
use App\Repositories\InspeccionRepository;
use App\Repositories\VehiculoRepository;

final class InspeccionService
{
    public function __construct(
        private readonly InspeccionRepository $repo = new InspeccionRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly AlertaRepository $alertas = new AlertaRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
        private readonly DanioRepository $danios = new DanioRepository(),
    ) {
    }

    public function paginate(int $page = 1): array
    {
        return $this->repo->paginate($page);
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->catalogos->getVehiculosOperativos(),
            'items' => InspeccionRepository::INSPECCION_ITEMS,
            'luces_tablero' => InspeccionRepository::LUCES_TABLERO,
        ];
    }

    public function getFormDataForCreate(?int $vehiculoId = null): array
    {
        $data = $this->getFormData();
        if ($vehiculoId !== null && $vehiculoId > 0) {
            $data['vehiculo_luces_preset'] = $this->vehiculos->getLucesTablero($vehiculoId);
        }

        return $data;
    }

    public function find(int $id): ?array
    {
        $data = $this->repo->findWithItems($id);
        if ($data === null) {
            return null;
        }
        $hasta = (string) ($data['created_at'] ?? ($data['fecha'] . ' 23:59:59'));
        $daniosAbiertos = $this->danios->getAbiertosPorVehiculo((int) $data['vehiculo_id'], $hasta);

        return ['inspeccion' => $data, 'danios_abiertos' => $daniosAbiertos];
    }

    public function eliminar(int $id): ?string
    {
        try {
            $inspeccion = $this->repo->findWithItems($id);
            if ($inspeccion === null) {
                return 'Inspección no encontrada.';
            }

            $this->repo->beginTransaction();

            $this->vehiculos->clearLucesTableroIfOrigin(
                (int) $inspeccion['vehiculo_id'],
                'inspeccion',
                $id
            );

            $this->eliminarArchivosInspeccion($inspeccion);

            if (!$this->repo->delete($id)) {
                throw new \RuntimeException('No se pudo eliminar la inspección.');
            }

            $this->repo->commit();
            AuditService::log('DELETE', 'inspecciones', $id, $inspeccion, null);
            return null;
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            return user_facing_error($e, 'No se pudo eliminar la inspección.');
        }
    }

    public function create(array $data, int $userId): int
    {
        $data = $this->normalizeHistorico($data);
        $this->assertKilometrajeValido($data);
        $items = $this->parseItems($data);
        $lucesTablero = $this->parseLucesTablero($data);
        $data['responsable_id'] = $userId;
        if (!empty($data['firma_data'])) {
            $data['firma_digital'] = FileUploader::saveBase64Signature((string) $data['firma_data'], 'firmas/inspecciones');
        }
        $data['resultado_general'] = $this->calcularResultadoGeneral($items);
        $data['folio'] = $this->repo->generateFolio();
        $data['nivel_combustible'] = $this->parseNivelCombustible($data);
        $id = $this->repo->createWithItems($data, $items, $lucesTablero);
        if (empty($data['es_historico'])) {
            $this->vehiculos->updateKilometraje((int) $data['vehiculo_id'], (int) $data['kilometraje'], $userId);
            $this->vehiculos->syncLucesTablero((int) $data['vehiculo_id'], $lucesTablero, 'inspeccion', $id);
            $this->generarAlertas((int) $data['vehiculo_id'], $id, $items);
        }
        AuditService::log('CREATE', 'inspecciones', $id, null, ['vehiculo_id' => $data['vehiculo_id']]);
        return $id;
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
                . number_format($kmActual) . ' km). Marque «Inspección olvidada» si la inspección fue con menor kilometraje.'
            );
        }
    }

    private function parseItems(array $data): array
    {
        $items = [];
        foreach (InspeccionRepository::INSPECCION_ITEMS as $item) {
            $codigo = $item['codigo'];
            $items[] = [
                'item_codigo' => $codigo,
                'item_nombre' => $item['nombre'],
                'calificacion' => $data['items'][$codigo] ?? 'bueno',
                'observaciones' => $data['obs_items'][$codigo] ?? null,
            ];
        }
        return $items;
    }

    private function parseLucesTablero(array $data): array
    {
        $selected = $data['luces_tablero'] ?? [];
        if (!is_array($selected)) {
            return [];
        }

        $validCodes = array_column(InspeccionRepository::LUCES_TABLERO, 'codigo');
        $luces = [];
        foreach ($selected as $codigo) {
            $codigo = (string) $codigo;
            if (in_array($codigo, $validCodes, true)) {
                $luces[] = $codigo;
            }
        }

        return array_values(array_unique($luces));
    }

    private function parseNivelCombustible(array $data): ?float
    {
        $raw = trim((string) ($data['nivel_combustible'] ?? ''));
        if ($raw === '') {
            return null;
        }

        $porcentaje = combustible_fraccion_a_porcentaje($raw);
        if ($porcentaje === null) {
            throw new \InvalidArgumentException(
                'El combustible debe indicarse en porcentaje: 0, 25, 50, 75 o 100.'
            );
        }

        return $porcentaje;
    }

    private function calcularResultadoGeneral(array $items): string
    {
        $malos = $regulares = 0;
        foreach ($items as $item) {
            if ($item['calificacion'] === 'malo') {
                $malos++;
            } elseif ($item['calificacion'] === 'regular') {
                $regulares++;
            }
        }
        if ($malos > 0) {
            return 'rechazada';
        }
        if ($regulares >= 3) {
            return 'condicionada';
        }
        return 'aprobada';
    }

    private function generarAlertas(int $vehiculoId, int $inspeccionId, array $items): void
    {
        $vehiculo = $this->vehiculos->findById($vehiculoId);
        foreach ($items as $item) {
            if ($item['calificacion'] === 'malo') {
                $tipo = 'inspeccion_' . $item['item_codigo'];
                if (!$this->alertas->existsActive($vehiculoId, $tipo)) {
                    $this->alertas->create([
                        'vehiculo_id' => $vehiculoId,
                        'tipo' => $tipo,
                        'titulo' => 'Inspección: ' . $item['item_nombre'] . ' en mal estado',
                        'mensaje' => 'Vehículo ' . ($vehiculo['numero_economico'] ?? $vehiculoId) . ' — ítem MALO.',
                        'nivel' => 'rojo',
                    ]);
                }
            }
        }
    }

    /** @param array<string, mixed> $inspeccion */
    private function eliminarArchivosInspeccion(array $inspeccion): void
    {
        $ruta = trim((string) ($inspeccion['firma_digital'] ?? ''));
        if ($ruta !== '') {
            $path = storage_path('uploads/' . ltrim($ruta, '/'));
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ($inspeccion['fotos'] ?? [] as $foto) {
            $rutaFoto = trim((string) ($foto['ruta'] ?? ''));
            if ($rutaFoto === '') {
                continue;
            }
            $path = storage_path('uploads/' . ltrim($rutaFoto, '/'));
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
