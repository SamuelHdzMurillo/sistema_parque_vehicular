<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\AuditService;

final class AuditoriaController extends BaseController
{
    public function __construct(
        private readonly AuditService $auditoria = new AuditService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $modulo = trim((string) $request->input('modulo', ''));
        $accion = trim((string) $request->input('accion', ''));
        $result = $this->auditoria->paginate(
            $page,
            30,
            $modulo !== '' ? $modulo : null,
            $accion !== '' ? $accion : null
        );
        $result['filtro_modulo'] = $modulo;
        $result['filtro_accion'] = $accion;
        $this->render('auditoria.index', $result);
    }
}
