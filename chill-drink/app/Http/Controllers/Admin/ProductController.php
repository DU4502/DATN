<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(12);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'gallery_images' => 'nullable|array|max:6',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock' => $validated['stock'],
            'status' => $validated['status'] ?? true,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        if (Schema::hasColumn('products', 'gallery_images')) {
            $galleryImages = $this->storeGalleryImages($request);

            if (! empty($galleryImages)) {
                $data['gallery_images'] = $galleryImages;
            }
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')
            ->withCount('orderItems')
            ->findOrFail($id);

        if (Schema::hasTable('reviews')) {
            $product->loadCount('reviews');
        } else {
            $product->setAttribute('reviews_count', 0);
        }

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'gallery_images' => 'nullable|array|max:6',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_gallery_images' => 'nullable|array',
            'remove_gallery_images.*' => 'string',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock' => $validated['stock'],
            'status' => $validated['status'] ?? true,
        ];

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        if (Schema::hasColumn('products', 'gallery_images')) {
            $galleryImages = $this->galleryImagePaths($product);
            $removeGalleryImages = array_filter((array) $request->input('remove_gallery_images', []));

            if (! empty($removeGalleryImages)) {
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

        $product->update($data);

        return redirect()
            ->route('admin.products.index', $this->returnPageParameters($request))
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        foreach ($this->galleryImagePaths($product) as $image) {
            if (! str_starts_with($image, 'http')) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index', $this->returnPageParameters(request()))
            ->with('success', 'Xóa sản phẩm thành công!');
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

    private function galleryImagePaths(Product $product): array
    {
        $galleryImages = $product->getRawOriginal('gallery_images');
        $galleryImages = is_string($galleryImages) ? json_decode($galleryImages, true) : $galleryImages;

        return is_array($galleryImages)
            ? array_values(array_filter($galleryImages))
            : [];
    }

    private function returnPageParameters(Request $request): array
    {
        $page = (int) ($request->input('return_page') ?: $request->query('page'));

        return $page > 1 ? ['page' => $page] : [];
    }
}
