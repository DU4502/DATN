<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductCatalog;
use Illuminate\Console\Command;

class FixProductDescriptions extends Command
{
    protected $signature = 'products:fix-descriptions';

    protected $description = 'Thay mô tả Lorem Ipsum bằng nội dung tiếng Việt';

    public function handle(): int
    {
        $updated = 0;

        Product::query()->with('category')->orderBy('id')->get()->each(function (Product $product, int $index) use (&$updated) {
            $catalog = ProductCatalog::ITEMS[$index] ?? null;

            if ($catalog) {
                $description = $catalog['description'];
            } elseif ($item = ProductCatalog::findBySku((string) $product->sku)) {
                $description = $item['description'];
            } elseif ($item = ProductCatalog::findByName($product->name)) {
                $description = $item['description'];
            } elseif (ProductCatalog::isPlaceholderDescription($product->description)) {
                $description = ProductCatalog::descriptionFor($product->name, $product->category?->name);
            } else {
                return;
            }

            if ($product->description !== $description) {
                $product->update(['description' => $description]);
                $updated++;
            }
        });

        $this->info("Đã cập nhật mô tả cho {$updated} sản phẩm.");

        return self::SUCCESS;
    }
}
