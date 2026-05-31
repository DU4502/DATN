<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
        $product = new Product([
            'price' => 0,
            'stock' => 0,
            'status' => true,
        ]);

        return view('admin.products.create', compact('categories', 'product'));
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($this->productData($request));

        return redirect()
            ->route('admin.products.show', $product->id)
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    public function show(string $product)
    {
        $product = $this->findProduct($product)
            ->load('category')
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

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, string $product)
    {
        $product = $this->findProduct($product);
        $product->update($this->productData($request, $product));

        return redirect()
            ->route('admin.products.show', $product->id)
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy(string $product)
    {
        $product = $this->findProduct($product);
        $this->deleteStoredImage($product->image);

        foreach ($this->galleryImagePaths($product) as $image) {
            if (! str_starts_with($image, 'http')) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Xóa sản phẩm thành công!');
    }

    private function productData(ProductRequest $request, ?Product $product = null): array
    {
        $validated = $request->validated();

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price' => (float) $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock' => (int) $validated['stock'],
            'status' => (bool) $validated['status'],
        ];

        if ($request->hasFile('image')) {
            if ($product) {
                $this->deleteStoredImage($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif (! empty($validated['image']) && is_string($validated['image'])) {
            $data['image'] = $validated['image'];
        }

        if (Schema::hasColumn('products', 'gallery_images')) {
            $galleryImages = $this->galleryImagePaths($product);
            $removeGalleryImages = array_filter((array) $request->input('remove_gallery_images', []));

            if ($product && ! empty($removeGalleryImages)) {
                foreach ($removeGalleryImages as $image) {
                    if (in_array($image, $galleryImages, true) && ! str_starts_with($image, 'http')) {
                        Storage::disk('public')->delete($image);
                    }
                }

                $galleryImages = array_values(array_diff($galleryImages, $removeGalleryImages));
            }

            $galleryImages = array_values(array_unique(array_merge(
                $galleryImages,
                $this->storeGalleryImages($request)
            )));

            $data['gallery_images'] = $galleryImages;
        }

        return $data;
    }

    private function findProduct(string $product): Product
    {
        return Product::query()
            ->whereKey($product)
            ->orWhere('slug', $product)
            ->firstOrFail();
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if (! $imagePath || str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }

    private function storeGalleryImages(Request $request): array
    {
        if (! $request->hasFile('gallery_images')) {
            return [];
        }

        return collect($request->file('gallery_images'))
            ->filter()
            ->map(fn ($file) => $file->store('products/gallery', 'public'))
            ->values()
            ->all();
    }

    private function galleryImagePaths(?Product $product): array
    {
        if (! $product) {
            return [];
        }

        $galleryImages = $product->getRawOriginal('gallery_images');
        $galleryImages = is_string($galleryImages) ? json_decode($galleryImages, true) : $galleryImages;

        return is_array($galleryImages)
            ? array_values(array_filter($galleryImages))
            : [];
    }
}