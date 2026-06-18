<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Services\ConductorService;

final class ConductorController extends BaseController
{
    public function __construct(
        private readonly ConductorService $conductores = new ConductorService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'q' => $request->input('q'),
            'area_id' => $request->input('area_id'),
            'activo' => $request->input('activo'),
        ], static fn ($v) => $v !== null && $v !== '');

        $this->render('catalogos.conductores.index', $this->conductores->paginate($page, $filters));
    }

    public function create(Request $request): never
    {
        $this->render('catalogos.conductores.create', $this->conductores->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->conductores->create($data);
        if (is_string($result)) {
            $_SESSION['_old'] = $data;
            flash('error', $result);
            $this->redirect('catalogos/conductores/create');
        }
        flash('success', 'Conductor registrado correctamente.');
        $this->redirect('catalogos/conductores');
    }

    public function quickStore(Request $request): never
    {
        $token = $request->input('_token');
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            Response::json(['ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'], 419);
        }

        $result = $this->conductores->create($request->all());
        if (is_string($result)) {
            Response::json(['ok' => false, 'error' => $result], 422);
        }

        $conductor = $this->conductores->find((int) $result);
        if ($conductor === null) {
            Response::json(['ok' => false, 'error' => 'No se pudo recuperar el conductor creado.'], 500);
        }

        $areaLabel = (string) ($conductor['area_label'] ?? catalogo_area_label($conductor));
        $nombre = (string) $conductor['nombre'];
        $telefono = (string) $conductor['telefono'];

        Response::json([
            'ok' => true,
            'conductor' => [
                'id' => (int) $conductor['id'],
                'nombre' => $nombre,
                'telefono' => $telefono,
                'area_label' => $areaLabel,
                'label' => $nombre . ' — ' . $areaLabel . ' — ' . $telefono,
            ],
        ]);
    }

    public function edit(Request $request, string $id): never
    {
        $conductor = $this->conductores->find((int) $id);
        if ($conductor === null) {
            flash('error', 'Conductor no encontrado.');
            $this->redirect('catalogos/conductores');
        }
        $this->render('catalogos.conductores.edit', array_merge(
            $this->conductores->getFormData(),
            ['conductor' => $conductor]
        ));
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $result = $this->conductores->update((int) $id, $data);
        if (is_string($result)) {
            flash('error', $result);
            $this->redirect('catalogos/conductores/' . $id . '/edit');
        }
        if ($result === false) {
            flash('error', 'No se pudo actualizar el conductor.');
            $this->redirect('catalogos/conductores/' . $id . '/edit');
        }
        flash('success', 'Conductor actualizado correctamente.');
        $this->redirect('catalogos/conductores');
    }

    public function toggle(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $activo = (string) $request->input('activo', '1') === '1';
        if (!$this->conductores->setActivo((int) $id, $activo)) {
            flash('error', 'No se pudo actualizar el estado del conductor.');
            $this->redirect('catalogos/conductores');
        }
        flash('success', $activo ? 'Conductor activado.' : 'Conductor desactivado.');
        $this->redirect('catalogos/conductores');
    }
}
