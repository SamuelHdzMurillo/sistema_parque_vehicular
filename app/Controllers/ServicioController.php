<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\ServicioService;

final class ServicioController extends BaseController
{
    public function __construct(
        private readonly ServicioService $servicios = new ServicioService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'q' => $request->input('q'),
            'activo' => $request->input('activo'),
        ], static fn ($v) => $v !== null && $v !== '');

        $this->render('catalogos.servicios.index', $this->servicios->paginate($page, $filters));
    }

    public function create(Request $request): never
    {
        $this->render('catalogos.servicios.create');
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->servicios->create($data);
        if (is_string($result)) {
            $_SESSION['_old'] = $data;
            flash('error', $result);
            $this->redirect('catalogos/servicios/create');
        }
        flash('success', 'Servicio registrado correctamente.');
        $this->redirect('catalogos/servicios');
    }

    public function edit(Request $request, string $id): never
    {
        $servicio = $this->servicios->find((int) $id);
        if ($servicio === null) {
            flash('error', 'Servicio no encontrado.');
            $this->redirect('catalogos/servicios');
        }
        $this->render('catalogos.servicios.edit', ['servicio' => $servicio]);
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->servicios->update((int) $id, $data);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/servicios/' . $id . '/edit');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el servicio.');
            $this->redirect('catalogos/servicios/' . $id . '/edit');
        }
        flash('success', 'Servicio actualizado correctamente.');
        $this->redirect('catalogos/servicios');
    }

    public function toggle(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $activo = (string) $request->input('activo', '1') === '1';
        $result = $this->servicios->setActivo((int) $id, $activo);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/servicios');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el estado del servicio.');
            $this->redirect('catalogos/servicios');
        }
        flash('success', $activo ? 'Servicio activado.' : 'Servicio desactivado.');
        $this->redirect('catalogos/servicios');
    }

    public function destroy(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $result = $this->servicios->delete((int) $id);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/servicios');
        }
        if ($result === false) {
            flash('error', 'No se pudo eliminar el servicio.');
            $this->redirect('catalogos/servicios');
        }
        flash('success', 'Servicio eliminado correctamente.');
        $this->redirect('catalogos/servicios');
    }
}
