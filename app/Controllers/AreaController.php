<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\AreaService;

final class AreaController extends BaseController
{
    public function __construct(
        private readonly AreaService $areas = new AreaService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'q' => $request->input('q'),
            'plantel_id' => $request->input('plantel_id'),
            'activo' => $request->input('activo'),
        ], static fn ($v) => $v !== null && $v !== '');

        $this->render('catalogos.areas.index', $this->areas->paginate($page, $filters));
    }

    public function create(Request $request): never
    {
        $this->render('catalogos.areas.create', $this->areas->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->areas->create($data);
        if (is_string($result)) {
            $_SESSION['_old'] = $data;
            flash('error', $result);
            $this->redirect('catalogos/areas/create');
        }
        flash('success', 'Área registrada correctamente.');
        $this->redirect('catalogos/areas');
    }

    public function edit(Request $request, string $id): never
    {
        $area = $this->areas->find((int) $id);
        if ($area === null) {
            flash('error', 'Área no encontrada.');
            $this->redirect('catalogos/areas');
        }
        $this->render('catalogos.areas.edit', array_merge($this->areas->getFormData(), ['area' => $area]));
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->areas->update((int) $id, $data);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/areas/' . $id . '/edit');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el área.');
            $this->redirect('catalogos/areas/' . $id . '/edit');
        }
        flash('success', 'Área actualizada correctamente.');
        $this->redirect('catalogos/areas');
    }

    public function toggle(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $activo = (string) $request->input('activo', '1') === '1';
        $result = $this->areas->setActivo((int) $id, $activo);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/areas');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el estado del área.');
            $this->redirect('catalogos/areas');
        }
        flash('success', $activo ? 'Área activada.' : 'Área desactivada.');
        $this->redirect('catalogos/areas');
    }
}
