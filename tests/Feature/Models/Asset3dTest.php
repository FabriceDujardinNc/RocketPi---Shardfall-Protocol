<?php

use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\PlayerLoadout;
use App\Models\Weapon;
use App\Models\WeaponSkin;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->op = makeOperator('legendary');
});

// -----------------------------------------------------------------------------
// Operator — colonnes 3D ajoutées
// -----------------------------------------------------------------------------

it('defaults operator base generation status to pending', function () {
    expect($this->op->base_generation_status)->toBe('pending');
    expect($this->op->base_rig_version)->toBe('humanoid-v1');
    expect($this->op->base_model_url)->toBeNull();
});

it('exposes isBaseModelReady() only when status ready AND url filled', function () {
    expect($this->op->isBaseModelReady())->toBeFalse();

    $this->op->update([
        'base_generation_status' => 'ready',
        'base_model_url'         => null,
    ]);
    expect($this->op->fresh()->isBaseModelReady())->toBeFalse();

    $this->op->update([
        'base_model_url' => 'models/operators/op-0001/base.glb',
    ]);
    expect($this->op->fresh()->isBaseModelReady())->toBeTrue();
});

// -----------------------------------------------------------------------------
// OperatorSkin
// -----------------------------------------------------------------------------

it('creates an operator skin with auto slug and pending status', function () {
    $skin = OperatorSkin::factory()->create([
        'operator_id' => $this->op->id,
        'name'        => 'Neon Vex',
    ]);

    expect($skin->slug)->toBe('neon-vex');
    expect($skin->generation_status)->toBe('pending');
    expect($skin->isReady())->toBeFalse();
    expect($skin->operator->is($this->op))->toBeTrue();
});

it('reports skin ready when status and texture url align', function () {
    $skin = OperatorSkin::factory()->ready()->create([
        'operator_id' => $this->op->id,
    ]);

    expect($skin->isReady())->toBeTrue();
});

it('cascades skin deletion when operator deleted', function () {
    OperatorSkin::factory()->create(['operator_id' => $this->op->id]);
    OperatorSkin::factory()->create(['operator_id' => $this->op->id]);

    expect(OperatorSkin::where('operator_id', $this->op->id)->count())->toBe(2);

    $this->op->forceDelete();

    expect(OperatorSkin::where('operator_id', $this->op->id)->count())->toBe(0);
});

it('enforces unique slug on operator_skins', function () {
    OperatorSkin::factory()->create([
        'operator_id' => $this->op->id,
        'name'        => 'Same Name',
    ]);

    // Le trait HasAutoSlug suffixe -2, -3 sur collision : la création
    // doit donc passer mais avec un slug différent.
    $second = OperatorSkin::factory()->create([
        'operator_id' => $this->op->id,
        'name'        => 'Same Name',
    ]);

    expect($second->slug)->toBe('same-name-2');
});

// -----------------------------------------------------------------------------
// Weapon + WeaponSkin
// -----------------------------------------------------------------------------

it('creates a weapon with default Hand_R socket', function () {
    $w = Weapon::factory()->create(['name' => 'Pulse Rifle']);

    expect($w->slug)->toBe('pulse-rifle');
    expect($w->socket_name)->toBe('Hand_R');
    expect($w->generation_status)->toBe('pending');
});

it('associates weapon skins and cascades on weapon delete', function () {
    $w = Weapon::factory()->create();
    WeaponSkin::factory()->count(3)->create(['weapon_id' => $w->id]);

    expect($w->skins()->count())->toBe(3);

    $w->delete();
    expect(WeaponSkin::where('weapon_id', $w->id)->count())->toBe(0);
});

it('rejects invalid weapon category', function () {
    expect(fn () => Weapon::factory()->create(['category' => 'plasma_cannon']))
        ->toThrow(QueryException::class);
});

// -----------------------------------------------------------------------------
// Accessory + pivot
// -----------------------------------------------------------------------------

it('creates an accessory with slot-aligned socket via factory state', function () {
    $a = Accessory::factory()->slot('head')->create();

    expect($a->slot)->toBe('head');
    expect($a->socket_name)->toBe('Head_Top');
});

it('attaches accessories to operators with pivot is_default', function () {
    $helmet = Accessory::factory()->slot('head')->create();
    $bag    = Accessory::factory()->slot('back')->create();

    $this->op->accessories()->attach($helmet->id, ['is_default' => true]);
    $this->op->accessories()->attach($bag->id,    ['is_default' => false]);

    $list = $this->op->accessories()->orderBy('slot')->get();
    expect($list)->toHaveCount(2);
    expect($list->firstWhere('id', $helmet->id)->pivot->is_default)->toBeTrue();
    expect($list->firstWhere('id', $bag->id)->pivot->is_default)->toBeFalse();
});

it('rejects duplicate operator_accessory link', function () {
    $a = Accessory::factory()->create();
    $this->op->accessories()->attach($a->id);

    expect(fn () => $this->op->accessories()->attach($a->id))
        ->toThrow(QueryException::class);
});

// -----------------------------------------------------------------------------
// PlayerLoadout
// -----------------------------------------------------------------------------

it('creates a loadout linking user, operator, optional gear', function () {
    $user   = makeUser();
    $skin   = OperatorSkin::factory()->create(['operator_id' => $this->op->id]);
    $weapon = Weapon::factory()->create();
    $skinW  = WeaponSkin::factory()->create(['weapon_id' => $weapon->id]);
    $helmet = Accessory::factory()->slot('head')->create();

    $loadout = PlayerLoadout::factory()->create([
        'user_id'           => $user->id,
        'operator_id'       => $this->op->id,
        'operator_skin_id'  => $skin->id,
        'weapon_id'         => $weapon->id,
        'weapon_skin_id'    => $skinW->id,
        'head_accessory_id' => $helmet->id,
    ]);

    expect($loadout->user->is($user))->toBeTrue();
    expect($loadout->operator->is($this->op))->toBeTrue();
    expect($loadout->operatorSkin->is($skin))->toBeTrue();
    expect($loadout->weapon->is($weapon))->toBeTrue();
    expect($loadout->weaponSkin->is($skinW))->toBeTrue();
    expect($loadout->headAccessory->is($helmet))->toBeTrue();
});

it('enforces one loadout per (user, operator)', function () {
    $user = makeUser();
    PlayerLoadout::factory()->create([
        'user_id'     => $user->id,
        'operator_id' => $this->op->id,
    ]);

    expect(fn () => PlayerLoadout::factory()->create([
        'user_id'     => $user->id,
        'operator_id' => $this->op->id,
    ]))->toThrow(QueryException::class);
});

it('nullifies loadout slots when cosmetic is deleted', function () {
    $user = makeUser();
    $skin = OperatorSkin::factory()->create(['operator_id' => $this->op->id]);

    $loadout = PlayerLoadout::factory()->create([
        'user_id'          => $user->id,
        'operator_id'      => $this->op->id,
        'operator_skin_id' => $skin->id,
    ]);

    $skin->delete();

    expect($loadout->fresh()->operator_skin_id)->toBeNull();
});
