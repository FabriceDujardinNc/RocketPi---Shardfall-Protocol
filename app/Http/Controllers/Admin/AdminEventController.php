<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Models\Banner;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminEventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $now = now();
        $events = Event::query()
            ->with('banner:id,name')
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Event $e) => array_merge($e->toArray(), [
                'phase' => $e->starts_at?->gt($now) ? 'scheduled'
                    : ($e->ends_at?->lt($now) ? 'expired' : 'current'),
            ]));

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Event::class);
        return Inertia::render('Admin/Events/Create', $this->formDeps());
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $e = Event::create($request->validated());
        return redirect()->route('admin.events.edit', $e)
            ->with('status', "Événement « {$e->name} » créé.");
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);
        return Inertia::render('Admin/Events/Edit', array_merge($this->formDeps(), [
            'event' => $event->load('banner:id,name'),
        ]));
    }

    public function update(StoreEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());
        return redirect()->route('admin.events.edit', $event)
            ->with('status', "Événement « {$event->name} » mis à jour.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);
        $event->delete();
        return redirect()->route('admin.events.index')->with('status', 'Événement supprimé.');
    }

    private function formDeps(): array
    {
        return [
            'enums'   => ['types' => Event::TYPES],
            'banners' => Banner::orderBy('name')->get(['id', 'name', 'type'])->values(),
        ];
    }
}
