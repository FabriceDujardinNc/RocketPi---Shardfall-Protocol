<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TriggerAssetGenerationRequest;
use App\Models\Operator;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminAsset3dController extends Controller
{
    /**
     * Vue gestion 3D pour un opérateur : mesh de base + skins + accessoires liés.
     * Les armes sont globales (pas operator-specific) → page admin séparée si besoin.
     */
    public function show(Operator $operator): Response
    {
        $this->authorize('manage-content');

        $operator->load(['skins', 'accessories']);

        return Inertia::render('Admin/Operators/Assets', [
            'operator' => [
                'id'                     => $operator->id,
                'slug'                   => $operator->slug,
                'codename'               => $operator->codename,
                'name'                   => $operator->name,
                'faction'                => $operator->faction,
                'rarity'                 => $operator->rarity,
                'base_generation_status' => $operator->base_generation_status,
                'base_meshy_task_id'     => $operator->base_meshy_task_id,
                'base_model_url'         => $operator->base_model_url,
                'base_rig_version'       => $operator->base_rig_version,
                'updated_at'             => $operator->updated_at,
            ],
            'skins' => $operator->skins->map(fn ($s) => [
                'id'                => $s->id,
                'slug'              => $s->slug,
                'name'              => $s->name,
                'rarity'            => $s->rarity,
                'is_active'         => $s->is_active,
                'generation_status' => $s->generation_status,
                'meshy_task_id'     => $s->meshy_task_id,
                'texture_url'       => $s->texture_url,
                'preview_url'       => $s->preview_url,
                'updated_at'        => $s->updated_at,
            ])->values(),
            'accessories' => $operator->accessories->map(fn ($a) => [
                'id'                => $a->id,
                'slug'              => $a->slug,
                'name'              => $a->name,
                'slot'              => $a->slot,
                'socket_name'       => $a->socket_name,
                'is_default'        => (bool) $a->pivot->is_default,
                'generation_status' => $a->generation_status,
                'meshy_task_id'     => $a->meshy_task_id,
                'base_model_url'    => $a->base_model_url,
                'updated_at'        => $a->updated_at,
            ])->values(),
        ]);
    }

    public function trigger(
        TriggerAssetGenerationRequest $request,
        MeshyGenerationService $service,
        Operator $operator,
    ): RedirectResponse {
        $entity = $request->resolveEntity();

        try {
            $taskId = $service->generate($entity, force: $request->boolean('force'));
        } catch (MeshyException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Génération lancée (task {$taskId}).");
    }
}
