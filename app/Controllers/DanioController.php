<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\DanioService;

final class DanioController extends BaseController
{
    public function __construct(
        private readonly DanioService $danios = new DanioService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $estado = $request->input('estado');
        $result = $this->danios->paginate($page, is_string($estado) ? $estado : null);
        $this->render('danios.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('danios.create', $this->danios->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $data = $request->all();
        $files = $request->files('fotos');
        if ($files === []) {
            $single = $request->file('foto');
            if ($single !== null) {
                $files = [$single];
            }
        }
        $data['fotos'] = $files;
        $id = $this->danios->create($data, $userId);
        flash('success', 'Daño reportado correctamente.');
        $this->redirect('danios/' . $id);
    }

    public function uploadFotos(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $files = $request->files('fotos');
        if ($files === []) {
            $single = $request->file('foto');
            if ($single !== null) {
                $files = [$single];
            }
        }
        try {
            $count = $this->danios->addFotos((int) $id, $files);
            flash('success', $count === 1 ? 'Fotografía agregada.' : "{$count} fotografías agregadas.");
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('danios/' . $id);
    }

    public function deleteFoto(Request $request, string $id, string $fotoId): never
    {
        $this->validateCsrf($request);
        try {
            $this->danios->deleteFoto((int) $id, (int) $fotoId);
            flash('success', 'Fotografía eliminada.');
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('danios/' . $id);
    }

    public function show(Request $request, string $id): never
    {
        $data = $this->danios->find((int) $id);
        if ($data === null) {
            flash('error', 'Registro de daño no encontrado.');
            $this->redirect('danios');
        }
        $this->render('danios.show', $data);
    }

    public function updateEstado(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $error = $this->danios->updateEstado(
            (int) $id,
            (string) $request->input('estado', ''),
            $userId,
            $request->input('comentario') ? (string) $request->input('comentario') : null
        );
        flash($error ? 'error' : 'success', $error ?? 'Estado actualizado correctamente.');
        $this->redirect('danios/' . $id);
    }
}
