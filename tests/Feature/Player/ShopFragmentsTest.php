<?php

use App\Models\Currency;
use App\Models\PlayerOperator;
use App\Models\Transaction;
use App\Services\ShopService;

it('redeems fragments for a new operator', function () {
    $u  = makeUser();
    $op = makeOperator('rare'); // cost: 80
    Currency::create(['user_id' => $u->id, 'type' => 'fragments_'.$op->codename, 'balance' => 100]);

    $this->actingAs($u)
        ->post("/shop/fragments/{$op->slug}/redeem")
        ->assertRedirect();

    $po = PlayerOperator::where('user_id', $u->id)->where('operator_id', $op->id)->first();
    expect($po)->not->toBeNull();
    expect($po->constellation)->toBe(0);
    expect(Currency::where('user_id', $u->id)->value('balance'))->toBe(20);
});

it('increases constellation when operator already owned', function () {
    $u  = makeUser();
    $op = makeOperator('epic'); // cost: 200
    PlayerOperator::create([
        'user_id' => $u->id, 'operator_id' => $op->id,
        'duplicate_count' => 0, 'constellation' => 1, 'obtained_at' => now(),
    ]);
    Currency::create(['user_id' => $u->id, 'type' => 'fragments_'.$op->codename, 'balance' => 250]);

    $this->actingAs($u)
        ->post("/shop/fragments/{$op->slug}/redeem")
        ->assertRedirect();

    $po = PlayerOperator::where('user_id', $u->id)->where('operator_id', $op->id)->first();
    expect($po->constellation)->toBe(2);
    expect(Currency::where('user_id', $u->id)->value('balance'))->toBe(50);
});

it('rejects when insufficient fragments', function () {
    $u  = makeUser();
    $op = makeOperator('legendary'); // cost: 500
    Currency::create(['user_id' => $u->id, 'type' => 'fragments_'.$op->codename, 'balance' => 300]);

    $this->actingAs($u)
        ->post("/shop/fragments/{$op->slug}/redeem")
        ->assertSessionHasErrors(['shop']);

    expect(Currency::where('user_id', $u->id)->value('balance'))->toBe(300);
    expect(PlayerOperator::where('user_id', $u->id)->count())->toBe(0);
});

it('rejects when no fragments at all', function () {
    $u  = makeUser();
    $op = makeOperator('common');

    $this->actingAs($u)
        ->post("/shop/fragments/{$op->slug}/redeem")
        ->assertSessionHasErrors(['shop']);
});

it('logs a transaction row for the debit', function () {
    $u  = makeUser();
    $op = makeOperator('rare');
    Currency::create(['user_id' => $u->id, 'type' => 'fragments_'.$op->codename, 'balance' => 100]);

    $this->actingAs($u)->post("/shop/fragments/{$op->slug}/redeem")->assertRedirect();

    $tx = Transaction::where('user_id', $u->id)->where('reason', 'shop_fragments_redeem')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(-80);
});

it('shows exchanges section in shop when player has fragments', function () {
    $u  = makeUser();
    $op = makeOperator('rare');
    Currency::create(['user_id' => $u->id, 'type' => Currency::TYPE_SHARDS, 'balance' => 0]);
    Currency::create(['user_id' => $u->id, 'type' => 'fragments_'.$op->codename, 'balance' => 50]);

    $this->actingAs($u)
        ->get('/shop')
        ->assertInertia(fn ($p) => $p
            ->component('Player/Shop')
            ->has('exchanges.0', fn ($x) => $x
                ->where('codename', $op->codename)
                ->where('fragments', 50)
                ->where('cost', 80)
                ->where('affordable', false)
                ->where('owned', false)
                ->etc()
            )
        );
});
