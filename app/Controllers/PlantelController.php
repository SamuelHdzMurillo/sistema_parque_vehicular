<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\PlantelService;

final class PlantelController extends BaseController
{
    public function __construct(
        private readonly PlantelService $planteles = new PlantelService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'q' => $request->input('q'),
            'activo' => $request->input('activo'),
        ], static fn ($v) => $v !== null && $v !== '');

        $this->render('catalogos.planteles.index', $this->planteles->paginate($page, $filters));
    }

    public function create(Request $request): never
    {
        $this->render('catalogos.planteles.create');
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->planteles->create($data);
        if (is_string($result)) {
            $_SESSION['_old'] = $data;
            flash('error', $result);
            $this->redirect('catalogos/planteles/create');
        }
        flash('success', 'Plantel registrado correctamente.');
        $this->redirect('catalogos/planteles');
    }

    public function edit(Request $request, string $id): never
    {
        $plantel = $this->planteles->find((int) $id);
        if ($plantel === null) {
            flash('error', 'Plantel no encontrado.');
            $this->redirect('catalogos/planteles');
        }
        $this->render('catalogos.planteles.edit', ['plantel' => $plantel]);
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->planteles->update((int) $id, $data);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/planteles/' . $id . '/edit');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el plantel.');
            $this->redirect('catalogos/planteles/' . $id . '/edit');
        }
        flash('success', 'Plantel actualizado correctamente.');
        $this->redirect('catalogos/planteles');
    }

    public function toggle(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $activo = (string) $request->input('activo', '1') === '1';
        $result = $this->planteles->setActivo((int) $id, $activo);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/planteles');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el estado del plantel.');
            $this->redirect('catalogos/planteles');
        }
        flash('success', $activo ? 'Plantel activado.' : 'Plantel desactivado.');
        $this->redirect('catalogos/planteles');
    }
}
