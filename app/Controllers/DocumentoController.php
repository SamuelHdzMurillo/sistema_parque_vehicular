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
}
