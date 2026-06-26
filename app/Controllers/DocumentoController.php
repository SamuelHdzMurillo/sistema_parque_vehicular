<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\DocumentoService;
use RuntimeException;

final class DocumentoController extends BaseController
{
    public function __construct(
        private readonly DocumentoService $documentos = new DocumentoService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $vehiculoId = $request->input('vehiculo_id');
        $result = $this->documentos->paginate($page, $vehiculoId ? (int) $vehiculoId : null);
        $this->render('documentos.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('documentos.create', $this->documentos->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $file = $request->file('archivo');
        if ($file === null) {
            flash('error', 'Debe seleccionar un archivo.');
            $this->redirect('documentos/create');
        }

        try {
            $this->documentos->create($request->all(), $file, $userId);
            flash('success', 'Documento cargado correctamente.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('documentos/create');
        }
        $this->redirect('documentos');
    }

    public function download(Request $request, string $id): never
    {
        $file = $this->documentos->getDownloadPath((int) $id);
        if ($file === null) {
            flash('error', 'Documento no encontrado.');
            $this->redirect('documentos');
        }
        Response::download($file['path'], $file['filename'], $file['content_type']);
    }

    public function edit(Request $request, string $id): never
    {
        $data = $this->documentos->getEditFormData((int) $id);
        if ($data === null) {
            flash('error', 'Documento no encontrado.');
            $this->redirect('documentos');
        }
        $this->render('documentos.edit', $data);
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $docId = (int) $id;
        try {
            $result = $this->documentos->update($docId, $request->all(), $request->file('archivo'), $userId);
            if ($result === false) {
                flash('error', 'No se pudo actualizar el documento.');
                $this->redirect('documentos/' . $docId . '/edit');
            }
            if (is_int($result)) {
                flash('success', 'Documento actualizado con nueva versión del archivo.');
            } else {
                flash('success', 'Documento actualizado correctamente.');
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('documentos/' . $docId . '/edit');
        }
        $this->redirect('documentos');
    }

    public function destroy(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        if (!$this->documentos->delete((int) $id)) {
            flash('error', 'No se pudo eliminar el documento.');
            $this->redirect('documentos');
        }
        flash('success', 'Documento eliminado correctamente.');
        $this->redirect('documentos');
    }
}
