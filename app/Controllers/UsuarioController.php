<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\UsuarioService;

final class UsuarioController extends BaseController
{
    public function __construct(
        private readonly UsuarioService $usuarios = new UsuarioService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $search = $request->input('q');
        $result = $this->usuarios->paginate($page, is_string($search) ? $search : null);
        $this->render('usuarios.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('usuarios.create', $this->usuarios->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $error = $this->usuarios->create($request->all());
        if ($error !== null) {
            $_SESSION['_old'] = $request->all();
            flash('error', $error);
            $this->redirect('usuarios/create');
        }
        flash('success', 'Usuario creado correctamente.');
        $this->redirect('usuarios');
    }

    public function edit(Request $request, string $id): never
    {
        $usuario = $this->usuarios->find((int) $id);
        if ($usuario === null) {
            flash('error', 'Usuario no encontrado.');
            $this->redirect('usuarios');
        }
        $this->render('usuarios.edit', array_merge($this->usuarios->getFormData(), ['usuario' => $usuario]));
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $error = $this->usuarios->update((int) $id, $request->all());
        if ($error !== null) {
            flash('error', $error);
            $this->redirect('usuarios/' . $id . '/edit');
        }
        flash('success', 'Usuario actualizado correctamente.');
        $this->redirect('usuarios');
    }
}
