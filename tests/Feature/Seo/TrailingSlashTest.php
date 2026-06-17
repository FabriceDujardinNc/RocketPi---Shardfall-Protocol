<?php

use App\Models\Faction;
use Illuminate\Http\Request;

beforeEach(function () {
    foreach (['ORBIT', 'FERRO', 'VEIL'] as $slug) {
        Faction::firstOrCreate(['slug' => $slug], [
            'name' => $slug, 'tagline' => 'tag', 'lore' => 'lore', 'color_hue' => 100,
        ]);
    }
});

// Note : Pest::get('/lore/') normalise l'URI via Symfony Request::create → le
// trailing slash est strippé avant que le middleware ne voie la requête.
// On teste donc le middleware en direct via le kernel HTTP, ce qui reproduit
// fidèlement le comportement runtime.

it('redirects trailing slash to no-slash with 301 via kernel', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $req = Request::create('/lore/', 'GET');
    $response = $kernel->handle($req);
    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toEndWith('/lore');
});

it('preserves query string on trailing slash redirect via kernel', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $req = Request::create('/lore/?ref=email', 'GET');
    $response = $kernel->handle($req);
    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toContain('ref=email');
});

it('does not redirect the root /', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $req = Request::create('/', 'GET');
    $response = $kernel->handle($req);
    expect($response->getStatusCode())->toBe(200);
});

it('passes through non-trailing-slash URLs', function () {
    $this->get('/lore')->assertOk();
});

it('skips redirect on non-GET methods', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $req = Request::create('/login/', 'POST', ['email' => 'x@x.com', 'password' => 'wrong']);
    $response = $kernel->handle($req);
    // Pas de 301 (POST n'est pas redirigé), donc on attend autre chose qu'un 301.
    expect($response->getStatusCode())->not->toBe(301);
});
