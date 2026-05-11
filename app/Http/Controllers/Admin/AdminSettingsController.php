<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Setting::class);

        return Inertia::render('Admin/Settings', [
            'settings' => Setting::orderBy('key')->get()
                ->map(fn (Setting $s) => [
                    'key'        => $s->key,
                    'type'       => $s->type,
                    'label'      => $s->label,
                    'description'=> $s->description,
                    'value'      => $s->typedValue(),
                ])
                ->values(),
        ]);
    }

    /**
     * Bulk update : un seul PATCH avec un payload {key => value} pour les flags.
     * Les valeurs sont coercées selon le `type` stocké en BDD pour éviter qu'un
     * admin envoie une string là où un bool était attendu.
     */
    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'values'   => ['required', 'array'],
            'values.*' => ['nullable'],
        ]);

        $known = Setting::pluck('type', 'key');
        $updated = 0;

        foreach ($payload['values'] as $key => $value) {
            if (! $known->has($key)) continue;
            $this->authorize('update', Setting::find($key));
            Setting::put($key, $value, $known[$key]);
            $updated++;
        }

        return back()->with('status', "{$updated} paramètre(s) mis à jour.");
    }
}
