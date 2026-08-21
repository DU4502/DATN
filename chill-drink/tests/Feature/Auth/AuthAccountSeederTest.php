<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AuthAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_account_seeder_creates_admin_and_super_admin_accounts_without_preexisting_roles(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'admin@chilldrink.com']);
        $this->assertDatabaseMissing('users', ['email' => User::SUPER_ADMIN_EMAIL]);

        $this->seed(AuthAccountSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@chilldrink.com', 'role_id' => 2, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['email' => User::SUPER_ADMIN_EMAIL, 'role_id' => 3, 'is_active' => true]);
        $this->assertTrue(User::where('email', 'admin@chilldrink.com')->where('role_id', 2)->exists());
        $this->assertTrue(User::where('email', User::SUPER_ADMIN_EMAIL)->where('role_id', 3)->exists());
        $this->assertFalse(Schema::hasColumn('users', 'plain_password'));
    }
}
