<?php

use App\Models\BattlePass;
use App\Models\BattlePassProgress;
use App\Models\BattlePassTier;
use App\Models\Currency;
use App\Services\BattlePassService;

function makeBP(array $attrs = []): BattlePass
{
    static $i = 0;
    $i++;
    return BattlePass::create(array_merge([
        'name'                 => "Saison test {$i}",
        'season_number'        => $i,
        'total_tiers'          => 5,
        'premium_price_shards' => 1000,
        'starts_at'            => now()->subDay(),
        'ends_at'              => now()->addWeek(),
        'is_active'            => true,
    ], $attrs));
}

function makeTier(BattlePass $bp, int $tier, int $xpRequired, ?array $free = null, ?array $premium = null): BattlePassTier
{
    return BattlePassTier::create([
        'battle_pass_id' => $bp->id,
        'tier_number'    => $tier,
        'xp_required'    => $xpRequired,
        'free_reward'    => $free ?? [['type' => 'credits', 'amount' => 100]],
        'premium_reward' => $premium ?? [['type' => 'shards', 'amount' => 50]],
        'is_milestone'   => false,
    ]);
}

it('returns null active BP when none configured', function () {
    expect(app(BattlePassService::class)->activeBattlePass())->toBeNull();
});

it('returns the active BP filtering by date and is_active', function () {
    makeBP(['is_active' => false]);
    $active = makeBP(['is_active' => true]);

    expect(app(BattlePassService::class)->activeBattlePass())
        ->id->toBe($active->id);
});

it('creates progress row on first call', function () {
    $user = makeUser();
    $bp   = makeBP();

    $p = app(BattlePassService::class)->progressFor($user, $bp);

    expect(BattlePassProgress::count())->toBe(1);
    expect($p->xp_earned)->toBe(0);
    expect($p->current_tier)->toBe(0);
});

it('addXp recalculates current_tier from tiers thresholds', function () {
    $user = makeUser();
    $bp   = makeBP();
    makeTier($bp, 1, 100);
    makeTier($bp, 2, 250);
    makeTier($bp, 3, 500);

    $svc = app(BattlePassService::class);
    $svc->addXp($user, 300);

    $p = BattlePassProgress::first();
    expect($p->xp_earned)->toBe(300);
    expect($p->current_tier)->toBe(2);   // 300 ≥ 250 mais < 500
});

it('addXp returns null when no active BP', function () {
    $user = makeUser();
    expect(app(BattlePassService::class)->addXp($user, 100))->toBeNull();
});

it('purchase debits exactly the premium price', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 2000);
    $bp = makeBP(['premium_price_shards' => 1000]);

    app(BattlePassService::class)->purchase($user, $bp);

    expect(Currency::where('user_id', $user->id)->value('balance'))->toBe(1000);
    expect(BattlePassProgress::first())
        ->is_premium->toBeTrue()
        ->purchased_at->not->toBeNull();
});

it('purchase throws on insufficient balance', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 500);
    $bp = makeBP(['premium_price_shards' => 1000]);

    expect(fn () => app(BattlePassService::class)->purchase($user, $bp))
        ->toThrow(RuntimeException::class, 'Solde insuffisant');
});

it('purchase throws on already-premium', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 5000);
    $bp = makeBP();

    $svc = app(BattlePassService::class);
    $svc->purchase($user, $bp);

    expect(fn () => $svc->purchase($user, $bp))
        ->toThrow(RuntimeException::class, 'déjà acheté');
});

it('purchase throws on inactive BP', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 5000);
    $bp = makeBP(['is_active' => false]);

    expect(fn () => app(BattlePassService::class)->purchase($user, $bp))
        ->toThrow(RuntimeException::class, 'non actif');
});

it('claim refuses tier not yet reached', function () {
    $user = makeUser();
    $bp   = makeBP();
    $tier = makeTier($bp, 5, 1000);

    expect(fn () => app(BattlePassService::class)->claim($user, $tier))
        ->toThrow(RuntimeException::class, 'pas encore atteint');
});

it('claim applies free reward only when not premium', function () {
    $user = makeUser();
    $bp   = makeBP();
    $tier = makeTier($bp, 1, 100,
        free:    [['type' => 'credits', 'amount' => 100]],
        premium: [['type' => 'shards',  'amount' => 50]],
    );

    $svc = app(BattlePassService::class);
    $svc->addXp($user, 100);
    $svc->claim($user, $tier);

    expect(Currency::where('user_id', $user->id)->where('type', 'credits')->value('balance'))->toBe(100);
    // Aucune Currency `shards` créée puisque pas premium → null (== pas appliqué)
    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->exists())->toBeFalse();
});

it('claim applies free + premium rewards when premium active', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 2000);
    $bp   = makeBP();
    $tier = makeTier($bp, 1, 100,
        free:    [['type' => 'credits', 'amount' => 100]],
        premium: [['type' => 'shards',  'amount' => 50]],
    );

    $svc = app(BattlePassService::class);
    $svc->purchase($user, $bp);                       // débite 1000 shards → 1000 reste
    $svc->addXp($user, 100);
    $svc->claim($user, $tier);                        // +50 shards premium

    expect(Currency::where('user_id', $user->id)->where('type', 'credits')->value('balance'))->toBe(100);
    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))->toBe(1050);
});

it('claim refuses double claim', function () {
    $user = makeUser();
    $bp   = makeBP();
    $tier = makeTier($bp, 1, 100);

    $svc = app(BattlePassService::class);
    $svc->addXp($user, 100);
    $svc->claim($user, $tier);

    expect(fn () => $svc->claim($user, $tier))
        ->toThrow(RuntimeException::class, 'déjà réclamé');
});

it('records claimed tiers as array in progress', function () {
    $user = makeUser();
    $bp   = makeBP();
    $t1 = makeTier($bp, 1, 100);
    $t2 = makeTier($bp, 2, 200);

    $svc = app(BattlePassService::class);
    $svc->addXp($user, 200);
    $svc->claim($user, $t1);
    $svc->claim($user, $t2);

    expect(BattlePassProgress::first()->claimed_tiers)->toBe([1, 2]);
});
