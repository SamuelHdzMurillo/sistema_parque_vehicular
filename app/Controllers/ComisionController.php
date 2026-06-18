<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\ComisionService;

final class ComisionController extends BaseController
{
    public function __construct(
        private readonly ComisionService $comisiones = new ComisionService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $estado = $request->input('estado');
        $result = $this->comisiones->paginate($page, is_string($estado) ? $estado : null);
        $this->render('comisiones.index', $result);
    }

    public function create(Request $request): never
    {
        $this->render('comisiones.create', $this->comisiones->getFormData());
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $data = $request->all();
        $data['responsable_id'] = $data['responsable_id'] ?? $userId;
        try {
            $id = $this->comisiones->create($data, $userId);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['_old'] = $data;
            flash('error', $e->getMessage());
            $this->redirect('comisiones/create');
        }
        flash('success', 'Comisión registrada correctamente.');
        $this->redirect('comisiones/' . $id);
    }

    public function show(Request $request, string $id): never
    {
        $comision = $this->comisiones->find((int) $id);
        if ($comision === null) {
            flash('error', 'Comisión no encontrada.');
            $this->redirect('comisiones');
        }
        $ultimoMantenimiento = $this->comisiones->getUltimoMantenimiento((int) $comision['vehiculo_id']);
        $this->render('comisiones.show', [
            'comision' => $comision,
            'ultimo_mantenimiento' => $ultimoMantenimiento,
            'luces_tablero' => $this->comisiones->getLucesCatalog(),
            'liquidos' => $this->comisiones->getLiquidosCatalog(),
            'nivel_opciones' => $this->comisiones->getNivelOpciones(),
        ]);
    }

    public function cargarDocumento(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $tipo = (string) $request->input('tipo', 'salida');
        $error = $this->comisiones->cargarDocumento((int) $id, $tipo, $request->file('archivo'));
        flash($error ? 'error' : 'success', $error ?? 'Documento firmado cargado correctamente.');
        $this->redirect('comisiones/' . $id . '#documentos');
    }

    public function edit(Request $request, string $id): never
    {
        $comision = $this->comisiones->find((int) $id);
        if ($comision === null) {
            flash('error', 'Comisión no encontrada.');
            $this->redirect('comisiones');
        }
        $this->render('comisiones.edit', array_merge($this->comisiones->getFormData(), ['comision' => $comision]));
    }

    public function update(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        try {
            $updated = $this->comisiones->update((int) $id, $request->all());
        } catch (\InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            $this->redirect('comisiones/' . $id . '/edit');
        }
        if (!$updated) {
            flash('error', 'No se pudo actualizar la comisión.');
            $this->redirect('comisiones/' . $id . '/edit');
        }
        flash('success', 'Comisión actualizada correctamente.');
        $this->redirect('comisiones/' . $id);
    }

    public function iniciar(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $error = $this->comisiones->iniciar((int) $id);
        flash($error ? 'error' : 'success', $error ?? 'Comisión iniciada correctamente.');
        $this->redirect('comisiones/' . $id . '#regreso');
    }

    public function finalizar(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $error = $this->comisiones->finalizar((int) $id, $request->all());
        flash($error ? 'error' : 'success', $error ?? 'Comisión finalizada correctamente.');
        $this->redirect('comisiones/' . $id . '#regreso');
    }

    public function cancelar(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $error = $this->comisiones->cancelar((int) $id, (string) $request->input('motivo', ''));
        flash($error ? 'error' : 'success', $error ?? 'Comisión cancelada.');
        $this->redirect('comisiones');
    }
}
