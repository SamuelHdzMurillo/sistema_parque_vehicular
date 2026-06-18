<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
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

    public function quickStore(Request $request): never
    {
        if (!can('usuarios.create') && !can('mantenimiento.create')) {
            Response::json(['ok' => false, 'error' => 'No tiene permiso para registrar responsables.'], 403);
        }

        $token = $request->input('_token');
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            Response::json(['ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'], 419);
        }

        $result = $this->usuarios->createQuick($request->all());
        if (is_string($result)) {
            Response::json(['ok' => false, 'error' => $result], 422);
        }

        $usuario = $this->usuarios->find((int) $result);
        if ($usuario === null) {
            Response::json(['ok' => false, 'error' => 'No se pudo recuperar el responsable creado.'], 500);
        }

        $nombre = trim((string) $usuario['nombre'] . ' ' . (string) $usuario['apellido_paterno']);

        Response::json([
            'ok' => true,
            'responsable' => [
                'id' => (int) $usuario['id'],
                'nombre' => $nombre,
                'label' => $nombre,
            ],
        ]);
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
