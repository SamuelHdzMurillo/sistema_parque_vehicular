<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\AlertaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\InspeccionRepository;
use App\Repositories\VehiculoRepository;

final class InspeccionService
{
    public function __construct(
        private readonly InspeccionRepository $repo = new InspeccionRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly AlertaRepository $alertas = new AlertaRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
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
        return ['inspeccion' => $data];
    }

    public function create(array $data, int $userId): int
    {
        $items = $this->parseItems($data);
        $lucesTablero = $this->parseLucesTablero($data);
        $data['responsable_id'] = $userId;
        if (!empty($data['firma_data'])) {
            $data['firma_digital'] = FileUploader::saveBase64Signature((string) $data['firma_data'], 'firmas/inspecciones');
        }
        $data['resultado_general'] = $this->calcularResultadoGeneral($items);
        $id = $this->repo->createWithItems($data, $items, $lucesTablero);
        $this->vehiculos->updateKilometraje((int) $data['vehiculo_id'], (int) $data['kilometraje'], $userId);
        $this->vehiculos->syncLucesTablero((int) $data['vehiculo_id'], $lucesTablero, 'inspeccion', $id);
        $this->generarAlertas((int) $data['vehiculo_id'], $id, $items);
        AuditService::log('CREATE', 'inspecciones', $id, null, ['vehiculo_id' => $data['vehiculo_id']]);
        return $id;
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
}
