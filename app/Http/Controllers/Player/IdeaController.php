<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Models\IdeaVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Création, modification, suppression et votes d'idées par les utilisateurs
 * connectés. Lecture côté Public\IdeaController.
 */
class IdeaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:6', 'max:120'],
            'body'  => ['required', 'string', 'min:20', 'max:4000'],
        ]);

        $idea = Idea::create([
            'user_id' => $request->user()->id,
            'title'   => $validated['title'],
            'body'    => $validated['body'],
            'status'  => Idea::STATUS_OPEN,
        ]);

        return redirect()->route('ideas.show', $idea->slug)
            ->with('status', 'Idée publiée.');
    }

    public function update(Request $request, Idea $idea): RedirectResponse
    {
        abort_unless($idea->user_id === $request->user()->id, 403);
        abort_if($idea->status !== Idea::STATUS_OPEN, 403,
            'Une idée déjà acceptée/refusée ne peut plus être éditée.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:6', 'max:120'],
            'body'  => ['required', 'string', 'min:20', 'max:4000'],
        ]);

        $idea->update($validated);

        return redirect()->route('ideas.show', $idea->slug)
            ->with('status', 'Idée mise à jour.');
    }

    public function destroy(Request $request, Idea $idea): RedirectResponse
    {
        abort_unless($idea->user_id === $request->user()->id, 403);

        $idea->delete();

        return redirect()->route('ideas.index')->with('status', 'Idée supprimée.');
    }

    /**
     * Ajoute un vote du user courant. Atomique (lock sur Idea) pour garantir
     * la cohérence du compteur dénormalisé `votes_count`.
     */
    public function vote(Request $request, Idea $idea): RedirectResponse
    {
        DB::transaction(function () use ($request, $idea) {
            $locked = Idea::where('id', $idea->id)->lockForUpdate()->first();
            $created = IdeaVote::firstOrCreate([
                'idea_id' => $locked->id,
                'user_id' => $request->user()->id,
            ]);
            if ($created->wasRecentlyCreated) {
                $locked->increment('votes_count');
            }
        });

        return back()->with('status', 'Merci pour ton vote !');
    }

    public function unvote(Request $request, Idea $idea): RedirectResponse
    {
        DB::transaction(function () use ($request, $idea) {
            $locked = Idea::where('id', $idea->id)->lockForUpdate()->first();
            $deleted = IdeaVote::where('idea_id', $locked->id)
                ->where('user_id', $request->user()->id)
                ->delete();
            if ($deleted > 0) {
                $locked->decrement('votes_count');
            }
        });

        return back()->with('status', 'Vote retiré.');
    }
}
