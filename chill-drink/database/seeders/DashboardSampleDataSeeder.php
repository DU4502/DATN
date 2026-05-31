<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DashboardSampleDataSeeder extends Seeder
{
    private const NOTE_PREFIX = '[SEED-MAU]';

    public function run(): void
    {
        DB::transaction(function () {
            $this->ensureBaseAdmin();
            $this->cleanupOldSampleOrders();

            $categories = $this->seedCategories();
            $products = $this->seedProducts($categories);
            $sizes = $this->seedSizes();
            $this->seedProductSizes($products, $sizes);
            $customers = $this->seedCustomers();
            $this->seedOrdersAndItems($products, $customers);
        });

        $this->command?->info('Dashboard sample data seeded successfully.');
    }

    private function ensureBaseAdmin(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $payload = [
            'name' => 'Admin',
            'password' => Hash::make('12345678'),
            'role_id' => 2,
            'is_active' => 1,
            'phone' => '0123456789',
        ];

        if (Schema::hasColumn('users', 'address')) {
            $payload['address'] = 'Ha Noi, Viet Nam';
        }

        if (Schema::hasColumn('users', 'area')) {
            $payload['area'] = 'Hoan Kiem, Ha Noi';
        }

        User::updateOrCreate(
            ['email' => 'admin@chilldrink.com'],
            $payload
        );
    }

    private function cleanupOldSampleOrders(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $sampleOrderIds = Order::query()
            ->where('note', 'like', self::NOTE_PREFIX.'%')
            ->pluck('id');

        if ($sampleOrderIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('order_items')) {
            DB::table('order_items')
                ->whereIn('order_id', $sampleOrderIds)
                ->delete();
        }

        Order::query()->whereIn('id', $sampleOrderIds)->delete();
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $rows = [
            ['name' => 'Tra Sua', 'slug' => 'tra-sua', 'description' => 'Do uong sua va tra'],
            ['name' => 'Ca Phe', 'slug' => 'ca-phe', 'description' => 'Do uong tu ca phe'],
            ['name' => 'Tra Trai Cay', 'slug' => 'tra-trai-cay', 'description' => 'Tra ket hop trai cay'],
            ['name' => 'Da Xay', 'slug' => 'da-xay', 'description' => 'Do uong da xay mat lanh'],
        ];

        $result = [];
        foreach ($rows as $row) {
            $payload = [
                'name' => $row['name'],
            ];

            if (Schema::hasColumn('categories', 'slug')) {
                $payload['slug'] = $row['slug'];
            }

            if (Schema::hasColumn('categories', 'description')) {
                $payload['description'] = $row['description'];
            }

            if (Schema::hasColumn('categories', 'status')) {
                $payload['status'] = 1;
            }

            $lookup = Schema::hasColumn('categories', 'slug')
                ? ['slug' => $row['slug']]
                : ['name' => $row['name']];

            $category = Category::updateOrCreate($lookup, $payload);
            $result[$row['slug']] = $category;
        }

        return $result;
    }

    /**
     * @param array<string, Category> $categories
     * @return Product[]
     */
    private function seedProducts(array $categories): array
    {
        $rows = [
            ['name' => 'Tra Sua Tran Chau Duong Den', 'slug' => 'tra-sua-tran-chau-duong-den', 'category' => 'tra-sua', 'price' => 62000, 'stock' => 320],
            ['name' => 'Tra Sua Matcha', 'slug' => 'tra-sua-matcha', 'category' => 'tra-sua', 'price' => 58000, 'stock' => 280],
            ['name' => 'Ca Phe Sua Da', 'slug' => 'ca-phe-sua-da', 'category' => 'ca-phe', 'price' => 45000, 'stock' => 300],
            ['name' => 'Cold Brew Cam', 'slug' => 'cold-brew-cam', 'category' => 'ca-phe', 'price' => 55000, 'stock' => 220],
            ['name' => 'Tra Dao Cam Sa', 'slug' => 'tra-dao-cam-sa', 'category' => 'tra-trai-cay', 'price' => 52000, 'stock' => 260],
            ['name' => 'Tra Vai Nha Dam', 'slug' => 'tra-vai-nha-dam', 'category' => 'tra-trai-cay', 'price' => 54000, 'stock' => 240],
            ['name' => 'Da Xay Socola', 'slug' => 'da-xay-socola', 'category' => 'da-xay', 'price' => 65000, 'stock' => 190],
            ['name' => 'Da Xay Cookies', 'slug' => 'da-xay-cookies', 'category' => 'da-xay', 'price' => 68000, 'stock' => 170],
        ];

        $products = [];
        foreach ($rows as $row) {
            $payload = [
                'category_id' => $categories[$row['category']]->id ?? null,
                'name' => $row['name'],
                'description' => 'San pham mau phuc vu kiem thu dashboard doanh thu.',
                'image' => null,
                'price' => $row['price'],
                'stock' => $row['stock'],
                'status' => 1,
            ];

            if (Schema::hasColumn('products', 'slug')) {
                $payload['slug'] = $row['slug'];
            }

            if (Schema::hasColumn('products', 'sku')) {
                $payload['sku'] = 'CD-'.strtoupper(substr(md5($row['slug']), 0, 6));
            }

            if (Schema::hasColumn('products', 'gallery_images')) {
                $payload['gallery_images'] = json_encode([]);
            }

            $lookup = Schema::hasColumn('products', 'slug')
                ? ['slug' => $row['slug']]
                : ['name' => $row['name']];

            $products[] = Product::updateOrCreate($lookup, $payload);
        }

        return $products;
    }

    /**
     * @return array<string, object>
     */
    private function seedSizes(): array
    {
        if (! Schema::hasTable('sizes')) {
            return [];
        }

        $rows = [
            ['name' => 'S', 'multiplier' => 0.9],
            ['name' => 'M', 'multiplier' => 1.0],
            ['name' => 'L', 'multiplier' => 1.15],
        ];

        $sizes = [];
        foreach ($rows as $row) {
            $existing = DB::table('sizes')->where('name', $row['name'])->first();
            if (! $existing) {
                $id = DB::table('sizes')->insertGetId([
                    'name' => $row['name'],
                    'multiplier' => $row['multiplier'],
                    'created_at' => now(),
                ]);
                $existing = DB::table('sizes')->where('id', $id)->first();
            }
            $sizes[$row['name']] = $existing;
        }

        return $sizes;
    }

    /**
     * @param Product[] $products
     * @param array<string, object> $sizes
     */
    private function seedProductSizes(array $products, array $sizes): void
    {
        if (! Schema::hasTable('product_sizes') || empty($sizes)) {
            return;
        }

        $multipliers = [
            'S' => 0.9,
            'M' => 1.0,
            'L' => 1.15,
        ];

        foreach ($products as $product) {
            foreach ($multipliers as $sizeName => $multiplier) {
                $size = $sizes[$sizeName] ?? null;
                if (! $size) {
                    continue;
                }

                $price = (int) round(((int) $product->price) * $multiplier);
                $existing = DB::table('product_sizes')
                    ->where('product_id', $product->id)
                    ->where('size_id', $size->id)
                    ->first();

                if ($existing) {
                    DB::table('product_sizes')
                        ->where('id', $existing->id)
                        ->update(['price' => $price]);
                } else {
                    DB::table('product_sizes')->insert([
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'price' => $price,
                    ]);
                }
            }
        }
    }

    /**
     * @return User[]
     */
    private function seedCustomers(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $customers = [];
        for ($i = 1; $i <= 6; $i++) {
            $email = "khach{$i}@chilldrink.local";
            $payload = [
                'name' => "Khach Hang {$i}",
                'password' => Hash::make('12345678'),
                'role_id' => 1,
                'is_active' => 1,
                'phone' => '09'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            ];

            if (Schema::hasColumn('users', 'address')) {
                $payload['address'] = 'So '.random_int(1, 99).' Duong Mau';
            }

            if (Schema::hasColumn('users', 'area')) {
                $payload['area'] = 'TP Ho Chi Minh';
            }

            $customers[] = User::updateOrCreate(['email' => $email], $payload);
        }

        return $customers;
    }

    /**
     * @param Product[] $products
     * @param User[] $customers
     */
    private function seedOrdersAndItems(array $products, array $customers): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_items')) {
            return;
        }

        $slots = $this->buildOrderSlots();
        $productIds = collect($products)->pluck('id')->all();

        if (empty($productIds) || empty($customers)) {
            return;
        }

        foreach ($slots as $index => $slot) {
            $customer = $customers[$index % count($customers)];

            $order = Order::create([
                'user_id' => $customer->id,
                'subtotal' => 0,
                'shipping_fee' => 15000,
                'discount' => 0,
                'total' => 0,
                'payment_method' => collect(['cod', 'bank_transfer', 'momo', 'vnpay'])->random(),
                'payment_status' => $slot['status'] === 'completed' ? 'paid' : 'pending',
                'status' => $slot['status'],
                'note' => self::NOTE_PREFIX.' Don hang mau #'.($index + 1),
                'created_at' => $slot['at'],
                'updated_at' => $slot['at'],
            ]);

            $itemCount = random_int(1, 3);
            $subtotal = 0;

            for ($i = 0; $i < $itemCount; $i++) {
                $productId = $productIds[array_rand($productIds)];
                $productSize = DB::table('product_sizes')
                    ->where('product_id', $productId)
                    ->inRandomOrder()
                    ->first();

                if (! $productSize) {
                    continue;
                }

                $quantity = random_int(1, 3);
                $unitPrice = (int) $productSize->price;
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                $item = [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'product_size_id' => $productSize->id,
                    'ice_level' => [0, 30, 50, 70, 100][array_rand([0, 30, 50, 70, 100])],
                    'sugar_level' => [0, 30, 50, 70, 100][array_rand([0, 30, 50, 70, 100])],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'created_at' => $slot['at'],
                ];

                if (Schema::hasColumn('order_items', 'price')) {
                    $item['price'] = $unitPrice;
                }

                DB::table('order_items')->insert($item);
            }

            $shipping = $slot['status'] === 'cancelled' ? 0 : 15000;
            $discount = $slot['status'] === 'completed' ? random_int(0, 10000) : 0;

            $order->update([
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'discount' => $discount,
                'total' => max(0, $subtotal + $shipping - $discount),
            ]);
        }
    }

    /**
     * @return array<int, array{status:string,at:Carbon}>
     */
    private function buildOrderSlots(): array
    {
        $now = Carbon::now();
        $slots = [];

        for ($i = 0; $i < 3; $i++) {
            $slots[] = ['status' => 'completed', 'at' => $now->copy()->subHours($i * 2)];
        }

        for ($i = 1; $i <= 5; $i++) {
            $slots[] = [
                'status' => 'completed',
                'at' => $now->copy()->startOfWeek(Carbon::MONDAY)->addDays($i)->setHour(10 + $i),
            ];
        }

        for ($i = 1; $i <= 8; $i++) {
            $slots[] = [
                'status' => 'completed',
                'at' => $now->copy()->startOfMonth()->addDays($i * 2)->setHour(14),
            ];
        }

        for ($i = 1; $i <= 10; $i++) {
            $slots[] = [
                'status' => 'completed',
                'at' => $now->copy()->subMonths(random_int(1, 5))->setDay(random_int(1, 25))->setHour(random_int(9, 20)),
            ];
        }

        $otherStatuses = ['pending', 'processing', 'cancelled', 'shipped', 'delivering'];
        for ($i = 1; $i <= 7; $i++) {
            $slots[] = [
                'status' => $otherStatuses[array_rand($otherStatuses)],
                'at' => $now->copy()->subDays(random_int(0, 20))->setHour(random_int(8, 22)),
            ];
        }

        return $slots;
    }
}
