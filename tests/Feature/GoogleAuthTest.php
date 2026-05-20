<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

uses(LazilyRefreshDatabase::class);

test('returns google redirect url', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('redirect')->andReturn(
        new RedirectResponse('https://accounts.google.com/o/oauth2/auth')
    );

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->getJson('/api/auth/google/redirect');

    $response->assertSuccessful()
        ->assertJson([
            'url' => 'https://accounts.google.com/o/oauth2/auth',
        ]);
});

test('creates a user and token from google callback', function () {
    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('google-123');
    $googleUser->shouldReceive('getEmail')->andReturn('google.user@example.com');
    $googleUser->shouldReceive('getName')->andReturn('Google User');
    $googleUser->shouldReceive('getNickname')->andReturn(null);
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->getJson('/api/auth/google/callback');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'message',
            'token',
            'data' => ['id', 'name', 'email', 'google_id', 'provider', 'avatar', 'created_at', 'updated_at'],
        ])
        ->assertJson([
            'data' => [
                'email' => 'google.user@example.com',
                'google_id' => 'google-123',
                'provider' => 'google',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'google.user@example.com',
        'google_id' => 'google-123',
        'provider' => 'google',
    ]);

    $user = User::where('email', 'google.user@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();
});
