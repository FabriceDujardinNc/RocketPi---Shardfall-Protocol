<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PlayerOperator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerApiController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'user' => $user->only([
                'id', 'name', 'display_name', 'email', 'role', 'avatar_url',
                'account_level', 'account_xp', 'referral_code',
            ]),
        ]);
    }

    public function operators(Request $request): JsonResponse
    {
        $owned = PlayerOperator::with('operator:id,name,codename,faction,role,rarity,portrait_url')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json(['operators' => $owned]);
    }

    public function currencies(Request $request): JsonResponse
    {
        $currencies = Currency::where('user_id', $request->user()->id)
            ->get(['type', 'balance']);

        return response()->json(['currencies' => $currencies]);
    }
}
