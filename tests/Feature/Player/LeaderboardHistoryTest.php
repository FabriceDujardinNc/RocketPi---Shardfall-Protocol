<?php

use App\Models\LeaderboardEntry;
use App\Models\LeaderboardSeason;

function makeArchivedSeason(array $attrs = []): LeaderboardSeason
{
    static $i = 100;
    $i++;
    return LeaderboardSeason::create(array_merge([
        'name'          => "Saison archive {$i}",
        'type'          => 'weekly',
        'season_number' => $i,
        'starts_at'     => now()->subWeeks(2),
        'ends_at'       => now()->subWeek(),
        'is_active'     => false,
    ], $attrs));
}

it('lists archived seasons with rank and score', function () {
    $u  = makeUser();
    $s1 = makeArchivedSeason();
    $s2 = makeArchivedSeason(['type' => 'monthly']);

    LeaderboardEntry::create(['season_id' => $s1->id, 'user_id' => $u->id, 'rank' => 7, 'score' => 1234]);
    LeaderboardEntry::create(['season_id' => $s2->id, 'user_id' => $u->id, 'rank' => 1, 'score' => 5000]);

    $this->actingAs($u)
        ->get('/leaderboard/history')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Player/LeaderboardHistory')
            ->where('totals.archived_seasons', 2)
            ->where('totals.top_1_count', 1)
            ->where('totals.top_10_count', 2)
            ->has('history', 2)
            ->has('best', 2)
        );
});

it('only shows entries from inactive seasons', function () {
    $u = makeUser();
    $active   = LeaderboardSeason::create([
        'name' => 'Active', 'type' => 'weekly', 'season_number' => 1,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek(),
        'is_active' => true,
    ]);
    $closed = makeArchivedSeason();
    LeaderboardEntry::create(['season_id' => $active->id, 'user_id' => $u->id, 'rank' => 1, 'score' => 100]);
    LeaderboardEntry::create(['season_id' => $closed->id, 'user_id' => $u->id, 'rank' => 5, 'score' => 50]);

    $this->actingAs($u)
        ->get('/leaderboard/history')
        ->assertInertia(fn ($p) => $p
            ->where('totals.archived_seasons', 1)
            ->has('history', 1)
        );
});

it('shows empty state when player has no archived entries', function () {
    $u = makeUser();
    $this->actingAs($u)
        ->get('/leaderboard/history')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('totals.archived_seasons', 0)
            ->where('totals.top_1_count', 0)
            ->has('history', 0)
        );
});

it('isolates history per player', function () {
    $a = makeUser();
    $b = makeUser();
    $s = makeArchivedSeason();
    LeaderboardEntry::create(['season_id' => $s->id, 'user_id' => $a->id, 'rank' => 1, 'score' => 999]);

    $this->actingAs($b)
        ->get('/leaderboard/history')
        ->assertInertia(fn ($p) => $p->where('totals.archived_seasons', 0));
});

it('requires auth', function () {
    $this->get('/leaderboard/history')->assertRedirect('/login');
});

it('ranks best top 3 ordered by rank ascending', function () {
    $u = makeUser();
    $seasons = collect(range(1, 5))->map(fn () => makeArchivedSeason());
    $ranks = [50, 1, 100, 3, 200];
    foreach ($seasons as $i => $s) {
        LeaderboardEntry::create(['season_id' => $s->id, 'user_id' => $u->id, 'rank' => $ranks[$i], 'score' => 100]);
    }

    $this->actingAs($u)
        ->get('/leaderboard/history')
        ->assertInertia(fn ($p) => $p
            ->has('best', 3)
            ->where('best.0.rank', 1)
            ->where('best.1.rank', 3)
            ->where('best.2.rank', 50)
        );
});
