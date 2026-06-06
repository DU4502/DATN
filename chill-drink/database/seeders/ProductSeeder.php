<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Support\ProductCatalog;
use App\Support\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private const SIZE_PRICE_EXTRAS = [
        'S' => 0,
        'M' => 5000,
        'L' => 10000,
        'XL' => 15000,
        'XXL' => 20000,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('products')) {
            $this->command?->warn('Table `products` does not exist, skipping ProductSeeder.');
            return;
        }

        foreach (ProductCatalog::ITEMS as $index => $item) {
            $category = $this->categoryFor($item['category']);
            $product = $this->productFor($item);
            $data = $this->productData($item, $category, $index);

            $product ? $product->update($data) : $product = Product::create($data);

            $this->seedSizePrices($product, (int) $item['price']);
        }

        $this->command?->info('Product catalog seeded: '.count(ProductCatalog::ITEMS).' products.');
    }

    private function categoryFor(string $name): Category
    {
        $category = Category::query()->where('name', $name)->first();

        if ($category) {
            return $category;
        }

        $data = [
            'name' => $name,
        ];

        if (Schema::hasColumn('categories', 'slug')) {
            $data['slug'] = Str::slug($name);
        }

        if (Schema::hasColumn('categories', 'description')) {
            $data['description'] = ProductCatalog::CATEGORY_DESCRIPTIONS[$name]
                ?? ProductCatalog::descriptionFor($name, $name);
        }

        if (Schema::hasColumn('categories', 'status')) {
            $data['status'] = true;
        }

        return Category::create($data);
    }

    private function productFor(array $item): ?Product
    {
        return Product::query()
            ->when(Schema::hasColumn('products', 'sku'), fn ($query) => $query->orWhere('sku', $item['sku']))
            ->when(Schema::hasColumn('products', 'slug'), fn ($query) => $query->orWhere('slug', $item['slug']))
            ->orWhere('name', $item['name'])
            ->first();
    }

    private function productData(array $item, Category $category, int $index): array
    {
        $data = [
            'category_id' => $category->id,
            'name' => $item['name'],
        ];

        if (Schema::hasColumn('products', 'slug')) {
            $data['slug'] = $item['slug'];
        }

        if (Schema::hasColumn('products', 'sku')) {
            $data['sku'] = $item['sku'];
        }

        if (Schema::hasColumn('products', 'price')) {
            $data['price'] = $item['price'];
        }

        if (Schema::hasColumn('products', 'description')) {
            $data['description'] = $item['description'];
        }

        if (Schema::hasColumn('products', 'image')) {
            $data['image'] = ProductImage::forProduct(null, $item['slug'], 700);
        }

        if (Schema::hasColumn('products', 'stock')) {
            $data['stock'] = 40 + (($index * 7) % 45);
        }

        if (Schema::hasColumn('products', 'status')) {
            $data['status'] = true;
        }

        return $data;
    }

    private function seedSizePrices(Product $product, int $basePrice): void
    {
        if (! Schema::hasTable('sizes') || ! Schema::hasTable('product_sizes')) {
            return;
        }

        foreach (self::SIZE_PRICE_EXTRAS as $sizeName => $extra) {
            $size = Size::firstOrCreate(
                ['name' => $sizeName],
                ['multiplier' => $this->sizeMultiplier($sizeName)]
            );

            ProductSize::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                ],
                [
                    'price' => $basePrice + $extra,
                ]
            );
        }
    }

    private function sizeMultiplier(string $sizeName): float
    {
        return match ($sizeName) {
            'S' => 1.00,
            'M' => 1.10,
            'L' => 1.20,
            'XL' => 1.30,
            'XXL' => 1.40,
            default => 1.00,
        };
    }
}
