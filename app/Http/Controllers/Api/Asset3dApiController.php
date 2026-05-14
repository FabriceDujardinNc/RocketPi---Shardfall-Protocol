<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TriggerGenerationRequest;
use App\Http\Resources\Asset3d\AccessoryResource;
use App\Http\Resources\Asset3d\OperatorAsset3dResource;
use App\Http\Resources\Asset3d\OperatorSkinResource;
use App\Http\Resources\Asset3d\WeaponResource;
use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints API consommés par :
 *  - le serveur MCP Node.js (Claude Desktop) — read-only sauf trigger
 *  - le client Unity WebGL — pour charger les loadouts
 *
 * Auth : Sanctum + ability `mcp:read` (lecture) ou `mcp:write` (trigger).
 * Les tokens sont créés côté admin via une commande dédiée (à venir) ou
 * directement via tinker en attendant.
 */
class Asset3dApiController extends Controller
{
    // ─── LISTING ───────────────────────────────────────────────────────────

    public function listOperators(): AnonymousResourceCollection
    {
        return OperatorAsset3dResource::collection(
            Operator::query()
                ->orderBy('codename')
                ->get()
        );
    }

    public function showOperator(string $slug): OperatorAsset3dResource
    {
        $operator = Operator::query()
            ->where('slug', $slug)
            ->orWhere('codename', $slug)
            ->with(['skins' => fn ($q) => $q->where('is_active', true)])
            ->with(['accessories' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        return new OperatorAsset3dResource($operator);
    }

    public function listSkins(string $operatorSlug): AnonymousResourceCollection
    {
        $operator = Operator::query()
            ->where('slug', $operatorSlug)
            ->orWhere('codename', $operatorSlug)
            ->firstOrFail();

        return OperatorSkinResource::collection(
            $operator->skins()->where('is_active', true)->orderBy('rarity')->get()
        );
    }

    public function listWeapons(Request $request): AnonymousResourceCollection
    {
        $query = Weapon::query()->where('is_active', true);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return WeaponResource::collection(
            $query->with(['skins' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('category')
                ->orderBy('slug')
                ->get()
        );
    }

    public function listAccessories(Request $request): AnonymousResourceCollection
    {
        $query = Accessory::query()->where('is_active', true);

        if ($slot = $request->query('slot')) {
            $query->where('slot', $slot);
        }

        return AccessoryResource::collection(
            $query->orderBy('slot')->orderBy('slug')->get()
        );
    }

    // ─── GENERATION ────────────────────────────────────────────────────────

    public function generationStatus(string $entityType, string $slug): JsonResponse
    {
        $entity = match ($entityType) {
            'operator'      => Operator::query()->where('slug', $slug)->firstOrFail(),
            'operator_skin' => OperatorSkin::query()->where('slug', $slug)->firstOrFail(),
            'weapon'        => Weapon::query()->where('slug', $slug)->firstOrFail(),
            'accessory'     => Accessory::query()->where('slug', $slug)->firstOrFail(),
            default         => abort(404, "Unknown entity type: {$entityType}"),
        };

        $service = app(MeshyGenerationService::class);
        [$statusCol, $taskIdCol] = $service->columnsFor($entity);

        return response()->json([
            'entity_type'       => $entityType,
            'slug'              => $slug,
            'generation_status' => $entity->{$statusCol},
            'meshy_task_id'     => $entity->{$taskIdCol},
        ]);
    }

    public function triggerGeneration(
        TriggerGenerationRequest $request,
        MeshyGenerationService $service,
    ): JsonResponse {
        $entity = $request->resolveEntity();

        try {
            $taskId = $service->generate($entity, force: (bool) $request->boolean('force'));
        } catch (MeshyException $e) {
            return response()->json([
                'error'   => 'meshy_generation_refused',
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'entity_type'   => $request->input('entity_type'),
            'slug'          => $request->input('slug'),
            'meshy_task_id' => $taskId,
            'status'        => 'queued',
        ], 202);
    }
}
