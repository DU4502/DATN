<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_voucher_index(): void
    {
        $admin = $this->admin();
        Voucher::factory()->create([
            'code' => 'CHILL10',
            'description' => 'Giảm 10% cho đơn hàng test',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.vouchers.index'));

        $response->assertOk();
        $response->assertSee('CHILL10');
        $response->assertSee('Thêm mã');
    }

    public function test_admin_can_create_voucher_with_created_at_from_submit_time(): void
    {
        $admin = $this->admin();
        $now = Carbon::parse('2026-06-01 10:30:00');
        Carbon::setTestNow($now);

        $response = $this->actingAs($admin)->post(route('admin.vouchers.store'), [
            'code' => 'SUMMER2026',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 15,
            'max_discount' => 25000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'required_rank' => '',
            'point_cost' => 0,
            'is_redeemable' => 0,
            'starts_at' => '',
            'expires_at' => $now->copy()->addMonth()->format('Y-m-d H:i:s'),
            'description' => 'Voucher tạo từ admin',
            'status' => 1,
        ]);

        Carbon::setTestNow();
        $voucher = Voucher::firstWhere('code', 'SUMMER2026');

        $this->assertNotNull($voucher);
        $this->assertTrue($voucher->status);
        $this->assertSame(15, (int) $voucher->value);
        $this->assertTrue($voucher->created_at->equalTo($now));
        $response->assertRedirect(route('admin.vouchers.index'));
    }

    public function test_admin_can_update_and_delete_voucher(): void
    {
        $admin = $this->admin();
        $voucher = Voucher::factory()->create([
            'code' => 'OLD10',
            'value' => 10000,
        ]);

        $this->actingAs($admin)->put(route('admin.vouchers.update', $voucher), [
            'code' => 'NEW10',
            'type' => Voucher::TYPE_FIXED,
            'value' => 12000,
            'min_order' => 30000,
            'usage_limit' => 20,
            'required_rank' => 'silver',
            'point_cost' => 10,
            'is_redeemable' => 1,
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'expires_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'description' => 'Đã cập nhật',
            'status' => 1,
        ])->assertRedirect(route('admin.vouchers.index'));

        $voucher->refresh();
        $this->assertSame('NEW10', $voucher->code);
        $this->assertSame(12000, (int) $voucher->value);
        $this->assertSame('silver', $voucher->required_rank);
        $this->assertTrue($voucher->is_redeemable);

        $this->actingAs($admin)
            ->delete(route('admin.vouchers.destroy', $voucher))
            ->assertRedirect(route('admin.vouchers.index'));

        $this->assertDatabaseMissing('coupons', ['id' => $voucher->id]);
    }

    public function test_percent_voucher_cannot_exceed_one_hundred_percent(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.vouchers.create'))
            ->post(route('admin.vouchers.store'), [
                'code' => 'BAD150',
                'type' => Voucher::TYPE_PERCENT,
                'value' => 150,
                'min_order' => 0,
                'usage_limit' => 10,
                'point_cost' => 0,
                'status' => 1,
            ])
            ->assertRedirect(route('admin.vouchers.create'))
            ->assertSessionHasErrors('value');

        $this->assertDatabaseMissing('coupons', ['code' => 'BAD150']);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => 'admin-voucher-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
            'is_active' => 1,
        ]);
    }
}
