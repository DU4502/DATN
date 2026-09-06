<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Cache::store('file')->put(
            'email-verification-verified:email:'.sha1('test@example.com'),
            true,
            now()->addMinutes(10)
        );

        $response = $this->post('/register', [
            'name' => 'Test User',
            'phone' => '0901234567',
            'email' => 'test@example.com',
            'email_verification_code' => '123456',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertTrue(User::where('email', 'test@example.com')->firstOrFail()->hasVerifiedEmail());
    }

    public function test_registration_email_code_can_be_verified(): void
    {
        Cache::store('file')->put(
            'email-verification-code:email:'.sha1('test@example.com'),
            Hash::make('123456'),
            now()->addMinutes(10)
        );

        $response = $this->postJson(route('register.email-code.verify'), [
            'email' => 'test@example.com',
            'email_verification_code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Gmail đã được xác minh.');

        $this->assertTrue(Cache::store('file')->has('email-verification-verified:email:'.sha1('test@example.com')));
    }

    public function test_registration_email_code_can_be_sent(): void
    {
        Notification::fake();

        $response = $this->postJson(route('register.email-code.send'), [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Mã xác minh đã được gửi tới email của bạn.');

        Notification::assertSentOnDemand(EmailVerificationCodeNotification::class);
    }

    public function test_registration_email_code_is_not_sent_for_existing_email(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson(route('register.email-code.send'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Email này đã được đăng ký.')
            ->assertJsonValidationErrors(['email']);

        Notification::assertNothingSent();
    }
}
