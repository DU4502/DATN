<?php

namespace Tests\Feature\Database;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EnsureUsersEmailUniqueMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_unique_index_when_email_has_no_duplicates(): void
    {
        User::factory()->count(2)->create();
        $this->dropUniqueEmailIndex();
        $rowsBefore = User::count();

        $this->migration()->up();

        $this->assertCount(1, $this->uniqueEmailIndexes());
        $this->assertSame($rowsBefore, User::count());
    }

    public function test_it_fails_without_deleting_users_when_duplicate_emails_exist(): void
    {
        $this->dropUniqueEmailIndex();
        User::factory()->create(['email' => 'duplicate@example.com']);
        User::factory()->create(['email' => 'duplicate@example.com']);
        $rowsBefore = User::count();

        try {
            $this->migration()->up();
            $this->fail('The migration must reject duplicate email records.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('1 duplicate email group(s)', $exception->getMessage());
            $this->assertStringContainsString('2 record(s)', $exception->getMessage());
        }

        $this->assertSame($rowsBefore, User::count());
        $this->assertCount(0, $this->uniqueEmailIndexes());
    }

    public function test_it_is_a_no_op_when_a_unique_email_index_already_exists(): void
    {
        User::factory()->count(2)->create();
        $indexesBefore = $this->uniqueEmailIndexes();
        $rowsBefore = User::count();

        $this->migration()->up();

        $this->assertCount(1, $indexesBefore);
        $this->assertSame($indexesBefore->pluck('name')->all(), $this->uniqueEmailIndexes()->pluck('name')->all());
        $this->assertSame($rowsBefore, User::count());
    }

    public function test_failure_preserves_user_related_records(): void
    {
        $this->dropUniqueEmailIndex();
        $first = User::factory()->create(['email' => 'related@example.com']);
        $second = User::factory()->create(['email' => 'related@example.com']);
        Address::create(['user_id' => $first->id, 'detail' => 'Address one']);
        Address::create(['user_id' => $second->id, 'detail' => 'Address two']);

        try {
            $this->migration()->up();
            $this->fail('The migration must reject duplicate email records.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseHas('users', ['id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id]);
        $this->assertDatabaseHas('addresses', ['user_id' => $first->id, 'detail' => 'Address one']);
        $this->assertDatabaseHas('addresses', ['user_id' => $second->id, 'detail' => 'Address two']);
        $this->assertSame(2, Address::whereIn('user_id', [$first->id, $second->id])->count());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_07_31_000001_ensure_users_email_unique.php');
    }

    private function dropUniqueEmailIndex(): void
    {
        foreach ($this->uniqueEmailIndexes() as $index) {
            Schema::table('users', function (Blueprint $table) use ($index) {
                $table->dropUnique($index['name']);
            });
        }
    }

    private function uniqueEmailIndexes()
    {
        return collect(Schema::getIndexes('users'))
            ->filter(function (array $index): bool {
                $columns = array_values($index['columns'] ?? []);

                return (bool) ($index['unique'] ?? false)
                    && count($columns) === 1
                    && $columns[0] === 'email';
            })
            ->values();
    }
}
