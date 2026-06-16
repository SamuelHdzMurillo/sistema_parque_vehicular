<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\DashboardService;

final class DashboardController extends BaseController
{
    public function __construct(
        private readonly DashboardService $dashboard = new DashboardService()
    ) {
    }

    public function index(Request $request): never
    {
        $data = $this->dashboard->getDashboardData();
        $this->render('dashboard.index', $data);
    }
}
