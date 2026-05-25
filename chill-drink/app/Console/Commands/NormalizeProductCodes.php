<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductCatalog;
use App\Support\ProductImage;
use Illuminate\Console\Command;

class NormalizeProductCodes extends Command
{
    protected $signature = 'products:normalize-codes';

    protected $description = 'Chuẩn hóa tên, mã SKU, slug và mô tả sản phẩm theo catalog';

    public function handle(): int
    {
        $updated = 0;
        $catalog = ProductCatalog::ITEMS;

        Product::query()->orderBy('id')->get()->each(function (Product $product, int $index) {
            $product->update([
                'sku' => 'TMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'slug' => 'tmp-'.$product->id.'-'.time(),
            ]);
        });

        Product::query()->with('category')->orderBy('id')->get()->each(function (Product $product, int $index) use ($catalog, &$updated) {
            if (! isset($catalog[$index])) {
                $codes = ProductCatalog::codesFor(
                    $product->name,
                    $product->category?->name,
                    $product->id,
                );

                $product->update([
                    'sku' => $codes['sku'],
                    'slug' => $codes['slug'],
                    'description' => $codes['description'],
                ]);
                $updated++;

                return;
            }

            $item = $catalog[$index];
            $category = $product->category;

            $product->update([
                'name' => $item['name'],
                'sku' => $item['sku'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'price' => $item['price'],
                'image' => ProductImage::forProduct($product->id, $item['slug'], 700),
            ]);

            if ($category && mb_strtolower($category->name) !== mb_strtolower($item['category'])) {
                $matched = \App\Models\Category::where('name', $item['category'])->first();
                if ($matched) {
                    $product->update(['category_id' => $matched->id]);
                }
            }

            $updated++;
        });

        $this->info("Đã chuẩn hóa {$updated} sản phẩm.");

        return self::SUCCESS;
    }
}
