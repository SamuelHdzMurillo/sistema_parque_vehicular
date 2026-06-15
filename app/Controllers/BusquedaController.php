<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\BusquedaService;

final class BusquedaController extends BaseController
{
    public function __construct(
        private readonly BusquedaService $busqueda = new BusquedaService()
    ) {
    }

    public function index(Request $request): never
    {
        $q = trim((string) $request->input('q', ''));
        $results = $this->busqueda->search($q);

        $wantsJson = $request->input('format') === 'json'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($wantsJson) {
            Response::json(['query' => $q, 'results' => $results]);
        }

        $this->render('busqueda.index', ['q' => $q, 'results' => $results]);
    }
}
