<?php

// Test Phase 3 chain: BP + Affinity + Achievements
echo 'BattlePasses: ' . App\Models\BattlePass::count() . PHP_EOL;
echo 'BP Tiers: ' . App\Models\BattlePassTier::count() . PHP_EOL;
echo 'Achievements: ' . App\Models\Achievement::count() . PHP_EOL;

$user = App\Models\User::where('email', 'player@rocketpi.local')->first();
$banner = App\Models\Banner::where('is_active', true)->first();

if (! $user || ! $banner) {
    echo "MISSING user or banner\n";
    exit(1);
}

echo "Before pull:\n";
$bpp = App\Models\BattlePassProgress::where('user_id', $user->id)->first();
echo "  BP tier=" . ($bpp->current_tier ?? 0) . " xp=" . ($bpp->xp_earned ?? 0) . PHP_EOL;
echo "  Affinities: " . App\Models\OperatorAffinity::where('user_id', $user->id)->count() . PHP_EOL;
echo "  Achievements: " . App\Models\UserAchievement::where('user_id', $user->id)->count() . PHP_EOL;

app(App\Services\GachaService::class)->pull($user, $banner, 10);

echo "After pull x10:\n";
$bpp = App\Models\BattlePassProgress::where('user_id', $user->id)->first();
echo "  BP tier=" . $bpp->current_tier . " xp=" . $bpp->xp_earned . PHP_EOL;
echo "  Affinities: " . App\Models\OperatorAffinity::where('user_id', $user->id)->count() . PHP_EOL;
echo "  Achievements unlocked: " . App\Models\UserAchievement::where('user_id', $user->id)->where('completed', true)->count() . PHP_EOL;
foreach (App\Models\UserAchievement::with('achievement')->where('user_id', $user->id)->get() as $ua) {
    echo "    - [" . ($ua->completed ? 'X' : ' ') . '] ' . $ua->achievement->title . ' (' . $ua->progress . ')' . PHP_EOL;
}
