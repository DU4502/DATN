<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
<<<<<<< Updated upstream
use Illuminate\Http\Request;
=======
use App\Models\Size;
use Illuminate\Support\Collection;
>>>>>>> Stashed changes
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(12);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $sizes = $this->availableSizes();
        $product = new Product([
            'price' => 0,
            'stock' => 0,
            'status' => true,
        ]);
        $sizePriceMap = [];

        return view('admin.products.create', compact('categories', 'product', 'sizes', 'sizePriceMap'));
    }

    public function store(Request $request)
    {
        $product = Product::create($this->productData($request));
        $this->syncSizePrices($product, $request->validated());

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    public function show(string $product)
    {
        $product = $this->findProduct($product)
            ->load(['category', 'sizes'])
            ->loadCount('orderItems');

        if (Schema::hasTable('reviews')) {
            $product->loadCount('reviews');
        } else {
            $product->setAttribute('reviews_count', 0);
        }

        return view('admin.products.show', compact('product'));
    }

    public function edit(string $product)
    {
        $product = $this->findProduct($product);
        $categories = Category::orderBy('name')->get();
        $sizes = $this->availableSizes();
        $sizePriceMap = $product->sizes()
            ->pluck('product_sizes.price', 'sizes.id')
            ->map(fn ($price) => (int) $price)
            ->all();

        return view('admin.products.edit', compact('product', 'categories', 'sizes', 'sizePriceMap'));
    }

    public function update(Request $request, string $product)
    {
        $product = $this->findProduct($product);
<<<<<<< Updated upstream
        $product->update($this->productData($request));
=======
        $validated = $request->validated();

        $product->update($this->productData($request, $product, $validated));
        $this->syncSizePrices($product, $validated);
>>>>>>> Stashed changes

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy(string $product)
    {
        $product = $this->findProduct($product);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Xóa sản phẩm thành công!');
    }

<<<<<<< Updated upstream
    private function productData(Request $request): array
    {
        $name = (string) $request->input('name', '');
        $slug = (string) $request->input('slug', '');

        return [
            'category_id' => $request->input('category_id') ?: null,
            'name' => $name,
            'slug' => Str::slug($slug ?: $name),
            'price' => (float) ($request->input('price') ?: 0),
            'description' => $request->input('description'),
            'stock' => (int) ($request->input('stock') ?: 0),
            'status' => $request->boolean('status'),
=======
    private function productData(ProductRequest $request, ?Product $product = null, ?array $validated = null): array
    {
        $validated ??= $request->validated();

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price' => (float) $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock' => isset($validated['stock'])
                ? (int) $validated['stock']
                : (int) ($product?->stock ?? 0),
            'status' => (bool) $validated['status'],
>>>>>>> Stashed changes
        ];
    }

    private function findProduct(string $product): Product
    {
        return Product::query()
            ->whereKey($product)
            ->orWhere('slug', $product)
            ->firstOrFail();
    }
<<<<<<< Updated upstream
=======

    private function availableSizes(): Collection
    {
        if (! Schema::hasTable('sizes')) {
            return collect();
        }

        return Size::query()->orderBy('id')->get();
    }

    private function syncSizePrices(Product $product, array $validated): void
    {
        if (! Schema::hasTable('sizes') || ! Schema::hasTable('product_sizes')) {
            return;
        }

        $basePrice = (float) ($validated['price'] ?? $product->price ?? 0);
        $inputSizePrices = $validated['size_prices'] ?? [];
        $sizes = $this->availableSizes();

        if ($sizes->isEmpty()) {
            return;
        }

        $syncData = [];

        foreach ($sizes as $size) {
            $rawPrice = $inputSizePrices[$size->id] ?? null;

            if ($rawPrice !== null && $rawPrice !== '') {
                $price = (float) $rawPrice;
            } else {
                $price = $this->defaultSizePrice((string) $size->name, $basePrice);
            }

            $syncData[$size->id] = [
                'price' => max(0, (int) round($price)),
            ];
        }

        $product->sizes()->sync($syncData);
    }

    private function defaultSizePrice(string $sizeName, float $basePrice): float
    {
        $normalized = mb_strtoupper(trim((string) preg_replace('/^size\s*/i', '', $sizeName)));

        return match ($normalized) {
            'M' => $basePrice + 5000,
            'L' => $basePrice + 10000,
            default => $basePrice,
        };
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if (! $imagePath || str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }
>>>>>>> Stashed changes
}
