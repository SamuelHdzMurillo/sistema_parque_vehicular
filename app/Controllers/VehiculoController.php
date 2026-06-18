<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Services\VehiculoService;
use PDOException;
use RuntimeException;

final class VehiculoController extends BaseController
{
    public function __construct(
        private readonly VehiculoService $vehiculos = new VehiculoService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $search = $request->input('q');
        $estado = $request->input('estado');
        $result = $this->vehiculos->paginate($page, is_string($search) ? $search : null, is_string($estado) ? $estado : null);
        $this->render('vehiculos.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('vehiculos.create', $this->vehiculos->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        try {
            $data = $request->all();
            $data['fotos'] = $request->files('fotos');
            $foto = $request->file('foto');
            if ($foto !== null) {
                $data['foto'] = $foto;
            }
            $id = $this->vehiculos->create($data, $userId);
            flash('success', 'Vehículo registrado correctamente.');
            $this->redirect('vehiculos/' . $id);
        } catch (ValidationException $e) {
            $_SESSION['_old'] = $request->all();
            $_SESSION['_field_errors'] = $e->getFieldErrors();
            flash('errors', $e->getErrors());
            $this->redirect('vehiculos/create');
        } catch (RuntimeException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', $e->getMessage());
            $this->redirect('vehiculos/create');
        } catch (PDOException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', 'Error de base de datos al registrar el vehículo. Intente de nuevo.');
            $this->redirect('vehiculos/create');
        }
    }

    public function show(Request $request, string $id): never
    {
        $expediente = $this->vehiculos->getExpediente((int) $id);
        if ($expediente === null) {
            flash('error', 'Vehículo no encontrado.');
            $this->redirect('vehiculos');
        }
        $this->render('vehiculos.show', $expediente);
    }

    public function edit(Request $request, string $id): never
    {
        $vehiculo = $this->vehiculos->find((int) $id);
        if ($vehiculo === null) {
            flash('error', 'Vehículo no encontrado.');
            $this->redirect('vehiculos');
        }
        $this->render('vehiculos.edit', array_merge($this->vehiculos->getFormDataForEdit($vehiculo), ['vehiculo' => $vehiculo]));
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        try {
            if (!$this->vehiculos->update((int) $id, $request->all(), $userId)) {
                flash('error', 'No se pudo actualizar el vehículo.');
                $this->redirect('vehiculos/' . $id . '/edit');
            }
            flash('success', 'Vehículo actualizado correctamente.');
            $this->redirect('vehiculos/' . $id);
        } catch (ValidationException $e) {
            $_SESSION['_old'] = $request->all();
            $_SESSION['_field_errors'] = $e->getFieldErrors();
            flash('errors', $e->getErrors());
            $this->redirect('vehiculos/' . $id . '/edit');
        } catch (PDOException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', 'Error de base de datos al actualizar el vehículo. Intente de nuevo.');
            $this->redirect('vehiculos/' . $id . '/edit');
        }
    }

    public function destroy(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $motivo = (string) $request->input('motivo', '');
        if (!$this->vehiculos->softDelete((int) $id, $userId, $motivo ?: null)) {
            flash('error', 'No se pudo dar de baja el vehículo.');
            $this->redirect('vehiculos/' . $id);
        }
        flash('success', 'Vehículo dado de baja correctamente.');
        $this->redirect('vehiculos');
    }

    public function uploadFoto(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $files = $request->files('fotos');
        if ($files === []) {
            $single = $request->file('foto');
            if ($single !== null) {
                $files = [$single];
            }
        }
        if ($files === []) {
            flash('error', 'Seleccione al menos una imagen.');
            $this->redirect('vehiculos/' . $id);
        }

        try {
            $this->vehiculos->uploadFotos(
                (int) $id,
                $files,
                $request->input('descripcion') ? (string) $request->input('descripcion') : null,
                (bool) $request->input('principal')
            );
            flash('success', count($files) > 1 ? 'Fotografías cargadas correctamente.' : 'Fotografía cargada correctamente.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('vehiculos/' . $id);
    }

    public function setFotoPrincipal(Request $request, string $id, string $fotoId): never
    {
        $this->validateCsrf($request);
        try {
            $this->vehiculos->setFotoPrincipal((int) $id, (int) $fotoId);
            flash('success', 'Fotografía principal actualizada.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('vehiculos/' . $id);
    }

    public function deleteFoto(Request $request, string $id, string $fotoId): never
    {
        $this->validateCsrf($request);
        try {
            $this->vehiculos->deleteFoto((int) $id, (int) $fotoId);
            flash('success', 'Fotografía eliminada.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('vehiculos/' . $id);
    }
}
