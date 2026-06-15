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
        $result = $this->auditoria->paginate($page);
        $this->render('auditoria.index', $result);
    }
}
