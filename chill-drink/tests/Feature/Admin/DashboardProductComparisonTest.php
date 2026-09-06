<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardProductComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_time_comparison_card_and_hides_recent_orders_card(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-03 10:00:00', 'Asia/Ho_Chi_Minh'));

        $adminBranch = $this->branch('CN-01', 'Chi nhánh 01');
        $admin = $this->admin($adminBranch);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertDontSee('So sánh theo thời gian', false)
            ->assertDontSee('Bảng lịch sử doanh thu của chi nhánh, mới nhất ở trên cùng.', false)
            ->assertDontSee('Cả hai', false);
    }

    public function test_admin_dashboard_time_comparison_respects_branch_scope_and_orders_periods_newest_first(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-03 10:00:00', 'Asia/Ho_Chi_Minh'));

        $adminBranch = $this->branch('CN-01', 'Chi nhánh 01');
        $otherBranch = $this->branch('CN-02', 'Chi nhánh 02');
        $admin = $this->admin($adminBranch);
        $customer = $this->customer();

        $this->createOrder($customer, $adminBranch, 258200, '2026-08-03 09:10:00');
        $this->createOrder($customer, $adminBranch, 373700, '2026-07-31 15:20:00');
        $this->createOrder($customer, $otherBranch, 999999, '2026-08-03 09:30:00');

        $response = $this->actingAs($admin)->get('/admin/dashboard/data?' . http_build_query([
            'period' => 'month',
            'month' => '2026-08',
            'admin_matrix_periods' => 8,
        ]));

        $response->assertOk()
            ->assertJsonPath('timeComparison.period_type', 'month')
            ->assertJsonPath('timeComparison.group', 'month')
            ->assertJsonPath('timeComparison.period_count', 8)
            ->assertJsonPath('timeComparison.periods.0.label', '08/2026')
            ->assertJsonPath('timeComparison.periods.1.label', '07/2026')
            ->assertJsonPath('timeComparison.rows.0.label', '08/2026')
            ->assertJsonPath('timeComparison.rows.0.revenue', 258200)
            ->assertJsonPath('timeComparison.rows.0.valid_order_count', 1)
            ->assertJsonPath('timeComparison.rows.0.average_order_value', 258200)
            ->assertJsonPath('timeComparison.rows.0.latest_change.revenue.type', 'new')
            ->assertJsonPath('timeComparison.rows.0.latest_change.orders.type', 'new')
            ->assertJsonPath('timeComparison.rows.1.label', '07/2026')
            ->assertJsonPath('timeComparison.rows.1.revenue', 373700)
            ->assertJsonPath('timeComparison.rows.1.valid_order_count', 1)
            ->assertJsonPath('timeComparison.rows.1.average_order_value', 373700);
    }

    public function test_admin_dashboard_exports_valid_xlsx_with_two_sheets(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-03 10:00:00', 'Asia/Ho_Chi_Minh'));

        $adminBranch = $this->branch('CN-01', 'Chi nhánh 01');
        $admin = $this->admin($adminBranch);
        $customer = $this->customer();

        $this->createOrder($customer, $adminBranch, 258200, '2026-08-03 09:10:00');
        $this->createOrder($customer, $adminBranch, 373700, '2026-07-31 15:20:00');

        $response = $this->actingAs($admin)->get('/admin/dashboard/export?' . http_build_query([
            'period' => 'month',
            'month' => '2026-08',
            'admin_matrix_periods' => 6,
        ]));

        $response->assertOk();

        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($path);

        $workbook = file_get_contents('phar://'.$path.'/xl/workbook.xml');
        $sheet1 = file_get_contents('phar://'.$path.'/xl/worksheets/sheet1.xml');
        $sheet2 = file_get_contents('phar://'.$path.'/xl/worksheets/sheet2.xml');

        $this->assertIsString($workbook);
        $this->assertIsString($sheet1);
        $this->assertIsString($sheet2);
        $this->assertStringContainsString('So sánh theo thời gian', $workbook);
        $this->assertStringContainsString('Điều kiện báo cáo', $workbook);
        $this->assertStringContainsString('Trung bình/đơn', $sheet1);
        $this->assertStringContainsString('Biến động doanh thu', $sheet1);
        $this->assertStringContainsString('Biến động số đơn', $sheet1);
        $this->assertStringContainsString('Quy tắc doanh thu', $sheet2);
        $this->assertStringContainsString('Mới nhất ở trên', $sheet2);
    }

    private function admin(Branch $branch): User
    {
        return User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]);
    }

    private function branch(string $code, string $name): Branch
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

    private function createOrder(User $customer, Branch $branch, int $total, string $createdAt): Order
    {
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal' => $total,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => $total,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
            'order_code' => 'ORD-'.uniqid(),
        ]);

        $order->timestamps = false;
        $order->forceFill([
            'created_at' => CarbonImmutable::parse($createdAt, 'Asia/Ho_Chi_Minh'),
            'updated_at' => CarbonImmutable::parse($createdAt, 'Asia/Ho_Chi_Minh'),
        ])->save();

        return $order->fresh();
    }
}
