<?php

use App\Models\LeaderboardEntry;
use App\Models\LeaderboardReward;
use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use App\Services\RewardService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // Clean Redis test DB (15) to avoid pollution between tests
    Redis::flushdb();
});

afterAll(function () {
    Redis::flushdb();
});

function makeSeason(array $attrs = []): LeaderboardSeason
{
    static $i = 0;
    $i++;
    return LeaderboardSeason::create(array_merge([
        'name'          => "Saison test {$i}",
        'type'          => 'weekly',
        'season_number' => $i,
        'starts_at'     => now()->subDay(),
        'ends_at'       => now()->addWeek(),
        'is_active'     => true,
    ], $attrs));
}

it('returns 0 when adding points to an inactive season', function () {
    $user   = makeUser();
    $season = makeSeason(['is_active' => false]);

    expect(app(LeaderboardService::class)->addPoints($user, $season, 100))->toBe(0);
});

it('addPoints stores into Redis ZSET and returns the new score', function () {
    $user   = makeUser();
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    $score = $svc->addPoints($user, $season, 50);

    expect($score)->toBe(50);
    expect($svc->scoreOf($user, $season))->toBe(50);
});

it('addPoints accumulates on the same user', function () {
    $user   = makeUser();
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    $svc->addPoints($user, $season, 30);
    $svc->addPoints($user, $season, 20);

    expect($svc->scoreOf($user, $season))->toBe(50);
});

it('topN returns users ordered desc by score with name hydrated', function () {
    $u1 = makeUser(['name' => 'Alpha']);
    $u2 = makeUser(['name' => 'Beta']);
    $u3 = makeUser(['name' => 'Gamma']);
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    $svc->addPoints($u1, $season, 10);
    $svc->addPoints($u2, $season, 30);
    $svc->addPoints($u3, $season, 20);

    $top = $svc->topN($season, 5);

    expect($top)->toHaveCount(3);
    expect($top[0]['rank'])->toBe(1);
    expect($top[0]['user_id'])->toBe($u2->id);
    expect($top[0]['score'])->toBe(30);
    expect($top[0]['name'])->toBe('Beta');
    expect($top[2]['user_id'])->toBe($u1->id);
});

it('topN returns empty array when ZSET is empty', function () {
    $season = makeSeason();
    expect(app(LeaderboardService::class)->topN($season))->toBe([]);
});

it('rankOf returns 1-indexed rank', function () {
    $u1 = makeUser();
    $u2 = makeUser();
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    $svc->addPoints($u1, $season, 100);
    $svc->addPoints($u2, $season, 50);

    expect($svc->rankOf($u1, $season))->toBe(1);
    expect($svc->rankOf($u2, $season))->toBe(2);
});

it('rankOf returns null when user not in ZSET', function () {
    $user = makeUser();
    $season = makeSeason();

    expect(app(LeaderboardService::class)->rankOf($user, $season))->toBeNull();
});

it('scoreOf returns 0 when user not in ZSET', function () {
    $user = makeUser();
    $season = makeSeason();

    expect(app(LeaderboardService::class)->scoreOf($user, $season))->toBe(0);
});

it('neighborsOf returns user with adjacent ranks and is_current_user flag', function () {
    $users = collect(range(1, 10))->map(fn () => makeUser());
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    foreach ($users as $i => $u) {
        $svc->addPoints($u, $season, 100 - ($i * 10));   // user 0 = 100pts, user 1 = 90pts…
    }

    // L'utilisateur 4 est rang 5 (score 60)
    $neighbors = $svc->neighborsOf($users[4], $season, 2);

    // 2 avant + user + 2 après = 5 entrées
    expect($neighbors)->toHaveCount(5);
    $current = collect($neighbors)->firstWhere('is_current_user', true);
    expect($current['user_id'])->toBe($users[4]->id);
    expect($current['rank'])->toBe(5);
});

it('participantCount returns ZSET cardinality', function () {
    $season = makeSeason();
    $svc = app(LeaderboardService::class);

    $svc->addPoints(makeUser(), $season, 10);
    $svc->addPoints(makeUser(), $season, 20);
    $svc->addPoints(makeUser(), $season, 30);

    expect($svc->participantCount($season))->toBe(3);
});

it('snapshotToMysql archives entries with rank and deletes the Redis key', function () {
    $u1 = makeUser();
    $u2 = makeUser();
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    $svc->addPoints($u1, $season, 100);
    $svc->addPoints($u2, $season, 50);

    $count = $svc->snapshotToMysql($season);

    expect($count)->toBe(2);
    expect(LeaderboardEntry::where('season_id', $season->id)->count())->toBe(2);

    $first = LeaderboardEntry::where('season_id', $season->id)->where('rank', 1)->first();
    expect($first->user_id)->toBe($u1->id);
    expect($first->score)->toBe(100);

    // Redis key supprimée
    expect(Redis::exists($svc->key($season->id)))->toBe(0);
});

it('distributeRewards matches top_1 tier and applies rewards', function () {
    $u1 = makeUser();
    $u2 = makeUser();
    $season = makeSeason();

    $svc = app(LeaderboardService::class);
    $svc->addPoints($u1, $season, 100);
    $svc->addPoints($u2, $season, 50);
    $svc->snapshotToMysql($season);

    LeaderboardReward::create([
        'season_id' => $season->id,
        'tier'      => 'top_1',
        'rank_min'  => '1',
        'rank_max'  => '1',
        'rewards'   => [['type' => 'shards', 'amount' => 1000]],
    ]);

    $distributed = $svc->distributeRewards($season, app(RewardService::class));

    expect($distributed)->toBe(1);
    expect(\App\Models\Currency::where('user_id', $u1->id)->where('type', 'shards')->value('balance'))->toBe(1000);
    expect(\App\Models\Currency::where('user_id', $u2->id)->where('type', 'shards')->exists())->toBeFalse();
});

it('distributeRewards refuses double distribution', function () {
    $season = makeSeason();
    $svc = app(LeaderboardService::class);

    $svc->addPoints(makeUser(), $season, 100);
    $svc->snapshotToMysql($season);
    $svc->distributeRewards($season, app(RewardService::class));

    // Second appel → 0 (rewards_distributed=true)
    expect($svc->distributeRewards($season, app(RewardService::class)))->toBe(0);
});

it('activeSeasons filters by is_active and date range', function () {
    makeSeason(['is_active' => false]);
    makeSeason(['ends_at' => now()->subDay()]);          // expirée
    makeSeason(['starts_at' => now()->addDay()]);        // pas encore commencée
    $valid = makeSeason();                                // active

    $list = app(LeaderboardService::class)->activeSeasons();

    expect($list->pluck('id')->toArray())->toBe([$valid->id]);
});
