<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\CombustibleService;
use RuntimeException;

final class CombustibleController extends BaseController
{
    public function __construct(
        private readonly CombustibleService $combustible = new CombustibleService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $vehiculoId = $request->input('vehiculo_id');
        $result = $this->combustible->paginate($page, $vehiculoId ? (int) $vehiculoId : null);
        $this->render('combustible.index', array_merge($result, $this->combustible->getFormData()));
    }

    public function create(Request $request): never
    {
        $vehiculoId = $request->input('vehiculo_id');
        $this->render('combustible.create', $this->combustible->getFormData(
            is_numeric($vehiculoId) ? (int) $vehiculoId : null
        ));
    }

    public function store(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        try {
            $this->combustible->create($request->all(), $userId);
            flash('success', 'Carga de combustible registrada correctamente.');
            $this->redirect('combustible');
        } catch (RuntimeException $e) {
            $_SESSION['_old'] = $request->all();
            flash('error', $e->getMessage());
            $this->redirect('combustible/create');
        }
    }
}
