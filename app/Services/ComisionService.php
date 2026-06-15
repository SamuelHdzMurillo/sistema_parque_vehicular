<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\CatalogoRepository;
use App\Repositories\ComisionRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\VehiculoRepository;

final class ComisionService
{
    public function __construct(
        private readonly ComisionRepository $repo = new ComisionRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly DocumentoRepository $documentos = new DocumentoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
    ) {
    }

    public function paginate(int $page = 1, ?string $estado = null): array
    {
        $filters = array_filter(['estado' => $estado]);
        return $this->repo->paginate($page, 15, $filters);
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->catalogos->getVehiculosDisponibles(),
            'areas' => $this->catalogos->getAreas(),
            'conductores' => $this->catalogos->getUsersForSelect(),
        ];
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data, int $userId): int
    {
        $data['created_by'] = $userId;
        $data['responsable_id'] = (int) ($data['responsable_id'] ?? $userId);
        $data['folio'] = $this->repo->generateFolio();
        $data['estado'] = 'borrador';
        $id = $this->repo->create($data);
        AuditService::log('CREATE', 'comisiones', $id, null, $data);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null || !in_array($before['estado'], ['borrador', 'en_curso'], true)) {
            return false;
        }
        $result = $this->repo->update($id, array_merge($before, $data));
        if ($result) {
            AuditService::log('UPDATE', 'comisiones', $id, $before, $data);
        }
        return $result;
    }

    public function iniciar(int $id): ?string
    {
        try {
            $comision = $this->repo->findById($id);
            if ($comision === null) {
                return 'Comisión no encontrada.';
            }
            $this->validateInicio((int) $comision['vehiculo_id'], (int) $comision['km_salida'], $id);
            $this->repo->update($id, array_merge($comision, ['estado' => 'en_curso']));
            $this->vehiculos->updateEstado((int) $comision['vehiculo_id'], 'en_comision', 'Comisión ' . $comision['folio'], auth_id());
            AuditService::log('UPDATE', 'comisiones', $id, ['estado' => 'borrador'], ['estado' => 'en_curso']);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function finalizar(int $id, array $data): ?string
    {
        try {
            $comision = $this->repo->findById($id);
            if ($comision === null || $comision['estado'] !== 'en_curso') {
                return 'Comisión no válida para finalizar.';
            }
            $merged = array_merge($comision, $data);
            if (empty($merged['hora_regreso']) || empty($merged['km_regreso']) || $merged['combustible_regreso'] === '') {
                return 'Complete hora regreso, km regreso y combustible regreso.';
            }
            if (!empty($data['firma_data'])) {
                $merged['firma_digital'] = FileUploader::saveBase64Signature((string) $data['firma_data'], 'firmas/comisiones');
            }
            $vehiculo = $this->vehiculos->findById((int) $comision['vehiculo_id']);
            $capacidad = (float) ($vehiculo['capacidad_tanque'] ?? 80);
            $metricas = $this->repo->calcularMetricas($merged, $capacidad);
            $merged = array_merge($merged, $metricas, ['estado' => 'finalizada']);
            $this->repo->update($id, $merged);
            $this->vehiculos->updateKilometraje((int) $comision['vehiculo_id'], (int) $merged['km_regreso'], auth_id());
            $this->vehiculos->updateEstado((int) $comision['vehiculo_id'], 'disponible', 'Fin comisión ' . $comision['folio'], auth_id());
            AuditService::log('UPDATE', 'comisiones', $id, ['estado' => 'en_curso'], ['estado' => 'finalizada']);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function cancelar(int $id, string $motivo): ?string
    {
        try {
            $comision = $this->repo->findById($id);
            if ($comision === null) {
                return 'Comisión no encontrada.';
            }
            if (!in_array($comision['estado'], ['borrador', 'en_curso'], true)) {
                return 'No se puede cancelar esta comisión.';
            }
            $this->repo->update($id, array_merge($comision, ['estado' => 'cancelada', 'observaciones' => $motivo]));
            if ($comision['estado'] === 'en_curso') {
                $this->vehiculos->updateEstado((int) $comision['vehiculo_id'], 'disponible', 'Cancelación ' . $comision['folio'], auth_id());
            }
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    private function validateInicio(int $vehiculoId, int $kmSalida, ?int $excludeId = null): void
    {
        if (!$this->vehiculos->isAvailableForComision($vehiculoId)) {
            throw new \InvalidArgumentException('El vehículo no está disponible.');
        }
        if ($this->repo->hasActiveComision($vehiculoId, $excludeId)) {
            throw new \InvalidArgumentException('El vehículo ya tiene una comisión activa.');
        }
        if ($this->documentos->hasDocumentosCriticosVencidos($vehiculoId)) {
            throw new \InvalidArgumentException('Documentos críticos vencidos.');
        }
        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo && $kmSalida < (int) $vehiculo['kilometraje_actual']) {
            throw new \InvalidArgumentException('Km salida menor al actual del vehículo.');
        }
    }
}
