<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Requests\Admin\SuperAdminAnalyticsRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Services\SuperAdminAnalyticsPeriodResolver;
use App\Services\SuperAdminAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request as BaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Str;

class SuperAdminAnalyticsBenchmark extends Command
{
    private const BENCH_PREFIX = '[ANALYTICS-BENCH]';
    private const BENCH_BRANCH_CODE_PREFIX = 'ABR-';
    private const BENCH_PRODUCT_SLUG_PREFIX = 'analytics-bench-product-';
    private const BENCH_CATEGORY_SLUG_PREFIX = 'analytics-bench-category-';
    private const BENCH_CUSTOMER_EMAIL_PREFIX = 'bench-customer-';

    protected $signature = 'analytics:super-admin-benchmark
        {--seed : Seed local/testing benchmark data before running the benchmark}
        {--cleanup : Remove benchmark rows after finishing}
        {--force : Allow running outside local/testing}
        {--orders=20000 : Target benchmark orders to seed}
        {--branches=20 : Target benchmark branches to seed}
        {--products=200 : Target benchmark products to seed}
        {--customers=100 : Target benchmark customers to seed}
        {--batch=250 : Batch size for order inserts}
        {--warmup=2 : Warm-up runs before measuring}
        {--runs=5 : Measured runs for each scenario}';

    protected $description = 'Seed and benchmark Super Admin analytics locally without touching UI or business logic.';

    private ?array $activeProbe = null;
    private bool $queryListenerRegistered = false;

    public function handle(): int
    {
        if (! $this->option('force') && ! app()->environment(['local', 'testing'])) {
            $this->error('Chỉ cho phép chạy trong local/testing. Dùng --force nếu bạn thực sự muốn chạy ở môi trường khác.');

            return self::FAILURE;
        }

        $this->registerQueryListener();

        $this->line('');
        $this->info('=== Super Admin Analytics Baseline ===');
        $this->line('Environment: '.app()->environment().' | DB: '.DB::connection()->getDriverName().' | PHP: '.PHP_VERSION);
        $this->line('Timezone: '.config('app.timezone'));
        $this->line('');

        $before = $this->collectDataBaseline();
        $this->table(
            ['Metric', 'Value'],
            [
                ['branches', number_format($before['branches'])],
                ['orders', number_format($before['orders'])],
                ['order_items', number_format($before['order_items'])],
                ['products', number_format($before['products'])],
                ['users', number_format($before['users'])],
                ['min_order_created_at', $before['min_order_created_at'] ?? 'n/a'],
                ['max_order_created_at', $before['max_order_created_at'] ?? 'n/a'],
                ['distinct_months', number_format($before['distinct_months'])],
            ]
        );

        $shouldSeed = $this->option('seed') || (int) $before['orders'] < 1000 || (int) $before['order_items'] < 1000;
        if ($shouldSeed) {
            $this->line('');
            $this->warn('Dữ liệu hiện tại quá ít để benchmark đáng tin cậy. Đang seed benchmark local/testing...');
            $seedReport = $this->seedBenchmarkData(
                max(1, (int) $this->option('branches')),
                max(1, (int) $this->option('products')),
                max(1, (int) $this->option('customers')),
                max(1, (int) $this->option('orders')),
                max(50, (int) $this->option('batch'))
            );

            $this->table(
                ['Seed item', 'Value'],
                [
                    ['benchmark branches', number_format($seedReport['branches'])],
                    ['benchmark categories', number_format($seedReport['categories'])],
                    ['benchmark products', number_format($seedReport['products'])],
                    ['benchmark sizes', number_format($seedReport['sizes'])],
                    ['benchmark customers', number_format($seedReport['customers'])],
                    ['benchmark orders', number_format($seedReport['orders'])],
                    ['benchmark order_items', number_format($seedReport['order_items'])],
                ]
            );
        } else {
            $this->info('Dữ liệu hiện tại đủ để benchmark sơ bộ, không seed thêm.');
        }

        $benchmark = $this->runBenchmarks(
            max(0, (int) $this->option('warmup')),
            max(1, (int) $this->option('runs'))
        );

        $this->line('');
        $this->info('--- Scenario Baseline ---');
        $this->table(
            ['Scenario', 'Query count (median)', 'SQL time (ms, median)', 'Wall time (ms, median)', 'Peak memory (MB, median)', 'Slowest SQL (ms)'],
            array_map(static function (array $row): array {
                return [
                    $row['label'],
                    number_format($row['query_count_median'], 0),
                    number_format($row['sql_time_median'], 1),
                    number_format($row['wall_time_median'], 1),
                    number_format($row['peak_memory_median'], 1),
                    number_format($row['slowest_query_ms'], 1),
                ];
            }, $benchmark)
        );

        $moduleMetrics = $this->runModuleBaseline(max(0, (int) $this->option('warmup')), max(1, (int) $this->option('runs')));

        $this->line('');
        $this->info('--- Module Baseline ---');
        $this->table(
            ['Module', 'Query count (median)', 'SQL time (ms, median)', 'Wall time (ms, median)', 'Peak memory (MB, median)', 'Slowest SQL (ms)'],
            array_map(static function (array $row): array {
                return [
                    $row['label'],
                    number_format($row['query_count_median'], 0),
                    number_format($row['sql_time_median'], 1),
                    number_format($row['wall_time_median'], 1),
                    number_format($row['peak_memory_median'], 1),
                    number_format($row['slowest_query_ms'], 1),
                ];
            }, $moduleMetrics)
        );

        if ($this->option('cleanup')) {
            $this->line('');
            $this->warn('Đang cleanup benchmark data...');
            $cleanup = $this->cleanupBenchmarkData();
            $this->table(
                ['Cleanup item', 'Value'],
                [
                    ['benchmark branches removed', number_format($cleanup['branches'])],
                    ['benchmark categories removed', number_format($cleanup['categories'])],
                    ['benchmark products removed', number_format($cleanup['products'])],
                    ['benchmark sizes removed', number_format($cleanup['sizes'])],
                    ['benchmark customers removed', number_format($cleanup['customers'])],
                    ['benchmark orders removed', number_format($cleanup['orders'])],
                    ['benchmark order_items removed', number_format($cleanup['order_items'])],
                ]
            );
        }

        $this->line('');
        $this->info('Benchmark chỉ đo hiệu năng hiện tại. Không tự động tạo, xóa hoặc thay đổi index.');

        return self::SUCCESS;
    }

    /**
     * @return array{branches:int,categories:int,products:int,sizes:int,customers:int,orders:int,order_items:int}
     */
    private function seedBenchmarkData(int $branchTarget, int $productTarget, int $customerTarget, int $orderTarget, int $batchSize): array
    {
        DB::transaction(function () {
            $this->cleanupBenchmarkData();
        });

        $branchIds = $this->ensureBenchmarkBranches($branchTarget);
        $categoryIds = $this->ensureBenchmarkCategories();
        $productIds = $this->ensureBenchmarkProducts($productTarget, $categoryIds);
        $sizeIds = $this->ensureSizes();
        $productVariants = $this->ensureProductVariants($productIds, $sizeIds);
        $customerIds = $this->ensureBenchmarkCustomers($customerTarget);

        $orderStats = $this->seedBenchmarkOrders($orderTarget, $batchSize, $branchIds, $customerIds, $productVariants);

        return [
            'branches' => count($branchIds),
            'categories' => count($categoryIds),
            'products' => count($productIds),
            'sizes' => count($sizeIds),
            'customers' => count($customerIds),
            'orders' => $orderStats['orders'],
            'order_items' => $orderStats['order_items'],
        ];
    }

    /**
     * @return array{branches:int,categories:int,products:int,sizes:int,customers:int,orders:int,order_items:int}
     */
    private function cleanupBenchmarkData(): array
    {
        $orderIds = Order::query()
            ->where('note', 'like', self::BENCH_PREFIX.'%')
            ->pluck('id');

        $orderCount = $orderIds->count();
        $orderItemCount = 0;

        if ($orderIds->isNotEmpty()) {
            $orderItemCount = OrderItem::query()->whereIn('order_id', $orderIds)->count();
            OrderItem::query()->whereIn('order_id', $orderIds)->delete();
            Order::query()->whereIn('id', $orderIds)->delete();
        }

        $customerCount = User::query()
            ->where('email', 'like', self::BENCH_CUSTOMER_EMAIL_PREFIX.'%@chilldrink.local')
            ->count();
        User::query()
            ->where('email', 'like', self::BENCH_CUSTOMER_EMAIL_PREFIX.'%@chilldrink.local')
            ->delete();

        $productCount = Product::query()
            ->where('slug', 'like', self::BENCH_PRODUCT_SLUG_PREFIX.'%')
            ->count();
        Product::withTrashed()
            ->where('slug', 'like', self::BENCH_PRODUCT_SLUG_PREFIX.'%')
            ->forceDelete();

        $branchCount = Branch::query()
            ->where('code', 'like', self::BENCH_BRANCH_CODE_PREFIX.'%')
            ->count();
        Branch::query()
            ->where('code', 'like', self::BENCH_BRANCH_CODE_PREFIX.'%')
            ->delete();

        $categoryCount = Category::query()
            ->where('slug', 'like', self::BENCH_CATEGORY_SLUG_PREFIX.'%')
            ->count();
        Category::query()
            ->where('slug', 'like', self::BENCH_CATEGORY_SLUG_PREFIX.'%')
            ->delete();

        $sizeCount = Size::query()
            ->where('name', 'like', 'Benchmark Size %')
            ->count();
        Size::query()
            ->where('name', 'like', 'Benchmark Size %')
            ->delete();

        return [
            'branches' => $branchCount,
            'categories' => $categoryCount,
            'products' => $productCount,
            'sizes' => $sizeCount,
            'customers' => $customerCount,
            'orders' => $orderCount,
            'order_items' => $orderItemCount,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function ensureBenchmarkBranches(int $target): array
    {
        $existing = Branch::query()
            ->where('code', 'like', self::BENCH_BRANCH_CODE_PREFIX.'%')
            ->pluck('id')
            ->all();

        $current = count($existing);
        $missing = max(0, $target - $current);

        for ($i = 1; $i <= $missing; $i++) {
            $index = $current + $i;
            Branch::query()->updateOrCreate(
                ['code' => self::BENCH_BRANCH_CODE_PREFIX.str_pad((string) $index, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Benchmark Chi nhánh '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'phone' => '09'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'email' => 'bench-branch-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'@chilldrink.local',
                    'address' => 'Benchmark address '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'status' => true,
                ]
            );
        }

        return Branch::query()
            ->where('code', 'like', self::BENCH_BRANCH_CODE_PREFIX.'%')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function ensureBenchmarkCategories(): array
    {
        $categoryIds = Category::query()->pluck('id')->all();
        if ($categoryIds !== []) {
            return $categoryIds;
        }

        $defaults = [
            ['slug' => self::BENCH_CATEGORY_SLUG_PREFIX.'milk-tea', 'name' => 'Benchmark Milk Tea'],
            ['slug' => self::BENCH_CATEGORY_SLUG_PREFIX.'coffee', 'name' => 'Benchmark Coffee'],
            ['slug' => self::BENCH_CATEGORY_SLUG_PREFIX.'fruit-tea', 'name' => 'Benchmark Fruit Tea'],
            ['slug' => self::BENCH_CATEGORY_SLUG_PREFIX.'blended', 'name' => 'Benchmark Blended'],
        ];

        foreach ($defaults as $row) {
            Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => 'Benchmark category',
                    'status' => 1,
                ]
            );
        }

        return Category::query()->pluck('id')->all();
    }

    /**
     * @return array<int, int>
     */
    private function ensureBenchmarkProducts(int $target, array $categoryIds): array
    {
        $existing = Product::query()
            ->where('slug', 'like', self::BENCH_PRODUCT_SLUG_PREFIX.'%')
            ->pluck('id')
            ->all();

        $current = count($existing);
        $missing = max(0, $target - $current);
        $categoryCount = max(1, count($categoryIds));

        for ($i = 1; $i <= $missing; $i++) {
            $index = $current + $i;
            $slug = self::BENCH_PRODUCT_SLUG_PREFIX.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $categoryId = $categoryIds[($index - 1) % $categoryCount] ?? null;
            $price = random_int(35000, 85000);

            Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categoryId,
                    'name' => 'Benchmark Product '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'sku' => Schema::hasColumn('products', 'sku')
                        ? 'BENCH-PRD-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT)
                        : null,
                    'description' => 'Benchmark product for analytics baseline.',
                    'image' => null,
                    'price' => $price,
                    'stock' => random_int(200, 900),
                    'status' => 1,
                ]
            );
        }

        return Product::query()
            ->where('slug', 'like', self::BENCH_PRODUCT_SLUG_PREFIX.'%')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function ensureSizes(): array
    {
        $sizeIds = Size::query()->pluck('id')->all();
        if ($sizeIds !== []) {
            return $sizeIds;
        }

        $rows = [
            ['name' => 'S', 'multiplier' => 0.9],
            ['name' => 'M', 'multiplier' => 1.0],
            ['name' => 'L', 'multiplier' => 1.15],
        ];

        foreach ($rows as $row) {
            Size::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'multiplier' => $row['multiplier'],
                    'created_at' => now(),
                ]
            );
        }

        return Size::query()->pluck('id')->all();
    }

    /**
     * @param array<int, int> $productIds
     * @param array<int, int> $sizeIds
     * @return array<int, array<int, array{product_size_id:int,price:int,size_id:int}>>
     */
    private function ensureProductVariants(array $productIds, array $sizeIds): array
    {
        $sizes = Size::query()->whereIn('id', $sizeIds)->get()->keyBy('id');
        $productVariants = [];

        foreach ($productIds as $productId) {
            $product = Product::query()->find($productId);
            if (! $product) {
                continue;
            }

            foreach ($sizes as $size) {
                $price = (int) round(((int) $product->price) * ((float) $size->multiplier));
                $variant = ProductSize::query()->updateOrCreate(
                    [
                        'product_id' => $productId,
                        'size_id' => $size->id,
                    ],
                    [
                        'price' => $price,
                    ]
                );

                $productVariants[$productId][] = [
                    'product_size_id' => (int) $variant->id,
                    'price' => $price,
                    'size_id' => (int) $size->id,
                ];
            }
        }

        return $productVariants;
    }

    /**
     * @return array<int, int>
     */
    private function ensureBenchmarkCustomers(int $target): array
    {
        $existing = User::query()
            ->where('email', 'like', self::BENCH_CUSTOMER_EMAIL_PREFIX.'%@chilldrink.local')
            ->pluck('id')
            ->all();

        $current = count($existing);
        $missing = max(0, $target - $current);

        for ($i = 1; $i <= $missing; $i++) {
            $index = $current + $i;
            $email = self::BENCH_CUSTOMER_EMAIL_PREFIX.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'@chilldrink.local';

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Benchmark Customer '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'password' => Hash::make('benchmark123456'),
                    'role_id' => 1,
                    'is_active' => true,
                    'phone' => '09'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                ]
            );
        }

        return User::query()
            ->where('email', 'like', self::BENCH_CUSTOMER_EMAIL_PREFIX.'%@chilldrink.local')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @param array<int, int> $branchIds
     * @param array<int, int> $customerIds
     * @param array<int, array<int, array{product_size_id:int,price:int,size_id:int}>> $productVariants
     * @return array{orders:int,order_items:int}
     */
    private function seedBenchmarkOrders(int $orderTarget, int $batchSize, array $branchIds, array $customerIds, array $productVariants): array
    {
        $branchPool = $this->buildWeightedBranchPool($branchIds);
        $productIds = array_keys($productVariants);
        $productPool = $this->buildWeightedProductPool($productIds);
        $now = CarbonImmutable::now(config('app.timezone'));
        $orderSequence = 0;
        $ordersCreated = 0;
        $itemsCreated = 0;

        for ($offset = 0; $offset < $orderTarget; $offset += $batchSize) {
            $orderRows = [];
            $blueprints = [];
            $batchCount = min($batchSize, $orderTarget - $offset);

            for ($i = 0; $i < $batchCount; $i++) {
                $orderSequence++;
                $orderNote = self::BENCH_PREFIX.' order#'.str_pad((string) $orderSequence, 5, '0', STR_PAD_LEFT);
                $createdAt = $this->benchmarkTimestamp($now, $orderSequence);
                $branchId = $branchPool[($orderSequence - 1) % count($branchPool)];
                $statusPayload = $this->benchmarkOrderStatus($orderSequence);
                $userId = $this->pickBenchmarkCustomerId($customerIds, $orderSequence);
                $isGuest = $userId === null;
                $itemCount = 1 + (($orderSequence - 1) % 4);
                $items = [];
                $subtotal = 0;

                for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
                    $productId = $productPool[($orderSequence + $itemIndex) % count($productPool)];
                    $variants = $productVariants[$productId] ?? [];
                    if ($variants === []) {
                        continue;
                    }

                    $variant = $variants[($orderSequence + $itemIndex) % count($variants)];
                    $quantity = 1 + (($orderSequence + $itemIndex) % 3);
                    $unitPrice = (int) $variant['price'];
                    $lineTotal = $unitPrice * $quantity;
                    $subtotal += $lineTotal;

                    $items[] = [
                        'product_id' => $productId,
                        'product_size_id' => $variant['product_size_id'],
                        'ice_level' => [0, 30, 50, 70, 100][($orderSequence + $itemIndex) % 5],
                        'sugar_level' => [0, 30, 50, 70, 100][($orderSequence + $itemIndex + 2) % 5],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ];
                }

                $shippingFee = in_array($statusPayload['status'], ['cancelled'], true) ? 0 : (($orderSequence % 3) * 5000);
                $discount = in_array($statusPayload['status'], ['completed'], true) ? (($orderSequence % 5) * 2000) : 0;
                $total = max(0, $subtotal + $shippingFee - $discount);

                $orderRows[] = [
                    'user_id' => $userId,
                    'guest_name' => $isGuest ? 'Benchmark Guest '.str_pad((string) $orderSequence, 5, '0', STR_PAD_LEFT) : null,
                    'guest_phone' => $isGuest ? '09'.str_pad((string) (($orderSequence * 37) % 100000000), 8, '0', STR_PAD_LEFT) : null,
                    'guest_email' => $isGuest ? 'guest-'.str_pad((string) $orderSequence, 5, '0', STR_PAD_LEFT).'@chilldrink.local' : null,
                    'guest_token' => $isGuest ? Str::random(64) : null,
                    'delivery_type' => ($orderSequence % 2 === 0) ? 'delivery' : 'pickup',
                    'branch_id' => $branchId,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount' => $discount,
                    'total' => $total,
                    'payment_method' => $statusPayload['payment_method'],
                    'payment_status' => $statusPayload['payment_status'],
                    'status' => $statusPayload['status'],
                    'note' => $orderNote,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                    'delivered_at' => in_array($statusPayload['status'], ['completed', 'delivering'], true) ? $createdAt->addMinutes(30)->toDateTimeString() : null,
                ];

                $blueprints[] = [
                    'note' => $orderNote,
                    'created_at' => $createdAt->toDateTimeString(),
                    'items' => $items,
                ];
            }

            DB::table('orders')->insert($orderRows);

            $orderIds = DB::table('orders')
                ->whereIn('note', array_column($blueprints, 'note'))
                ->pluck('id', 'note');

            $itemRows = [];
            foreach ($blueprints as $blueprint) {
                $orderId = $orderIds[$blueprint['note']] ?? null;
                if ($orderId === null) {
                    continue;
                }

                foreach ($blueprint['items'] as $item) {
                    $itemRows[] = [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'product_size_id' => $item['product_size_id'],
                        'ice_level' => $item['ice_level'],
                        'sugar_level' => $item['sugar_level'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'created_at' => $blueprint['created_at'],
                    ];
                }
            }

            if ($itemRows !== []) {
                DB::table('order_items')->insert($itemRows);
                $itemsCreated += count($itemRows);
            }

            $ordersCreated += count($orderRows);
        }

        return [
            'orders' => $ordersCreated,
            'order_items' => $itemsCreated,
        ];
    }

    /**
     * @param array<int, int> $branchIds
     * @return array<int, int>
     */
    private function buildWeightedBranchPool(array $branchIds): array
    {
        $pool = [];
        foreach ($branchIds as $index => $branchId) {
            $weight = $index < 3 ? 8 : ($index < 8 ? 4 : 2);
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $branchId;
            }
        }

        return $pool !== [] ? $pool : [1];
    }

    /**
     * @param array<int, int> $productIds
     * @return array<int, int>
     */
    private function buildWeightedProductPool(array $productIds): array
    {
        $pool = [];
        foreach ($productIds as $index => $productId) {
            $weight = $index < 10 ? 10 : ($index < 50 ? 4 : 1);
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $productId;
            }
        }

        return $pool !== [] ? $pool : [1];
    }

    /**
     * @param array<int, int> $customerIds
     */
    private function pickBenchmarkCustomerId(array $customerIds, int $sequence): ?int
    {
        if ($customerIds === []) {
            return null;
        }

        if ($sequence % 4 === 0) {
            return null;
        }

        return $customerIds[($sequence - 1) % count($customerIds)];
    }

    private function benchmarkTimestamp(CarbonImmutable $now, int $sequence): CarbonImmutable
    {
        if ($sequence % 97 === 0) {
            return $now->copy()->subMonths($sequence % 12)->subDays($sequence % 7)->startOfDay()->addHours(10);
        }

        if ($sequence % 41 === 0) {
            return $now->copy()->subDays($sequence % 30)->setTime(9 + ($sequence % 8), 15);
        }

        if ($sequence % 11 === 0) {
            return $now->copy()->subMonthNoOverflow()->subDays($sequence % 25)->setTime(14, 30);
        }

        return $now->copy()->subMonths(($sequence - 1) % 12)->subDays(($sequence - 1) % 20)->setTime(8 + ($sequence % 10), ($sequence * 7) % 60);
    }

    /**
     * @return array{status:string,payment_status:string,payment_method:string}
     */
    private function benchmarkOrderStatus(int $sequence): array
    {
        return match ($sequence % 10) {
            0, 1 => ['status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'cod'],
            2, 3 => ['status' => 'preparing', 'payment_status' => 'paid', 'payment_method' => 'bank_transfer'],
            4 => ['status' => 'preparing', 'payment_status' => 'paid', 'payment_method' => 'vnpay'],
            5 => ['status' => 'delivering', 'payment_status' => 'paid', 'payment_method' => 'momo'],
            6 => ['status' => 'completed', 'payment_status' => 'pending', 'payment_method' => 'cod'],
            7 => ['status' => 'pending', 'payment_status' => 'pending', 'payment_method' => 'cod'],
            8 => ['status' => 'cancelled', 'payment_status' => 'pending', 'payment_method' => 'cod'],
            default => ['status' => 'cancelled', 'payment_status' => 'failed', 'payment_method' => 'wallet'],
        };
    }

    /**
     * @return array<int, array{label:string,context:array,branch_scope:array<string,mixed>}> 
     */
    private function benchmarkDefinitions(): array
    {
        $branchIds = $this->benchmarkBranchIds();
        $branchCount = count($branchIds);
        $oneBranch = $branchIds[0] ?? null;
        $threeBranches = array_slice($branchIds, 0, min(3, $branchCount));
        $twentyBranches = array_slice($branchIds, 0, min(20, $branchCount));

        $latestDate = Order::query()
            ->max('created_at');
        $latest = $latestDate ? CarbonImmutable::parse((string) $latestDate, config('app.timezone')) : CarbonImmutable::now(config('app.timezone'));
        $safeLatest = $latest->isAfter(CarbonImmutable::now(config('app.timezone')))
            ? CarbonImmutable::now(config('app.timezone'))->subDay()
            : ($latest->isSameDay(CarbonImmutable::now(config('app.timezone'))) ? $latest->subDay() : $latest);
        $latestDay = $latest->format('Y-m-d');
        $latestMonth = $latest->format('Y-m');
        $rangeStart = $safeLatest->subDays(29)->format('Y-m-d');
        $rangeEnd = $safeLatest->format('Y-m-d');

        return [
            [
                'label' => 'A. Toàn thời gian, tất cả chi nhánh',
                'context' => ['analytics_period_type' => 'all'],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'B. Một ngày cụ thể, tất cả chi nhánh',
                'context' => ['analytics_period_type' => 'day', 'analytics_date' => $latestDay],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'C. Một tháng cụ thể, tất cả chi nhánh',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'D. Khoảng 30 ngày',
                'context' => ['analytics_period_type' => 'range', 'analytics_start_date' => $rangeStart, 'analytics_end_date' => $rangeEnd],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'E. Một chi nhánh',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth, 'analytics_branch_ids' => $oneBranch ? [$oneBranch] : []],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'F. Ba chi nhánh',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth, 'analytics_branch_ids' => $threeBranches],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'G. Hai mươi chi nhánh',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth, 'analytics_branch_ids' => $twentyBranches],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'H. Một tháng + comparison previous',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth, 'analytics_compare_type' => 'previous'],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'I. Một tháng + previous year',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth, 'analytics_compare_type' => 'previous_year'],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->businessSummary($context),
            ],
            [
                'label' => 'J. Một sản phẩm bán mạnh theo chi nhánh',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->productBranchPerformance($context, $this->topProductId($service, $context), ['sort_by' => 'quantity']),
            ],
            [
                'label' => 'K. Một chi nhánh có nhiều sản phẩm',
                'context' => ['analytics_period_type' => 'month', 'analytics_month' => $latestMonth],
                'call' => fn (SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context) => $service->branchProductDetail($context, $this->topBranchId($service, $context), ['sort_by' => 'quantity']),
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function benchmarkBranchIds(): array
    {
        return Branch::query()
            ->where('status', true)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function runBenchmarks(int $warmups, int $runs): array
    {
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);
        $definitions = $this->benchmarkDefinitions();
        $results = [];

        foreach ($definitions as $definition) {
            $context = $resolver->resolve($definition['context']);
            $callback = function () use ($definition, $context) {
                return $definition['call'](app(SuperAdminAnalyticsService::class), $context);
            };

            $results[] = $this->benchmarkScenario($definition['label'], $callback, $warmups, $runs);
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function runModuleBaseline(int $warmups, int $runs): array
    {
        $service = app(SuperAdminAnalyticsService::class);
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);
        $branchIds = $this->benchmarkBranchIds();
        $oneBranch = $branchIds[0] ?? null;
        $twentyBranches = array_slice($branchIds, 0, min(20, count($branchIds)));
        $latestDate = Order::query()->max('created_at');
        $latest = $latestDate ? CarbonImmutable::parse((string) $latestDate, config('app.timezone')) : CarbonImmutable::now(config('app.timezone'));
        $monthContext = $resolver->resolve([
            'analytics_period_type' => 'month',
            'analytics_month' => $latest->format('Y-m'),
            'analytics_compare_type' => 'previous',
        ]);

        $topProductId = $this->topProductId($service, $monthContext);
        $topBranchId = $this->topBranchId($service, $monthContext);

        $definitions = [
            [
                'label' => 'businessSummary (month, all branches)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->businessSummary($monthContext),
            ],
            [
                'label' => 'itemQuantitySummary (month, all branches)',
                'call' => function () use ($monthContext) {
                    $service = app(SuperAdminAnalyticsService::class);
                    $query = $service->validSalesOrderItemsQuery();
                    $service->applyDateRange($query, $monthContext->currentStart, $monthContext->currentEnd);

                    return $service->itemQuantitySummary($query);
                },
            ],
            [
                'label' => 'topProducts (month, all branches)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->topProducts($monthContext, 'quantity', 5),
            ],
            [
                'label' => 'branchComparison (month, previous)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->branchComparison($monthContext, ['ranking_period' => 'month', 'per_page' => 10]),
            ],
            [
                'label' => 'branchComparison (month, 20 branches)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->branchComparison($monthContext, ['ranking_period' => 'month', 'per_page' => 10, 'analytics_branch_ids' => $twentyBranches]),
            ],
            [
                'label' => 'branchProductDetail (top branch)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->branchProductDetail($monthContext, $topBranchId ?? 0, ['sort_by' => 'quantity']),
            ],
            [
                'label' => 'productBranchPerformance (top product)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->productBranchPerformance($monthContext, $topProductId ?? 0, ['sort_by' => 'quantity']),
            ],
            [
                'label' => 'productBranchPerformance (20 branches)',
                'call' => fn () => app(SuperAdminAnalyticsService::class)->productBranchPerformance($monthContext, $topProductId ?? 0, ['sort_by' => 'quantity', 'analytics_branch_ids' => $twentyBranches]),
            ],
            [
                'label' => 'route /admin/super-admin (render)',
                'call' => fn () => $this->renderSuperAdminRoute($oneBranch ? [$oneBranch] : [], $latest->format('Y-m')),
            ],
        ];

        $results = [];
        foreach ($definitions as $definition) {
            $results[] = $this->benchmarkScenario($definition['label'], $definition['call'], $warmups, $runs);
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function benchmarkScenario(string $label, callable $callback, int $warmups, int $runs): array
    {
        for ($i = 0; $i < $warmups; $i++) {
            $this->measureCallable($callback, false);
        }

        $samples = [];
        for ($i = 0; $i < $runs; $i++) {
            $samples[] = $this->measureCallable($callback, true);
        }

        return [
            'label' => $label,
            'query_count_median' => $this->median(array_column($samples, 'query_count')),
            'sql_time_median' => $this->median(array_column($samples, 'sql_time_ms')),
            'wall_time_median' => $this->median(array_column($samples, 'wall_time_ms')),
            'peak_memory_median' => $this->median(array_column($samples, 'peak_memory_mb')),
            'slowest_query_ms' => max(array_column($samples, 'slowest_query_ms')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function measureCallable(callable $callback, bool $captureQueries): array
    {
        $probe = [
            'query_count' => 0,
            'sql_time_ms' => 0.0,
            'slowest_query_ms' => 0.0,
        ];

        $baselineMemory = memory_get_usage(true);
        $this->activeProbe = $captureQueries ? $probe : null;

        $start = microtime(true);
        $result = $callback();
        if ($result instanceof \Illuminate\Contracts\Support\Renderable) {
            $result->render();
        } elseif ($result instanceof \Illuminate\View\View) {
            $result->render();
        }
        $wallTime = (microtime(true) - $start) * 1000;

        $probe = $this->activeProbe ?? $probe;
        $this->activeProbe = null;
        gc_collect_cycles();

        return [
            'query_count' => (int) $probe['query_count'],
            'sql_time_ms' => (float) $probe['sql_time_ms'],
            'slowest_query_ms' => (float) $probe['slowest_query_ms'],
            'wall_time_ms' => $wallTime,
            'peak_memory_mb' => max(0, (memory_get_peak_usage(true) - $baselineMemory) / 1024 / 1024),
        ];
    }

    private function registerQueryListener(): void
    {
        if ($this->queryListenerRegistered) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            if ($this->activeProbe === null) {
                return;
            }

            $sqlTime = (float) $query->time;
            $this->activeProbe['query_count']++;
            $this->activeProbe['sql_time_ms'] += $sqlTime;
            $this->activeProbe['slowest_query_ms'] = max($this->activeProbe['slowest_query_ms'], $sqlTime);
        });

        $this->queryListenerRegistered = true;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function collectDataBaseline(): array
    {
        return [
            'branches' => Schema::hasTable('branches') ? Branch::count() : 0,
            'orders' => Schema::hasTable('orders') ? Order::count() : 0,
            'order_items' => Schema::hasTable('order_items') ? OrderItem::count() : 0,
            'products' => Schema::hasTable('products') ? Product::count() : 0,
            'users' => Schema::hasTable('users') ? User::count() : 0,
            'min_order_created_at' => Schema::hasTable('orders') ? Order::min('created_at') : null,
            'max_order_created_at' => Schema::hasTable('orders') ? Order::max('created_at') : null,
            'distinct_months' => Schema::hasTable('orders')
                ? Order::query()->pluck('created_at')->filter()->map(static fn ($value) => substr((string) $value, 0, 7))->unique()->count()
                : 0,
        ];
    }

    private function topProductId(SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context): ?int
    {
        $row = $service->topProducts($context, 'quantity', 1)->first();

        return $row['product_id'] ?? null;
    }

    private function topBranchId(SuperAdminAnalyticsService $service, \App\Services\AnalyticsPeriodContext $context): ?int
    {
        $comparison = $service->branchComparison($context, ['per_page' => 1]);
        $row = $comparison['paginator']?->getCollection()?->first();

        return $row['branch_id'] ?? null;
    }

    /**
     * @param array<int, int> $branchIds
     */
    private function renderSuperAdminRoute(array $branchIds, string $month): string
    {
        $superAdmin = User::query()->updateOrCreate(
            ['email' => User::SUPER_ADMIN_EMAIL],
            [
                'name' => 'Super Admin Benchmark',
                'password' => Hash::make('benchmark123456'),
                'role_id' => 3,
                'is_active' => true,
            ]
        );
        Auth::setUser($superAdmin);
        View::share('errors', new ViewErrorBag());

        $params = [
            'analytics_period_type' => 'month',
            'analytics_month' => $month,
            'analytics_compare_type' => 'previous',
        ];

        if ($branchIds !== []) {
            $params['analytics_branch_ids'] = $branchIds;
        }

        $baseRequest = BaseRequest::create('/admin/super-admin', 'GET', $params);
        $request = SuperAdminAnalyticsRequest::createFromBase($baseRequest);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setUserResolver(static fn () => $superAdmin);
        $request->validateResolved();

        $controller = app(SuperAdminController::class);
        $view = $controller->index($request);

        return $view->render();
    }

    /**
     * @param array<int, int|float> $values
     */
    private function median(array $values): float
    {
        $values = array_values(array_filter($values, static fn ($value) => is_numeric($value)));
        if ($values === []) {
            return 0.0;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
