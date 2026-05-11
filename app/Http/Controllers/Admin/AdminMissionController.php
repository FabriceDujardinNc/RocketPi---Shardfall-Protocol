<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMissionRequest;
use App\Models\Mission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Mission::class);

        $filters = $request->validate([
            'q'       => 'nullable|string|max:80',
            'type'    => 'nullable|in:daily,weekly,event,story,challenge',
            'active'  => 'nullable|in:1,0',
            'trashed' => 'nullable|in:with,only',
        ]);

        $missions = Mission::query()
            ->when($filters['trashed'] ?? null, fn ($q, $t) => $t === 'only' ? $q->onlyTrashed() : $q->withTrashed())
            ->when($filters['q']       ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters['type']    ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when(isset($filters['active']),   fn ($q) => $q->where('is_active', $filters['active'] === '1'))
            ->orderBy('type')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Missions/Index', [
            'missions' => $missions,
            'filters'  => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Mission::class);
        return Inertia::render('Admin/Missions/Create', ['enums' => $this->enums()]);
    }

    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $mission = Mission::create($request->validated());
        return redirect()->route('admin.missions.show', $mission)
            ->with('status', "Mission « {$mission->title} » créée.");
    }

    public function show(Mission $mission): Response
    {
        $this->authorize('view', $mission);
        return Inertia::render('Admin/Missions/Show', ['mission' => $mission]);
    }

    public function edit(Mission $mission): Response
    {
        $this->authorize('update', $mission);
        return Inertia::render('Admin/Missions/Edit', [
            'mission' => $mission,
            'enums'   => $this->enums(),
        ]);
    }

    public function update(StoreMissionRequest $request, Mission $mission): RedirectResponse
    {
        $mission->update($request->validated());
        return redirect()->route('admin.missions.show', $mission)
            ->with('status', "Mission « {$mission->title} » mise à jour.");
    }

    public function destroy(Mission $mission): RedirectResponse
    {
        $this->authorize('delete', $mission);
        $mission->delete();
        return redirect()->route('admin.missions.index')
            ->with('status', 'Mission archivée.');
    }

    public function restore(int $id): RedirectResponse
    {
        $mission = Mission::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $mission);
        $mission->restore();
        return redirect()->route('admin.missions.show', $mission)
            ->with('status', 'Mission restaurée.');
    }

    private function enums(): array
    {
        return [
            'types'           => Mission::TYPES,
            'objective_types' => Mission::OBJECTIVE_TYPES,
            'reward_types'    => Mission::REWARD_TYPES,
        ];
    }
}
