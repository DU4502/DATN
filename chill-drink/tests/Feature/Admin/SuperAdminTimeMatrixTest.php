<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTimeMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_time_matrix_renders_replacement_table_and_export_links(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?analytics_period_type=year&analytics_year=2026');

        $response->assertOk()
            ->assertSee('So sánh chi nhánh theo thời gian', false)
            ->assertSee('Tải Excel', false)
            ->assertSee('Bảng đang xem', false)
            ->assertSee('Toàn bộ dữ liệu', false)
            ->assertDontSee('Doanh thu 7 ngày', false)
            ->assertDontSee('Người dùng mới', false);
    }

    public function test_super_admin_time_matrix_exports_valid_xlsx_with_three_sheets_and_scope_rules(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);
        $branchOne = $this->createBranch('CN-01', 'Chi nhánh 01');
        $branchTwo = $this->createBranch('CN-02', 'Chi nhánh 02');

        $this->createValidOrder($customer, $branchOne, [
            'total' => 120000,
            'created_at' => CarbonImmutable::parse('2026-08-03 09:30:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createValidOrder($customer, $branchTwo, [
            'total' => 45000,
            'created_at' => CarbonImmutable::parse('2026-08-02 11:15:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $currentResponse = $this->actingAs($superAdmin)->get('/admin/super-admin?analytics_period_type=year&analytics_year=2026&branch_time_search=CN-01&branch_time_per_page=1&branch_time_indicator=both&branch_time_period_count=5&analytics_time_matrix_export=current');
        $currentResponse->assertOk();

        $allResponse = $this->actingAs($superAdmin)->get('/admin/super-admin?analytics_period_type=year&analytics_year=2026&branch_time_search=CN-01&branch_time_per_page=1&branch_time_indicator=both&branch_time_period_count=5&analytics_time_matrix_export=all');
        $allResponse->assertOk();

        $currentPath = $currentResponse->baseResponse->getFile()->getPathname();
        $allPath = $allResponse->baseResponse->getFile()->getPathname();

        $this->assertFileExists($currentPath);
        $this->assertFileExists($allPath);

        $currentWorkbook = file_get_contents('phar://'.$currentPath.'/xl/workbook.xml');
        $allWorkbook = file_get_contents('phar://'.$allPath.'/xl/workbook.xml');
        $this->assertIsString($currentWorkbook);
        $this->assertIsString($allWorkbook);
        $this->assertStringContainsString('So sánh', $currentWorkbook);
        $this->assertStringContainsString('Dữ liệu chuẩn', $currentWorkbook);
        $this->assertStringContainsString('Điều kiện báo cáo', $currentWorkbook);
        $this->assertStringContainsString('So sánh', $allWorkbook);

        $currentSheet2 = file_get_contents('phar://'.$currentPath.'/xl/worksheets/sheet2.xml');
        $allSheet2 = file_get_contents('phar://'.$allPath.'/xl/worksheets/sheet2.xml');
        $this->assertIsString($currentSheet2);
        $this->assertIsString($allSheet2);
        $this->assertGreaterThan(
            substr_count($currentSheet2, '<row '),
            substr_count($allSheet2, '<row '),
            'Xuất toàn bộ phải có nhiều dòng dữ liệu hơn bảng đang xem.'
        );
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
