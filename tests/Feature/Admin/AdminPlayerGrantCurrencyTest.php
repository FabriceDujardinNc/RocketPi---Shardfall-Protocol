<?php

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;

function adminUserPC(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('credits currency to a player with audit log', function () {
    $player = makeUser(['role' => User::ROLE_USER]);

    $this->actingAs(adminUserPC())
        ->post("/admin/players/{$player->id}/currency", [
            'currency_type' => 'shards',
            'amount'        => 500,
            'reason'        => 'compensation bug',
        ])
        ->assertRedirect();

    expect(Currency::where('user_id', $player->id)->where('type', 'shards')->value('balance'))->toBe(500);

    $tx = Transaction::where('user_id', $player->id)->where('reason', 'admin_grant')->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(500);
    expect($tx->description)->toContain('compensation bug');
});

it('debits currency with negative amount', function () {
    $player = makeUser(['role' => User::ROLE_USER]);
    Currency::create(['user_id' => $player->id, 'type' => 'shards', 'balance' => 1000]);

    $this->actingAs(adminUserPC())
        ->post("/admin/players/{$player->id}/currency", [
            'currency_type' => 'shards',
            'amount'        => -300,
            'reason'        => 'remboursement gacha pull frauduleux',
        ])
        ->assertRedirect();

    expect(Currency::where('user_id', $player->id)->value('balance'))->toBe(700);
});

it('rejects debit that would make balance negative', function () {
    $player = makeUser(['role' => User::ROLE_USER]);
    Currency::create(['user_id' => $player->id, 'type' => 'shards', 'balance' => 100]);

    $this->actingAs(adminUserPC())
        ->post("/admin/players/{$player->id}/currency", [
            'currency_type' => 'shards',
            'amount'        => -500,
            'reason'        => 'oops',
        ])
        ->assertSessionHasErrors(['amount']);

    expect(Currency::where('user_id', $player->id)->value('balance'))->toBe(100);
});

it('rejects amount = 0', function () {
    $player = makeUser(['role' => User::ROLE_USER]);

    $this->actingAs(adminUserPC())
        ->post("/admin/players/{$player->id}/currency", [
            'currency_type' => 'shards',
            'amount'        => 0,
            'reason'        => 'test',
        ])
        ->assertSessionHasErrors(['amount']);
});

it('forbids non-admin from granting', function () {
    $u      = makeUser(['role' => User::ROLE_USER]);
    $other  = makeUser();
    $this->actingAs($u)
        ->post("/admin/players/{$other->id}/currency", [
            'currency_type' => 'shards', 'amount' => 100, 'reason' => 'try',
        ])
        ->assertForbidden();
});
