<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Order;
use App\Models\SystemLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_real_dashboard_data_and_filter_admins(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);
        User::factory()->create([
            'name' => 'Admin Can Tim',
            'email' => 'search-admin@chilldrink.com',
            'role_id' => 3,
        ]);
        User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?q=search-admin');

        $response->assertOk()
            ->assertSee('Tổng quan &amp; Phân tích hệ thống', false)
            ->assertSee('Bán chạy toàn hệ thống', false)
            ->assertSee('Một món bán tốt ở đâu?', false)
            ->assertSee('data-product-branch-performance-region', false)
            ->assertSee('So sánh chi nhánh', false)
            ->assertSee('Theo số lượng', false)
            ->assertSee('Theo doanh thu', false)
            ->assertSee('search-admin@chilldrink.com')
            ->assertDontSee('Không có quản trị viên phù hợp');
    }

    public function test_super_admin_actions_are_disabled_for_super_admin_rows(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);
        $targetSuperAdmin = User::factory()->create([
            'name' => 'Super Admin Khác',
            'email' => 'super-other@chilldrink.com',
            'role_id' => 3,
        ]);
        $admin = User::factory()->create([
            'name' => 'Admin Hệ thống',
            'email' => 'admin-system@chilldrink.com',
            'role_id' => 2,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin');

        $response->assertOk()
            ->assertSee('data-admin-actions-locked="'.$targetSuperAdmin->id.'"', false)
            ->assertSee('title="Không áp dụng cho quản trị cấp cao"', false)
            ->assertDontSee('data-bs-target="#adminActionsModal'.$targetSuperAdmin->id.'"', false)
            ->assertSee('data-bs-target="#adminActionsModal'.$admin->id.'"', false)
            ->assertSee('title="Thao tác"', false);
    }

    public function test_super_admin_dashboard_renders_five_business_kpis(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin');

        $response->assertOk();
        $this->assertSame(5, substr_count($response->getContent(), 'data-business-kpi-card='));
    }

    public function test_super_admin_dashboard_keeps_branch_ranking_region_before_admin_list(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);
        $branch = $this->createBranch('CN-RANK', 'Chi nhánh Xếp hạng');

        $this->createValidOrder($customer, $branch, [
            'total' => 125000,
            'created_at' => CarbonImmutable::parse('2026-07-27 09:30:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin');

        $response->assertOk()
            ->assertSee('data-branch-ranking-region', false)
            ->assertSee('id="branch-ranking"', false)
            ->assertSee('So sánh chi nhánh', false)
            ->assertSee('Hạng', false)
            ->assertSee('Trung bình/đơn', false)
            ->assertSee('Món bán chạy nhất', false)
            ->assertSee('Thao tác', false)
            ->assertSee('data-admins-region', false)
            ->assertSee('id="admins"', false)
            ->assertSee('Danh sách quản trị viên', false);

        $content = $response->getContent();
        $branchRankingMarker = '<section class="sa-panel" id="branch-ranking" data-branch-ranking-region>';
        $adminsMarker = '<div id="admins-region" data-admins-region>';

        $this->assertNotFalse(strpos($content, $branchRankingMarker));
        $this->assertNotFalse(strpos($content, $adminsMarker));
        $this->assertLessThan(
            strpos($content, $adminsMarker),
            strpos($content, $branchRankingMarker),
            'Branch ranking region phải render trước danh sách quản trị viên.'
        );
    }

    public function test_super_admin_dashboard_xhr_response_still_contains_branch_ranking_and_admin_regions(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?branch_search=chi-nhanh', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html',
        ]);

        $response->assertOk()
            ->assertSee('data-branch-ranking-region', false)
            ->assertSee('data-admins-region', false)
            ->assertSee('So sánh chi nhánh', false)
            ->assertSee('Danh sách quản trị viên', false);
    }

    public function test_super_admin_dashboard_query_count_does_not_scale_with_branch_count(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);

        $smallBranches = collect([
            $this->createBranch('CN-A', 'Chi nhánh A'),
            $this->createBranch('CN-B', 'Chi nhánh B'),
            $this->createBranch('CN-C', 'Chi nhánh C'),
        ]);
        $smallBranches->each(fn (Branch $branch) => $this->createValidOrder($customer, $branch));

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $this->actingAs($superAdmin)->get('/admin/super-admin')->assertOk();
        $smallQueryCount = count($connection->getQueryLog());

        $connection->flushQueryLog();

        $manyBranches = collect(range(1, 15))->map(function (int $index) use ($customer): Branch {
            $branch = $this->createBranch('CN-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'Chi nhánh '.$index);
            $this->createValidOrder($customer, $branch, [
                'total' => 50000 + ($index * 1000),
                'created_at' => CarbonImmutable::parse('2026-07-20 10:'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).':00', 'Asia/Ho_Chi_Minh'),
            ]);

            return $branch;
        });

        $manyBranches->each(function (Branch $branch) use ($customer): void {
            $this->createValidOrder($customer, $branch, [
                'total' => 75000,
                'created_at' => CarbonImmutable::parse('2026-07-20 12:00:00', 'Asia/Ho_Chi_Minh'),
            ]);
        });

        $connection->flushQueryLog();
        $this->actingAs($superAdmin)->get('/admin/super-admin')->assertOk();
        $manyQueryCount = count($connection->getQueryLog());

        $this->assertLessThanOrEqual($smallQueryCount + 2, $manyQueryCount);
    }

    public function test_super_admin_can_create_an_admin_account(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 2,
        ]);

        $response = $this->actingAs($superAdmin)->post('/admin/super-admin/admins', [
            'name' => 'Admin Mới',
            'email' => 'admin-moi@chilldrink.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'admin-moi@chilldrink.com',
            'role_id' => 2,
            'is_active' => 1,
        ]);
        $this->assertTrue(SystemLog::where('action', 'like', '%admin-moi@chilldrink.com%')->exists());
    }

    public function test_regular_admin_layout_does_not_show_super_admin_entry(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertDontSee('/admin/super-admin', false);
    }

    public function test_successful_login_updates_last_login_and_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'Đăng nhập hệ thống',
            'status' => 'success',
        ]);
    }

    private function createBranch(string $code, string $name): Branch
    {
        return Branch::query()->create([
            'name' => $name,
            'code' => $code,
            'phone' => '0900000000',
            'email' => strtolower($code).'@chilldrink.test',
            'address' => 'Địa chỉ '.$name,
            'status' => true,
        ]);
    }

    private function createValidOrder(User $customer, Branch $branch, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal' => 50000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 50000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
            'order_code' => 'ORD-'.uniqid(),
        ], $overrides));
    }
}
