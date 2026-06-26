<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\InspeccionService;

final class InspeccionController extends BaseController
{
    public function __construct(
        private readonly InspeccionService $inspecciones = new InspeccionService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $result = $this->inspecciones->paginate($page);
        $this->render('inspecciones.index', $result);
    }

    public function create(Request $request): never
    {
        $vehiculoId = $request->input('vehiculo_id');
        $presetId = (is_string($vehiculoId) && $vehiculoId !== '' && ctype_digit($vehiculoId))
            ? (int) $vehiculoId
            : null;
        $this->render('inspecciones.create', $this->inspecciones->getFormDataForCreate($presetId));
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
            $data['es_historico'] = !empty($data['es_historico']) ? 1 : 0;
            $id = $this->inspecciones->create($data, $userId);
            flash('success', 'Inspección registrada correctamente.');
            $this->redirect('inspecciones/' . $id);
        } catch (\RuntimeException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', $e->getMessage());
            $this->redirect('inspecciones/create');
        } catch (\InvalidArgumentException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', $e->getMessage());
            $this->redirect('inspecciones/create');
        }
    }

    public function show(Request $request, string $id): never
    {
        $data = $this->inspecciones->find((int) $id);
        if ($data === null) {
            flash('error', 'Inspección no encontrada.');
            $this->redirect('inspecciones');
        }
        $this->render('inspecciones.show', $data);
    }

    public function edit(Request $request, string $id): never
    {
        $data = $this->inspecciones->getFormDataForEdit((int) $id);
        if ($data === null) {
            flash('error', 'Inspección no encontrada.');
            $this->redirect('inspecciones');
        }
        $this->render('inspecciones.edit', $data);
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        try {
            $data = $request->all();
            $data['es_historico'] = !empty($data['es_historico']) ? 1 : 0;
            $error = $this->inspecciones->update((int) $id, $data, $userId);
            if ($error !== null) {
                flash('error', $error);
                $this->redirect('inspecciones/' . $id . '/edit');
            }
            flash('success', 'Inspección actualizada correctamente.');
            $this->redirect('inspecciones/' . $id);
        } catch (\RuntimeException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', $e->getMessage());
            $this->redirect('inspecciones/' . $id . '/edit');
        } catch (\InvalidArgumentException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', $e->getMessage());
            $this->redirect('inspecciones/' . $id . '/edit');
        }
    }

    public function eliminar(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $error = $this->inspecciones->eliminar((int) $id);
        flash($error ? 'error' : 'success', $error ?? 'Inspección eliminada correctamente.');
        $this->redirect('inspecciones');
    }
}
