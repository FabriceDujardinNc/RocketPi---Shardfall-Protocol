<?php

use App\Models\Faction;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('seo:sitemap:xml');
    foreach (['ORBIT', 'FERRO', 'VEIL'] as $slug) {
        Faction::firstOrCreate(['slug' => $slug], [
            'name' => $slug, 'tagline' => 'tag', 'lore' => 'lore', 'color_hue' => 100,
        ]);
    }
});

it('serves sitemap.xml with XML content type', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8');
});

it('includes home and lore base URLs', function () {
    $body = $this->get('/sitemap.xml')->getContent();
    expect($body)->toContain('<loc>'.config('app.url').'/</loc>');
    expect($body)->toContain('/lore</loc>');
    expect($body)->toContain('/top</loc>');
});

it('lists factions on lore namespace', function () {
    $body = $this->get('/sitemap.xml')->getContent();
    expect($body)->toContain('/lore/factions/ORBIT</loc>');
    expect($body)->toContain('/lore/factions/FERRO</loc>');
    expect($body)->toContain('/lore/factions/VEIL</loc>');
});

it('includes published operators on lore namespace', function () {
    $op = Operator::create([
        'name' => 'Vex', 'codename' => 'VX-99',
        'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'legendary',
        'is_available' => true,
        'stat_hp' => 100, 'stat_damage' => 100, 'stat_mobility' => 100,
    ]);

    Cache::forget('seo:sitemap:xml');
    $body = $this->get('/sitemap.xml')->getContent();
    expect($body)->toContain("/lore/operators/{$op->slug}</loc>");
});

it('excludes unavailable operators', function () {
    $op = Operator::create([
        'name' => 'Hidden', 'codename' => 'HD-99',
        'faction' => 'VEIL', 'role' => 'scout', 'rarity' => 'rare',
        'is_available' => false,
        'stat_hp' => 1, 'stat_damage' => 1, 'stat_mobility' => 1,
    ]);
    Cache::forget('seo:sitemap:xml');
    expect($this->get('/sitemap.xml')->getContent())
        ->not->toContain("/lore/operators/{$op->slug}</loc>");
});

it('includes only level 5+ active players in profiles', function () {
    $hi = User::factory()->create(['account_level' => 20, 'is_banned' => false]);
    $lo = User::factory()->create(['account_level' => 2,  'is_banned' => false]);
    $bn = User::factory()->create(['account_level' => 50, 'is_banned' => true]);
    Cache::forget('seo:sitemap:xml');

    $body = $this->get('/sitemap.xml')->getContent();
    expect($body)->toContain("/profile/{$hi->slug}</loc>");
    expect($body)->not->toContain("/profile/{$lo->slug}</loc>");
    expect($body)->not->toContain("/profile/{$bn->slug}</loc>");
});
