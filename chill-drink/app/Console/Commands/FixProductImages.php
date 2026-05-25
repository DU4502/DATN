<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductImage;
use Illuminate\Console\Command;

class FixProductImages extends Command
{
    protected $signature = 'products:fix-images';

    protected $description = 'Cập nhật ảnh sản phẩm theo danh mục (sửa placeholder / ảnh trùng)';

    public function handle(): int
    {
        $updated = 0;

        Product::query()->with('category')->chunkById(50, function ($products) use (&$updated) {
            foreach ($products as $product) {
                $product->update([
                    'image' => ProductImage::forProduct($product->id, $product->slug, 700),
                ]);

                $updated++;
            }
        });

        $this->info("Đã cập nhật {$updated} sản phẩm.");

        return self::SUCCESS;
    }
}
