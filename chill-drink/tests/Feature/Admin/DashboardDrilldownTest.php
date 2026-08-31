<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Review;
use App\Models\Size;
use App\Models\User;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDrilldownTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;

    private Branch $branchB;

    private User $admin;

    private User $superAdmin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:00:00', 'Asia/Ho_Chi_Minh'));
        $this->branchA = $this->branch('TRACE-A');
        $this->branchB = $this->branch('TRACE-B');
        $this->admin = User::factory()->create(['role_id' => 2, 'branch_id' => $this->branchA->id]);
        $this->superAdmin = User::factory()->create(['role_id' => 3]);
        $this->customer = User::factory()->create(['role_id' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_revenue_and_order_detail_reconcile_and_include_exact_boundaries(): void
    {
        $first = $this->order($this->branchA, 'BOUNDARY-FIRST', 'completed', 120000, '2026-08-01 00:00:00');
        $last = $this->order($this->branchA, 'BOUNDARY-LAST', 'completed', 180000, '2026-08-31 12:00:00');
        $this->order($this->branchA, 'OUTSIDE', 'completed', 900000, '2026-07-31 23:59:59');
        $this->order($this->branchA, 'CANCELLED', 'cancelled', 700000, '2026-08-15 10:00:00');

        $response = $this->actingAs($admin = $this->admin)->getJson($this->url('revenue'));

        $response->assertOk()->assertJsonPath('value', 300000)->assertJsonPath('summary.order_count', 2);
        $rows = collect($response->json('data.rows'));
        $this->assertSame(300000, $rows->sum('contribution'));
        $this->assertEqualsCanonicalizing([$first->id, $last->id], $rows->pluck('id')->all());

        $this->actingAs($admin)->getJson($this->url('orders'))
            ->assertOk()->assertJsonPath('value', 2)->assertJsonPath('data.total', 2);
        $this->actingAs($admin)->getJson($this->url('average_order_value'))
            ->assertOk()->assertJsonPath('value', 150000)->assertJsonPath('summary.revenue', 300000)->assertJsonPath('summary.order_count', 2);
    }

    public function test_admin_branch_scope_cannot_be_bypassed_by_tampering_branch_id(): void
    {
        $this->order($this->branchA, 'VISIBLE', 'completed', 100000, '2026-08-10 09:00:00');
        $this->order($this->branchB, 'HIDDEN', 'completed', 500000, '2026-08-10 09:00:00');

        $response = $this->actingAs($this->admin)->getJson($this->url('revenue', ['branch_id' => $this->branchB->id]));

        $response->assertOk()->assertJsonPath('value', 100000)->assertJsonPath('branch_id', $this->branchA->id);
        $this->assertSame(['VISIBLE'], collect($response->json('data.rows'))->pluck('order_code')->all());
    }

    public function test_super_admin_can_scope_a_branch_or_reconcile_all_branches(): void
    {
        $this->order($this->branchA, 'A', 'completed', 100000, '2026-08-10 09:00:00');
        $this->order($this->branchB, 'B', 'completed', 200000, '2026-08-10 09:00:00');

        $base = '/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('revenue'));
        $this->actingAs($this->superAdmin)->getJson($base)->assertOk()->assertJsonPath('value', 300000);
        $this->actingAs($this->superAdmin)->getJson($base.'&branch_id='.$this->branchB->id)
            ->assertOk()->assertJsonPath('value', 200000)->assertJsonPath('data.total', 1);
    }

    public function test_cancellation_rate_explains_numerator_denominator_and_lists_only_cancelled_orders(): void
    {
        $this->order($this->branchA, 'DONE-1', 'completed', 100000, '2026-08-10 09:00:00');
        $this->order($this->branchA, 'DONE-2', 'completed', 100000, '2026-08-10 10:00:00');
        $this->order($this->branchA, 'CONFIRMED', 'confirmed', 100000, '2026-08-31 11:00:00');
        $this->order($this->branchA, 'CANCELLED', 'cancelled', 100000, '2026-08-10 12:00:00');

        $response = $this->actingAs($this->admin)->getJson($this->url('cancellation_rate'));
        $response->assertOk()
            ->assertJsonPath('value', 25)
            ->assertJsonPath('summary.cancelled_count', 1)
            ->assertJsonPath('summary.denominator_count', 4)
            ->assertJsonPath('data.total', 1);
        $this->assertStringContainsString('1 đơn đã hủy ÷ 4 đơn hàng × 100 = 25,0%', $response->json('formula'));
        $this->assertStringNotContainsString('cancelled_count', $response->json('formula'));
        $this->assertSame(['CANCELLED'], collect($response->json('data.rows'))->pluck('order_code')->all());
    }

    public function test_product_quantity_uses_completed_order_items_and_reconciles_detail(): void
    {
        [$product, $productSize] = $this->product();
        $validOne = $this->order($this->branchA, 'ITEM-1', 'completed', 60000, '2026-08-10 09:00:00');
        $validTwo = $this->order($this->branchA, 'ITEM-2', 'completed', 90000, '2026-08-10 10:00:00');
        $invalid = $this->order($this->branchA, 'ITEM-X', 'cancelled', 300000, '2026-08-10 11:00:00');
        $this->item($validOne, $product, $productSize, 2, 60000);
        $this->item($validTwo, $product, $productSize, 3, 90000);
        $this->item($invalid, $product, $productSize, 10, 300000);

        $response = $this->actingAs($this->admin)->getJson($this->url('product_sales', ['product_id' => $product->id]));

        $response->assertOk()->assertJsonPath('value', 5)->assertJsonPath('summary.quantity', 5)->assertJsonPath('data.total', 2);
        $this->assertSame(5, collect($response->json('data.rows'))->sum('quantity'));
        $response->assertJsonPath('data.rows.0.status_label', OrderStatus::label(OrderStatus::COMPLETED));
    }

    public function test_items_sold_metric_reconciles_without_a_product_filter(): void
    {
        [$product, $productSize] = $this->product();
        $otherProduct = Product::create([
            'category_id' => $product->category_id,
            'name' => 'Trace Product Two',
            'slug' => 'trace-product-two',
            'sku' => 'TRACE-P-2',
            'price' => 20000,
            'status' => true,
        ]);
        $otherSize = ProductSize::create(['product_id' => $otherProduct->id, 'size_id' => $productSize->size_id, 'price' => 20000]);
        $order = $this->order($this->branchA, 'ALL-ITEMS', OrderStatus::COMPLETED, 100000, '2026-08-10 09:00:00');
        $this->item($order, $product, $productSize, 2, 60000);
        $this->item($order, $otherProduct, $otherSize, 2, 40000);

        $response = $this->actingAs($this->admin)->getJson($this->url('items_sold'));

        $response->assertOk()
            ->assertJsonPath('value', 4)
            ->assertJsonPath('summary.quantity', 4)
            ->assertJsonPath('summary.revenue', 100000)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('overview', null);
        $this->assertSame(4, collect($response->json('data.rows'))->sum('quantity'));
        $this->assertSame(100000, collect($response->json('data.rows'))->sum('contribution'));
    }

    public function test_admin_new_customer_kpi_chart_and_detail_are_branch_scoped(): void
    {
        $customerA = User::factory()->create(['role_id' => 1, 'created_at' => '2026-08-10 08:00:00']);
        $customerB = User::factory()->create(['role_id' => 1, 'created_at' => '2026-08-10 08:30:00']);
        User::factory()->create(['role_id' => 1, 'created_at' => '2026-08-10 08:45:00']);
        $this->order($this->branchA, 'NEW-CUSTOMER-A', OrderStatus::COMPLETED, 50000, '2026-08-10 09:00:00', $customerA);
        $this->order($this->branchB, 'NEW-CUSTOMER-B', OrderStatus::COMPLETED, 70000, '2026-08-10 09:30:00', $customerB);

        $dashboard = $this->actingAs($this->admin)->getJson('/admin/dashboard/data?period=month&month=2026-08');
        $dashboard->assertOk()->assertJsonPath('totalUsers', 1);
        $this->assertSame(1, collect($dashboard->json('chartDatasets.users.bars'))->sum('value'));

        $detail = $this->actingAs($this->admin)->getJson($this->url('new_customers', ['branch_id' => $this->branchB->id]));
        $detail->assertOk()
            ->assertJsonPath('branch_id', $this->branchA->id)
            ->assertJsonPath('value', 1)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rows.0.id', $customerA->id);

        $global = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('new_customers')));
        $global->assertOk()->assertJsonPath('value', 4)->assertJsonPath('branch_id', null);
    }

    public function test_product_branch_drilldowns_keep_product_branch_and_time_context(): void
    {
        [$product, $productSize] = $this->product();
        $otherProduct = Product::create([
            'category_id' => $product->category_id,
            'name' => 'Sản phẩm khác',
            'slug' => 'san-pham-khac-trace',
            'sku' => 'TRACE-OTHER',
            'price' => 20000,
            'status' => true,
        ]);
        $otherSize = ProductSize::create(['product_id' => $otherProduct->id, 'size_id' => $productSize->size_id, 'price' => 20000]);
        $completedA = $this->order($this->branchA, 'PRODUCT-A-DONE', OrderStatus::COMPLETED, 80000, '2026-08-10 09:00:00');
        $cancelledA = $this->order($this->branchA, 'PRODUCT-A-CANCELLED', OrderStatus::CANCELLED, 90000, '2026-08-10 10:00:00');
        $completedB = $this->order($this->branchB, 'PRODUCT-B-DONE', OrderStatus::COMPLETED, 120000, '2026-08-10 11:00:00');
        $outside = $this->order($this->branchA, 'PRODUCT-OUTSIDE', OrderStatus::COMPLETED, 150000, '2026-07-31 23:59:59');
        $this->item($completedA, $product, $productSize, 2, 60000);
        $this->item($completedA, $otherProduct, $otherSize, 1, 20000);
        $this->item($cancelledA, $product, $productSize, 3, 90000);
        $this->item($completedB, $product, $productSize, 4, 120000);
        $this->item($outside, $product, $productSize, 5, 150000);
        Review::create(['user_id' => $this->customer->id, 'product_id' => $product->id, 'order_id' => $completedA->id, 'rating' => 5, 'comment' => 'Chi nhánh A', 'status' => true]);
        Review::create(['user_id' => $this->customer->id, 'product_id' => $product->id, 'order_id' => $completedB->id, 'rating' => 2, 'comment' => 'Chi nhánh B', 'status' => true]);

        $base = ['product_id' => $product->id, 'branch_id' => $this->branchA->id];
        $quantity = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_sales', $base)));
        $quantity->assertOk()
            ->assertJsonPath('value', 2)
            ->assertJsonPath('summary.order_count', 1)
            ->assertJsonPath('overview.quantity', 2)
            ->assertJsonPath('overview.revenue', 60000)
            ->assertJsonPath('overview.completed_order_count', 1)
            ->assertJsonPath('overview.related_order_count', 2)
            ->assertJsonPath('overview.cancelled_order_count', 1)
            ->assertJsonPath('overview.cancellation_rate', 50)
            ->assertJsonPath('overview.review_count', 1)
            ->assertJsonPath('overview.average_rating', 5)
            ->assertJsonPath('branch.id', $this->branchA->id)
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rows.0.product_id', $product->id)
            ->assertJsonPath('data.rows.0.branch_name', $this->branchA->name);
        $this->assertSame(2, collect($quantity->json('data.rows'))->sum('quantity'));

        $revenue = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_revenue', $base)));
        $revenue->assertOk()->assertJsonPath('value', 60000)->assertJsonPath('data.total', 1);
        $this->assertSame(60000, collect($revenue->json('data.rows'))->sum('contribution'));

        $cancellation = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_cancellation_rate', $base)));
        $cancellation->assertOk()
            ->assertJsonPath('value', 50)
            ->assertJsonPath('summary.cancelled_count', 1)
            ->assertJsonPath('summary.denominator_count', 2)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rows.0.id', $cancelledA->id);

        $reviews = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_reviews', $base)));
        $reviews->assertOk()
            ->assertJsonPath('value', 5)
            ->assertJsonPath('summary.review_count', 1)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rows.0.rating', 5)
            ->assertJsonPath('data.rows.0.branch_name', $this->branchA->name);

        $emptyBranch = $this->branch('TRACE-EMPTY');
        $empty = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_sales', ['product_id' => $product->id, 'branch_id' => $emptyBranch->id])));
        $empty->assertOk()->assertJsonPath('value', 0)->assertJsonPath('branch.id', $emptyBranch->id)->assertJsonPath('product.id', $product->id)->assertJsonPath('data.total', 0);
        $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_cancellation_rate', ['product_id' => $product->id, 'branch_id' => $emptyBranch->id])))
            ->assertOk()->assertJsonPath('value', 0)->assertJsonPath('summary.denominator_count', 0)->assertJsonPath('data.total', 0);
        $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_reviews', ['product_id' => $product->id, 'branch_id' => $emptyBranch->id])))
            ->assertOk()->assertJsonPath('value', 0)->assertJsonPath('summary.review_count', 0)->assertJsonPath('data.total', 0);

        $searchWithinContext = $this->actingAs($this->superAdmin)->getJson('/admin/super-admin/dashboard/drilldown?'.http_build_query($this->params('product_sales', $base + ['search' => 'PRODUCT-B-DONE'])));
        $searchWithinContext->assertOk()
            ->assertJsonPath('branch.id', $this->branchA->id)
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('value', 2)
            ->assertJsonPath('data.total', 0);

        $adminScoped = $this->actingAs($this->admin)->getJson($this->url('product_sales', ['product_id' => $product->id, 'branch_id' => $this->branchB->id]));
        $adminScoped->assertOk()->assertJsonPath('branch_id', $this->branchA->id)->assertJsonPath('value', 2);
    }

    public function test_day_week_month_and_year_ranges_use_inclusive_boundaries(): void
    {
        $this->order($this->branchA, 'YEAR', 'completed', 10000, '2026-01-01 00:00:00');
        $this->order($this->branchA, 'WEEK-START', 'completed', 20000, '2026-08-24 00:00:00');
        $this->order($this->branchA, 'DAY-START', 'completed', 30000, '2026-08-31 00:00:00');
        $this->order($this->branchA, 'NOW', 'completed', 40000, '2026-08-31 12:00:00');

        $ranges = [
            ['2026-08-31 00:00:00', '2026-08-31 12:00:00', 2, 70000],
            ['2026-08-24 00:00:00', '2026-08-31 12:00:00', 3, 90000],
            ['2026-08-01 00:00:00', '2026-08-31 12:00:00', 3, 90000],
            ['2026-01-01 00:00:00', '2026-08-31 12:00:00', 4, 100000],
        ];

        foreach ($ranges as [$from, $to, $orders, $revenue]) {
            $response = $this->actingAs($this->admin)->getJson($this->url('revenue', compact('from', 'to')));
            $response->assertOk()->assertJsonPath('value', $revenue)->assertJsonPath('summary.order_count', $orders);
            $this->assertSame($revenue, collect($response->json('data.rows'))->sum('contribution'));
        }
    }

    public function test_pagination_empty_state_validation_and_authorization(): void
    {
        foreach (range(1, 21) as $index) {
            $this->order($this->branchA, 'PAGE-'.$index, 'completed', 1000, '2026-08-10 09:00:00');
        }

        $this->actingAs($this->admin)->getJson($this->url('orders'))
            ->assertOk()->assertJsonPath('data.total', 21)->assertJsonPath('data.last_page', 2)->assertJsonCount(20, 'data.rows');
        $this->actingAs($this->admin)->getJson($this->url('orders', ['page' => 2]))
            ->assertOk()->assertJsonCount(1, 'data.rows');
        $this->actingAs($this->admin)->getJson($this->url('orders', ['from' => '2026-08-20 00:00:00', 'to' => '2026-08-20 23:59:59']))
            ->assertOk()->assertJsonPath('value', 0)->assertJsonPath('data.total', 0)->assertJsonCount(0, 'data.rows');
        $emptyAverage = $this->actingAs($this->admin)->getJson($this->url('average_order_value', ['from' => '2026-08-20 00:00:00', 'to' => '2026-08-20 23:59:59']));
        $emptyAverage->assertOk()->assertJsonPath('value', 0);
        $this->assertStringNotContainsString('chia cho 0', $emptyAverage->json('formula'));
        $emptyCancellation = $this->actingAs($this->admin)->getJson($this->url('cancellation_rate', ['from' => '2026-08-20 00:00:00', 'to' => '2026-08-20 23:59:59']));
        $emptyCancellation->assertOk()->assertJsonPath('value', 0);
        $this->assertStringNotContainsString('÷ 0', $emptyCancellation->json('formula'));
        $this->actingAs($this->admin)->getJson($this->url('raw_sql'))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson($this->url('product_sales'))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson('/admin/dashboard/drilldown?metric=orders&from=not-a-date')->assertUnprocessable();
        $this->actingAs($this->admin)->getJson($this->url('orders', ['branch_id' => 999999]))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson($this->url('product_sales', ['product_id' => 999999]))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson($this->url('orders', ['page' => 100001]))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson($this->url('orders', ['per_page' => 1000]))->assertUnprocessable();
        $this->actingAs($this->admin)->getJson($this->url('orders', ['from' => '2000-01-01 00:00:00', 'to' => '2026-08-31 12:00:00']))->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->getJson('/admin/dashboard/drilldown?metric=orders')->assertUnauthorized();
        $this->actingAs($this->customer)->getJson('/admin/dashboard/drilldown?metric=orders')->assertRedirect(route('home'));
    }

    public function test_both_dashboards_render_accessible_drilldown_triggers_and_modal(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-drilldown="revenue"', false)
            ->assertSee('data-drilldown="orders"', false)
            ->assertSee('id="dashboardTraceModal"', false);

        $module = file_get_contents(resource_path('views/admin/super-admin/partials/product-branch-performance.blade.php'));
        $this->assertStringNotContainsString('Tỷ trọng SL', $module);
        $this->assertStringNotContainsString('Tỷ trọng DT', $module);
        $this->assertStringContainsString('Tỷ lệ hủy', $module);
        $this->assertStringContainsString('Đánh giá', $module);
        $this->assertStringContainsString('data-drilldown="product_cancellation_rate"', $module);
        $this->assertStringContainsString('data-drilldown="product_reviews"', $module);

        $modal = file_get_contents(resource_path('views/admin/partials/dashboard-drilldown.blade.php'));
        $this->assertStringContainsString('config.branchId', $modal);
        $this->assertStringContainsString('config.productId', $modal);

        $this->actingAs($this->superAdmin)->get('/admin/super-admin')
            ->assertOk()
            ->assertSee('data-drilldown="average_order_value"', false)
            ->assertSee('data-drilldown="cancellation_rate"', false)
            ->assertSee('id="dashboardTraceModal"', false);
    }

    public function test_drilldown_presentations_are_vietnamese_and_do_not_expose_technical_formulas(): void
    {
        $this->order($this->branchA, 'VIET-HOA', OrderStatus::COMPLETED, 125000, '2026-08-10 09:00:00');

        foreach (['revenue', 'orders', 'average_order_value', 'customers', 'new_customers', 'products'] as $metric) {
            $response = $this->actingAs($this->admin)->getJson($this->url($metric));
            $response->assertOk();
            $formula = (string) $response->json('formula');
            $this->assertDoesNotMatchRegularExpression('/\b(?:SUM|COUNT|AVG|DISTINCT)\b|orders\.|users\.|order_items|completed|cancelled_count|total_orders|completed_orders/i', $formula);
        }

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Cách tính')
            ->assertSee('Đang tải dữ liệu...')
            ->assertSee('Trang trước')
            ->assertSee('Nhấn để xem chi tiết')
            ->assertDontSee('Công thức / điều kiện');
    }

    private function params(string $metric, array $overrides = []): array
    {
        return array_merge(['metric' => $metric, 'from' => '2026-08-01 00:00:00', 'to' => '2026-08-31 12:00:00'], $overrides);
    }

    private function url(string $metric, array $overrides = []): string
    {
        return '/admin/dashboard/drilldown?'.http_build_query($this->params($metric, $overrides));
    }

    private function branch(string $code): Branch
    {
        return Branch::create(['name' => $code, 'code' => $code, 'address' => 'Test', 'status' => true]);
    }

    private function order(Branch $branch, string $code, string $status, int $total, string $createdAt, ?User $customer = null): Order
    {
        $order = Order::create(['order_code' => $code, 'user_id' => ($customer ?? $this->customer)->id, 'branch_id' => $branch->id, 'subtotal' => $total, 'shipping_fee' => 0, 'discount' => 0, 'total' => $total, 'payment_method' => 'cod', 'payment_status' => $status === 'completed' ? 'paid' : 'pending', 'status' => $status]);
        $order->timestamps = false;
        $order->forceFill(['created_at' => Carbon::parse($createdAt), 'updated_at' => Carbon::parse($createdAt)])->save();

        return $order->fresh();
    }

    private function product(): array
    {
        $category = Category::factory()->create();
        $product = Product::create(['category_id' => $category->id, 'name' => 'Trace Product', 'slug' => 'trace-product', 'sku' => 'TRACE-P', 'price' => 30000, 'status' => true]);
        $size = Size::create(['name' => 'Trace M', 'multiplier' => 1]);
        $productSize = ProductSize::create(['product_id' => $product->id, 'size_id' => $size->id, 'price' => 30000]);

        return [$product, $productSize];
    }

    private function item(Order $order, Product $product, ProductSize $size, int $quantity, int $total): void
    {
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_size_id' => $size->id, 'quantity' => $quantity, 'unit_price' => (int) ($total / $quantity), 'total_price' => $total]);
    }
}
