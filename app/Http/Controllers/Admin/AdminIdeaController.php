<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Modération des idées de la communauté : change le statut (open → accepted /
 * rejected / done) ou supprime les abus.
 */
class AdminIdeaController extends Controller
{
    public function index(Request $request): Response
    {
        $statusFilter = $request->string('status')->toString();
        $valid = in_array($statusFilter, Idea::STATUSES, true) ? $statusFilter : null;

        $ideas = Idea::query()
            ->with('author:id,display_name,name,email')
            ->when($valid, fn ($q) => $q->where('status', $valid))
            ->orderByDesc('votes_count')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Idea $i) => [
                'id'           => $i->id,
                'slug'         => $i->slug,
                'title'        => $i->title,
                'body_preview' => mb_substr($i->body, 0, 200),
                'status'       => $i->status,
                'votes_count'  => $i->votes_count,
                'author_id'    => $i->author?->id,
                'author_name'  => $i->author?->display_name ?? $i->author?->name,
                'author_email' => $i->author?->email,
                'created_at'   => $i->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Ideas', [
            'ideas'        => $ideas,
            'statusFilter' => $valid,
        ]);
    }

    public function updateStatus(Request $request, Idea $idea): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Idea::STATUSES)],
        ]);

        $idea->update(['status' => $validated['status']]);

        return back()->with('status', "Statut mis à jour : {$validated['status']}.");
    }

    public function destroy(Idea $idea): RedirectResponse
    {
        $idea->delete();
        return back()->with('status', 'Idée supprimée.');
    }
}
