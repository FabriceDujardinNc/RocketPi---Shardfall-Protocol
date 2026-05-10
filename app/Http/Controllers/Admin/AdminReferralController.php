<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminReferralController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Referrals', [
            'referrals' => [],
            'suspectPatterns' => [],
        ]);
    }

    public function flag(Request $request, int $referral): RedirectResponse
    {
        return back()->with('status', "Parrainage {$referral} flaggé.");
    }
}
