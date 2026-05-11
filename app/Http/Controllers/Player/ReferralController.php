<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralReward;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $service) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $referrals = Referral::with('referee:id,name,email,display_name,account_level,email_verified_at')
            ->where('referrer_id', $user->id)
            ->latest('created_at')
            ->get()
            ->map(fn (Referral $r) => [
                'id'         => $r->id,
                'status'     => $r->status,
                'created_at' => $r->created_at,
                'validated_at' => $r->validated_at,
                'referee'    => [
                    'id'           => $r->referee?->id,
                    'name'         => $r->referee?->display_name ?? $r->referee?->name,
                    'level'        => $r->referee?->account_level ?? 1,
                    'verified'     => $r->referee?->email_verified_at !== null,
                ],
            ]);

        return Inertia::render('Player/Referral', [
            'referralCode'   => $user->referral_code,
            'referralLink'   => url("/r/{$user->referral_code}"),
            'referredCount'  => $referrals->count(),
            'validatedCount' => $referrals->whereIn('status', [Referral::STATUS_VALIDATED, Referral::STATUS_REWARDED])->count(),
            'pendingRewards' => $this->service->pendingRewardsFor($user),
            'referrals'      => $referrals->values(),
        ]);
    }

    public function claim(Request $request, ReferralReward $reward): RedirectResponse
    {
        $this->authorize('claim', $reward);

        try {
            $this->service->claim($request->user(), $reward, $request->ip());
            return back()->with('status', 'Récompense réclamée.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['referral' => $e->getMessage()]);
        }
    }
}
