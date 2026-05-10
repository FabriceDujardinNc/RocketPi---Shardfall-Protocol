<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMissionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Missions/Index', ['missions' => []]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Missions/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.missions.index');
    }

    public function show(int $id): Response
    {
        return Inertia::render('Admin/Missions/Show', ['id' => $id]);
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Missions/Edit', ['id' => $id]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        return redirect()->route('admin.missions.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        return redirect()->route('admin.missions.index');
    }
}
