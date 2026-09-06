<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAnalyticsPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_overview_applies_month_filter_globally(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();
        $branch = $this->createBranch(['name' => 'Chi nhánh A', 'code' => 'CNA']);
        $customer = User::factory()->create(['role_id' => 1]);

        $this->createOrder($customer, $branch, [
            'status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::parse('2026-07-10 09:00:00'),
            'total' => 120000,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?' . http_build_query([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-07',
            'analytics_branch_ids' => [$branch->id],
        ]));

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->periodType === 'month' && $context->isAllBranches())
            ->assertSee('data-drilldown-default-from="2026-07-01 00:00:00"', false)
            ->assertSee('data-drilldown-default-to="2026-07-27 12:00:00"', false);
    }

    public function test_legacy_single_branch_id_does_not_change_global_overview_scope(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();
        $branch = $this->createBranch(['name' => 'Chi nhánh Legacy', 'code' => 'CNL']);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?' . http_build_query([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-07',
            'analytics_branch_id' => $branch->id,
        ]));

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->isAllBranches());
    }

    public function test_legacy_branch_ids_array_does_not_change_global_overview_scope(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();
        $branchA = $this->createBranch(['name' => 'Chi nhánh A', 'code' => 'CNA']);
        $branchB = $this->createBranch(['name' => 'Chi nhánh B', 'code' => 'CNB']);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?' . http_build_query([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-07',
            'branch_ids' => [$branchA->id, $branchB->id],
        ]));

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->isAllBranches());
    }

    public function test_branch_scope_inputs_do_not_change_global_overview_scope(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();
        $canonicalBranch = $this->createBranch(['name' => 'Chi nhánh Canonical', 'code' => 'CNC']);
        $legacyBranch = $this->createBranch(['name' => 'Chi nhánh Legacy', 'code' => 'CNL']);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?' . http_build_query([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-07',
            'analytics_branch_ids' => [$canonicalBranch->id],
            'branch_ids' => [$legacyBranch->id],
        ]));

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->isAllBranches());
    }

    public function test_duplicate_branch_ids_do_not_change_global_overview_scope(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();
        $branch = $this->createBranch(['name' => 'Chi nhánh Duplicates', 'code' => 'CND']);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?' . http_build_query([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-07',
            'analytics_branch_ids' => [$branch->id, $branch->id, $branch->id],
        ]));

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->isAllBranches());
    }

    public function test_empty_branch_ids_default_to_all_branches(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?analytics_period_type=month&analytics_month=2026-07&analytics_branch_ids%5B0%5D=');

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->isAllBranches());
    }

    public function test_invalid_canonical_branch_ids_are_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->from('/admin/super-admin')
            ->get('/admin/super-admin?' . http_build_query([
                'analytics_period_type' => 'month',
                'analytics_month' => '2026-07',
                'analytics_branch_ids' => [999999],
            ]))
            ->assertRedirect('/admin/super-admin')
            ->assertSessionHasErrors(['analytics_branch_ids.0']);
    }

    public function test_future_day_is_rejected_by_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->from('/admin/super-admin')
            ->get('/admin/super-admin?analytics_period_type=day&analytics_date=2026-07-28')
            ->assertRedirect('/admin/super-admin')
            ->assertSessionHasErrors(['analytics_date']);
    }

    public function test_invalid_branch_is_rejected_by_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->from('/admin/super-admin')
            ->get('/admin/super-admin?analytics_period_type=month&analytics_month=2026-07&analytics_branch_id=999999')
            ->assertRedirect('/admin/super-admin')
            ->assertSessionHasErrors(['analytics_branch_id']);
    }

    public function test_multiple_branch_ids_do_not_change_global_overview_scope(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();
        $branchA = $this->createBranch(['name' => 'Chi nhánh A', 'code' => 'CNA']);
        $branchB = $this->createBranch(['name' => 'Chi nhánh B', 'code' => 'CNB']);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?' . http_build_query([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-07',
            'analytics_branch_ids' => [$branchA->id, $branchB->id],
        ]));

        $response->assertOk()
            ->assertViewHas('analyticsContext', fn ($context) => $context->isAllBranches());
    }

    public function test_invalid_product_sort_is_rejected_by_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 12:00:00'));

        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->from('/admin/super-admin')
            ->get('/admin/super-admin?analytics_period_type=month&analytics_month=2026-07&analytics_product_sort=invalid')
            ->assertRedirect('/admin/super-admin')
            ->assertSessionHasErrors(['analytics_product_sort']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
            'is_active' => true,
        ]);
    }

    private function createBranch(array $overrides = []): Branch
    {
        return Branch::query()->create(array_merge([
            'name' => 'Chi nhánh test',
            'code' => 'CN'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'phone' => '0900000000',
            'email' => 'branch-'.uniqid().'@chilldrink.test',
            'address' => 'Địa chỉ test',
            'status' => true,
        ], $overrides));
    }

    private function createOrder(?User $user, Branch $branch, array $overrides = []): Order
    {
        $createdAt = $overrides['created_at'] ?? now();
        $order = Order::query()->create(array_merge([
            'user_id' => $user?->id,
            'branch_id' => $branch->id,
            'subtotal' => 50000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 50000,
            'payment_method' => 'cod',
            'status' => 'pending',
            'payment_status' => 'pending',
            'note' => null,
        ], $overrides));

        $order->timestamps = false;
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $order->fresh();
    }
}
