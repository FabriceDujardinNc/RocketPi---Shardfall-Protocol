<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBattlePassRequest;
use App\Http\Requests\Admin\UpdateBattlePassTiersRequest;
use App\Models\BattlePass;
use App\Models\BattlePassTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminBattlePassController extends Controller
{
    /**
     * Mission : permettre l'admin d'éditer chaque saison Battle Pass et ses 50 paliers,
     * avec une contrainte centrale : deux saisons ne peuvent pas se chevaucher temporellement.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', BattlePass::class);

        $now = now();
        $passes = BattlePass::query()
            ->withCount('tiers')
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (BattlePass $bp) => [
                'id'                    => $bp->id,
                'slug'                  => $bp->slug,
                'name'                  => $bp->name,
                'season_number'         => $bp->season_number,
                'total_tiers'           => $bp->total_tiers,
                'tiers_count'           => $bp->tiers_count,
                'premium_price_shards'  => $bp->premium_price_shards,
                'premium_price_tickets' => $bp->premium_price_tickets,
                'starts_at'             => $bp->starts_at,
                'ends_at'               => $bp->ends_at,
                'is_active'             => $bp->is_active,
                // État dérivé pour l'UI (scheduled / current / expired)
                'phase' => $bp->starts_at?->gt($now) ? 'scheduled'
                    : ($bp->ends_at?->lt($now) ? 'expired' : 'current'),
            ]);

        return Inertia::render('Admin/BattlePass/Index', [
            'battlePasses' => $passes,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', BattlePass::class);
        return Inertia::render('Admin/BattlePass/Create');
    }

    public function store(StoreBattlePassRequest $request): RedirectResponse
    {
        $bp = DB::transaction(function () use ($request) {
            $bp = BattlePass::create($request->validated());
            $this->seedDefaultTiers($bp);
            return $bp;
        });

        return redirect()
            ->route('admin.battle-passes.edit', $bp)
            ->with('status', "Saison « {$bp->name} » créée avec {$bp->total_tiers} paliers par défaut.");
    }

    public function show(BattlePass $battlePass): Response
    {
        $this->authorize('view', $battlePass);
        return Inertia::render('Admin/BattlePass/Show', [
            'battlePass' => $battlePass->load('tiers'),
        ]);
    }

    public function edit(BattlePass $battlePass): Response
    {
        $this->authorize('update', $battlePass);
        return Inertia::render('Admin/BattlePass/Edit', [
            'battlePass' => $battlePass->load('tiers'),
        ]);
    }

    public function update(StoreBattlePassRequest $request, BattlePass $battlePass): RedirectResponse
    {
        $battlePass->update($request->validated());

        // Si on change total_tiers, on aligne les rows. Ne supprime jamais
        // d'avance — un admin verbeux peut juste laisser les nouveaux paliers vides.
        if ($battlePass->wasChanged('total_tiers')) {
            $this->ensureTierCount($battlePass);
        }

        return redirect()
            ->route('admin.battle-passes.edit', $battlePass)
            ->with('status', "Saison « {$battlePass->name} » mise à jour.");
    }

    public function destroy(BattlePass $battlePass): RedirectResponse
    {
        $this->authorize('delete', $battlePass);
        $battlePass->delete();
        return redirect()->route('admin.battle-passes.index')
            ->with('status', "Saison supprimée. Les progressions joueurs ont été cascade-deleted.");
    }

    /**
     * Bulk update des paliers : un seul appel met à jour les N tiers (xp_required,
     * free_reward, premium_reward, is_milestone) en transaction. Les tiers absents
     * du payload restent intacts ; ceux présents mais non-existants sont créés.
     */
    public function updateTiers(UpdateBattlePassTiersRequest $request, BattlePass $battlePass): RedirectResponse
    {
        DB::transaction(function () use ($request, $battlePass) {
            foreach ($request->input('tiers') as $payload) {
                BattlePassTier::updateOrCreate(
                    [
                        'battle_pass_id' => $battlePass->id,
                        'tier_number'    => $payload['tier_number'],
                    ],
                    [
                        'xp_required'    => $payload['xp_required'],
                        'free_reward'    => $payload['free_reward']    ?? null,
                        'premium_reward' => $payload['premium_reward'] ?? null,
                        'is_milestone'   => $payload['is_milestone']   ?? false,
                    ]
                );
            }
        });

        return back()->with('status', count($request->input('tiers')) . ' palier(s) mis à jour.');
    }

    private function seedDefaultTiers(BattlePass $bp): void
    {
        $milestones = [5, 10, 25, 50];
        for ($tier = 1; $tier <= $bp->total_tiers; $tier++) {
            BattlePassTier::firstOrCreate(
                ['battle_pass_id' => $bp->id, 'tier_number' => $tier],
                [
                    'xp_required'    => $tier * 250,
                    'free_reward'    => [['type' => 'credits', 'amount' => 200]],
                    'premium_reward' => [['type' => 'shards',  'amount' => 30]],
                    'is_milestone'   => in_array($tier, $milestones, true),
                ]
            );
        }
    }

    private function ensureTierCount(BattlePass $bp): void
    {
        $existing = BattlePassTier::where('battle_pass_id', $bp->id)->count();
        if ($existing >= $bp->total_tiers) {
            return;
        }
        for ($tier = $existing + 1; $tier <= $bp->total_tiers; $tier++) {
            BattlePassTier::create([
                'battle_pass_id' => $bp->id,
                'tier_number'    => $tier,
                'xp_required'    => $tier * 250,
                'free_reward'    => [['type' => 'credits', 'amount' => 200]],
                'premium_reward' => [['type' => 'shards',  'amount' => 30]],
                'is_milestone'   => false,
            ]);
        }
    }
}
