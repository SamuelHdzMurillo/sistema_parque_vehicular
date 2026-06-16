<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\CombustibleRepository;
use App\Repositories\VehiculoRepository;

final class CombustibleService
{
    public function __construct(
        private readonly CombustibleRepository $repo = new CombustibleRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly AlertaRepository $alertas = new AlertaRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
    ) {
    }

    public function paginate(int $page = 1, ?int $vehiculoId = null): array
    {
        $filters = array_filter(['vehiculo_id' => $vehiculoId]);
        return $this->repo->paginate($page, 15, $filters);
    }

    public function getFormData(?int $vehiculoId = null): array
    {
        $vehiculos = $this->catalogos->getVehiculosDisponibles();
        if ($vehiculoId !== null) {
            $ids = array_map('intval', array_column($vehiculos, 'id'));
            if (!in_array($vehiculoId, $ids, true)) {
                $vehiculo = $this->vehiculos->findById($vehiculoId);
                if ($vehiculo !== null) {
                    $vehiculos[] = [
                        'id' => (int) $vehiculo['id'],
                        'numero_economico' => $vehiculo['numero_economico'],
                        'marca' => $vehiculo['marca'],
                        'modelo' => $vehiculo['modelo'],
                        'placas' => $vehiculo['placas'],
                        'kilometraje_actual' => (int) $vehiculo['kilometraje_actual'],
                        'estado' => $vehiculo['estado'],
                    ];
                }
            }
        }

        return [
            'vehiculos' => $vehiculos,
            'proveedores' => $this->catalogos->getProveedores('combustible'),
        ];
    }

    public function create(array $data, int $userId): int
    {
        $data['registrado_por'] = $userId;
        return $this->registrarCarga($data);
    }

    public function registrarCarga(array $data): int
    {
        $vehiculoId = (int) $data['vehiculo_id'];
        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo === null) {
            throw new \RuntimeException('Vehículo no encontrado');
        }
        $kilometraje = (int) $data['kilometraje'];
        $kmActual = (int) $vehiculo['kilometraje_actual'];
        if ($kilometraje < $kmActual) {
            throw new \RuntimeException(
                'El kilometraje al cargar (' . number_format($kilometraje) . ' km) no puede ser menor al actual del vehículo (' . number_format($kmActual) . ' km).'
            );
        }
        $litros = (float) $data['litros'];
        $metricas = $this->repo->calcularRendimiento($vehiculoId, $kilometraje, $litros);
        if ($metricas !== null) {
            $importe = (float) $data['importe'];
            $data['rendimiento'] = $metricas['rendimiento'];
            $data['costo_por_km'] = $metricas['km_recorridos'] > 0 ? round($importe / $metricas['km_recorridos'], 4) : null;
        }
        $id = $this->repo->create($data);
        $this->vehiculos->updateKilometraje($vehiculoId, $kilometraje, auth_id());
        AuditService::log('CREATE', 'combustible_cargas', $id, null, $data);
        return $id;
    }
}
