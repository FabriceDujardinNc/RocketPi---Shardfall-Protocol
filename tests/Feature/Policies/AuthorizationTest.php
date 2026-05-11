<?php

use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;

it('admin can ban a regular user', function () {
    $admin  = makeUser(['role' => User::ROLE_ADMIN]);
    $target = makeUser(['role' => User::ROLE_USER]);

    expect($admin->can('ban', $target))->toBeTrue();
});

it('admin cannot ban a super_admin', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $super = makeUser(['role' => User::ROLE_SUPER_ADMIN]);

    expect($admin->can('ban', $super))->toBeFalse();
});

it('admin cannot ban themselves', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);

    expect($admin->can('ban', $admin))->toBeFalse();
});

it('admin cannot promote anyone', function () {
    $admin  = makeUser(['role' => User::ROLE_ADMIN]);
    $target = makeUser(['role' => User::ROLE_USER]);

    expect($admin->can('promote', $target))->toBeFalse();
});

it('super_admin can promote a regular user', function () {
    $super  = makeUser(['role' => User::ROLE_SUPER_ADMIN]);
    $target = makeUser(['role' => User::ROLE_USER]);

    expect($super->can('promote', $target))->toBeTrue();
});

it('super_admin cannot promote or ban themselves', function () {
    $super = makeUser(['role' => User::ROLE_SUPER_ADMIN]);

    expect($super->can('ban', $super))->toBeFalse();
    expect($super->can('promote', $super))->toBeFalse();
});

it('regular user cannot ban anyone', function () {
    $user   = makeUser(['role' => User::ROLE_USER]);
    $target = makeUser(['role' => User::ROLE_USER]);

    expect($user->can('ban', $target))->toBeFalse();
});

it('user can claim only their own referral reward when unclaimed', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $referral = Referral::create([
        'referrer_id' => $referrer->id,
        'referee_id'  => $referee->id,
        'status'      => Referral::STATUS_VALIDATED,
    ]);
    $reward = ReferralReward::create([
        'referral_id'    => $referral->id,
        'beneficiary_id' => $referee->id,
        'trigger'        => ReferralReward::TRIGGER_REFEREE_EMAIL_VERIFIED,
        'reward_type'    => 'shards',
        'reward_amount'  => 100,
        'claimed'        => false,
    ]);

    expect($referee->can('claim', $reward))->toBeTrue();
    expect($referrer->can('claim', $reward))->toBeFalse();

    $reward->update(['claimed' => true, 'claimed_at' => now()]);
    expect($referee->fresh()->can('claim', $reward->fresh()))->toBeFalse();
});

it('gates restrict admin-only abilities', function () {
    $user  = makeUser(['role' => User::ROLE_USER]);
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $super = makeUser(['role' => User::ROLE_SUPER_ADMIN]);

    foreach (['access-admin', 'manage-content', 'view-gacha-logs', 'flag-referrals', 'reset-leaderboards'] as $ability) {
        expect($user->can($ability))->toBeFalse();
        expect($admin->can($ability))->toBeTrue();
        expect($super->can($ability))->toBeTrue();
    }
});

it('only super_admin holds change-roles gate', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $super = makeUser(['role' => User::ROLE_SUPER_ADMIN]);

    expect($admin->can('change-roles'))->toBeFalse();
    expect($super->can('change-roles'))->toBeTrue();
});
