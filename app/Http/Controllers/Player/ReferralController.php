<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Player/Referral', [
            'referralCode' => $user->referral_code,
            'referralLink' => url("/r/{$user->referral_code}"),
            'referredCount' => $user->referrals()->count(),
            'pendingRewards' => [],
        ]);
    }
}
