<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\MatchSession;
use App\Models\PlayerReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Soumission de signalement joueur (Phase 4).
 *
 * Anti-abus :
 *  - Rate limit 5/heure par utilisateur (`throttle:5,60` sur la route)
 *  - Unique index reporter+reported+session côté BDD (pas de spam doublons)
 *  - Ne peut pas se signaler soi-même
 *  - Si banni → 403 (signalé)
 */
class ReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reported_id'      => 'required|integer|exists:users,id',
            'match_session_id' => 'nullable|integer|exists:match_sessions,id',
            'reason'           => 'required|string|in:'.implode(',', PlayerReport::REASONS),
            'description'      => 'nullable|string|max:2000',
        ], [
            'reason.in' => 'Raison invalide.',
        ]);

        $reporterId = $request->user()->id;

        if ((int) $validated['reported_id'] === $reporterId) {
            return back()->withErrors(['reported_id' => 'Tu ne peux pas te signaler toi-même.']);
        }

        // Anti-spam : un même couple reporter→reported sur la même session existe déjà ?
        $existing = PlayerReport::query()
            ->where('reporter_id', $reporterId)
            ->where('reported_id', $validated['reported_id'])
            ->where('match_session_id', $validated['match_session_id'] ?? null)
            ->exists();
        if ($existing) {
            return back()->withErrors(['report' => 'Tu as déjà signalé ce joueur pour cette session.']);
        }

        // Si la session est fournie, vérifier qu'elle implique le reporter (sinon spam).
        if (! empty($validated['match_session_id'])) {
            $owns = MatchSession::where('id', $validated['match_session_id'])
                ->where('user_id', $reporterId)
                ->exists();
            if (! $owns) {
                return back()->withErrors(['match_session_id' => 'Tu ne peux signaler que pour des matchs auxquels tu as participé.']);
            }
        }

        PlayerReport::create([
            'reporter_id'      => $reporterId,
            'reported_id'      => $validated['reported_id'],
            'match_session_id' => $validated['match_session_id'] ?? null,
            'reason'           => $validated['reason'],
            'description'      => $validated['description'] ?? null,
            'status'           => 'pending',
        ]);

        return back()->with('status', 'Signalement transmis aux modérateurs.');
    }
}
