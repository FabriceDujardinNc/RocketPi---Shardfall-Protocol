<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Models\Banner;
use App\Models\Operator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBannerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Banner::class);

        $filters = $request->validate([
            'q'       => 'nullable|string|max:80',
            'type'    => 'nullable|in:permanent,event,faction,collab',
            'active'  => 'nullable|in:1,0',
            'trashed' => 'nullable|in:with,only',
        ]);

        $banners = Banner::query()
            ->when($filters['trashed'] ?? null, fn ($q, $t) => $t === 'only' ? $q->onlyTrashed() : $q->withTrashed())
            ->when($filters['q']       ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['type']    ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when(isset($filters['active']),   fn ($q) => $q->where('is_active', $filters['active'] === '1'))
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'filters' => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Banner::class);
        return Inertia::render('Admin/Banners/Create', $this->formDeps());
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $banner = Banner::create($request->validated());
        return redirect()->route('admin.banners.show', $banner)
            ->with('status', "Bannière « {$banner->name} » créée.");
    }

    public function show(Banner $banner): Response
    {
        $this->authorize('view', $banner);
        return Inertia::render('Admin/Banners/Show', [
            'banner' => $banner,
        ]);
    }

    public function edit(Banner $banner): Response
    {
        $this->authorize('update', $banner);
        return Inertia::render('Admin/Banners/Edit', [
            'banner' => $banner,
            ...$this->formDeps(),
        ]);
    }

    public function update(StoreBannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->update($request->validated());
        return redirect()->route('admin.banners.show', $banner)
            ->with('status', "Bannière « {$banner->name} » mise à jour.");
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);
        $banner->delete();
        return redirect()->route('admin.banners.index')
            ->with('status', "Bannière archivée.");
    }

    public function restore(string $slug): RedirectResponse
    {
        $banner = Banner::onlyTrashed()->where('slug', $slug)->firstOrFail();
        $this->authorize('restore', $banner);
        $banner->restore();
        return redirect()->route('admin.banners.show', $banner)
            ->with('status', "Bannière restaurée.");
    }

    public function activate(Banner $banner): RedirectResponse
    {
        $this->authorize('activate', $banner);
        $banner->update(['is_active' => ! $banner->is_active]);
        $msg = $banner->is_active ? 'Bannière activée.' : 'Bannière désactivée.';
        return back()->with('status', $msg);
    }

    private function formDeps(): array
    {
        return [
            'enums' => [
                'types' => Banner::TYPES,
            ],
            // Liste des codenames pour la sélection rate-up / featured
            'operators' => Operator::orderBy('name')
                ->get(['id', 'name', 'codename', 'rarity', 'faction'])
                ->map(fn ($o) => [
                    'codename' => $o->codename,
                    'name'     => $o->name,
                    'rarity'   => $o->rarity,
                    'faction'  => $o->faction,
                ])
                ->values(),
        ];
    }
}
