<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\ProveedorService;

final class ProveedorController extends BaseController
{
    public function __construct(
        private readonly ProveedorService $proveedores = new ProveedorService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'q' => $request->input('q'),
            'tipo' => $request->input('tipo'),
            'activo' => $request->input('activo'),
        ], static fn ($v) => $v !== null && $v !== '');

        $result = $this->proveedores->paginate($page, $filters);
        $result['tipos'] = $this->proveedores->getTipos();
        $this->render('proveedores.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('proveedores.create', ['tipos' => $this->proveedores->getTipos()]);
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        if (trim((string) ($data['razon_social'] ?? '')) === '') {
            $_SESSION['_old'] = $data;
            flash('error', 'La razón social es obligatoria.');
            $this->redirect('proveedores/create');
        }

        $id = $this->proveedores->create($data);
        flash('success', 'Proveedor registrado correctamente.');
        $this->redirect('proveedores');
    }

    public function edit(Request $request, string $id): never
    {
        $proveedor = $this->proveedores->find((int) $id);
        if ($proveedor === null) {
            flash('error', 'Proveedor no encontrado.');
            $this->redirect('proveedores');
        }
        $this->render('proveedores.edit', [
            'proveedor' => $proveedor,
            'tipos' => $this->proveedores->getTipos(),
        ]);
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $data = $request->all();
        if (trim((string) ($data['razon_social'] ?? '')) === '') {
            flash('error', 'La razón social es obligatoria.');
            $this->redirect('proveedores/' . $id . '/edit');
        }

        if (!$this->proveedores->update((int) $id, $data)) {
            flash('error', 'No se pudo actualizar el proveedor.');
            $this->redirect('proveedores/' . $id . '/edit');
        }
        flash('success', 'Proveedor actualizado correctamente.');
        $this->redirect('proveedores');
    }

    public function toggle(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $activo = (string) $request->input('activo', '1') === '1';
        if (!$this->proveedores->setActivo((int) $id, $activo)) {
            flash('error', 'No se pudo actualizar el estado del proveedor.');
            $this->redirect('proveedores');
        }
        flash('success', $activo ? 'Proveedor activado.' : 'Proveedor desactivado.');
        $this->redirect('proveedores');
    }
}
