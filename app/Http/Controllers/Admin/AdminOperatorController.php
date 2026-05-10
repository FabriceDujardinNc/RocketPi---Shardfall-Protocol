<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOperatorController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Operators/Index', ['operators' => []]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Operators/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.operators.index');
    }

    public function show(int $id): Response
    {
        return Inertia::render('Admin/Operators/Show', ['id' => $id]);
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Operators/Edit', ['id' => $id]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        return redirect()->route('admin.operators.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        return redirect()->route('admin.operators.index');
    }
}
