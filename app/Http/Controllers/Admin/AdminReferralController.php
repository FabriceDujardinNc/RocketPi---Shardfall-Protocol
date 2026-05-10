<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => 'nullable|in:pending,validated,rewarded,flagged',
            'q'      => 'nullable|string|max:80',
        ]);

        $query = Referral::with([
            'referrer:id,name,email,display_name',
            'referee:id,name,email,display_name,account_level,email_verified_at',
        ])->latest('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->whereHas('referrer', fn ($q) => $q->where('email', 'like', "%{$term}%"))
                ->orWhereHas('referee', fn ($q) => $q->where('email', 'like', "%{$term}%"));
        }

        $referrals = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/Referrals', [
            'referrals' => $referrals,
            'filters'   => $filters,
            'stats'     => [
                'total'      => Referral::count(),
                'pending'    => Referral::where('status', Referral::STATUS_PENDING)->count(),
                'validated'  => Referral::where('status', Referral::STATUS_VALIDATED)->count(),
                'rewarded'   => Referral::where('status', Referral::STATUS_REWARDED)->count(),
                'flagged'    => Referral::where('status', Referral::STATUS_FLAGGED)->count(),
                'same_ip'    => Referral::where('same_ip_as_referrer', true)->count(),
            ],
        ]);
    }

    public function flag(Request $request, Referral $referral): RedirectResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:255'])['reason'];

        $referral->update([
            'status'      => Referral::STATUS_FLAGGED,
            'flagged_at'  => now(),
            'flag_reason' => $reason,
        ]);

        return back()->with('status', "Parrainage #{$referral->id} flaggé.");
    }
}
