<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DeliveryFeeSetting;
use App\Models\User;
use App\Services\DeliveryRoutingService;
use App\Support\BranchShippingFee;
use App\Support\OrderDistancePolicy;
use App\Support\ShippingFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ShippingFeeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        BranchShippingFee::clearSettingsCache();
        ShippingFee::clearSettingsCache();
    }

    public function test_global_setting_is_used_without_a_branch_override(): void
    {
        $this->setGlobalSettings(2, 9000, [
            ['min_cups' => 1, 'max_cups' => 5, 'price_per_km' => 1000],
            ['min_cups' => 6, 'max_cups' => null, 'price_per_km' => 2000],
        ]);

        $quote = ShippingFee::calculate(5, 'standard', 6);

        $this->assertSame(3.0, $quote['billable_distance_km']);
        $this->assertSame(2000, $quote['rate_per_km']);
        $this->assertSame(6000, $quote['total_fee']);
    }

    public function test_branch_override_uses_the_explicit_checkout_cup_count(): void
    {
        $branch = $this->createBranch('FEE-A');
        $this->setBranchSettings($branch, 1, 12000, [
            ['max_cups' => 5, 'per_km_fee' => 3000],
            ['max_cups' => null, 'per_km_fee' => 7000],
        ]);
        request()->merge(['branch_id' => $branch->id, 'cup_count' => 1]);

        $quote = ShippingFee::calculate(5, 'standard', 8);

        $this->assertSame($branch->id, $quote['branch_id']);
        $this->assertSame(8, $quote['cup_count']);
        $this->assertSame(7000, $quote['per_km_fee']);
        $this->assertSame(28000, $quote['total_fee']);
    }

    public function test_unconfigured_branch_falls_back_to_the_global_setting_without_error(): void
    {
        $branch = $this->createBranch('FEE-FALLBACK');
        $this->setGlobalSettings(3, 8000, [
            ['min_cups' => 1, 'max_cups' => null, 'price_per_km' => 2500],
        ]);
        request()->merge(['branch_id' => $branch->id]);

        $quote = ShippingFee::calculate(7, 'standard', 1);

        $this->assertArrayNotHasKey('branch_id', $quote);
        $this->assertSame(10000, $quote['total_fee']);
        $this->assertNull(BranchShippingFee::settingsForBranch($branch->id));
    }

    public function test_multiple_branches_keep_their_own_fee_configuration(): void
    {
        $branchA = $this->createBranch('FEE-MULTI-A');
        $branchB = $this->createBranch('FEE-MULTI-B');
        $this->setBranchSettings($branchA, 1, 8000, [['max_cups' => null, 'per_km_fee' => 2000]]);
        $this->setBranchSettings($branchB, 3, 8000, [['max_cups' => null, 'per_km_fee' => 5000]]);

        request()->merge(['branch_id' => $branchA->id]);
        $quoteA = ShippingFee::calculate(5, 'standard', 1);
        request()->merge(['branch_id' => $branchB->id]);
        $quoteB = ShippingFee::calculate(5, 'standard', 1);

        $this->assertSame(8000, $quoteA['total_fee']);
        $this->assertSame(10000, $quoteB['total_fee']);
        $this->assertNotSame($quoteA['branch_id'], $quoteB['branch_id']);
    }

    public function test_super_admin_can_manage_branch_settings_and_admin_cannot(): void
    {
        $branch = $this->createBranch('FEE-AUTH');
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $payload = [
            'free_km' => 2,
            'fast_surcharge' => 10000,
            'tiers' => [
                ['max_cups' => 5, 'per_km_fee' => 3000],
                ['max_cups' => null, 'per_km_fee' => 6000],
            ],
        ];

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.shipping-fees.show', $branch))
            ->assertOk()
            ->assertJsonPath('configured', false);

        $this->actingAs($superAdmin)
            ->putJson(route('admin.super-admin.shipping-fees.update', $branch), $payload)
            ->assertOk()
            ->assertJsonPath('settings.branch_id', $branch->id)
            ->assertJsonPath('settings.free_km', 2);

        $this->actingAs($admin)
            ->putJson(route('admin.super-admin.shipping-fees.update', $branch), array_merge($payload, ['free_km' => 9]))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('branch_shipping_fee_settings', [
            'branch_id' => $branch->id,
            'free_km' => 2,
            'updated_by' => $superAdmin->id,
        ]);
    }

    public function test_only_super_admin_can_update_the_global_setting(): void
    {
        $branch = $this->createBranch('FEE-GLOBAL-AUTH');
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $payload = [
            'free_distance_km' => 4,
            'fast_surcharge' => 11000,
            'tier_max' => [5, null],
            'tier_price' => [3500, 6500],
        ];

        $this->actingAs($superAdmin)
            ->put(route('admin.super-admin.manage.staff.delivery-fee-settings.update'), $payload)
            ->assertRedirect();

        $this->assertSame(4.0, DeliveryFeeSetting::query()->firstOrFail()->free_distance_km);

        $this->actingAs($admin)
            ->put(route('admin.super-admin.manage.staff.delivery-fee-settings.update'), array_merge($payload, ['free_distance_km' => 9]))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(4.0, DeliveryFeeSetting::query()->firstOrFail()->free_distance_km);
    }

    public function test_missing_gps_and_routing_fallback_fail_closed_without_throwing(): void
    {
        $branch = $this->createBranch('FEE-ROUTING', 10.7769, 106.7009);

        $this->assertNull(OrderDistancePolicy::distanceFromBranch($branch, null, null));

        $this->mock(DeliveryRoutingService::class, function ($mock) {
            $mock->shouldReceive('route')->once()->andReturn([
                'fallback' => true,
                'distance_m' => 1000,
                'duration_s' => 300,
            ]);
        });

        $this->assertNull(OrderDistancePolicy::distanceFromBranch($branch, 10.7869, 106.7109));
    }

    public function test_fee_setting_smoke_data_rolls_back_cleanly(): void
    {
        $branch = $this->createBranch('FEE-ROLLBACK');
        $before = DB::table('branch_shipping_fee_settings')->count();

        try {
            DB::transaction(function () use ($branch) {
                DB::table('branch_shipping_fee_settings')->insert([
                    'branch_id' => $branch->id,
                    'free_km' => 1,
                    'fast_surcharge' => 8000,
                    'tiers' => json_encode([['max_cups' => null, 'per_km_fee' => 5000]]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                throw new RuntimeException('Rollback shipping fee smoke data');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame($before, DB::table('branch_shipping_fee_settings')->count());
    }

    private function setGlobalSettings(float $freeKm, int $fastSurcharge, array $tiers): void
    {
        DeliveryFeeSetting::query()->firstOrFail()->update([
            'free_distance_km' => $freeKm,
            'fast_surcharge' => $fastSurcharge,
            'cup_tiers' => $tiers,
        ]);
        ShippingFee::clearSettingsCache();
    }

    private function setBranchSettings(Branch $branch, float $freeKm, int $fastSurcharge, array $tiers): void
    {
        DB::table('branch_shipping_fee_settings')->insert([
            'branch_id' => $branch->id,
            'free_km' => $freeKm,
            'fast_surcharge' => $fastSurcharge,
            'tiers' => json_encode($tiers),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        BranchShippingFee::forgetBranch($branch->id);
    }

    private function createBranch(
        string $code,
        ?float $latitude = null,
        ?float $longitude = null
    ): Branch {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => true,
        ]);
    }
}
