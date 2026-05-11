<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAchievementRequest;
use App\Models\Achievement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAchievementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Achievement::class);

        $filters = $request->validate([
            'q'        => 'nullable|string|max:80',
            'category' => 'nullable|in:collection,combat,social,progression,special',
            'hidden'   => 'nullable|in:1,0',
        ]);

        $achievements = Achievement::query()
            ->withCount(['userProgress as completed_count' => fn ($q) => $q->where('completed', true)])
            ->when($filters['q']        ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$v}%")->orWhere('key', 'like', "%{$v}%")))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when(isset($filters['hidden']), fn ($q) => $q->where('is_hidden', $filters['hidden'] === '1'))
            ->orderBy('category')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Achievements/Index', [
            'achievements' => $achievements,
            'filters'      => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Achievement::class);
        return Inertia::render('Admin/Achievements/Create', ['enums' => ['categories' => Achievement::CATEGORIES]]);
    }

    public function store(StoreAchievementRequest $request): RedirectResponse
    {
        $a = Achievement::create($request->validated());
        return redirect()->route('admin.achievements.edit', $a)
            ->with('status', "Achievement « {$a->title} » créé.");
    }

    public function edit(Achievement $achievement): Response
    {
        $this->authorize('update', $achievement);
        return Inertia::render('Admin/Achievements/Edit', [
            'achievement' => $achievement,
            'enums'       => ['categories' => Achievement::CATEGORIES],
        ]);
    }

    public function update(StoreAchievementRequest $request, Achievement $achievement): RedirectResponse
    {
        $achievement->update($request->validated());
        return redirect()->route('admin.achievements.edit', $achievement)
            ->with('status', "Achievement « {$achievement->title} » mis à jour.");
    }

    public function destroy(Achievement $achievement): RedirectResponse
    {
        $this->authorize('delete', $achievement);
        $achievement->delete();
        return redirect()->route('admin.achievements.index')
            ->with('status', 'Achievement supprimé.');
    }
}
