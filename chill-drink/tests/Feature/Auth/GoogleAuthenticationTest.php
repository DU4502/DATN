<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_creates_a_new_user_and_logs_them_in(): void
    {
        $provider = Mockery::mock(Provider::class);
        $socialiteUser = Mockery::mock();

        $socialiteUser->shouldReceive('getId')->andReturn('google-user-001');
        $socialiteUser->shouldReceive('getEmail')->andReturn('google-user@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Google User');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);

        $provider->shouldReceive('scopes')->once()->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/auth/google/callback');
        config()->set('services.google.stateless', true);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'google-user@example.com',
            'google_id' => 'google-user-001',
            'name' => 'Google User',
        ]);
    }

    public function test_google_callback_links_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        $provider = Mockery::mock(Provider::class);
        $socialiteUser = Mockery::mock();

        $socialiteUser->shouldReceive('getId')->andReturn('google-user-002');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);

        $provider->shouldReceive('scopes')->once()->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/auth/google/callback');
        config()->set('services.google.stateless', true);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existingUser->fresh());
        $response->assertRedirect(route('home', absolute: false));

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing@example.com',
            'google_id' => 'google-user-002',
        ]);
    }
}
