<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\CatalogoRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class MantenimientoService
{
    public function __construct(
        private readonly MantenimientoRepository $repo = new MantenimientoRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
    ) {
    }

    public function paginate(int $page = 1, ?string $estado = null): array
    {
        return $this->repo->paginate($page, 15, array_filter(['estado' => $estado]));
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->catalogos->getVehiculosDisponibles(),
            'proveedores' => $this->catalogos->getProveedores(),
            'responsables' => $this->catalogos->getUsersForSelect(),
            'tipos' => ['preventivo', 'correctivo', 'predictivo'],
            'estados' => ['pendiente', 'programado', 'autorizado', 'en_proceso', 'finalizado', 'cancelado'],
        ];
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data, int $userId): int
    {
        $files = $this->extractFiles($data);
        $data['folio'] = $this->repo->generateFolio();
        $data['created_by'] = $userId;
        $data['responsable_id'] = (int) ($data['responsable_id'] ?? $userId);
        $data['estado'] = $data['estado'] ?? 'pendiente';
        $id = $this->repo->create($data);

        $rutas = $this->storeFacturaFiles($id, $files);
        if ($rutas !== []) {
            $mant = $this->repo->findById($id);
            if ($mant !== null) {
                $this->repo->update($id, array_merge($mant, $rutas));
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
        $files = $this->extractFiles($data);
        $rutas = $this->storeFacturaFiles($id, $files);
        $result = $this->repo->update($id, array_merge($before, $data, $rutas));
        if ($result) {
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
            return $e->getMessage();
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
            $vehiculoId = (int) $mant['vehiculo_id'];
            $this->vehiculos->updateKilometraje($vehiculoId, (int) $mant['kilometraje'], auth_id());
            $this->vehiculos->updateEstado($vehiculoId, 'disponible', 'Fin mantenimiento ' . $mant['folio'], auth_id());
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
