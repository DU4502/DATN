<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoBranchStatisticsSeeder extends Seeder
{
    private const DEMO_PREFIX = '[BRANCH-DEMO]';

    public function run(): void
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders') || ! Schema::hasTable('users')) {
            $this->command?->info('Required tables missing. Skipping DemoBranchStatisticsSeeder.');
            return;
        }

        DB::transaction(function () {
            $branches = $this->createDemoBranches();
            $products = $this->getOrCreateProducts();

            if (empty($branches) || empty($products)) {
                $this->command?->warn('Could not create demo data.');
                return;
            }

            $this->createDemoOrders($branches, $products);
            $this->command?->info('Demo branch statistics data created successfully.');
        });
    }

    private function createDemoBranches(): array
    {
        $branchConfigs = [
            'cn1' => [
                'display_name' => 'Admin Chi nhánh 1',
                'branch_name' => 'Chi nhánh 1',
                'code' => 'CN1',
                'email' => 'admin@chilldrink.com',
                'phone' => '0243.3333.123',
                'address' => 'QQFJ+MX Quảng Phú, Thanh Hóa, Việt Nam',
            ],
            'cn2' => [
                'display_name' => 'Admin Chi nhánh 2',
                'branch_name' => 'Chi nhánh 2',
                'code' => 'CN2',
                'email' => 'admin_cn2@chilldrink.com',
                'phone' => '0237.3333.456',
                'address' => 'QQVV+9W Hạc Thành, Thanh Hóa, Việt Nam',
            ],
            'cn3' => [
                'display_name' => 'Admin Chi nhánh 3',
                'branch_name' => 'Chi nhánh 3',
                'code' => 'CN3',
                'email' => 'admin_cn3@chilldrink.com',
                'phone' => '0236.3333.789',
                'address' => 'RQ4G+W9 Hạc Thành, Thanh Hóa, Việt Nam',
            ],
        ];

        $branches = [];

        foreach ($branchConfigs as $key => $config) {
            $adminEmail = $config['email'];
            $admin = User::where('email', $adminEmail)->first();

            if ($admin && $admin->branch_id) {
                $branch = Branch::find($admin->branch_id);
                if ($branch) {
                    $branches[$key] = $branch;
                    $this->command?->line("  Reusing: {$config['display_name']} (ID: {$branch->id})");
                    continue;
                }
            }

            if (! $admin) {
                $admin = User::create([
                    'name' => $config['display_name'],
                    'email' => $adminEmail,
                    'password' => Hash::make('12345678'),
                    'role_id' => 2,
                    'is_active' => true,
                    'phone' => $config['phone'],
                ]);
            }

            $branch = Branch::updateOrCreate(
                ['code' => $config['code']],
                [
                    'name' => $config['branch_name'],
                    'email' => $adminEmail,
                    'phone' => $config['phone'],
                    'address' => $config['address'],
                    'status' => true,
                ]
            );

            if (! $admin->branch_id) {
                $admin->update(['branch_id' => $branch->id]);
            }

            $branches[$key] = $branch;
            $this->command?->line("  Created: {$config['display_name']} (ID: {$branch->id})");
        }

        return $branches;
    }

    private function getOrCreateProducts(): array
    {
        $existingProducts = Product::where('status', true)
            ->limit(5)
            ->get()
            ->all();

        if (! empty($existingProducts)) {
            $count = count($existingProducts);
            $this->command?->line("  Using {$count} existing active products");
            return $existingProducts;
        }

        $this->command?->line('  Creating demo products...');

        $productNames = [
            'Trà Sữa Trân Châu',
            'Trà Sữa Matcha',
            'Cà Phê Sữa Đá',
            'Cold Brew Cam',
            'Trà Đào Cam Sa',
        ];

        $products = [];
        foreach ($productNames as $name) {
            $product = Product::updateOrCreate(
                ['name' => $name],
                [
                    'description' => 'Sản phẩm demo cho thống kê chi nhánh',
                    'price' => random_int(45000, 70000),
                    'status' => true,
                ]
            );
            $products[] = $product;
        }

        return $products;
    }

    private function createDemoOrders(array $branches, array $products): void
    {
        // Delete old demo orders
        $oldOrderIds = Order::where('note', 'like', self::DEMO_PREFIX.'%')->pluck('id');
        if ($oldOrderIds->isNotEmpty()) {
            if (Schema::hasTable('order_items')) {
                DB::table('order_items')->whereIn('order_id', $oldOrderIds)->delete();
            }
            Order::whereIn('id', $oldOrderIds)->delete();
        }

        // Create customer for orders
        $customer = User::where('email', 'demo_customer@chilldrink.test')->first();
        if (! $customer) {
            $customer = User::create([
                'name' => 'Khách Hàng Demo',
                'email' => 'demo_customer@chilldrink.test',
                'password' => Hash::make('demo12345678'),
                'role_id' => 1,
                'is_active' => true,
                'phone' => '0901.234.567',
            ]);
        }

        // Get default product size for order items
        $defaultProductSize = null;
        if (Schema::hasTable('product_sizes')) {
            $defaultProductSize = DB::table('product_sizes')->first();
        }

        // Order distribution: CN1: 5, CN2: 3, CN3: 2
        $orderDistribution = [
            'cn1' => 5,
            'cn2' => 3,
            'cn3' => 2,
        ];

        $orderCounter = 0;

        foreach ($orderDistribution as $branchKey => $count) {
            $branch = $branches[$branchKey];

            for ($i = 0; $i < $count; $i++) {
                $rand = random_int(0, 99);
                if ($rand < 50) {
                    $status = 'completed';
                    $paymentStatus = 'paid';
                } elseif ($rand < 80) {
                    $status = 'pending';
                    $paymentStatus = 'pending';
                } else {
                    $status = 'cancelled';
                    $paymentStatus = 'pending';
                }

                if ($i < 2) {
                    $createdAt = Carbon::now()->subHours(random_int(0, 24));
                } else {
                    $createdAt = Carbon::now()
                        ->startOfMonth()
                        ->addDays(random_int(0, Carbon::now()->daysInMonth() - 1))
                        ->setHour(random_int(9, 20));
                }

                $subtotal = random_int(100000, 300000);
                $shippingFee = 0;
                $discount = random_int(0, 20000);
                $total = max(0, $subtotal + $shippingFee - $discount);
                $orderNumber = $orderCounter + 1;
                $noteText = self::DEMO_PREFIX . " {$branch->name} - Đơn #{$orderNumber}";

                $order = Order::create([
                    'user_id' => $customer->id,
                    'delivery_type' => 'pickup',
                    'branch_id' => $branch->id,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount' => $discount,
                    'total' => $total,
                    'payment_method' => 'cod',
                    'payment_status' => $paymentStatus,
                    'status' => $status,
                    'note' => $noteText,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Create order items only if product_sizes exist
                if ($defaultProductSize && Schema::hasTable('order_items')) {
                    $itemCount = random_int(1, 2);
                    for ($j = 0; $j < $itemCount; $j++) {
                        $product = $products[array_rand($products)];
                        $quantity = random_int(1, 2);
                        $unitPrice = (int) $product->price;
                        $lineTotal = $unitPrice * $quantity;

                        $itemData = [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_size_id' => $defaultProductSize->id,
                            'ice_level' => random_int(30, 70),
                            'sugar_level' => random_int(30, 70),
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $lineTotal,
                            'created_at' => $createdAt,
                        ];

                        if (Schema::hasColumn('order_items', 'price')) {
                            $itemData['price'] = $unitPrice;
                        }

                        DB::table('order_items')->insert($itemData);
                    }
                }

                $orderCounter++;
            }

            $this->command?->line("  {$branchKey}: {$count} orders created");
        }

        $this->command?->line("Total: {$orderCounter} demo orders created");
    }
}
