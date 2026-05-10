<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBannerController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Banners/Index', ['banners' => []]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Banners/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.banners.index');
    }

    public function show(int $id): Response
    {
        return Inertia::render('Admin/Banners/Show', ['id' => $id]);
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Banners/Edit', ['id' => $id]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        return redirect()->route('admin.banners.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        return redirect()->route('admin.banners.index');
    }

    public function activate(Request $request, int $banner): RedirectResponse
    {
        return back()->with('status', "Bannière {$banner} activée.");
    }
}
