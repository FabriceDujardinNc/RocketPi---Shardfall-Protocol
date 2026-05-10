<?php

use App\Models\Currency;
use App\Models\GachaPull;
use App\Models\PityCounter;
use App\Models\PlayerOperator;
use App\Models\Transaction;
use App\Services\GachaService;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // Empêche les tests de toucher Redis (qui nécessite un serveur live)
    $mock = Mockery::mock(LeaderboardService::class);
    $mock->shouldReceive('activeSeasons')->andReturn(collect([]));
    $mock->shouldReceive('addPoints')->andReturn(0);
    $this->app->instance(LeaderboardService::class, $mock);

    // Au moins 1 opérateur disponible pour chaque rareté
    foreach (['common', 'rare', 'epic', 'legendary'] as $r) {
        makeOperator($r);
    }
});

it('refuses pull if user has insufficient shards', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 5);   // 5 < 10 nécessaire pour 1 pull
    $banner = makeBanner();

    expect(fn () => app(GachaService::class)->pull($user, $banner, 1))
        ->toThrow(RuntimeException::class, 'Solde insuffisant');
});

it('refuses pull on inactive banner', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner(['is_active' => false]);

    expect(fn () => app(GachaService::class)->pull($user, $banner, 1))
        ->toThrow(RuntimeException::class, 'non active');
});

it('debits exactly the right cost on a single pull', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 100);
    $banner = makeBanner();

    app(GachaService::class)->pull($user, $banner, 1);

    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))
        ->toBe(90);   // 100 - 10
});

it('debits 100 shards on a 10-pull (no discount)', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner();

    app(GachaService::class)->pull($user, $banner, 10);

    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))
        ->toBe(900);
});

it('rejects counts other than 1 or 10', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner();

    expect(fn () => app(GachaService::class)->pull($user, $banner, 5))
        ->toThrow(RuntimeException::class);
});

it('creates immutable GachaPull rows for each draw', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner();

    app(GachaService::class)->pull($user, $banner, 10);

    expect(GachaPull::count())->toBe(10);
    expect(GachaPull::where('user_id', $user->id)->count())->toBe(10);
});

it('creates a Transaction debit row for each pull invocation', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner();

    app(GachaService::class)->pull($user, $banner, 10);

    $tx = Transaction::where('user_id', $user->id)->where('reason', 'gacha_pull')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(-100);
});

it('forces an epic at the configured pity threshold', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner(['pity_epic' => 3]);

    // 3 pulls : avec pity_epic=3, le 3e DOIT être au moins épique
    app(GachaService::class)->pull($user, $banner, 1);
    app(GachaService::class)->pull($user, $banner, 1);
    app(GachaService::class)->pull($user, $banner, 1);

    $thirdPull = GachaPull::where('user_id', $user->id)->orderBy('id')->skip(2)->first();
    expect(in_array($thirdPull->rarity, ['epic', 'legendary'], true))->toBeTrue();
});

it('resets the legendary pity counter on a legendary draw', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner(['pity_legendary' => 1]);   // garantit légendaire à chaque pull

    app(GachaService::class)->pull($user, $banner, 1);

    $pity = PityCounter::where('user_id', $user->id)->first();
    expect($pity->legendary_counter)->toBe(0);
    expect($pity->total_pulls)->toBe(1);
});

it('grants fragments on duplicate operators', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner();

    // Force le draw — on n'a pas le contrôle direct, mais avec 100 pulls
    // sur les seuls 4 opérateurs créés, on aura forcément des doublons.
    for ($i = 0; $i < 5; $i++) {
        app(GachaService::class)->pull($user, $banner, 10);
    }

    // Au moins 1 currency fragments_* doit exister
    $fragmentTypes = Currency::where('user_id', $user->id)
        ->where('type', 'LIKE', 'fragments_%')
        ->count();
    expect($fragmentTypes)->toBeGreaterThan(0);
});

it('upserts PlayerOperator with duplicate_count incrementing', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);
    $banner = makeBanner();

    app(GachaService::class)->pull($user, $banner, 10);
    app(GachaService::class)->pull($user, $banner, 10);

    $totalDups = PlayerOperator::where('user_id', $user->id)->sum('duplicate_count');
    $totalRows = PlayerOperator::where('user_id', $user->id)->count();

    // 20 pulls = 20 unités. unités = rows + duplicates.
    expect($totalRows + $totalDups)->toBe(20);
});

it('rolls back the entire pull on inner exception (atomic)', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 100);
    $banner = makeBanner();

    // Supprime tous les opérateurs : le pickOperator throwera
    \App\Models\Operator::query()->delete();

    expect(fn () => app(GachaService::class)->pull($user, $banner, 1))
        ->toThrow(RuntimeException::class);

    // Le solde n'a PAS été débité
    expect(Currency::where('user_id', $user->id)->value('balance'))->toBe(100);
    // Aucun GachaPull créé
    expect(GachaPull::count())->toBe(0);
});
