<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class FacebookAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_callback_creates_a_new_user_and_logs_them_in(): void
    {
        $provider = Mockery::mock(Provider::class);
        $socialiteUser = Mockery::mock();

        $socialiteUser->shouldReceive('getId')->andReturn('facebook-user-001');
        $socialiteUser->shouldReceive('getEmail')->andReturn('facebook-user@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Facebook User');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);

        $provider->shouldReceive('scopes')->once()->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('facebook')->andReturn($provider);

        config()->set('services.facebook.client_id', 'test-client-id');
        config()->set('services.facebook.client_secret', 'test-client-secret');
        config()->set('services.facebook.redirect', 'http://127.0.0.1:8000/auth/facebook/callback');
        config()->set('services.facebook.stateless', true);

        $response = $this->get(route('auth.facebook.callback'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'facebook-user@example.com',
            'facebook_id' => 'facebook-user-001',
            'name' => 'Facebook User',
        ]);
    }

    public function test_facebook_callback_links_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing-facebook@example.com',
            'facebook_id' => null,
        ]);

        $provider = Mockery::mock(Provider::class);
        $socialiteUser = Mockery::mock();

        $socialiteUser->shouldReceive('getId')->andReturn('facebook-user-002');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing-facebook@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing Facebook User');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);

        $provider->shouldReceive('scopes')->once()->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('facebook')->andReturn($provider);

        config()->set('services.facebook.client_id', 'test-client-id');
        config()->set('services.facebook.client_secret', 'test-client-secret');
        config()->set('services.facebook.redirect', 'http://127.0.0.1:8000/auth/facebook/callback');
        config()->set('services.facebook.stateless', true);

        $response = $this->get(route('auth.facebook.callback'));

        $this->assertAuthenticatedAs($existingUser->fresh());
        $response->assertRedirect(route('home', absolute: false));

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing-facebook@example.com',
            'facebook_id' => 'facebook-user-002',
        ]);
    }
}
