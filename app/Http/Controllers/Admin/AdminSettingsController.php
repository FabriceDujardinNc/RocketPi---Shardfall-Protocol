<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'maintenance' => false,
                'gacha_enabled' => true,
                'shop_enabled' => true,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        return back()->with('status', 'Paramètres mis à jour.');
    }
}
