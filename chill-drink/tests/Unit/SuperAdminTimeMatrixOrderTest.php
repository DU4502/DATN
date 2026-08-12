<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use App\Services\SuperAdminAnalyticsPeriodResolver;
use App\Services\SuperAdminAnalyticsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTimeMatrixOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_periods_render_newest_first_from_left_to_right(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        $this->createBranch();

        /** @var SuperAdminAnalyticsService $service */
        $service = app(SuperAdminAnalyticsService::class);
        /** @var SuperAdminAnalyticsPeriodResolver $resolver */
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);

        $dayMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'day',
            'analytics_date' => '2026-08-03',
        ]), [
            'period_count' => 7,
        ]);
        $this->assertSame(
            ['2026-08-03', '2026-08-02', '2026-08-01', '2026-07-31', '2026-07-30', '2026-07-29', '2026-07-28'],
            $dayMatrix['periods']->pluck('key')->all()
        );

        $weekMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'week',
            'analytics_week' => '2026-W32',
        ]), [
            'period_count' => 4,
        ]);
        $this->assertSame(
            collect(range(0, 3))
                ->map(static fn (int $offset): string => CarbonImmutable::parse('2026-08-03 12:00:00', 'Asia/Ho_Chi_Minh')
                    ->subWeeks($offset)
                    ->startOfWeek(Carbon::MONDAY)
                    ->format('o-\WW'))
                ->all(),
            $weekMatrix['periods']->pluck('key')->all()
        );

        $monthContext = $resolver->resolve([
            'analytics_period_type' => 'month',
            'analytics_month' => '2026-08',
        ]);
        $monthMatrix = $service->branchTimeComparison($monthContext, [
            'period_count' => 6,
        ]);

        $this->assertSame(['2026-08', '2026-07', '2026-06', '2026-05', '2026-04', '2026-03'], $monthMatrix['periods']->pluck('key')->all());

        $yearContext = $resolver->resolve([
            'analytics_period_type' => 'year',
            'analytics_year' => '2026',
        ]);
        $yearMatrix = $service->branchTimeComparison($yearContext, [
            'period_count' => 3,
        ]);

        $this->assertSame(['2026', '2025', '2024'], $yearMatrix['periods']->pluck('key')->all());
    }

    public function test_custom_range_periods_render_newest_first_for_day_week_and_month_groups(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        $this->createBranch();

        /** @var SuperAdminAnalyticsService $service */
        $service = app(SuperAdminAnalyticsService::class);
        /** @var SuperAdminAnalyticsPeriodResolver $resolver */
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);

        $dayMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'range',
            'analytics_start_date' => '2026-08-01',
            'analytics_end_date' => '2026-08-03',
        ]));
        $this->assertSame(['2026-08-03', '2026-08-02', '2026-08-01'], $dayMatrix['periods']->pluck('key')->all());
        $this->assertTrue((bool) ($dayMatrix['periods']->first()['is_partial'] ?? false));

        $weekMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'range',
            'analytics_start_date' => '2026-06-01',
            'analytics_end_date' => '2026-07-12',
        ]));
        $this->assertSame('week', $weekMatrix['group_type']);
        $this->assertSame('2026-W28', (string) ($weekMatrix['periods']->first()['key'] ?? ''));
        $this->assertSame('2026-W23', (string) ($weekMatrix['periods']->last()['key'] ?? ''));
        $this->assertGreaterThan(
            $weekMatrix['periods']->get(1)['start']->timestamp,
            $weekMatrix['periods']->first()['start']->timestamp
        );

        $monthMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'range',
            'analytics_start_date' => '2025-10-01',
            'analytics_end_date' => '2026-08-03',
        ]));
        $this->assertSame('month', $monthMatrix['group_type']);
        $this->assertSame('2026-08', (string) ($monthMatrix['periods']->first()['key'] ?? ''));
        $this->assertSame('2025-10', (string) ($monthMatrix['periods']->last()['key'] ?? ''));
        $this->assertTrue((bool) ($monthMatrix['periods']->first()['is_partial'] ?? false));
    }

    public function test_latest_change_uses_first_two_periods_and_single_period_reports_insufficient_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        $branch = $this->createBranch();
        $customer = User::factory()->create(['role_id' => 1]);

        $this->createValidOrder($customer, $branch, [
            'total' => 200000,
            'created_at' => CarbonImmutable::parse('2026-08-03 08:15:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createValidOrder($customer, $branch, [
            'total' => 100000,
            'created_at' => CarbonImmutable::parse('2026-08-02 10:30:00', 'Asia/Ho_Chi_Minh'),
        ]);

        /** @var SuperAdminAnalyticsService $service */
        $service = app(SuperAdminAnalyticsService::class);
        /** @var SuperAdminAnalyticsPeriodResolver $resolver */
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);

        $dayMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'day',
            'analytics_date' => '2026-08-03',
        ]), [
            'period_count' => 7,
        ]);

        $row = $dayMatrix['branches']->first();
        $firstPeriodKey = (string) ($dayMatrix['periods']->first()['key'] ?? '');
        $secondPeriodKey = (string) ($dayMatrix['periods']->get(1)['key'] ?? '');

        $this->assertSame(200000, (int) ($row['periods'][$firstPeriodKey]['revenue'] ?? -1));
        $this->assertSame(100000, (int) ($row['periods'][$secondPeriodKey]['revenue'] ?? -1));
        $this->assertSame(
            (int) ($row['periods'][$firstPeriodKey]['revenue'] ?? -1),
            (int) ($row['latest_revenue_change']['current_value'] ?? -1)
        );
        $this->assertSame(
            (int) ($row['periods'][$secondPeriodKey]['revenue'] ?? -1),
            (int) ($row['latest_revenue_change']['previous_value'] ?? -1)
        );
        $this->assertSame('Tăng', (string) ($row['latest_revenue_change']['label'] ?? ''));

        $singlePeriodMatrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'range',
            'analytics_start_date' => '2026-08-03',
            'analytics_end_date' => '2026-08-03',
        ]));
        $singleRow = $singlePeriodMatrix['branches']->first();
        $this->assertSame('Chưa đủ dữ liệu', (string) ($singleRow['latest_revenue_change']['label'] ?? ''));
    }

    public function test_totals_and_rows_keep_the_same_order_after_reversal_when_period_has_no_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        $this->createBranch();

        /** @var SuperAdminAnalyticsService $service */
        $service = app(SuperAdminAnalyticsService::class);
        /** @var SuperAdminAnalyticsPeriodResolver $resolver */
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);

        $matrix = $service->branchTimeComparison($resolver->resolve([
            'analytics_period_type' => 'day',
            'analytics_date' => '2026-08-03',
        ]), [
            'period_count' => 7,
        ]);

        $periodKeys = $matrix['periods']->pluck('key')->values()->all();
        $totalKeys = $matrix['totals']['periods']->pluck('period_key')->values()->all();

        $this->assertSame($periodKeys, $totalKeys);
        $this->assertSame('2026-08-03', (string) ($matrix['periods']->first()['key'] ?? ''));
        $this->assertSame('2026-07-28', (string) ($matrix['periods']->last()['key'] ?? ''));
    }

    private function createBranch(): Branch
    {
        return Branch::query()->create([
            'name' => 'Chi nhánh kiểm thử',
            'code' => 'CN-TEST',
            'phone' => '0900000000',
            'email' => 'test-'.uniqid().'@chilldrink.test',
            'address' => 'Địa chỉ test',
            'status' => true,
        ]);
    }

    private function createValidOrder(User $customer, Branch $branch, array $overrides = []): Order
    {
        $timestamps = [];
        if (array_key_exists('created_at', $overrides)) {
            $timestamps['created_at'] = $overrides['created_at'];
            $timestamps['updated_at'] = $overrides['updated_at'] ?? $overrides['created_at'];
        } elseif (array_key_exists('updated_at', $overrides)) {
            $timestamps['updated_at'] = $overrides['updated_at'];
        }

        $order = Order::query()->create(array_merge([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
            'order_code' => 'ORD-'.uniqid(),
        ], array_diff_key($overrides, array_flip(['created_at', 'updated_at']))));

        if ($timestamps !== []) {
            $order->timestamps = false;
            $order->forceFill($timestamps)->saveQuietly();
            $order->refresh();
        }

        return $order;
    }
}
