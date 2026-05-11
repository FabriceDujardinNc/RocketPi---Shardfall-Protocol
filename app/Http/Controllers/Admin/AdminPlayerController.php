<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlayerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();

        $players = User::query()
            ->when($search, fn($q) => $q->where('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Players/Index', [
            'players' => $players,
            'q' => $search,
        ]);
    }

    public function show(User $user): Response
    {
        $currencies = Currency::where('user_id', $user->id)
            ->orderBy('type')
            ->get(['type', 'balance'])
            ->map(fn ($c) => ['type' => $c->type, 'balance' => (int) $c->balance])
            ->values();

        return Inertia::render('Admin/Players/Show', [
            'user' => $user->only([
                'id', 'name', 'email', 'role', 'account_level',
                'is_banned', 'ban_reason', 'banned_at',
                'created_at', 'last_active_at', 'referral_code',
            ]),
            'currencies' => $currencies,
        ]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $this->authorize('ban', $user);

        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];

        $user->update([
            'is_banned' => true,
            'ban_reason' => $reason,
            'banned_at' => now(),
        ]);

        return back()->with('status', 'Joueur banni.');
    }

    public function unban(User $user): RedirectResponse
    {
        $this->authorize('ban', $user);

        $user->update([
            'is_banned' => false,
            'ban_reason' => null,
            'banned_at' => null,
        ]);

        return back()->with('status', 'Bannissement levé.');
    }

    /**
     * Crédit (ou débit avec montant négatif) d'une currency au joueur.
     * Atomique : lockForUpdate sur la row Currency, incrément, transaction log.
     * Borné à [-1M, +1M] par appel pour éviter les fat-fingers admin.
     */
    public function grantCurrency(Request $request, User $user): RedirectResponse
    {
        $this->authorize('ban', $user); // Même niveau de privilège (admin only, pas sur super_admin)

        $validated = $request->validate([
            'currency_type' => 'required|string|max:64',
            'amount'        => 'required|integer|min:-1000000|max:1000000|not_in:0',
            'reason'        => 'required|string|max:255',
        ]);

        $type   = $validated['currency_type'];
        $amount = (int) $validated['amount'];
        $reason = $validated['reason'];

        DB::transaction(function () use ($user, $type, $amount, $reason, $request) {
            $currency = Currency::firstOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                ['balance' => 0]
            );
            $locked = Currency::where('id', $currency->id)->lockForUpdate()->first();

            $newBalance = $locked->balance + $amount;
            if ($newBalance < 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => "Le solde résultant serait négatif (actuel : {$locked->balance}, après : {$newBalance}).",
                ]);
            }

            $locked->update(['balance' => $newBalance]);

            Transaction::create([
                'user_id'        => $user->id,
                'currency_type'  => $type,
                'amount'         => $amount,
                'balance_after'  => $newBalance,
                'reason'         => 'admin_grant',
                'description'    => "{$reason} (par admin#{$request->user()->id})",
                'ip_address'     => $request->ip(),
            ]);
        });

        $verb = $amount > 0 ? 'créditée' : 'débitée';
        $abs  = abs($amount);
        return back()->with('status', "Currency {$type} {$verb} de {$abs} pour {$user->email}.");
    }
}
