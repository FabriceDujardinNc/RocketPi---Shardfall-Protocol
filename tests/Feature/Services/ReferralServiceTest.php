<?php

use App\Models\Currency;
use App\Models\Referral;
use App\Models\ReferralReward;
use App\Services\ReferralService;

it('creates a Referral with status pending on first call', function () {
    $referrer = makeUser();
    $referee  = makeUser();

    $referral = app(ReferralService::class)->createForNewUser($referrer, $referee, '1.2.3.4');

    expect($referral->status)->toBe(Referral::STATUS_PENDING);
    expect($referral->referrer_id)->toBe($referrer->id);
    expect($referral->referee_id)->toBe($referee->id);
    expect($referral->referee_ip)->toBe('1.2.3.4');
    expect($referral->same_ip_as_referrer)->toBeFalse();
});

it('auto-flags referral when same IP as another referee', function () {
    $referrer = makeUser();
    $referee1 = makeUser();
    $referee2 = makeUser();

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee1, '5.6.7.8');
    $second = $svc->createForNewUser($referrer, $referee2, '5.6.7.8');

    expect($second->status)->toBe(Referral::STATUS_FLAGGED);
    expect($second->same_ip_as_referrer)->toBeTrue();
    expect($second->flag_reason)->toContain('IP');
});

it('throws when referrer hits the active referrals limit', function () {
    $referrer = makeUser();

    // Crée 50 parrainages validés (active)
    for ($i = 0; $i < ReferralService::MAX_ACTIVE_REFERRALS_PER_USER; $i++) {
        $referee = makeUser();
        Referral::create([
            'referrer_id' => $referrer->id,
            'referee_id'  => $referee->id,
            'status'      => Referral::STATUS_VALIDATED,
        ]);
    }

    $newReferee = makeUser();
    expect(fn () => app(ReferralService::class)->createForNewUser($referrer, $newReferee, '9.9.9.9'))
        ->toThrow(RuntimeException::class, 'Limite de parrainages');
});

it('validateOnEmailVerified marks status validated and creates starter pack reward', function () {
    $referrer = makeUser();
    $referee  = makeUser();

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $referral = $svc->validateOnEmailVerified($referee);

    expect($referral->status)->toBe(Referral::STATUS_VALIDATED);
    expect($referral->validated_at)->not->toBeNull();

    $reward = ReferralReward::where('referral_id', $referral->id)
        ->where('trigger', ReferralReward::TRIGGER_REFEREE_EMAIL_VERIFIED)
        ->first();
    expect($reward)->not->toBeNull();
    expect($reward->beneficiary_id)->toBe($referee->id);
});

it('validateOnEmailVerified is idempotent if already validated', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    $second = $svc->validateOnEmailVerified($referee);

    expect($second->status)->toBe(Referral::STATUS_VALIDATED);
    expect(ReferralReward::where('trigger', ReferralReward::TRIGGER_REFEREE_EMAIL_VERIFIED)->count())->toBe(1);
});

it('checkLevelMilestones creates rewards at levels 5, 15 and 30', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);

    // Level 4 → 6 → palier 5 atteint
    $svc->checkLevelMilestones($referee, 4, 6);
    expect(ReferralReward::where('trigger', ReferralReward::TRIGGER_REFEREE_LEVEL_5)->count())->toBe(1);

    // Level 14 → 16 → palier 15 atteint
    $svc->checkLevelMilestones($referee, 14, 16);
    expect(ReferralReward::where('trigger', ReferralReward::TRIGGER_REFEREE_LEVEL_15)->count())->toBe(1);

    // Level 29 → 31 → palier 30 atteint
    $svc->checkLevelMilestones($referee, 29, 31);
    expect(ReferralReward::where('trigger', ReferralReward::TRIGGER_REFEREE_LEVEL_30)->count())->toBe(1);
});

it('checkLevelMilestones does nothing if no validated referral', function () {
    $user = makeUser();   // pas de Referral

    app(ReferralService::class)->checkLevelMilestones($user, 4, 6);

    expect(ReferralReward::count())->toBe(0);
});

it('claim refuses if user is not the beneficiary', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $other    = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    $reward = ReferralReward::first();

    expect(fn () => $svc->claim($other, $reward))
        ->toThrow(RuntimeException::class, 'ne te concerne pas');
});

it('claim applies rewards and marks claimed', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    $reward = ReferralReward::first();

    $svc->claim($referee, $reward);

    // Starter pack = 500 shards + 5 tickets_standard + 1 token_rare_choice
    expect(Currency::where('user_id', $referee->id)->where('type', 'shards')->value('balance'))->toBe(500);
    expect(Currency::where('user_id', $referee->id)->where('type', 'tickets_standard')->value('balance'))->toBe(5);
    expect($reward->fresh()->claimed)->toBeTrue();
});

it('claim refuses double claim', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    $reward = ReferralReward::first();
    $svc->claim($referee, $reward);

    expect(fn () => $svc->claim($referee, $reward->fresh()))
        ->toThrow(RuntimeException::class, 'déjà réclamée');
});

it('claim transitions referral to rewarded when all rewards consumed', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);

    $referral = Referral::first();
    expect($referral->status)->toBe(Referral::STATUS_VALIDATED);

    $reward = ReferralReward::first();
    $svc->claim($referee, $reward);

    expect($referral->fresh()->status)->toBe(Referral::STATUS_REWARDED);
});

it('pendingRewardsFor returns only unclaimed rewards for the user', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);

    $list = $svc->pendingRewardsFor($referee);
    expect($list)->toHaveCount(1);

    // Après claim, plus rien
    $svc->claim($referee, ReferralReward::first());
    expect($svc->pendingRewardsFor($referee))->toHaveCount(0);
});

it('registerFirstPurchase credits 50% of shards spent to the referrer (one-shot)', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $referral = $svc->createForNewUser($referrer, $referee, '2.2.2.2');
    $referral->update(['status' => Referral::STATUS_VALIDATED, 'validated_at' => now()]);

    $first = $svc->registerFirstPurchase($referee, 500);
    expect($first)->not->toBeNull();
    expect($first->beneficiary_id)->toBe($referrer->id);
    expect($first->reward_type)->toBe('shards');
    expect($first->reward_amount)->toBe(250);

    // Idempotent : pas de double row
    $second = $svc->registerFirstPurchase($referee, 1000);
    expect($second)->toBeNull();
    expect(ReferralReward::where('trigger', ReferralReward::TRIGGER_REFEREE_FIRST_PURCHASE)->count())->toBe(1);
});

it('registerFirstPurchase is a no-op when referee has no validated referral', function () {
    $referee = makeUser();
    expect(app(ReferralService::class)->registerFirstPurchase($referee, 500))->toBeNull();
});

it('referrer can claim the first-purchase reward with dynamic shards amount', function () {
    $referrer = makeUser();
    $referee  = makeUser();
    $svc = app(ReferralService::class);

    $referral = $svc->createForNewUser($referrer, $referee, '3.3.3.3');
    $referral->update(['status' => Referral::STATUS_VALIDATED, 'validated_at' => now()]);
    $reward = $svc->registerFirstPurchase($referee, 800);

    $svc->claim($referrer, $reward);

    expect(Currency::where('user_id', $referrer->id)->where('type', 'shards')->value('balance'))->toBe(400);
    expect($reward->fresh()->claimed)->toBeTrue();
});
