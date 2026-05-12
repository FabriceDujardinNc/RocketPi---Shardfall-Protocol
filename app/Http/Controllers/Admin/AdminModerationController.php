<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayerReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Modération admin — gestion des signalements joueurs.
 *
 * Workflow :
 *   pending → reviewed (admin a vu mais pas tranché) →
 *   { dismissed (rejet, RAS) | sanctioned (ban appliqué) }
 *
 * Toute action est immuable une fois `reviewed_at` rempli — audit.
 */
class AdminModerationController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status', 'pending')->toString();
        $reason = $request->string('reason')->toString();

        $reports = PlayerReport::query()
            ->with([
                'reporter:id,name,display_name,slug',
                'reported:id,name,display_name,slug,is_banned',
                'session:id,mode,rank_type,started_at',
                'reviewer:id,name,display_name',
            ])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($reason, fn ($q) => $q->where('reason', $reason))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        // Comptes par statut pour les badges de l'UI
        $counts = PlayerReport::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        return Inertia::render('Admin/Moderation/Index', [
            'reports' => $reports,
            'counts'  => $counts,
            'filters' => (object) ['status' => $status, 'reason' => $reason],
            'reasons' => PlayerReport::REASONS,
            'statuses' => PlayerReport::STATUSES,
        ]);
    }

    /**
     * Rejet : RAS, le signalement n'a pas lieu d'être.
     */
    public function dismiss(Request $request, PlayerReport $report): RedirectResponse
    {
        $request->validate(['notes' => 'nullable|string|max:1000']);

        if ($report->status !== 'pending' && $report->status !== 'reviewed') {
            return back()->withErrors(['report' => 'Signalement déjà clôturé.']);
        }

        $report->update([
            'status'      => 'dismissed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_notes' => $request->input('notes'),
        ]);

        return back()->with('status', 'Signalement rejeté.');
    }

    /**
     * Sanction : ban le joueur signalé. Optionnel : durée + raison.
     * La table `users.is_banned/ban_reason/banned_at` est mise à jour de manière
     * audited (cohérent avec `AdminPlayerController::ban`).
     */
    public function sanction(Request $request, PlayerReport $report): RedirectResponse
    {
        $validated = $request->validate([
            'ban_reason' => 'required|string|max:500',
            'notes'      => 'nullable|string|max:1000',
        ]);

        if (in_array($report->status, ['dismissed', 'sanctioned'], true)) {
            return back()->withErrors(['report' => 'Signalement déjà clôturé.']);
        }

        DB::transaction(function () use ($report, $validated, $request) {
            $reported = User::lockForUpdate()->find($report->reported_id);
            if ($reported && ! $reported->isAdmin()) {
                $reported->update([
                    'is_banned'  => true,
                    'ban_reason' => $validated['ban_reason'].' (report #'.$report->id.', admin#'.$request->user()->id.')',
                    'banned_at'  => now(),
                ]);
            }
            $report->update([
                'status'      => 'sanctioned',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('status', 'Joueur banni et signalement clôturé.');
    }

    /**
     * Marque "vu" sans trancher (utilitaire pour passer du tableau pending au reviewed).
     */
    public function markReviewed(Request $request, PlayerReport $report): RedirectResponse
    {
        if ($report->status !== 'pending') {
            return back();
        }
        $report->update([
            'status'      => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        return back()->with('status', 'Marqué comme vu.');
    }
}
