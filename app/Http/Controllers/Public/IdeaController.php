<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pages publiques des idées de la communauté.
 *
 * Lecture ouverte (visiteurs et connectés). Le post + vote sont gérés par
 * Player\IdeaController (auth requise).
 */
class IdeaController extends Controller
{
    public function index(Request $request): Response
    {
        $statusFilter = $request->string('status')->toString();
        $valid = in_array($statusFilter, Idea::STATUSES, true) ? $statusFilter : null;

        $ideas = Idea::query()
            ->with('author:id,display_name,name')
            ->when($valid, fn ($q) => $q->where('status', $valid))
            ->orderByDesc('votes_count')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Idea $i) => [
                'id'           => $i->id,
                'slug'         => $i->slug,
                'title'        => $i->title,
                'status'       => $i->status,
                'votes_count'  => $i->votes_count,
                'author_name'  => $i->author?->display_name ?? $i->author?->name ?? 'Anonyme',
                'created_at'   => $i->created_at?->toDateString(),
                'voted_by_me'  => $i->isVotedBy($request->user()),
            ]);

        $counts = [
            'open'     => Idea::where('status', Idea::STATUS_OPEN)->count(),
            'accepted' => Idea::where('status', Idea::STATUS_ACCEPTED)->count(),
            'done'     => Idea::where('status', Idea::STATUS_DONE)->count(),
            'rejected' => Idea::where('status', Idea::STATUS_REJECTED)->count(),
        ];

        return Inertia::render('Public/Ideas/Index', [
            'ideas'        => $ideas,
            'statusFilter' => $valid,
            'counts'       => $counts,
        ]);
    }

    public function show(Request $request, Idea $idea): Response
    {
        $idea->load('author:id,display_name,name');

        return Inertia::render('Public/Ideas/Show', [
            'idea' => [
                'id'           => $idea->id,
                'slug'         => $idea->slug,
                'title'        => $idea->title,
                'body'         => $idea->body,
                'status'       => $idea->status,
                'votes_count'  => $idea->votes_count,
                'author_id'    => $idea->author?->id,
                'author_name'  => $idea->author?->display_name ?? $idea->author?->name ?? 'Anonyme',
                'created_at'   => $idea->created_at?->toIso8601String(),
                'updated_at'   => $idea->updated_at?->toIso8601String(),
                'voted_by_me'  => $idea->isVotedBy($request->user()),
                'can_edit'     => $request->user()?->id === $idea->user_id,
            ],
        ]);
    }
}
