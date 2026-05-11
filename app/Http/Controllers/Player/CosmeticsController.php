<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Cosmetic;
use App\Services\CosmeticService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CosmeticsController extends Controller
{
    public function __construct(private readonly CosmeticService $service) {}

    public function index(Request $request): Response
    {
        $inventory = $this->service->inventory($request->user());

        return Inertia::render('Player/Cosmetics', [
            'inventory' => $inventory->values(),
            'totalCount' => $inventory->count(),
        ]);
    }

    public function equip(Request $request, Cosmetic $cosmetic): RedirectResponse
    {
        try {
            $this->service->equip($request->user(), $cosmetic);
            return back()->with('status', "« {$cosmetic->name} » équipé.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cosmetic' => $e->getMessage()]);
        }
    }

    public function unequip(Request $request, Cosmetic $cosmetic): RedirectResponse
    {
        $this->service->unequip($request->user(), $cosmetic);
        return back()->with('status', "« {$cosmetic->name} » retiré.");
    }
}
