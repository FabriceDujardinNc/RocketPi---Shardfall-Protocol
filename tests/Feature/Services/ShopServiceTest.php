<?php

use App\Models\Currency;
use App\Models\Transaction;
use App\Services\ShopService;

it('lists packs with affordable flag based on user balance', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 50);

    $packs = app(ShopService::class)->listPacks($user);

    expect($packs)->toBeArray();
    expect(count($packs))->toBeGreaterThan(0);

    // event_apex coûte 500 shards → non affordable avec 50
    $apex = collect($packs)->firstWhere('id', 'event_apex');
    expect($apex['affordable'])->toBeFalse();

    // starter_basic gratuit → toujours affordable
    $starter = collect($packs)->firstWhere('id', 'starter_basic');
    expect($starter['affordable'])->toBeTrue();
});

it('throws on unknown pack id', function () {
    $user = makeUser();

    expect(fn () => app(ShopService::class)->purchasePack($user, 'nonexistent'))
        ->toThrow(RuntimeException::class, 'Pack inconnu');
});

it('throws when buying paid pack with insufficient shards', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 100);   // < 500 du pack apex

    expect(fn () => app(ShopService::class)->purchasePack($user, 'event_apex'))
        ->toThrow(RuntimeException::class, 'Solde insuffisant');
});

it('debits shards and applies rewards on paid pack', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);

    app(ShopService::class)->purchasePack($user, 'event_apex');

    // Solde shards final = 1000 (start) - 500 (price) + 50 (reward) = 550
    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))->toBe(550);
    expect(Currency::where('user_id', $user->id)->where('type', 'tickets_premium')->value('balance'))->toBe(1);
});

it('applies rewards on free pack without debiting', function () {
    $user = makeUser();
    // Pas de currency initiale → starter ne nécessite rien

    app(ShopService::class)->purchasePack($user, 'starter_basic');

    // starter_basic : 200 shards + 1000 credits + 3 tickets_standard
    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))->toBe(200);
    expect(Currency::where('user_id', $user->id)->where('type', 'credits')->value('balance'))->toBe(1000);
    expect(Currency::where('user_id', $user->id)->where('type', 'tickets_standard')->value('balance'))->toBe(3);
});

it('logs Transaction debit row on paid pack', function () {
    $user = makeUser();
    giveCurrency($user, 'shards', 1000);

    app(ShopService::class)->purchasePack($user, 'event_apex');

    $tx = Transaction::where('user_id', $user->id)->where('reason', 'shop_purchase')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(-500);
});
