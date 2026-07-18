<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'status' => trim((string) $request->query('status', '')),
            'stock' => trim((string) $request->query('stock', '')),
            'sort' => trim((string) $request->query('sort', 'latest')),
        ];

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $categoryIds = $categories->pluck('id')->map(fn ($id) => (string) $id)->all();

        $productsQuery = Product::query()
            ->with('category')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];

                $query->where(function ($builder) use ($keyword) {
                    $builder
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('slug', 'like', '%'.$keyword.'%')
                        ->orWhere('description', 'like', '%'.$keyword.'%')
                        ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                            $categoryQuery->where('name', 'like', '%'.$keyword.'%');
                        });

                    if (Schema::hasColumn('products', 'sku')) {
                        $builder->orWhere('sku', 'like', '%'.$keyword.'%');
                    }
                });
            })
            ->when(in_array($filters['category'], $categoryIds, true), function ($query) use ($filters) {
                $query->where('category_id', (int) $filters['category']);
            })
            ->when($filters['status'] === 'active', fn ($query) => $query->where('status', true))
            ->when($filters['status'] === 'hidden', fn ($query) => $query->where('status', false))
            ->when($filters['stock'] === 'low', fn ($query) => $query->where('stock', '>', 0)->where('stock', '<=', 5))
            ->when($filters['stock'] === 'out', fn ($query) => $query->where('stock', '<=', 0));

        match ($filters['sort']) {
            'name' => $productsQuery->orderBy('name'),
            'price_asc' => $productsQuery->orderBy('price'),
            'price_desc' => $productsQuery->orderByDesc('price'),
            'stock_asc' => $productsQuery->orderBy('stock'),
            default => $productsQuery->latest(),
        };

        $products = $productsQuery->paginate(12)->withQueryString();
        $totalProducts = Product::count();
        $lowStockProducts = Product::where('stock', '>', 0)->where('stock', '<=', 5)->count();
        $activeFiltersCount = collect($filters)
            ->filter(fn ($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))
            ->count();
        $quickCategories = $categories
            ->filter(fn ($category) => in_array($category->name, ['Trà Sữa', 'Cà Phê', 'Nước Ép'], true))
            ->values();

        return view('admin.products.index', compact(
            'products',
            'categories',
            'quickCategories',
            'filters',
            'totalProducts',
            'lowStockProducts',
            'activeFiltersCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        if (\App\Models\Size::count() === 0) {
            foreach (['L', 'M', 'S'] as $s) {
                \App\Models\Size::firstOrCreate(['name' => $s]);
            }
        }

        $allSizes = \App\Models\Size::all()->sortBy(function($size) {
            return match(strtoupper(trim($size->name))) {
                'S' => 1,
                'M' => 2,
                'L' => 3,
                default => 4
            };
        })->values();
        $allToppings = \App\Models\Topping::where('status', true)->get();

        return view('admin.products.create', compact('categories', 'allSizes', 'allToppings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($sizeError = $this->validateSizePrices($request)) {
            return back()->withInput()->withErrors(['sizes' => $sizeError]);
        }

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock' => $validated['stock'] ?? 999,
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

        $product = Product::create($data);

        if ($request->has('sizes')) {
            $sizeData = [];
            foreach ($request->input('sizes') as $sizeId) {
                $sizePrice = $request->input("size_prices.{$sizeId}", 0);
                $sizeData[$sizeId] = ['price' => $sizePrice ?: 0];
            }
            $product->sizes()->sync($sizeData);
        }

        if ($request->has('toppings')) {
            $product->toppings()->sync($request->input('toppings'));
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')
            ->withCount('orderItems')
            ->whereKey($id)
            ->orWhere('slug', $id)
            ->firstOrFail();

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
        $product = $this->findProduct($id);
        $categories = Category::orderBy('name')->get();

        if (\App\Models\Size::count() === 0) {
            foreach (['L', 'M', 'S'] as $s) {
                \App\Models\Size::firstOrCreate(['name' => $s]);
            }
        }

        $allSizes = \App\Models\Size::all()->sortBy(function($size) {
            return match(strtoupper(trim($size->name))) {
                'S' => 1,
                'M' => 2,
                'L' => 3,
                default => 4
            };
        })->values();
        $allToppings = \App\Models\Topping::all();
        $selectedSizes = $product->sizes()->pluck('product_sizes.price', 'sizes.id')->toArray();
        $selectedToppings = $product->toppings()->pluck('toppings.id')->toArray();

        return view('admin.products.edit', compact('product', 'categories', 'allSizes', 'allToppings', 'selectedSizes', 'selectedToppings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = $this->findProduct($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'remove_gallery_images' => 'nullable|array',
            'remove_gallery_images.*' => 'string',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($sizeError = $this->validateSizePrices($request)) {
            return back()->withInput()->withErrors(['sizes' => $sizeError]);
        }

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'stock' => $validated['stock'] ?? 999,
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

        if ($request->has('sizes')) {
            $sizeData = [];
            foreach ($request->input('sizes') as $sizeId) {
                $sizePrice = $request->input("size_prices.{$sizeId}", 0);
                $sizeData[$sizeId] = ['price' => $sizePrice ?: 0];
            }
            $product->sizes()->sync($sizeData);
        } else {
            $product->sizes()->detach();
        }

        if ($request->has('toppings')) {
            $product->toppings()->sync($request->input('toppings'));
        } else {
            $product->toppings()->detach();
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = $this->findProduct($id);

        // Soft delete instead of permanent delete
        $product->delete();

        return redirect()
            ->route('admin.products.index', $this->returnPageParameters(request()))
            ->with('success', 'Sản phẩm đã được chuyển vào thùng rác!');
    }

    public function trash(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'sort' => trim((string) $request->query('sort', 'latest')),
        ];

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $categoryIds = $categories->pluck('id')->map(fn ($id) => (string) $id)->all();

        $productsQuery = Product::onlyTrashed()
            ->with('category')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];

                $query->where(function ($builder) use ($keyword) {
                    $builder
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('slug', 'like', '%'.$keyword.'%')
                        ->orWhere('description', 'like', '%'.$keyword.'%')
                        ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                            $categoryQuery->where('name', 'like', '%'.$keyword.'%');
                        });

                    if (Schema::hasColumn('products', 'sku')) {
                        $builder->orWhere('sku', 'like', '%'.$keyword.'%');
                    }
                });
            })
            ->when(in_array($filters['category'], $categoryIds, true), function ($query) use ($filters) {
                $query->where('category_id', (int) $filters['category']);
            });

        match ($filters['sort']) {
            'name' => $productsQuery->orderBy('name'),
            'price_asc' => $productsQuery->orderBy('price'),
            'price_desc' => $productsQuery->orderByDesc('price'),
            default => $productsQuery->latest('deleted_at'),
        };

        $products = $productsQuery->paginate(12)->withQueryString();
        $totalProducts = Product::onlyTrashed()->count();
        $activeFiltersCount = collect($filters)
            ->filter(fn ($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))
            ->count();

        return view('admin.products.trash', compact(
            'products',
            'categories',
            'filters',
            'totalProducts',
            'activeFiltersCount'
        ));
    }

    public function restore(string $id)
    {
        $product = Product::withTrashed()->whereKey($id)->orWhere('slug', $id)->firstOrFail();
        $product->restore();

        return redirect()
            ->route('admin.products.trash')
            ->with('success', 'Đã khôi phục sản phẩm thành công!');
    }

    public function forceDelete(string $id)
    {
        $product = Product::withTrashed()->whereKey($id)->orWhere('slug', $id)->firstOrFail();

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        foreach ($this->galleryImagePaths($product) as $image) {
            if (! str_starts_with($image, 'http')) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->forceDelete();

        return redirect()
            ->route('admin.products.trash')
            ->with('success', 'Đã xóa vĩnh viễn sản phẩm!');
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

        return array_filter(array_merge(
            request()->only(['q', 'category', 'status', 'stock', 'sort']),
            $page > 1 ? ['page' => $page] : []
        ), fn ($value) => $value !== null && $value !== '');
    }

    private function findProduct(string $id): Product
    {
        return Product::query()
            ->whereKey($id)
            ->orWhere('slug', $id)
            ->firstOrFail();
    }

    private function validateSizePrices(Request $request): ?string
    {
        $sizeM = \App\Models\Size::where('name', 'M')->first();
        $sizeL = \App\Models\Size::where('name', 'L')->first();

        $sizesInput = $request->input('sizes', []);
        $sizePricesInput = $request->input('size_prices', []);

        if ($sizeM && $sizeL && in_array($sizeM->id, $sizesInput) && in_array($sizeL->id, $sizesInput)) {
            $priceM = (int) ($sizePricesInput[$sizeM->id] ?? 0);
            $priceL = (int) ($sizePricesInput[$sizeL->id] ?? 0);

            if ($priceL <= $priceM) {
                $minL = $priceM + 1000;
                return 'Giá cộng thêm của Size L (' . number_format($priceL, 0, ',', '.') . 'đ) phải lớn hơn giá cộng thêm của Size M (' . number_format($priceM, 0, ',', '.') . 'đ) tối thiểu 1.000đ (tối thiểu ' . number_format($minL, 0, ',', '.') . 'đ).';
            }
        }

        return null;
    }
}
