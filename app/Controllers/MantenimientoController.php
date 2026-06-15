<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\MantenimientoService;

final class MantenimientoController extends BaseController
{
    public function __construct(
        private readonly MantenimientoService $mantenimientos = new MantenimientoService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $estado = $request->input('estado');
        $result = $this->mantenimientos->paginate($page, is_string($estado) ? $estado : null);
        $this->render('mantenimiento.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('mantenimiento.create', $this->mantenimientos->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $data = $request->all();
        $data['responsable_id'] = $data['responsable_id'] ?? $userId;
        $id = $this->mantenimientos->create($data, $userId);
        flash('success', 'Mantenimiento registrado correctamente.');
        $this->redirect('mantenimiento/' . $id);
    }

    public function show(Request $request, string $id): never
    {
        $mantenimiento = $this->mantenimientos->find((int) $id);
        if ($mantenimiento === null) {
            flash('error', 'Mantenimiento no encontrado.');
            $this->redirect('mantenimiento');
        }
        $this->render('mantenimiento.show', ['mantenimiento' => $mantenimiento]);
    }

    public function edit(Request $request, string $id): never
    {
        $mantenimiento = $this->mantenimientos->find((int) $id);
        if ($mantenimiento === null) {
            flash('error', 'Mantenimiento no encontrado.');
            $this->redirect('mantenimiento');
        }
        $this->render('mantenimiento.edit', array_merge($this->mantenimientos->getFormData(), ['mantenimiento' => $mantenimiento]));
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        if (!$this->mantenimientos->update((int) $id, $request->all())) {
            flash('error', 'No se pudo actualizar el mantenimiento.');
            $this->redirect('mantenimiento/' . $id . '/edit');
        }
        flash('success', 'Mantenimiento actualizado correctamente.');
        $this->redirect('mantenimiento/' . $id);
    }

    public function autorizar(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $error = $this->mantenimientos->autorizar((int) $id, $userId);
        flash($error ? 'error' : 'success', $error ?? 'Mantenimiento autorizado.');
        $this->redirect('mantenimiento/' . $id);
    }

    public function finalizar(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $error = $this->mantenimientos->finalizar((int) $id);
        flash($error ? 'error' : 'success', $error ?? 'Mantenimiento finalizado.');
        $this->redirect('mantenimiento/' . $id);
    }
}
