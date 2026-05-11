<?php

use App\Models\Achievement;
use App\Models\User;

function adminUserAch(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/achievements')->assertForbidden();
});

it('creates an achievement', function () {
    $this->actingAs(adminUserAch())
        ->post('/admin/achievements', [
            'key'         => 'first_legendary',
            'title'       => 'Première légende',
            'description' => 'Tirer son premier légendaire.',
            'category'    => 'collection',
            'is_hidden'   => false,
            'rewards'     => [['type' => 'shards', 'amount' => 100]],
        ])
        ->assertRedirect();

    $a = Achievement::where('key', 'first_legendary')->first();
    expect($a)->not->toBeNull()->title->toBe('Première légende');
});

it('rejects invalid key format', function () {
    $this->actingAs(adminUserAch())
        ->post('/admin/achievements', [
            'key'      => 'BadKey-WithCaps',
            'title'    => 'X',
            'category' => 'special',
        ])
        ->assertSessionHasErrors(['key']);
});

it('rejects duplicate key', function () {
    Achievement::create(['key' => 'unique_one', 'title' => 'X', 'category' => 'collection', 'is_hidden' => false]);

    $this->actingAs(adminUserAch())
        ->post('/admin/achievements', [
            'key' => 'unique_one', 'title' => 'Y', 'category' => 'combat',
        ])
        ->assertSessionHasErrors(['key']);
});

it('updates an achievement', function () {
    $a = Achievement::create(['key' => 'reach_50', 'title' => 'Halfway', 'category' => 'progression', 'is_hidden' => false]);

    $this->actingAs(adminUserAch())
        ->put("/admin/achievements/{$a->key}", [
            'key'       => 'reach_50',
            'title'     => 'À mi-parcours',
            'category'  => 'progression',
            'is_hidden' => true,
            'rewards'   => [['type' => 'tickets_premium', 'amount' => 2]],
        ])
        ->assertRedirect();

    expect($a->fresh())->title->toBe('À mi-parcours')->is_hidden->toBeTrue();
});

it('destroys an achievement', function () {
    $a = Achievement::create(['key' => 'to_delete', 'title' => 'X', 'category' => 'special', 'is_hidden' => false]);

    $this->actingAs(adminUserAch())->delete("/admin/achievements/{$a->key}")->assertRedirect();
    expect(Achievement::find($a->id))->toBeNull();
});
