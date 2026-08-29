<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Services\AnalyticsPeriodContext;
use App\Services\SuperAdminAnalyticsService;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_sales_orders_query_applies_the_shared_business_rule(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();

        $completedUnpaid = $this->createOrder($user, $branch, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 110000,
        ]);
        $paidProcessing = $this->createOrder($user, $branch, [
            'status' => OrderStatus::PREPARING,
            'payment_status' => 'paid',
            'total' => 120000,
        ]);
        $cancelledPaid = $this->createOrder($user, $branch, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'total' => 130000,
        ]);
        $cancelledUnpaid = $this->createOrder($user, $branch, [
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'total' => 140000,
        ]);
        $pendingUnpaid = $this->createOrder($user, $branch, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'total' => 150000,
        ]);

        $validIds = $service->validSalesOrdersQuery()->pluck('orders.id');

        $this->assertTrue($validIds->contains($completedUnpaid->id));
        $this->assertFalse($validIds->contains($paidProcessing->id));
        $this->assertFalse($validIds->contains($cancelledPaid->id));
        $this->assertFalse($validIds->contains($cancelledUnpaid->id));
        $this->assertFalse($validIds->contains($pendingUnpaid->id));
    }

    public function test_revenue_summary_uses_orders_total(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();

        $this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'subtotal' => 90000,
            'total' => 100000,
        ]);
        $this->createOrder($user, $branch, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'subtotal' => 180000,
            'total' => 200000,
        ]);

        $this->assertSame(300000, $service->revenueSummary($service->validSalesOrdersQuery()));
    }

    public function test_item_quantity_summary_uses_sum_of_order_item_quantity(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();
        [$product, $productSize] = $this->createProductWithSize();

        $validOrder = $this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
        ]);
        $invalidOrder = $this->createOrder($user, $branch, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
        ]);

        $this->createOrderItem($validOrder, $product, $productSize, [
            'quantity' => 2,
            'unit_price' => 30000,
            'total_price' => 60000,
        ]);
        $this->createOrderItem($validOrder, $product, $productSize, [
            'quantity' => 3,
            'unit_price' => 30000,
            'total_price' => 90000,
        ]);
        $this->createOrderItem($invalidOrder, $product, $productSize, [
            'quantity' => 99,
            'unit_price' => 30000,
            'total_price' => 2970000,
        ]);

        $this->assertSame(5, $service->itemQuantitySummary($service->validSalesOrderItemsQuery()));
    }

    public function test_customer_summary_counts_distinct_registered_users_only(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->createOrder($userA, $branch, ['status' => 'completed', 'payment_status' => 'pending']);
        $this->createOrder($userA, $branch, ['status' => OrderStatus::COMPLETED, 'payment_status' => 'paid']);
        $this->createOrder($userB, $branch, ['status' => OrderStatus::COMPLETED, 'payment_status' => 'paid']);
        $this->createOrder(null, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'guest_name' => 'Khach Vang Lai',
            'guest_phone' => '0900000000',
            'guest_email' => 'guest@example.com',
        ]);

        $this->assertSame(2, $service->customerSummary($service->validSalesOrdersQuery()));
    }

    public function test_branch_scope_filters_to_the_selected_branch(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Branch A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Branch B']);
        $user = User::factory()->create();

        $this->createOrder($user, $branchA, ['status' => OrderStatus::COMPLETED, 'payment_status' => 'paid', 'total' => 111000]);
        $this->createOrder($user, $branchB, ['status' => OrderStatus::COMPLETED, 'payment_status' => 'paid', 'total' => 222000]);

        $query = $service->validSalesOrdersQuery();
        $service->applyBranchScope($query, $branchB->id);

        $this->assertSame(222000, $service->revenueSummary($query));
    }

    public function test_date_range_filters_by_orders_created_at(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();

        $this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'created_at' => Carbon::parse('2026-07-10 09:00:00'),
            'total' => 100000,
        ]);
        $this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'created_at' => Carbon::parse('2026-07-20 09:00:00'),
            'total' => 200000,
        ]);

        $query = $service->validSalesOrdersQuery();
        $service->applyDateRange(
            $query,
            Carbon::parse('2026-07-15 00:00:00'),
            Carbon::parse('2026-07-27 23:59:59')
        );

        $this->assertSame(200000, $service->revenueSummary($query));
    }

    public function test_business_summary_uses_valid_sales_rules_distinct_customers_and_item_quantities(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $otherBranch = $this->createBranch(['code' => 'OTR', 'name' => 'Chi nhánh khác']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$product, $productSize] = $this->createProductWithSize();

        $currentPaid = $this->createOrder($userA, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 120000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($currentPaid, $product, $productSize, ['quantity' => 2, 'total_price' => 60000]);

        $currentCompleted = $this->createOrder($userA, $branch, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 80000,
            'created_at' => CarbonImmutable::parse('2026-07-21 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($currentCompleted, $product, $productSize, ['quantity' => 1, 'total_price' => 30000]);

        $guestOrder = $this->createOrder(null, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 50000,
            'created_at' => CarbonImmutable::parse('2026-07-22 09:00:00', 'Asia/Ho_Chi_Minh'),
            'guest_name' => 'Khach vang lai',
            'guest_phone' => '0900000000',
        ]);
        $this->createOrderItem($guestOrder, $product, $productSize, ['quantity' => 3, 'total_price' => 90000]);

        $this->createOrder($userB, $branch, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'total' => 999000,
            'created_at' => CarbonImmutable::parse('2026-07-23 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $this->createOrder($userB, $otherBranch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 333000,
            'created_at' => CarbonImmutable::parse('2026-07-23 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $context = $this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-27 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Ngày 20/07/2026 - 27/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]);

        $summary = $service->businessSummary($context);

        $this->assertSame(250000, $summary['revenue']['current_value']);
        $this->assertSame(3, $summary['orders']['current_value']);
        $this->assertSame(1, $summary['customers']['current_value']);
        $this->assertSame(6, $summary['items_sold']['current_value']);
        $this->assertSame(83333, $summary['average_order_value']['current_value']);
        $this->assertSame(1, $summary['guest_order_count']);
        $this->assertNull($summary['revenue']['compare_value']);
        $this->assertSame('unavailable', $summary['revenue']['change_state']);
    }

    public function test_business_summary_returns_new_activity_when_compare_period_is_empty(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();

        $this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 450000,
            'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $context = $this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        $summary = $service->businessSummary($context);

        $this->assertSame(450000, $summary['revenue']['current_value']);
        $this->assertSame(0, $summary['revenue']['compare_value']);
        $this->assertSame('new_activity', $summary['revenue']['change_state']);
        $this->assertNull($summary['revenue']['percentage_change']);
    }

    public function test_business_summary_returns_unchanged_when_current_and_compare_are_zero(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();

        $context = $this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        $summary = $service->businessSummary($context);

        $this->assertSame(0, $summary['revenue']['current_value']);
        $this->assertSame(0, $summary['revenue']['compare_value']);
        $this->assertSame('unchanged', $summary['revenue']['change_state']);
        $this->assertSame(0.0, $summary['revenue']['percentage_change']);
    }

    public function test_business_summary_applies_branch_scope(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Branch A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Branch B']);
        $user = User::factory()->create();

        $this->createOrder($user, $branchA, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 100000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrder($user, $branchB, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 300000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $summary = $service->businessSummary($this->analyticsContext([
            'branchId' => $branchB->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]));

        $this->assertSame(300000, $summary['revenue']['current_value']);
        $this->assertSame(1, $summary['orders']['current_value']);
        $this->assertSame(1, $summary['customers']['current_value']);
    }

    public function test_business_summary_applies_multiple_branch_scope_to_every_metric(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Branch A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Branch B']);
        $branchC = $this->createBranch(['code' => 'BRC', 'name' => 'Branch C']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$product, $productSize] = $this->createProductWithSize();

        $orderA = $this->createOrder($userA, $branchA, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 100000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($orderA, $product, $productSize, ['quantity' => 2, 'total_price' => 60000]);

        $orderB = $this->createOrder($userB, $branchB, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 150000,
            'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($orderB, $product, $productSize, ['quantity' => 3, 'total_price' => 90000]);

        $this->createOrder($userB, $branchC, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 999000,
            'created_at' => CarbonImmutable::parse('2026-07-20 11:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $summary = $service->businessSummary($this->analyticsContext([
            'branchIds' => [$branchA->id, $branchB->id],
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]));

        $this->assertSame(250000, $summary['revenue']['current_value']);
        $this->assertSame(2, $summary['orders']['current_value']);
        $this->assertSame(2, $summary['customers']['current_value']);
        $this->assertSame(5, $summary['items_sold']['current_value']);
    }

    public function test_business_summary_uses_two_queries_without_comparison_and_four_with_comparison(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();
        [$product, $productSize] = $this->createProductWithSize();
        $this->createOrderItem($this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 100000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]), $product, $productSize, ['quantity' => 2]);

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $service->businessSummary($this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]));

        $noCompareQueries = collect($connection->getQueryLog())->filter(function (array $query): bool {
            return preg_match('/from\s+[`"]?orders[`"]?/i', $query['query']) === 1
                || preg_match('/from\s+[`"]?order_items[`"]?/i', $query['query']) === 1;
        });

        $this->assertCount(2, $noCompareQueries);

        $connection->flushQueryLog();

        $service->businessSummary($this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]));

        $compareQueries = collect($connection->getQueryLog())->filter(function (array $query): bool {
            return preg_match('/from\s+[`"]?orders[`"]?/i', $query['query']) === 1
                || preg_match('/from\s+[`"]?order_items[`"]?/i', $query['query']) === 1;
        });

        $this->assertCount(4, $compareQueries);
    }

    public function test_top_products_orders_by_quantity_and_revenue_and_limits_to_five(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();
        [$productA, $productSizeA] = $this->createProductWithSize(['name' => 'Sản phẩm A', 'price' => 111111]);
        [$productB, $productSizeB] = $this->createProductWithSize(['name' => 'Sản phẩm B', 'price' => 222222]);
        [$productC, $productSizeC] = $this->createProductWithSize(['name' => 'Sản phẩm C', 'price' => 333333]);
        [$productD, $productSizeD] = $this->createProductWithSize(['name' => 'Sản phẩm D', 'price' => 444444]);
        [$productE, $productSizeE] = $this->createProductWithSize(['name' => 'Sản phẩm E', 'price' => 555555]);
        [$productF, $productSizeF] = $this->createProductWithSize(['name' => 'Sản phẩm F', 'price' => 666666]);

        $this->createProductSale($user, $branch, $productA, $productSizeA, 5, 50000, '2026-07-20 09:00:00');
        $this->createProductSale($user, $branch, $productB, $productSizeB, 5, 70000, '2026-07-20 09:10:00');
        $this->createProductSale($user, $branch, $productC, $productSizeC, 4, 80000, '2026-07-20 09:20:00');
        $this->createProductSale($user, $branch, $productD, $productSizeD, 2, 90000, '2026-07-20 09:30:00');
        $this->createProductSale($user, $branch, $productE, $productSizeE, 1, 10000, '2026-07-20 09:40:00');
        $this->createProductSale($user, $branch, $productF, $productSizeF, 6, 12000, '2026-07-20 09:50:00');

        $productA->update(['price' => 999999]);

        $context = $this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]);

        $quantityRows = $service->topProducts($context, 'quantity', 5);
        $this->assertCount(5, $quantityRows);
        $this->assertSame([$productF->id, $productB->id, $productA->id, $productC->id, $productD->id], $quantityRows->pluck('product_id')->all());
        $this->assertSame(50000, $quantityRows[2]['total_revenue']);
        $this->assertSame('quantity', $quantityRows[0]['sort_by']);

        $revenueRows = $service->topProducts($context, 'revenue', 5);
        $this->assertSame([$productD->id, $productC->id, $productB->id, $productA->id, $productF->id], $revenueRows->pluck('product_id')->all());
        $this->assertSame(90000, $revenueRows[0]['total_revenue']);
        $this->assertSame('revenue', $revenueRows[0]['sort_by']);
    }

    public function test_top_products_applies_valid_sales_rule_date_range_and_branch_scope(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Chi nhánh A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Chi nhánh B']);
        $user = User::factory()->create();
        [$product, $productSize] = $this->createProductWithSize(['name' => 'Sản phẩm scope']);

        $this->createProductSale($user, $branchA, $product, $productSize, 2, 20000, '2026-07-20 09:00:00');
        $this->createProductSale($user, $branchB, $product, $productSize, 9, 90000, '2026-07-20 09:10:00');
        $this->createOrder($user, $branchA, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'total' => 999000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:20:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrder($user, $branchA, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 888000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:30:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrder($user, $branchA, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 777000,
            'created_at' => CarbonImmutable::parse('2026-07-10 09:30:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $rows = $service->topProducts($this->analyticsContext([
            'branchId' => $branchA->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]), 'quantity', 5);

        $this->assertCount(1, $rows);
        $this->assertSame($product->id, $rows[0]['product_id']);
        $this->assertSame(2, $rows[0]['total_quantity']);
        $this->assertSame(20000, $rows[0]['total_revenue']);
        $this->assertSame($branchA->id, $rows[0]['strongest_branch_id']);
        $this->assertSame('Chi nhánh A', $rows[0]['strongest_branch_name']);
    }

    public function test_top_products_identifies_strongest_branch_and_tie_breaks_stably(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Branch A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Branch B']);
        $user = User::factory()->create();
        [$productTie, $tieSize] = $this->createProductWithSize(['name' => 'Sản phẩm hòa']);
        [$productRevenue, $revenueSize] = $this->createProductWithSize(['name' => 'Sản phẩm doanh thu']);

        $this->createProductSale($user, $branchA, $productTie, $tieSize, 2, 20000, '2026-07-20 10:00:00');
        $this->createProductSale($user, $branchB, $productTie, $tieSize, 2, 20000, '2026-07-20 10:05:00');
        $this->createProductSale($user, $branchA, $productRevenue, $revenueSize, 1, 10000, '2026-07-20 10:10:00');
        $this->createProductSale($user, $branchB, $productRevenue, $revenueSize, 3, 30000, '2026-07-20 10:15:00');

        $quantityRows = $service->topProducts($this->analyticsContext([
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]), 'quantity', 5);

        $tieRow = $quantityRows->firstWhere('product_id', $productTie->id);
        $this->assertNotNull($tieRow);
        $this->assertSame($branchA->id, $tieRow['strongest_branch_id']);
        $this->assertSame(2, $tieRow['strongest_branch_quantity']);

        $revenueRows = $service->topProducts($this->analyticsContext([
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]), 'revenue', 5);

        $revenueRow = $revenueRows->firstWhere('product_id', $productRevenue->id);
        $this->assertNotNull($revenueRow);
        $this->assertSame($branchB->id, $revenueRow['strongest_branch_id']);
        $this->assertSame(30000, $revenueRow['strongest_branch_revenue']);
    }

    public function test_top_products_keeps_soft_deleted_and_missing_products_in_report(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $user = User::factory()->create();
        [$softProduct, $softSize] = $this->createProductWithSize(['name' => 'Sản phẩm còn mềm']);
        [$missingProduct, $missingSize] = $this->createProductWithSize(['name' => 'Sản phẩm sẽ mất']);

        $this->createProductSale($user, $branch, $softProduct, $softSize, 2, 40000, '2026-07-20 08:00:00');
        $this->createProductSale($user, $branch, $missingProduct, $missingSize, 1, 50000, '2026-07-20 08:10:00');

        $softProduct->delete();
        $missingProduct->update(['name' => '']);

        $rows = $service->topProducts($this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]), 'quantity', 5);

        $this->assertSame('Sản phẩm còn mềm', $rows[0]['product_name']);
        $this->assertSame('Sản phẩm #'.$missingProduct->id, $rows[1]['product_name']);
        $this->assertNotEmpty($rows[1]['product_image_url']);
    }

    public function test_top_products_uses_two_queries_and_returns_empty_collection_without_data(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch();
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $emptyRows = $service->topProducts($this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]), 'quantity', 5);

        $this->assertTrue($emptyRows->isEmpty());
        $this->assertCount(1, $connection->getQueryLog());

        [$product, $productSize] = $this->createProductWithSize(['name' => 'Sản phẩm log']);
        $user = User::factory()->create();
        $this->createProductSale($user, $branch, $product, $productSize, 2, 20000, '2026-07-20 08:00:00');
        $connection->flushQueryLog();

        $rows = $service->topProducts($this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]), 'quantity', 5);

        $this->assertCount(1, $rows);
        $this->assertCount(2, $connection->getQueryLog());
    }

    public function test_product_branch_performance_ranks_branches_keeps_zero_sales_branches_and_compares_periods(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Chi nhánh A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Chi nhánh B']);
        $branchC = $this->createBranch(['code' => 'BRC', 'name' => 'Chi nhánh C']);
        $user = User::factory()->create();
        [$product, $productSize] = $this->createProductWithSize(['name' => 'Sản phẩm focus']);

        $this->createProductSale($user, $branchA, $product, $productSize, 2, 20000, '2026-07-20 09:00:00');
        $this->createProductSale($user, $branchB, $product, $productSize, 2, 40000, '2026-07-20 09:05:00');
        $this->createProductSale($user, $branchA, $product, $productSize, 1, 10000, '2026-07-19 09:00:00');
        $this->createProductSale($user, $branchB, $product, $productSize, 2, 30000, '2026-07-19 09:05:00');

        $result = $service->productBranchPerformance($this->analyticsContext([
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]), $product->id, [
            'sort_by' => 'quantity',
            'search' => '',
            'page' => 1,
        ]);

        $rows = $result['branches'];

        $this->assertSame($product->id, $result['product']['id']);
        $this->assertSame(4, $result['summary']['total_quantity']);
        $this->assertSame(60000, $result['summary']['total_revenue']);
        $this->assertSame(2, $result['summary']['branches_with_sales']);
        $this->assertSame(3, $result['summary']['total_branches_in_scope']);
        $this->assertSame($branchB->id, $result['summary']['strongest_branch_id']);
        $this->assertSame('Chi nhánh B', $result['summary']['strongest_branch_name']);
        $this->assertSame([$branchB->id, $branchA->id, $branchC->id], $rows->pluck('branch_id')->all());
        $this->assertSame(2, $rows[0]['total_quantity']);
        $this->assertSame(40000, $rows[0]['total_revenue']);
        $this->assertSame(2, $rows[1]['total_quantity']);
        $this->assertSame(20000, $rows[1]['total_revenue']);
        $this->assertSame(0, $rows[2]['total_quantity']);
        $this->assertSame(0, $rows[2]['total_revenue']);
        $this->assertSame(0.0, $rows[0]['quantity_change_percentage']);
        $this->assertSame(33.3, $rows[0]['revenue_change_percentage']);
        $this->assertSame(100.0, $rows[1]['quantity_change_percentage']);
        $this->assertSame(100.0, $rows[1]['revenue_change_percentage']);
        $this->assertSame('unchanged', $rows[2]['quantity_change_state']);
        $this->assertSame(0.0, $rows[2]['quantity_change_percentage']);
    }

    public function test_branch_product_detail_aggregates_orders_items_comparison_and_product_fallbacks(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch(['code' => 'BPD', 'name' => 'Chi nhánh BPD']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$productA, $sizeA] = $this->createProductWithSize(['name' => 'Sản phẩm A']);
        [$productB, $sizeB] = $this->createProductWithSize(['name' => 'Sản phẩm B']);
        [$softProduct, $softSize] = $this->createProductWithSize(['name' => 'Sản phẩm mềm']);
        [$missingProduct, $missingSize] = $this->createProductWithSize(['name' => 'Sản phẩm sẽ mất']);

        $currentPaid = $this->createOrder($userA, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 120000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($currentPaid, $productA, $sizeA, ['quantity' => 2, 'unit_price' => 30000, 'total_price' => 60000]);
        $this->createOrderItem($currentPaid, $productB, $sizeB, ['quantity' => 1, 'unit_price' => 40000, 'total_price' => 40000]);

        $currentCompleted = $this->createOrder($userB, $branch, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 80000,
            'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($currentCompleted, $productA, $sizeA, ['quantity' => 1, 'unit_price' => 30000, 'total_price' => 30000]);
        $this->createOrderItem($currentCompleted, $softProduct, $softSize, ['quantity' => 1, 'unit_price' => 50000, 'total_price' => 50000]);

        $currentGuest = $this->createOrder(null, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 50000,
            'created_at' => CarbonImmutable::parse('2026-07-20 11:00:00', 'Asia/Ho_Chi_Minh'),
            'guest_name' => 'Khách lẻ',
            'guest_phone' => '0900000000',
            'guest_email' => 'guest@example.com',
        ]);
        $this->createOrderItem($currentGuest, $missingProduct, $missingSize, ['quantity' => 2, 'unit_price' => 25000, 'total_price' => 50000]);

        $this->createOrder($userA, $branch, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'total' => 70000,
            'created_at' => CarbonImmutable::parse('2026-07-20 12:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $compareOrder = $this->createOrder($userA, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 110000,
            'created_at' => CarbonImmutable::parse('2026-07-19 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($compareOrder, $productA, $sizeA, ['quantity' => 1, 'unit_price' => 30000, 'total_price' => 30000]);
        $this->createOrderItem($compareOrder, $productB, $sizeB, ['quantity' => 2, 'unit_price' => 35000, 'total_price' => 70000]);

        $softProduct->delete();
        $missingProduct->update(['name' => '']);

        $result = $service->branchProductDetail($this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]), $branch->id, [
            'sort_by' => 'quantity',
        ]);

        $this->assertSame($branch->id, $result['branch']['id']);
        $this->assertSame(250000, $result['summary']['revenue']);
        $this->assertSame(3, $result['summary']['valid_order_count']);
        $this->assertSame(2, $result['summary']['unique_customer_count']);
        $this->assertSame(7, $result['summary']['items_sold']);
        $this->assertSame(83333, $result['summary']['average_order_value']);
        $this->assertSame(3, $result['summary']['completed_order_count']);
        $this->assertSame(1, $result['summary']['cancelled_order_count']);
        $this->assertSame(4, $result['summary']['total_created_order_count']);
        $this->assertSame(25.0, $result['summary']['cancellation_rate']);

        $this->assertSame(110000, $result['comparison']['compare_revenue']);
        $this->assertSame(1, $result['comparison']['compare_order_count']);
        $this->assertSame(3, $result['comparison']['compare_items_sold']);
        $this->assertSame(110000, $result['comparison']['compare_average_order_value']);
        $this->assertSame(127.3, $result['comparison']['revenue_change_percentage']);
        $this->assertSame(200.0, $result['comparison']['order_change_percentage']);
        $this->assertSame(133.3, $result['comparison']['items_change_percentage']);
        $this->assertSame(-24.2, $result['comparison']['average_order_value_change_percentage']);
        $this->assertSame(0.0, $result['comparison']['compare_cancellation_rate']);

        $topProducts = $result['top_products'];
        $this->assertSame([$productA->id, $missingProduct->id, $softProduct->id, $productB->id], $topProducts->pluck('product_id')->all());
        $this->assertSame('Sản phẩm A', $topProducts[0]['product_name']);
        $this->assertSame('Sản phẩm #'.$missingProduct->id.' đã xóa', $topProducts[1]['product_name']);
        $this->assertSame('Sản phẩm mềm', $topProducts[2]['product_name']);
        $this->assertSame(42.9, $topProducts[0]['quantity_share_percentage']);
        $this->assertSame(39.1, $topProducts[0]['revenue_share_percentage']);
        $this->assertSame(1, $topProducts[0]['compare_quantity']);
        $this->assertSame(30000, $topProducts[0]['compare_revenue']);
        $this->assertNull($topProducts[1]['compare_quantity']);
    }

    public function test_branch_product_detail_query_count_does_not_scale_with_product_volume(): void
    {
        $branch = $this->createBranch(['code' => 'BQC', 'name' => 'Chi nhánh query']);
        $user = User::factory()->create();
        $connection = DB::connection();
        $category = Category::query()->create([
            'name' => 'Danh mục query count',
            'slug' => 'danh-muc-query-count',
            'description' => 'Danh mục test',
            'status' => true,
        ]);
        $size = Size::query()->create([
            'name' => 'Query',
            'multiplier' => 1,
        ]);

        foreach (range(1, 5) as $index) {
            $product = Product::query()->create([
                'category_id' => $category->id,
                'name' => 'Sản phẩm ít '.$index,
                'slug' => 'san-pham-it-'.$index,
                'sku' => 'QC-IT-'.$index,
                'image' => null,
                'price' => 30000,
                'description' => 'Mô tả',
                'stock' => 100,
                'status' => true,
            ]);
            $productSize = ProductSize::query()->create([
                'product_id' => $product->id,
                'size_id' => $size->id,
                'price' => 30000,
            ]);

            $this->createProductSale($user, $branch, $product, $productSize, 1, 10000 + ($index * 1000), '2026-07-20 08:00:00');
            $this->createProductSale($user, $branch, $product, $productSize, 1, 9000 + ($index * 1000), '2026-07-19 08:00:00');
        }

        $context = $this->analyticsContext([
            'branchId' => $branch->id,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        $connection->flushQueryLog();
        $connection->enableQueryLog();

        app(SuperAdminAnalyticsService::class)->branchProductDetail($context, $branch->id, ['sort_by' => 'quantity']);

        $fewProductQueries = count($connection->getQueryLog());
        $connection->flushQueryLog();

        foreach (range(6, 40) as $index) {
            $product = Product::query()->create([
                'category_id' => $category->id,
                'name' => 'Sản phẩm nhiều '.$index,
                'slug' => 'san-pham-nhieu-'.$index,
                'sku' => 'QC-NHIEU-'.$index,
                'image' => null,
                'price' => 30000,
                'description' => 'Mô tả',
                'stock' => 100,
                'status' => true,
            ]);
            $productSize = ProductSize::query()->create([
                'product_id' => $product->id,
                'size_id' => $size->id,
                'price' => 30000,
            ]);

            $this->createProductSale($user, $branch, $product, $productSize, 1, 10000 + $index, '2026-07-20 09:00:00');
            $this->createProductSale($user, $branch, $product, $productSize, 1, 9000 + $index, '2026-07-19 09:00:00');
        }

        $connection->flushQueryLog();

        app(SuperAdminAnalyticsService::class)->branchProductDetail($context, $branch->id, ['sort_by' => 'quantity']);

        $manyProductQueries = count($connection->getQueryLog());

        $this->assertLessThanOrEqual(5, $fewProductQueries);
        $this->assertLessThanOrEqual(5, $manyProductQueries);
        $this->assertSame($fewProductQueries, $manyProductQueries);
    }

    public function test_product_branch_performance_query_count_does_not_scale_with_branch_volume_or_comparison(): void
    {
        $user = User::factory()->create();
        [$product, $size] = $this->createProductWithSize(['name' => 'Sản phẩm query nhánh']);
        $branches = collect([
            $this->createBranch(['code' => 'Q01', 'name' => 'Chi nhánh 1']),
            $this->createBranch(['code' => 'Q02', 'name' => 'Chi nhánh 2']),
            $this->createBranch(['code' => 'Q03', 'name' => 'Chi nhánh 3']),
        ]);

        $this->createProductSale($user, $branches[0], $product, $size, 2, 20000, '2026-07-20 08:00:00');
        $this->createProductSale($user, $branches[1], $product, $size, 1, 15000, '2026-07-20 08:05:00');
        $this->createProductSale($user, $branches[0], $product, $size, 1, 10000, '2026-07-19 08:00:00');
        $this->createProductSale($user, $branches[1], $product, $size, 2, 22000, '2026-07-19 08:05:00');

        $smallNoCompareContext = $this->analyticsContext([
            'branchIds' => $branches->pluck('id')->all(),
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]);

        $smallCompareContext = $this->analyticsContext([
            'branchIds' => $branches->pluck('id')->all(),
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        app(SuperAdminAnalyticsService::class)->productBranchPerformance($smallNoCompareContext, $product->id, ['sort_by' => 'quantity']);

        $smallNoCompareQueries = count($connection->getQueryLog());
        $connection->flushQueryLog();

        app(SuperAdminAnalyticsService::class)->productBranchPerformance($smallCompareContext, $product->id, ['sort_by' => 'quantity']);
        $smallCompareQueries = count($connection->getQueryLog());
        $connection->flushQueryLog();

        $moreBranches = collect(range(4, 20))->map(function (int $index) use ($user, $product, $size): Branch {
            $branch = $this->createBranch([
                'code' => 'Q'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'name' => 'Chi nhánh '.$index,
            ]);

            if ($index <= 10) {
                $this->createProductSale($user, $branch, $product, $size, 1, 10000 + $index, '2026-07-20 09:00:00');
                $this->createProductSale($user, $branch, $product, $size, 1, 9000 + $index, '2026-07-19 09:00:00');
            }

            return $branch;
        });

        $allBranches = $branches->concat($moreBranches)->pluck('id')->all();

        $manyNoCompareContext = $this->analyticsContext([
            'branchIds' => $allBranches,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Không so sánh',
        ]);

        $manyCompareContext = $this->analyticsContext([
            'branchIds' => $allBranches,
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        $connection->flushQueryLog();

        app(SuperAdminAnalyticsService::class)->productBranchPerformance($manyNoCompareContext, $product->id, ['sort_by' => 'quantity']);
        $manyNoCompareQueries = count($connection->getQueryLog());
        $connection->flushQueryLog();

        app(SuperAdminAnalyticsService::class)->productBranchPerformance($manyCompareContext, $product->id, ['sort_by' => 'quantity']);
        $manyCompareQueries = count($connection->getQueryLog());

        $this->assertLessThanOrEqual(4, $smallNoCompareQueries);
        $this->assertLessThanOrEqual(4, $manyNoCompareQueries);
        $this->assertSame($smallNoCompareQueries, $manyNoCompareQueries);
        $this->assertLessThanOrEqual(5, $smallCompareQueries);
        $this->assertLessThanOrEqual(5, $manyCompareQueries);
        $this->assertSame($smallCompareQueries, $manyCompareQueries);
    }

    public function test_branch_comparison_returns_all_branches_and_keeps_branches_without_orders_visible(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchA = $this->createBranch(['code' => 'BRA', 'name' => 'Chi nhánh A']);
        $branchB = $this->createBranch(['code' => 'BRB', 'name' => 'Chi nhánh B']);
        $user = User::factory()->create();

        $this->createOrder($user, $branchA, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 125000,
            'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $result = $service->branchComparison($this->analyticsContext([
            'periodType' => 'all',
            'currentStart' => null,
            'currentEnd' => null,
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Tất cả thời gian',
            'comparisonLabel' => 'Không so sánh',
        ]), [
            'ranking_period' => 'all',
            'sort' => 'revenue',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 10,
            'page' => 1,
        ]);

        $paginator = $result['paginator'];
        $rows = $paginator->getCollection();

        $this->assertSame(2, $paginator->total());
        $this->assertCount(2, $rows);
        $this->assertSame([$branchA->id, $branchB->id], $rows->pluck('branch_id')->all());
        $this->assertSame(125000, $rows[0]['revenue']);
        $this->assertSame(0, $rows[1]['revenue']);
        $this->assertSame(0, $rows[1]['valid_order_count']);
        $this->assertSame(0, $rows[1]['average_order_value']);
        $this->assertSame('Chưa có dữ liệu', $rows[1]['top_product_name']);
        $this->assertSame('unavailable', $rows[1]['change_state']);
    }

    public function test_branch_comparison_uses_comparison_period_and_growth_states(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branchUp = $this->createBranch(['code' => 'UP1', 'name' => 'Chi nhánh tăng']);
        $branchDown = $this->createBranch(['code' => 'DN1', 'name' => 'Chi nhánh giảm']);
        $user = User::factory()->create();
        [$productUp, $productUpSize] = $this->createProductWithSize(['name' => 'Sản phẩm tăng']);
        [$productDown, $productDownSize] = $this->createProductWithSize(['name' => 'Sản phẩm giảm']);

        $this->createProductSale($user, $branchUp, $productUp, $productUpSize, 2, 200000, '2026-07-20 09:00:00');
        $this->createOrder($user, $branchUp, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 90000,
            'created_at' => CarbonImmutable::parse('2026-07-19 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $this->createProductSale($user, $branchDown, $productDown, $productDownSize, 1, 50000, '2026-07-19 09:10:00');

        $result = $service->branchComparison($this->analyticsContext([
            'periodType' => 'day',
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]), [
            'ranking_period' => 'all',
            'sort' => 'growth',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 10,
            'page' => 1,
        ]);

        $rows = $result['paginator']->getCollection();
        $upRow = $rows->firstWhere('branch_id', $branchUp->id);
        $downRow = $rows->firstWhere('branch_id', $branchDown->id);

        $this->assertNotNull($upRow);
        $this->assertNotNull($downRow);
        $this->assertSame(200000, $upRow['revenue']);
        $this->assertSame(90000, $upRow['compare_revenue']);
        $this->assertSame('increased', $upRow['change_state']);
        $this->assertSame(122.2, $upRow['revenue_change_percentage']);
        $this->assertSame(0, $downRow['revenue']);
        $this->assertSame(50000, $downRow['compare_revenue']);
        $this->assertSame('decreased', $downRow['change_state']);
        $this->assertSame('Sản phẩm tăng', $upRow['top_product_name']);
        $this->assertSame(2, $upRow['top_product_quantity']);
    }

    public function test_branch_comparison_aggregates_revenue_orders_items_top_product_and_filters_correctly(): void
    {
        $service = app(SuperAdminAnalyticsService::class);
        $branch = $this->createBranch(['code' => 'CMP1', 'name' => 'Chi nhánh so sánh']);
        $noOrdersBranch = $this->createBranch(['code' => 'CMP2', 'name' => 'Không đơn']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$productA, $sizeA] = $this->createProductWithSize(['name' => 'Sản phẩm A']);
        [$productB, $sizeB] = $this->createProductWithSize(['name' => 'Sản phẩm B']);
        [$softProduct, $softSize] = $this->createProductWithSize(['name' => 'Sản phẩm mềm']);
        [$missingProduct, $missingSize] = $this->createProductWithSize(['name' => 'Sản phẩm khuyết']);

        $paidA = $this->createOrder($userA, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 100000,
            'created_at' => CarbonImmutable::parse('2026-07-20 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($paidA, $productA, $sizeA, ['quantity' => 3, 'unit_price' => 20000, 'total_price' => 60000]);
        $this->createOrderItem($paidA, $productB, $sizeB, ['quantity' => 1, 'unit_price' => 40000, 'total_price' => 40000]);

        $completedB = $this->createOrder($userB, $branch, [
            'status' => 'completed',
            'payment_status' => 'pending',
            'total' => 80000,
            'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($completedB, $softProduct, $softSize, ['quantity' => 2, 'unit_price' => 40000, 'total_price' => 80000]);

        $guestCurrent = $this->createOrder(null, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 50000,
            'created_at' => CarbonImmutable::parse('2026-07-20 11:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($guestCurrent, $missingProduct, $missingSize, ['quantity' => 2, 'unit_price' => 25000, 'total_price' => 50000]);

        $this->createOrder($userA, $branch, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'total' => 70000,
            'created_at' => CarbonImmutable::parse('2026-07-20 12:00:00', 'Asia/Ho_Chi_Minh'),
        ]);

        $compareOrder = $this->createOrder($userA, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => 110000,
            'created_at' => CarbonImmutable::parse('2026-07-19 09:00:00', 'Asia/Ho_Chi_Minh'),
        ]);
        $this->createOrderItem($compareOrder, $productA, $sizeA, ['quantity' => 1, 'unit_price' => 30000, 'total_price' => 30000]);
        $this->createOrderItem($compareOrder, $productB, $sizeB, ['quantity' => 2, 'unit_price' => 40000, 'total_price' => 80000]);

        $softProduct->delete();
        $missingProduct->update(['name' => '']);

        $context = $this->analyticsContext([
            'periodType' => 'day',
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        $result = $service->branchComparison($context, [
            'ranking_period' => 'all',
            'sort' => 'revenue',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 10,
            'page' => 1,
        ]);

        $rows = $result['paginator']->getCollection();
        $branchRow = $rows->firstWhere('branch_id', $branch->id);
        $emptyRow = $rows->firstWhere('branch_id', $noOrdersBranch->id);

        $this->assertNotNull($branchRow);
        $this->assertNotNull($emptyRow);
        $this->assertSame(230000, $branchRow['revenue']);
        $this->assertSame(3, $branchRow['valid_order_count']);
        $this->assertSame(3, $branchRow['completed_order_count']);
        $this->assertSame(1, $branchRow['cancelled_order_count']);
        $this->assertSame(4, $branchRow['total_created_order_count']);
        $this->assertSame(2, $branchRow['unique_customer_count']);
        $this->assertSame(8, $branchRow['items_sold']);
        $this->assertSame(76667, $branchRow['average_order_value']);
        $this->assertSame(25.0, $branchRow['cancellation_rate']);
        $this->assertSame(110000, $branchRow['compare_revenue']);
        $this->assertSame(1, $branchRow['compare_order_count']);
        $this->assertSame(3, $branchRow['compare_items_sold']);
        $this->assertSame(109.1, $branchRow['revenue_change_percentage']);
        $this->assertSame(200.0, $branchRow['order_change_percentage']);
        $this->assertSame(166.7, $branchRow['items_change_percentage']);
        $this->assertSame('increased', $branchRow['change_state']);
        $this->assertSame($productA->id, $branchRow['top_product_id']);
        $this->assertSame('Sản phẩm A', $branchRow['top_product_name']);
        $this->assertSame(3, $branchRow['top_product_quantity']);
        $this->assertSame(60000, $branchRow['top_product_revenue']);

        $this->assertSame(0, $emptyRow['revenue']);
        $this->assertSame(0, $emptyRow['total_created_order_count']);
        $this->assertSame('Chưa có dữ liệu', $emptyRow['top_product_name']);

        $noOrderResult = $service->branchComparison($context, [
            'ranking_period' => 'all',
            'sort' => 'revenue',
            'direction' => 'desc',
            'performance' => 'no_orders',
            'per_page' => 10,
            'page' => 1,
        ]);
        $this->assertSame([$noOrdersBranch->id], $noOrderResult['paginator']->getCollection()->pluck('branch_id')->all());

        $increasedResult = $service->branchComparison($context, [
            'ranking_period' => 'all',
            'sort' => 'growth',
            'direction' => 'desc',
            'performance' => 'increased',
            'per_page' => 10,
            'page' => 1,
        ]);
        $this->assertSame([$branch->id], $increasedResult['paginator']->getCollection()->pluck('branch_id')->all());
    }

    public function test_branch_comparison_search_sort_pagination_and_query_count_do_not_scale_with_branch_count(): void
    {
        $user = User::factory()->create();
        $branches = collect([
            $this->createBranch(['code' => 'AA1', 'name' => 'Alpha']),
            $this->createBranch(['code' => 'BB1', 'name' => 'Beta']),
            $this->createBranch(['code' => 'CC1', 'name' => 'Gamma']),
        ]);

        $this->createOrder($user, $branches[0], ['status' => OrderStatus::COMPLETED, 'payment_status' => 'paid', 'total' => 100000, 'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh')]);
        $this->createOrder($user, $branches[0], ['status' => 'completed', 'payment_status' => 'pending', 'total' => 50000, 'created_at' => CarbonImmutable::parse('2026-07-20 10:05:00', 'Asia/Ho_Chi_Minh')]);
        $this->createOrder($user, $branches[1], ['status' => OrderStatus::COMPLETED, 'payment_status' => 'paid', 'total' => 70000, 'created_at' => CarbonImmutable::parse('2026-07-20 10:10:00', 'Asia/Ho_Chi_Minh')]);

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $firstRun = app(SuperAdminAnalyticsService::class)->branchComparison($this->analyticsContext([
            'periodType' => 'all',
            'currentStart' => null,
            'currentEnd' => null,
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Tất cả thời gian',
            'comparisonLabel' => 'Không so sánh',
        ]), [
            'ranking_period' => 'all',
            'search' => 'Alpha',
            'sort' => 'orders',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 2,
            'page' => 1,
        ]);

        $firstQueries = count(DB::connection()->getQueryLog());
        DB::connection()->flushQueryLog();

        $manyBranches = collect(range(1, 20))->map(function (int $index) use ($user): Branch {
            return $this->createBranch([
                'code' => 'B'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'name' => 'Branch '.$index,
            ]);
        });

        $manyBranches->take(10)->each(function (Branch $branch) use ($user): void {
            $this->createOrder($user, $branch, [
                'status' => OrderStatus::COMPLETED,
                'payment_status' => 'paid',
                'total' => 10000,
                'created_at' => CarbonImmutable::parse('2026-07-20 10:00:00', 'Asia/Ho_Chi_Minh'),
            ]);
        });

        DB::connection()->flushQueryLog();

        $manyRun = app(SuperAdminAnalyticsService::class)->branchComparison($this->analyticsContext([
            'periodType' => 'all',
            'currentStart' => null,
            'currentEnd' => null,
            'compareStart' => null,
            'compareEnd' => null,
            'displayLabel' => 'Tất cả thời gian',
            'comparisonLabel' => 'Không so sánh',
        ]), [
            'ranking_period' => 'all',
            'sort' => 'revenue',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 20,
            'page' => 1,
        ]);

        $manyQueries = count(DB::connection()->getQueryLog());
        $manyRows = $manyRun['paginator']->getCollection();

        $this->assertSame(1, $firstRun['paginator']->total());
        $this->assertCount(1, $firstRun['paginator']->getCollection());
        $this->assertLessThanOrEqual(4, $firstQueries);
        $this->assertSame(23, $manyRun['paginator']->total());
        $this->assertCount(20, $manyRows);
        $this->assertLessThanOrEqual(4, $manyQueries);
        $this->assertSame($firstQueries, $manyQueries);
    }

    public function test_branch_comparison_query_count_does_not_scale_with_branch_or_product_volume_when_comparing(): void
    {
        $user = User::factory()->create();
        [$productA, $productASize] = $this->createProductWithSize(['name' => 'Sản phẩm A']);
        [$productB, $productBSize] = $this->createProductWithSize(['name' => 'Sản phẩm B']);

        $smallBranches = collect([
            $this->createBranch(['code' => 'SMA1', 'name' => 'Small A']),
            $this->createBranch(['code' => 'SMA2', 'name' => 'Small B']),
            $this->createBranch(['code' => 'SMA3', 'name' => 'Small C']),
        ]);

        $smallBranches->each(function (Branch $branch) use ($user, $productA, $productASize, $productB, $productBSize): void {
            $this->createProductSale($user, $branch, $productA, $productASize, 2, 60000, '2026-07-20 09:00:00');
            $this->createProductSale($user, $branch, $productB, $productBSize, 1, 30000, '2026-07-20 11:00:00');
            $this->createProductSale($user, $branch, $productA, $productASize, 1, 30000, '2026-07-19 09:00:00');
        });

        $compareContext = $this->analyticsContext([
            'periodType' => 'day',
            'currentStart' => CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            'currentEnd' => CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            'compareStart' => CarbonImmutable::parse('2026-07-19 00:00:00', 'Asia/Ho_Chi_Minh'),
            'compareEnd' => CarbonImmutable::parse('2026-07-19 23:59:59', 'Asia/Ho_Chi_Minh'),
            'displayLabel' => 'Ngày 20/07/2026',
            'comparisonLabel' => 'Kỳ liền trước',
        ]);

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        app(SuperAdminAnalyticsService::class)->branchComparison($compareContext, [
            'ranking_period' => 'all',
            'sort' => 'growth',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 10,
            'page' => 1,
        ]);

        $smallQueries = count(DB::connection()->getQueryLog());
        DB::connection()->flushQueryLog();

        $manyBranches = collect(range(1, 20))->map(function (int $index): Branch {
            return $this->createBranch([
                'code' => 'CMP'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'name' => 'Compare '.$index,
            ]);
        });

        $manyBranches->each(function (Branch $branch, int $index) use ($user, $productA, $productASize, $productB, $productBSize): void {
            $this->createProductSale($user, $branch, $productA, $productASize, 2 + ($index % 3), 60000 + (($index % 3) * 10000), '2026-07-20 09:00:00');
            $this->createProductSale($user, $branch, $productB, $productBSize, 1, 30000, '2026-07-20 11:00:00');
            $this->createProductSale($user, $branch, $productA, $productASize, 1, 30000, '2026-07-19 09:00:00');
        });

        DB::connection()->flushQueryLog();

        app(SuperAdminAnalyticsService::class)->branchComparison($compareContext, [
            'ranking_period' => 'all',
            'sort' => 'growth',
            'direction' => 'desc',
            'performance' => 'all',
            'per_page' => 20,
            'page' => 1,
        ]);

        $manyQueries = count(DB::connection()->getQueryLog());

        $this->assertLessThanOrEqual(5, $smallQueries);
        $this->assertLessThanOrEqual(5, $manyQueries);
        $this->assertSame($smallQueries, $manyQueries);
    }

    private function analyticsContext(array $overrides = []): AnalyticsPeriodContext
    {
        $branchIds = $overrides['branchIds'] ?? (
            array_key_exists('branchId', $overrides) && $overrides['branchId'] !== null
                ? [$overrides['branchId']]
                : []
        );

        return new AnalyticsPeriodContext(
            periodType: $overrides['periodType'] ?? 'day',
            currentStart: $overrides['currentStart'] ?? CarbonImmutable::parse('2026-07-20 00:00:00', 'Asia/Ho_Chi_Minh'),
            currentEnd: $overrides['currentEnd'] ?? CarbonImmutable::parse('2026-07-20 23:59:59', 'Asia/Ho_Chi_Minh'),
            compareStart: $overrides['compareStart'] ?? null,
            compareEnd: $overrides['compareEnd'] ?? null,
            displayLabel: $overrides['displayLabel'] ?? 'Ngày 20/07/2026',
            comparisonLabel: $overrides['comparisonLabel'] ?? 'Không so sánh',
            branchIds: $branchIds,
            branchId: $overrides['branchId'] ?? null,
            branchScopeLabel: $overrides['branchScopeLabel'] ?? (count($branchIds) > 1 ? count($branchIds).' chi nhánh được chọn' : (count($branchIds) === 1 ? '1 chi nhánh được chọn' : 'Tất cả chi nhánh')),
            normalizedQueryParameters: $overrides['normalizedQueryParameters'] ?? [],
            timezone: $overrides['timezone'] ?? 'Asia/Ho_Chi_Minh',
        );
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

    private function createProductWithSize(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $product = Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'Sản phẩm test '.uniqid(),
            'slug' => 'san-pham-test-'.uniqid(),
            'sku' => 'SKU-'.uniqid(),
            'image' => null,
            'price' => 30000,
            'description' => 'Mo ta',
            'stock' => 100,
            'status' => true,
        ], $overrides));
        $size = Size::query()->create([
            'name' => 'M',
            'multiplier' => 1,
        ]);
        $productSize = ProductSize::query()->create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 30000,
        ]);

        return [$product, $productSize];
    }

    private function createProductSale(User $user, Branch $branch, Product $product, ProductSize $productSize, int $quantity, int $totalPrice, string $createdAt): Order
    {
        $order = $this->createOrder($user, $branch, [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => 'paid',
            'total' => $totalPrice,
            'created_at' => CarbonImmutable::parse($createdAt, 'Asia/Ho_Chi_Minh'),
        ]);

        $this->createOrderItem($order, $product, $productSize, [
            'quantity' => $quantity,
            'unit_price' => (int) round($totalPrice / max(1, $quantity)),
            'total_price' => $totalPrice,
        ]);

        return $order;
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

    private function createOrderItem(Order $order, Product $product, ProductSize $productSize, array $overrides = []): OrderItem
    {
        return OrderItem::query()->create(array_merge([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'ice_level' => 100,
            'sugar_level' => 100,
            'quantity' => 1,
            'unit_price' => 30000,
            'total_price' => 30000,
        ], $overrides));
    }
}
