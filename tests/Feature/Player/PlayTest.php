<?php

use App\Models\User;

// La connexion n'est PAS obligatoire pour jouer : /play est public.

it('laisse un invité accéder à la page de jeu', function () {
    $this->get('/play')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Player/Play')
            // Invité → pas de token Sanctum, user_id 0.
            ->where('unityConfig.api_token', '')
            ->where('unityConfig.user_id', 0)
        );
});

it('émet un token Unity éphémère pour un utilisateur connecté', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->get('/play')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Player/Play')
            ->where('unityConfig.user_id', $user->id)
            ->where('unityConfig.api_token', fn ($token) => is_string($token) && $token !== '')
        );

    expect($user->tokens()->where('name', 'unity-webgl')->exists())->toBeTrue();
});
