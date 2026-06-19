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
            'planteles' => $this->catalogos->getPlanteles(),
            'conductores' => $this->catalogos->getConductores(),
            'usuarios' => $this->catalogos->getUsersForSelect(),
            'luces_tablero' => InspeccionRepository::LUCES_TABLERO,
            'liquidos' => ComisionRepository::LIQUIDOS,
            'nivel_opciones' => ComisionRepository::NIVEL_OPCIONES,
            'folio_sugerido' => $this->repo->generateFolio(),
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

    /** @return list<string> */
    public function getLucesVehiculo(int $vehiculoId): array
    {
        return $this->vehiculos->getLucesTablero($vehiculoId);
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
        $data = $this->coalesceCombustiblePost($data, 'combustible_salida', 100.0);
        $data['combustible_salida'] = $this->resolveCombustiblePercent($data, 'combustible_salida', 100.0);
        $data['folio'] = $this->resolveFolio($data['folio'] ?? null);
        $data['estado'] = 'borrador';
        $id = $this->repo->create($data);
        $lucesSalida = $this->parseLuces($data, 'luces_salida');
        $this->repo->saveLuces($id, 'salida', $lucesSalida);
        $this->vehiculos->syncLucesTablero((int) $data['vehiculo_id'], $lucesSalida, 'comision', $id);
        $this->repo->saveNiveles($id, 'salida', $this->parseNiveles($data, 'niveles_salida'));
        AuditService::log('CREATE', 'comisiones', $id, null, $data);
        return $id;
    }

    public function update(int $id, array $data): ?string
    {
        try {
            $before = $this->find($id);
            if ($before === null) {
                return 'Comisión no encontrada.';
            }
            if (!in_array($before['estado'], ['borrador', 'en_curso', 'finalizada'], true)) {
                return 'No se puede editar una comisión cancelada.';
            }

            $data = $this->normalizeResponsableRegreso($data);
            $data = $this->normalizeConductor($data);
            $data = $this->sanitizeCombustibleInputForUpdate($data);
            $data = $this->applyCombustibleFields($data, $before);

            $merged = array_merge($before, $data);
            $merged['vehiculo_id'] = (int) ($data['vehiculo_id'] ?? $before['vehiculo_id']);
            $merged['km_salida'] = (int) ($data['km_salida'] ?? $before['km_salida']);

            $oldVehiculoId = (int) $before['vehiculo_id'];
            $newVehiculoId = (int) $merged['vehiculo_id'];
            $vehiculoCambio = $newVehiculoId !== $oldVehiculoId;

            if ($before['estado'] === 'finalizada') {
                $error = $this->validateUpdateFinalizada($before, $merged, $vehiculoCambio);
                if ($error !== null) {
                    return $error;
                }
            } elseif ($vehiculoCambio) {
                $this->validateInicio($newVehiculoId, $merged['km_salida'], $id);
            } elseif ($merged['km_salida'] < (int) ($before['kilometraje_actual'] ?? 0) && $before['estado'] === 'borrador') {
                $vehiculo = $this->vehiculos->findById($oldVehiculoId);
                if ($vehiculo && $merged['km_salida'] < (int) $vehiculo['kilometraje_actual']) {
                    return sprintf(
                        'El km de salida (%s) no puede ser menor al kilometraje actual del vehículo (%s).',
                        number_format($merged['km_salida']),
                        number_format((int) $vehiculo['kilometraje_actual'])
                    );
                }
            }

            $this->repo->beginTransaction();

            if ($before['estado'] === 'finalizada') {
                $this->syncKilometrajeOnFinalizadaUpdate($before, $merged, $vehiculoCambio);
            } elseif ($before['estado'] === 'en_curso' && $vehiculoCambio) {
                $this->vehiculos->updateEstado(
                    $oldVehiculoId,
                    'disponible',
                    'Cambio de vehículo en comisión ' . $before['folio'],
                    auth_id()
                );
                $this->vehiculos->updateEstado(
                    $newVehiculoId,
                    'en_comision',
                    'Comisión ' . $before['folio'],
                    auth_id()
                );
            }

            if ($before['estado'] === 'finalizada') {
                $vehiculo = $this->vehiculos->findById($newVehiculoId);
                if ($vehiculo !== null) {
                    $capacidad = (float) ($vehiculo['capacidad_tanque'] ?? 80);
                    $metricas = $this->repo->calcularMetricas($merged, $capacidad);
                    $merged = array_merge($merged, $metricas);
                }
            }

            if (!$this->repo->update($id, $merged)) {
                throw new \RuntimeException('No se pudo guardar la comisión.');
            }

            if (array_key_exists('luces_salida', $data)) {
                $lucesSalida = $this->parseLuces($data, 'luces_salida');
                $this->repo->saveLuces($id, 'salida', $lucesSalida);
                if ($before['estado'] !== 'finalizada') {
                    $this->vehiculos->syncLucesTablero((int) $merged['vehiculo_id'], $lucesSalida, 'comision', $id);
                }
            }
            if (array_key_exists('niveles_salida', $data)) {
                $this->repo->saveNiveles($id, 'salida', $this->parseNiveles($data, 'niveles_salida'));
            }
            if ($before['estado'] === 'finalizada') {
                $lucesRegreso = $this->parseLuces($data, 'luces_regreso');
                $this->repo->saveLuces($id, 'regreso', $lucesRegreso);
                $this->vehiculos->syncLucesTablero((int) $merged['vehiculo_id'], $lucesRegreso, 'comision', $id);
                $this->repo->saveNiveles($id, 'regreso', $this->parseNiveles($data, 'niveles_regreso'));
            }

            $this->repo->commit();
            AuditService::log('UPDATE', 'comisiones', $id, $before, $merged);
            return null;
        } catch (\InvalidArgumentException $e) {
            $this->repo->rollBack();
            return $e->getMessage();
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            return user_facing_error($e, 'No se pudo actualizar la comisión.');
        }
    }

    public function eliminar(int $id): ?string
    {
        try {
            $comision = $this->find($id);
            if ($comision === null) {
                return 'Comisión no encontrada.';
            }

            $this->repo->beginTransaction();

            if ($comision['estado'] === 'finalizada') {
                $this->revertirKilometrajeComision($comision);
            } elseif ($comision['estado'] === 'en_curso') {
                $this->vehiculos->updateEstado(
                    (int) $comision['vehiculo_id'],
                    'disponible',
                    'Eliminación comisión ' . $comision['folio'],
                    auth_id()
                );
            }

            $this->eliminarArchivosComision($comision);

            if (!$this->repo->delete($id)) {
                throw new \RuntimeException('No se pudo eliminar la comisión.');
            }

            $this->repo->commit();
            AuditService::log('DELETE', 'comisiones', $id, $comision, null);
            return null;
        } catch (\InvalidArgumentException $e) {
            $this->repo->rollBack();
            return $e->getMessage();
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            return user_facing_error($e, 'No se pudo eliminar la comisión.');
        }
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
            return user_facing_error($e, 'No se pudo cargar el documento.');
        }
    }

    public function streamDocumentosCombinados(int $id): never
    {
        $comision = $this->repo->findById($id);
        if ($comision === null) {
            http_response_code(404);
            exit('Comisión no encontrada.');
        }

        $salida = trim((string) ($comision['doc_salida_ruta'] ?? ''));
        $regreso = trim((string) ($comision['doc_regreso_ruta'] ?? ''));
        if ($salida === '' || $regreso === '') {
            http_response_code(404);
            exit('Deben estar cargados los documentos de salida y regreso.');
        }

        $pathSalida = storage_path('uploads/' . ltrim($salida, '/'));
        $pathRegreso = storage_path('uploads/' . ltrim($regreso, '/'));
        if (!is_file($pathSalida) || !is_file($pathRegreso)) {
            http_response_code(404);
            exit('Uno o ambos archivos PDF no están disponibles en el servidor.');
        }

        $folio = (string) ($comision['folio'] ?? ('comision_' . $id));
        $filename = 'comision_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $folio) . '_completo.pdf';
        $exportPath = storage_path('exports/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '.pdf');

        try {
            $merger = new PdfMergeService();
            $merger->mergeToFile([$pathSalida, $pathRegreso], $exportPath);
        } catch (\Throwable $e) {
            http_response_code(500);
            exit(user_facing_error($e, 'No se pudo combinar los documentos PDF.'));
        }

        AuditService::log('EXPORT', 'comisiones', $id, null, [
            'tipo' => 'documentos_combinados',
            'archivo' => basename($exportPath),
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($exportPath));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($exportPath);
        exit;
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

    /** Si el POST no trae combustible o viene vacío, usa el valor por defecto indicado. */
    private function coalesceCombustiblePost(array $data, string $field, ?float $defaultPercent = null): array
    {
        $raw = $data[$field] ?? null;

        if (is_array($raw)) {
            $candidates = array_values(array_filter($raw, static function (mixed $value): bool {
                return !is_array($value) && trim((string) $value) !== '';
            }));
            $raw = $candidates !== [] ? (string) end($candidates) : null;
            if ($raw !== null) {
                $data[$field] = $raw;

                return $data;
            }
            unset($data[$field]);
        }

        $missing = !array_key_exists($field, $data);
        $raw = $missing ? null : $data[$field];

        if (!$missing && !is_array($raw) && trim((string) $raw) !== '') {
            return $data;
        }

        if ($defaultPercent !== null) {
            $data[$field] = (string) (int) round($defaultPercent);
        } elseif (!$missing) {
            unset($data[$field]);
        }

        return $data;
    }

    private function parseCombustibleField(array $data, string $field): ?float
    {
        if (!array_key_exists($field, $data)) {
            return null;
        }

        $raw = $data[$field];
        if (is_array($raw)) {
            $candidates = array_values(array_filter($raw, static function (mixed $value): bool {
                return !is_array($value) && trim((string) $value) !== '';
            }));
            $raw = $candidates !== [] ? (string) end($candidates) : '';
        }

        $raw = trim((string) $raw);
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

    private function resolveCombustiblePercent(array $data, string $field, float $default): float
    {
        $data = $this->coalesceCombustiblePost($data, $field, $default);

        return $this->parseCombustibleField($data, $field) ?? $default;
    }

    private function resolveCombustibleField(array $data, string $field, bool $required): float
    {
        $porcentaje = $this->parseCombustibleField($data, $field);
        if ($porcentaje !== null) {
            return $porcentaje;
        }

        if ($required) {
            throw new \InvalidArgumentException(
                $field === 'combustible_regreso'
                    ? 'Indique el nivel de combustible al regreso.'
                    : 'Indique el nivel de combustible a la salida.'
            );
        }

        throw new \InvalidArgumentException('Nivel de combustible no válido.');
    }

    private function applyCombustibleFields(array $data, ?array $before = null): array
    {
        $estado = (string) ($before['estado'] ?? '');
        $before = $before ?? [];

        $salidaDefault = (float) ($before['combustible_salida'] ?? 100);
        $data['combustible_salida'] = $this->resolveCombustiblePercent($data, 'combustible_salida', $salidaDefault);

        if ($estado === 'finalizada') {
            $regresoDefault = (float) ($before['combustible_regreso'] ?? $before['combustible_salida'] ?? 100);
            $data['combustible_regreso'] = $this->resolveCombustiblePercent($data, 'combustible_regreso', $regresoDefault);
        }

        return $data;
    }

    private function sanitizeCombustibleInputForUpdate(array $data): array
    {
        foreach (['combustible_salida', 'combustible_regreso'] as $campo) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }
            $raw = $data[$campo];
            if (is_array($raw) || $raw === '' || $raw === null) {
                unset($data[$campo]);
            }
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
            return user_facing_error($e, 'No se pudo iniciar la comisión.');
        }
    }

    public function finalizar(int $id, array $data): ?string
    {
        try {
            $comision = $this->repo->findById($id);
            if ($comision === null) {
                return 'Comisión no encontrada.';
            }
            if ($comision['estado'] !== 'en_curso') {
                return 'Solo se puede registrar el regreso de comisiones en estado «En curso». Estado actual: '
                    . ($comision['estado'] ?? 'desconocido') . '.';
            }

            $merged = array_merge($comision, $data);
            $merged['combustible_regreso'] = $this->resolveCombustiblePercent(
                $data,
                'combustible_regreso',
                (float) ($comision['combustible_salida'] ?? 100)
            );

            if (empty($merged['hora_regreso'])) {
                return 'Indique la hora de regreso.';
            }

            $kmSalida = (int) $comision['km_salida'];
            $kmRegreso = isset($merged['km_regreso']) ? (int) $merged['km_regreso'] : 0;
            if ($kmRegreso <= 0) {
                return 'Indique el kilometraje de regreso.';
            }
            if ($kmRegreso < $kmSalida) {
                return sprintf(
                    'El km de regreso (%s) no puede ser menor al km de salida (%s).',
                    number_format($kmRegreso),
                    number_format($kmSalida)
                );
            }

            $vehiculo = $this->vehiculos->findById((int) $comision['vehiculo_id']);
            if ($vehiculo === null) {
                return 'Vehículo de la comisión no encontrado.';
            }

            $kmActualVehiculo = (int) ($vehiculo['kilometraje_actual'] ?? 0);
            if ($kmRegreso < $kmActualVehiculo) {
                return sprintf(
                    'El km de regreso (%s) no puede ser menor al kilometraje actual del vehículo (%s). '
                    . 'El odómetro pudo haberse actualizado por otra operación mientras el vehículo estaba en comisión.',
                    number_format($kmRegreso),
                    number_format($kmActualVehiculo)
                );
            }

            if (!empty($data['firma_data'])) {
                $firma = FileUploader::saveBase64Signature((string) $data['firma_data'], 'firmas/comisiones');
                if ($firma === null) {
                    return 'La firma digital no es válida. Dibuje la firma en el recuadro o déjelo vacío.';
                }
                $merged['firma_digital'] = $firma;
            }

            $capacidad = (float) ($vehiculo['capacidad_tanque'] ?? 80);
            $metricas = $this->repo->calcularMetricas($merged, $capacidad);
            $merged = array_merge($merged, $metricas, ['estado' => 'finalizada']);

            $this->repo->beginTransaction();

            if (!$this->repo->update($id, $merged)) {
                throw new \RuntimeException('No se pudo guardar los datos de regreso en la comisión.');
            }

            $lucesRegreso = $this->parseLuces($data, 'luces_regreso');
            $this->repo->saveLuces($id, 'regreso', $lucesRegreso);
            $this->vehiculos->syncLucesTablero((int) $comision['vehiculo_id'], $lucesRegreso, 'comision', $id);
            $this->repo->saveNiveles($id, 'regreso', $this->parseNiveles($data, 'niveles_regreso'));

            if (!$this->vehiculos->updateKilometraje((int) $comision['vehiculo_id'], $kmRegreso, auth_id())) {
                throw new \RuntimeException(sprintf(
                    'No se pudo actualizar el kilometraje del vehículo. Verifique que el km de regreso (%s) '
                    . 'sea mayor o igual al registrado (%s).',
                    number_format($kmRegreso),
                    number_format($kmActualVehiculo)
                ));
            }

            if (!$this->vehiculos->updateEstado(
                (int) $comision['vehiculo_id'],
                'disponible',
                'Fin comisión ' . $comision['folio'],
                auth_id()
            )) {
                throw new \RuntimeException('No se pudo cambiar el estado del vehículo a disponible.');
            }

            $this->repo->commit();
            AuditService::log('UPDATE', 'comisiones', $id, ['estado' => 'en_curso'], ['estado' => 'finalizada']);
            return null;
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        } catch (\Throwable $e) {
            $this->repo->rollBack();
            return user_facing_error($e, 'No se pudo finalizar la comisión.');
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
            return user_facing_error($e, 'No se pudo cancelar la comisión.');
        }
    }

    private function resolveFolio(?string $input): string
    {
        $folio = trim((string) ($input ?? ''));
        if ($folio === '') {
            return $this->repo->generateFolio();
        }
        if (!preg_match('/^COM-\d{4}-\d+$/i', $folio)) {
            throw new \InvalidArgumentException(
                'El folio debe tener el formato COM-AAAA-NNNN (ejemplo: COM-2026-0001).'
            );
        }
        if (preg_match('/^COM-(\d{4})-(\d+)$/i', $folio, $m)) {
            $folio = sprintf('COM-%s-%04d', $m[1], (int) $m[2]);
        }
        if ($this->repo->folioExists($folio)) {
            throw new \InvalidArgumentException('El folio "' . $folio . '" ya está registrado. Elija otro.');
        }
        return $folio;
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

    private function validateUpdateFinalizada(array $before, array $merged, bool $vehiculoCambio): ?string
    {
        $kmSalida = (int) $merged['km_salida'];
        $kmRegreso = isset($merged['km_regreso']) ? (int) $merged['km_regreso'] : 0;
        if ($kmRegreso <= 0) {
            return 'Indique el kilometraje de regreso.';
        }
        if ($kmRegreso < $kmSalida) {
            return sprintf(
                'El km de regreso (%s) no puede ser menor al km de salida (%s).',
                number_format($kmRegreso),
                number_format($kmSalida)
            );
        }

        if ($vehiculoCambio) {
            $nuevoVehiculo = $this->vehiculos->findById((int) $merged['vehiculo_id']);
            if ($nuevoVehiculo === null) {
                return 'El vehículo seleccionado no existe.';
            }
            if ($kmSalida < (int) $nuevoVehiculo['kilometraje_actual']) {
                return sprintf(
                    'El km de salida (%s) no puede ser menor al kilometraje actual del nuevo vehículo (%s).',
                    number_format($kmSalida),
                    number_format((int) $nuevoVehiculo['kilometraje_actual'])
                );
            }
            if ($kmRegreso < (int) $nuevoVehiculo['kilometraje_actual']) {
                return sprintf(
                    'El km de regreso (%s) no puede ser menor al kilometraje actual del nuevo vehículo (%s).',
                    number_format($kmRegreso),
                    number_format((int) $nuevoVehiculo['kilometraje_actual'])
                );
            }
            if ($this->repo->hasActiveComision((int) $merged['vehiculo_id'], (int) $before['id'])) {
                return 'El vehículo seleccionado ya tiene una comisión en curso.';
            }
        }

        return null;
    }

    private function syncKilometrajeOnFinalizadaUpdate(array $before, array $merged, bool $vehiculoCambio): void
    {
        $oldVehiculoId = (int) $before['vehiculo_id'];
        $newVehiculoId = (int) $merged['vehiculo_id'];
        $oldKmRegreso = (int) ($before['km_regreso'] ?? 0);
        $newKmRegreso = (int) ($merged['km_regreso'] ?? 0);

        if ($vehiculoCambio) {
            $this->revertirKilometrajeComision($before);
            $this->aplicarKilometrajeRegreso($newVehiculoId, $newKmRegreso, 'Edición comisión ' . $before['folio']);
            return;
        }

        if ($oldKmRegreso !== $newKmRegreso) {
            $this->ajustarKilometrajeVehiculo($oldVehiculoId, $oldKmRegreso, $newKmRegreso);
        }
    }

    private function revertirKilometrajeComision(array $comision): void
    {
        if (($comision['estado'] ?? '') !== 'finalizada' || empty($comision['km_regreso'])) {
            return;
        }

        $vehiculoId = (int) $comision['vehiculo_id'];
        $kmSalida = (int) $comision['km_salida'];
        $kmRegreso = (int) $comision['km_regreso'];
        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo === null) {
            throw new \InvalidArgumentException('Vehículo de la comisión no encontrado.');
        }

        $kmActual = (int) $vehiculo['kilometraje_actual'];
        if ($kmActual !== $kmRegreso) {
            throw new \InvalidArgumentException(sprintf(
                'No se pueden revertir los kilómetros: el odómetro actual del vehículo (%s km) no coincide con el km de regreso de la comisión (%s km). '
                . 'Puede haber registros posteriores (otra comisión, combustible, etc.).',
                number_format($kmActual),
                number_format($kmRegreso)
            ));
        }

        if (!$this->vehiculos->setKilometraje($vehiculoId, $kmSalida, auth_id())) {
            throw new \RuntimeException('No se pudo revertir el kilometraje del vehículo.');
        }
    }

    private function aplicarKilometrajeRegreso(int $vehiculoId, int $kmRegreso, string $motivo): void
    {
        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo === null) {
            throw new \InvalidArgumentException('Vehículo no encontrado.');
        }

        $kmActual = (int) $vehiculo['kilometraje_actual'];
        if ($kmRegreso < $kmActual) {
            throw new \InvalidArgumentException(sprintf(
                'El km de regreso (%s) no puede ser menor al kilometraje actual del vehículo (%s).',
                number_format($kmRegreso),
                number_format($kmActual)
            ));
        }

        if ($kmRegreso > $kmActual && !$this->vehiculos->updateKilometraje($vehiculoId, $kmRegreso, auth_id())) {
            throw new \RuntimeException('No se pudo actualizar el kilometraje del vehículo.');
        }
    }

    private function ajustarKilometrajeVehiculo(int $vehiculoId, int $kmAnterior, int $kmNuevo): void
    {
        if ($kmAnterior === $kmNuevo) {
            return;
        }

        $vehiculo = $this->vehiculos->findById($vehiculoId);
        if ($vehiculo === null) {
            throw new \InvalidArgumentException('Vehículo no encontrado.');
        }

        $kmActual = (int) $vehiculo['kilometraje_actual'];
        if ($kmActual !== $kmAnterior) {
            throw new \InvalidArgumentException(sprintf(
                'No se puede ajustar el kilometraje: el odómetro actual (%s km) no coincide con el km de regreso registrado (%s km).',
                number_format($kmActual),
                number_format($kmAnterior)
            ));
        }

        if (!$this->vehiculos->setKilometraje($vehiculoId, $kmNuevo, auth_id())) {
            throw new \RuntimeException('No se pudo actualizar el kilometraje del vehículo.');
        }
    }

    /** @param array<string, mixed> $comision */
    private function eliminarArchivosComision(array $comision): void
    {
        foreach (['doc_salida_ruta', 'doc_regreso_ruta', 'firma_digital'] as $campo) {
            $ruta = trim((string) ($comision[$campo] ?? ''));
            if ($ruta === '') {
                continue;
            }
            $path = storage_path('uploads/' . ltrim($ruta, '/'));
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
