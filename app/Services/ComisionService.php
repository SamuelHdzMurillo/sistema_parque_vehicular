<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\CatalogoRepository;
use App\Repositories\ComisionRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\InspeccionRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class ComisionService
{
    public function __construct(
        private readonly ComisionRepository $repo = new ComisionRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly DocumentoRepository $documentos = new DocumentoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
        private readonly MantenimientoRepository $mantenimientos = new MantenimientoRepository(),
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
            'vehiculos' => $this->catalogos->getVehiculosCatalogo(),
            'areas' => $this->catalogos->getAreas(),
            'conductores' => $this->catalogos->getConductores(),
            'usuarios' => $this->catalogos->getUsersForSelect(),
            'luces_tablero' => InspeccionRepository::LUCES_TABLERO,
            'liquidos' => ComisionRepository::LIQUIDOS,
            'nivel_opciones' => ComisionRepository::NIVEL_OPCIONES,
        ];
    }

    public function find(int $id): ?array
    {
        $comision = $this->repo->findById($id);
        if ($comision === null) {
            return null;
        }
        $luces = $this->repo->getLuces($id);
        $comision['luces_salida'] = $luces['salida'];
        $comision['luces_regreso'] = $luces['regreso'];
        $niveles = $this->repo->getNiveles($id);
        $comision['niveles_salida'] = $niveles['salida'];
        $comision['niveles_regreso'] = $niveles['regreso'];
        return $comision;
    }

    public function getUltimoMantenimiento(int $vehiculoId): ?array
    {
        return $this->mantenimientos->getUltimoFinalizado($vehiculoId);
    }

    public function getLucesCatalog(): array
    {
        return InspeccionRepository::LUCES_TABLERO;
    }

    public function getLiquidosCatalog(): array
    {
        return ComisionRepository::LIQUIDOS;
    }

    public function getNivelOpciones(): array
    {
        return ComisionRepository::NIVEL_OPCIONES;
    }

    public function create(array $data, int $userId): int
    {
        $data['created_by'] = $userId;
        $data['responsable_id'] = (int) ($data['responsable_id'] ?? $userId);
        $data = $this->normalizeResponsableRegreso($data);
        $data = $this->normalizeConductor($data);
        $data['folio'] = $this->repo->generateFolio();
        $data['estado'] = 'borrador';
        $id = $this->repo->create($data);
        $this->repo->saveLuces($id, 'salida', $this->parseLuces($data, 'luces_salida'));
        $this->repo->saveNiveles($id, 'salida', $this->parseNiveles($data, 'niveles_salida'));
        AuditService::log('CREATE', 'comisiones', $id, null, $data);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null || !in_array($before['estado'], ['borrador', 'en_curso'], true)) {
            return false;
        }
        $data = $this->normalizeResponsableRegreso($data);
        $data = $this->normalizeConductor($data);
        $result = $this->repo->update($id, array_merge($before, $data));
        if ($result) {
            if (array_key_exists('luces_salida', $data)) {
                $this->repo->saveLuces($id, 'salida', $this->parseLuces($data, 'luces_salida'));
            }
            if (array_key_exists('niveles_salida', $data)) {
                $this->repo->saveNiveles($id, 'salida', $this->parseNiveles($data, 'niveles_salida'));
            }
            AuditService::log('UPDATE', 'comisiones', $id, $before, $data);
        }
        return $result;
    }

    public function cargarDocumento(int $id, string $tipo, ?array $file): ?string
    {
        $tipo = $tipo === 'regreso' ? 'regreso' : 'salida';
        $comision = $this->repo->findById($id);
        if ($comision === null) {
            return 'Comisión no encontrada.';
        }
        if ($file === null) {
            return 'Debe seleccionar un archivo PDF.';
        }
        try {
            $ruta = FileUploader::uploadDocument($file, 'comisiones/documentos');
            if ($ruta === null) {
                return 'No se pudo cargar el documento.';
            }
            $this->repo->updateDocumento($id, $tipo, $ruta);
            AuditService::log('UPDATE', 'comisiones', $id, null, ['documento_' . $tipo => $ruta]);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /** @return list<string> */
    private function parseLuces(array $data, string $campo): array
    {
        $selected = $data[$campo] ?? [];
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

    /** @return array<string, string> */
    private function parseNiveles(array $data, string $campo): array
    {
        $selected = $data[$campo] ?? [];
        if (!is_array($selected)) {
            return [];
        }
        $validNiveles = array_keys(ComisionRepository::NIVEL_OPCIONES);
        $niveles = [];
        foreach (ComisionRepository::LIQUIDOS as $liquido) {
            $codigo = $liquido['codigo'];
            $nivel = (string) ($selected[$codigo] ?? '');
            if (in_array($nivel, $validNiveles, true)) {
                $niveles[$codigo] = $nivel;
            }
        }
        return $niveles;
    }

    private function normalizeResponsableRegreso(array $data): array
    {
        if (array_key_exists('conductor_id', $data)) {
            $conductorId = $data['conductor_id'];
            $data['conductor_id'] = ($conductorId === '' || $conductorId === null) ? null : (int) $conductorId;
        }

        if (array_key_exists('responsable_regreso_id', $data) || array_key_exists('responsable_regreso_nombre', $data)) {
            $respId = $data['responsable_regreso_id'] ?? null;
            $respId = ($respId === '' || $respId === null) ? null : (int) $respId;
            $data['responsable_regreso_id'] = $respId;

            $nombre = trim((string) ($data['responsable_regreso_nombre'] ?? ''));
            if ($nombre === '' && $respId !== null) {
                $nombre = (string) ($this->repo->getUserFullName($respId) ?? '');
            }
            $data['responsable_regreso_nombre'] = $nombre !== '' ? $nombre : null;
        }

        return $data;
    }

    private function normalizeConductor(array $data): array
    {
        if (array_key_exists('conductor_id', $data)) {
            $conductorId = $data['conductor_id'];
            $conductorId = ($conductorId === '' || $conductorId === null) ? null : (int) $conductorId;
            $data['conductor_id'] = $conductorId;
            if ($conductorId !== null) {
                $conductor = $this->catalogos->getConductorById($conductorId);
                if ($conductor !== null) {
                    $data['conductor_nombre'] = $conductor['nombre'];
                }
            }
        }

        $nombre = trim((string) ($data['conductor_nombre'] ?? ''));
        if ($nombre === '' && empty($data['conductor_id'])) {
            $data['conductor_nombre'] = '';
        } elseif ($nombre !== '') {
            $data['conductor_nombre'] = $nombre;
        }

        return $data;
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
            $this->repo->saveLuces($id, 'regreso', $this->parseLuces($data, 'luces_regreso'));
            $this->repo->saveNiveles($id, 'regreso', $this->parseNiveles($data, 'niveles_regreso'));
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
