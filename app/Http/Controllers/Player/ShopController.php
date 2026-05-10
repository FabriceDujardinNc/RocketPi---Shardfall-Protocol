<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Currency;
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

        return Inertia::render('Player/Shop', [
            'packs'    => $this->service->listPacks($user),
            'shards'   => $shards,
            'credits'  => $credits,
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
}
