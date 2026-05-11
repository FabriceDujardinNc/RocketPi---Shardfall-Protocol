<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Operator;
use App\Models\PlayerOperator;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(private readonly ShopService $service) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $shards  = (int) (Currency::where('user_id', $user->id)->where('type', Currency::TYPE_SHARDS)->value('balance')  ?? 0);
        $credits = (int) (Currency::where('user_id', $user->id)->where('type', Currency::TYPE_CREDITS)->value('balance') ?? 0);

        // Solde de fragments par opérateur (currency.type LIKE 'fragments_*')
        $fragmentBalances = Currency::where('user_id', $user->id)
            ->where('type', 'like', 'fragments_%')
            ->where('balance', '>', 0)
            ->pluck('balance', 'type'); // [fragments_VX-01 => 12, ...]

        $codenames = $fragmentBalances->keys()->map(fn ($t) => substr($t, strlen('fragments_')))->all();

        $ownedOpIds = PlayerOperator::where('user_id', $user->id)->pluck('operator_id')->all();

        $exchanges = Operator::query()
            ->whereIn('codename', $codenames)
            ->get(['id', 'slug', 'name', 'codename', 'rarity', 'faction', 'portrait_url'])
            ->map(function (Operator $op) use ($fragmentBalances, $ownedOpIds) {
                $cost = ShopService::FRAGMENTS_TO_OPERATOR[$op->rarity] ?? null;
                $have = (int) ($fragmentBalances['fragments_'.$op->codename] ?? 0);
                return [
                    'id'          => $op->id,
                    'slug'        => $op->slug,
                    'name'        => $op->name,
                    'codename'    => $op->codename,
                    'rarity'      => $op->rarity,
                    'faction'     => $op->faction,
                    'portrait_url'=> $op->portrait_url,
                    'fragments'   => $have,
                    'cost'        => $cost,
                    'affordable'  => $cost !== null && $have >= $cost,
                    'owned'       => in_array($op->id, $ownedOpIds, true),
                ];
            })
            ->sortByDesc('affordable')
            ->values();

        return Inertia::render('Player/Shop', [
            'packs'     => $this->service->listPacks($user),
            'shards'    => $shards,
            'credits'   => $credits,
            'exchanges' => $exchanges,
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $packId = $request->validate(['pack_id' => 'required|string'])['pack_id'];

        try {
            $result = $this->service->purchasePack($request->user(), $packId, $request->ip());
            return back()->with('status', "Pack {$result['pack_id']} acheté.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['shop' => $e->getMessage()]);
        }
    }

    public function redeemFragments(Request $request, Operator $operator): RedirectResponse
    {
        try {
            $result = $this->service->redeemFragments($request->user(), $operator, $request->ip());
            $verb = $result['is_new']
                ? "{$operator->name} recruté"
                : "Constellation {$operator->name} → C{$result['constellation']}";
            return back()->with('status', "{$verb} (–{$result['fragments_used']} fragments, reste {$result['fragments_left']}).");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['shop' => $e->getMessage()]);
        }
    }
}
