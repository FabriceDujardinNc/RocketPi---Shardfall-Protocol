<?php

use App\Models\Referral;
use App\Models\Setting;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Cache;

it('does not promote referral when email_verified_at is recent', function () {
    $referrer = makeUser();
    $referee  = makeUser(['last_active_at' => now()]);

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);

    expect(Referral::where('referee_id', $referee->id)->value('status'))->toBe(Referral::STATUS_PENDING);
});

it('promotes referral once delay has elapsed and referee is active', function () {
    $referrer = makeUser();
    $referee  = makeUser(['last_active_at' => now()]);

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);

    // Simule 8 jours plus tard
    Referral::where('referee_id', $referee->id)->update(['email_verified_at' => now()->subDays(8)]);

    $promoted = $svc->promoteActiveSweep();

    expect($promoted)->toBe(1);
    $r = Referral::where('referee_id', $referee->id)->first();
    expect($r->status)->toBe(Referral::STATUS_VALIDATED);
    expect($r->validated_at)->not->toBeNull();
});

it('does not promote if referee has never returned (last_active_at null)', function () {
    $referrer = makeUser();
    $referee  = makeUser(['last_active_at' => null]);

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    Referral::where('referee_id', $referee->id)->update(['email_verified_at' => now()->subDays(10)]);

    expect($svc->promoteActiveSweep())->toBe(0);
    expect(Referral::where('referee_id', $referee->id)->value('status'))->toBe(Referral::STATUS_PENDING);
});

it('honors setting referrals.activity_delay_days override', function () {
    Cache::forget('app:settings:all');
    Setting::put('referrals.activity_delay_days', 3, 'int');

    $referrer = makeUser();
    $referee  = makeUser(['last_active_at' => now()]);

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    Referral::where('referee_id', $referee->id)->update(['email_verified_at' => now()->subDays(4)]);

    expect($svc->promoteActiveSweep())->toBe(1);
});

it('milestones only fire after promotion', function () {
    $referrer = makeUser();
    $referee  = makeUser(['last_active_at' => now()]);

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);

    // Avant promotion : aucun reward parrain malgré le level-up
    $svc->checkLevelMilestones($referee, 4, 6);
    expect(\App\Models\ReferralReward::where('trigger', \App\Models\ReferralReward::TRIGGER_REFEREE_LEVEL_5)->count())->toBe(0);

    // Simule 8 jours et promeut
    Referral::where('referee_id', $referee->id)->update(['email_verified_at' => now()->subDays(8)]);
    $svc->promoteActiveSweep();

    // Maintenant le palier 5 doit déclencher
    $svc->checkLevelMilestones($referee, 4, 6);
    expect(\App\Models\ReferralReward::where('trigger', \App\Models\ReferralReward::TRIGGER_REFEREE_LEVEL_5)->count())->toBe(1);
});

it('promote command runs and reports count', function () {
    $referrer = makeUser();
    $referee  = makeUser(['last_active_at' => now()]);

    $svc = app(ReferralService::class);
    $svc->createForNewUser($referrer, $referee, '1.1.1.1');
    $svc->validateOnEmailVerified($referee);
    Referral::where('referee_id', $referee->id)->update(['email_verified_at' => now()->subDays(8)]);

    $this->artisan('referrals:promote-active')
        ->expectsOutput('Referrals promus : 1')
        ->assertExitCode(0);
});
