<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin Controller
 *
 * Handles admin panel pages.
 * Access is restricted to users with the wildcard (*) permission via middleware.
 */
class AdminController extends Controller
{
    /**
     * Display the role management page.
     *
     * @return View
     */
    public function roles(): View
    {
        return view('admin.roles.index');
    }

    /**
     * Display the create role page.
     *
     * @return View
     */
    public function createRole(): View
    {
        return view('admin.roles.create');
    }

    /**
     * Display the edit role page.
     *
     * @param int $id
     * @return View
     */
    public function editRole(int $id): View
    {
        return view('admin.roles.edit', ['roleId' => $id]);
    }
}
