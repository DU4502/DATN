<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AuthAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $this->assertDatabaseHas('roles', ['id' => 4, 'name' => 'cskh']);
        $this->assertDatabaseMissing('users', ['email' => 'cskh@chilldrink.com']);
        $this->assertTrue(User::where('email', 'admin@chilldrink.com')->where('role_id', 2)->exists());
        $this->assertTrue(User::where('email', User::SUPER_ADMIN_EMAIL)->where('role_id', 3)->exists());
        $this->assertFalse(Schema::hasColumn('users', 'plain_password'));
    }

    public function test_auth_account_seeder_does_not_create_or_modify_a_legacy_cskh_account(): void
    {
        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['name' => 'cskh', 'description' => 'Legacy customer support'],
        );
        $legacyCskh = User::create([
            'name' => 'Legacy CSKH',
            'email' => 'legacy-cskh@example.com',
            'password' => Hash::make('unchanged-password'),
            'role_id' => 4,
            'is_active' => false,
        ]);
        $original = $legacyCskh->only(['name', 'email', 'password', 'role_id', 'branch_id', 'is_active']);

        $this->seed(AuthAccountSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'cskh@chilldrink.com']);
        $this->assertSame($original, $legacyCskh->fresh()->only(array_keys($original)));
        $this->assertSame(1, User::query()->where('role_id', 4)->count());
    }
}
