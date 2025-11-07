<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        /** @var view-string $viewName */
        $viewName = 'dashboard';
        return view($viewName);
    }
}
