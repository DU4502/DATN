<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'availability' => trim((string) $request->query('availability', '')),
            'sort' => trim((string) $request->query('sort', 'latest')),
        ];

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $categoryIds = $categories->pluck('id')->map(fn($id) => (string) $id)->all();

        $user = $request->user();
        $managedBranch = $user->isSuperAdmin() ? null : $user->branch;
        $branches = $user->isSuperAdmin()
            ? Branch::query()->where('status', true)->orderBy('name')->get()
            : collect([$managedBranch])->filter();

        $productsQuery = Product::query()
            ->with(['category', 'branchStatuses.branch'])
            ->when(! $user->isSuperAdmin(), function ($query) use ($managedBranch) {
                $query->whereHas('branchStatuses', fn ($statusQuery) => $statusQuery
                    ->where('branch_id', $managedBranch?->id));
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($builder) use ($filters) {
                    $this->applyProductSearchKeyword($builder, $filters['q']);
                });
            })
            ->when(in_array($filters['category'], $categoryIds, true), function ($query) use ($filters) {
                $query->where('category_id', (int) $filters['category']);
            })
            ->when($filters['status'] === 'active', fn($query) => $query->where('status', true))
            ->when($filters['status'] === 'hidden', fn($query) => $query->where('status', false))
            ->when(in_array($filters['availability'], ['available', 'out_of_stock', 'unassigned'], true), function ($query) use ($filters, $managedBranch, $request) {
                $branchId = $managedBranch?->id ?: (int) $request->query('branch_id');

                if (! $branchId) {
                    return;
                }

                if ($filters['availability'] === 'unassigned') {
                    $query->whereDoesntHave('branchStatuses', fn ($statusQuery) => $statusQuery->where('branch_id', $branchId));
                    return;
                }

                $query->whereHas('branchStatuses', fn ($statusQuery) => $statusQuery
                    ->where('branch_id', $branchId)
                    ->where('is_available', $filters['availability'] === 'available'));
            });

        match ($filters['sort']) {
            'name' => $productsQuery->orderBy('name'),
            'price_asc' => $productsQuery->orderBy('price'),
            'price_desc' => $productsQuery->orderByDesc('price'),
            default => $productsQuery->latest(),
        };

        $products = $productsQuery->paginate(12)->withQueryString();
        $totalProducts = Product::count();
        $unavailableProducts = $managedBranch
            ? Product::query()->whereHas('branchStatuses', fn ($query) => $query
                ->where('branch_id', $managedBranch->id)
                ->where('is_available', false))->count()
            : 0;
        $activeFiltersCount = collect($filters)
            ->filter(fn($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))
            ->count();
        $quickCategories = $categories
            ->filter(fn($category) => in_array($category->name, ['Trà Sữa', 'Cà Phê', 'Nước Ép'], true))
            ->values();

        return view('admin.products.index', compact(
            'products',
            'categories',
            'quickCategories',
            'filters',
            'totalProducts',
            'unavailableProducts',
            'branches',
            'managedBranch',
            'activeFiltersCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        foreach (['S', 'M', 'L'] as $s) {
            \App\Models\Size::firstOrCreate(['name' => $s]);
        }

        $allSizes = \App\Models\Size::all()->sortBy(function ($size) {
            return match (strtoupper(trim($size->name))) {
                'S' => 1,
                'M' => 2,
                'L' => 3,
                default => 4
            };
        })->values();
        $allToppings = \App\Models\Topping::where('status', true)->get();
        $branches = auth()->user()->isSuperAdmin()
            ? Branch::query()->where('status', true)->orderBy('name')->get()
            : Branch::query()->whereKey(auth()->user()->branch_id)->where('status', true)->get();

        return view('admin.products.create', compact('categories', 'allSizes', 'allToppings', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug((string) $request->input('name', '')),
        ]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->whereNull('deleted_at'),
            ],
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'branch_statuses' => ['nullable', 'array'],
            'branch_statuses.*' => ['required', 'boolean'],
        ]);

        if ($sizeError = $this->validateSizePrices($request)) {
            return back()->withInput()->withErrors(['sizes' => $sizeError]);
        }

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
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

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            if ($request->boolean('branch_statuses_submitted')) {
                app(ProductAvailabilityService::class)->syncProduct(
                    $product,
                    $this->authorizedBranchStatuses($request)
                );
            }

            $this->syncProductSizes($product, $request);

            if ($request->has('toppings')) {
                $product->toppings()->sync($request->input('toppings'));
            }

            return $product;
        });

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with([
            'category',
            'branchStatuses' => fn ($query) => $query
                ->with('branch')
                ->when(! auth()->user()->isSuperAdmin(), fn ($statusQuery) => $statusQuery->where('branch_id', auth()->user()->branch_id)),
        ])
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

        foreach (['S', 'M', 'L'] as $s) {
            \App\Models\Size::firstOrCreate(['name' => $s]);
        }

        $allSizes = \App\Models\Size::all()->sortBy(function ($size) {
            return match (strtoupper(trim($size->name))) {
                'S' => 1,
                'M' => 2,
                'L' => 3,
                default => 4
            };
        })->values();
        $allToppings = \App\Models\Topping::all();
        $selectedSizes = $product->sizes()->pluck('product_sizes.price', 'sizes.id')->toArray();
        $selectedToppings = $product->toppings()->pluck('toppings.id')->toArray();
        $branches = auth()->user()->isSuperAdmin()
            ? Branch::query()->where('status', true)->orderBy('name')->get()
            : Branch::query()->whereKey(auth()->user()->branch_id)->where('status', true)->get();
        $branchStatuses = $product->branchStatuses()->pluck('is_available', 'branch_id')->all();

        return view('admin.products.edit', compact('product', 'categories', 'allSizes', 'allToppings', 'selectedSizes', 'selectedToppings', 'branches', 'branchStatuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = $this->findProduct($id);

        $request->merge([
            'slug' => Str::slug((string) $request->input('name', '')),
        ]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')
                    ->ignore($product->id)
                    ->whereNull('deleted_at'),
            ],
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:10240',
            'remove_gallery_images' => 'nullable|array',
            'remove_gallery_images.*' => 'string',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'branch_statuses' => ['nullable', 'array'],
            'branch_statuses.*' => ['required', 'boolean'],
        ]);

        if ($sizeError = $this->validateSizePrices($request)) {
            return back()->withInput()->withErrors(['sizes' => $sizeError]);
        }

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
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

        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            if ($request->boolean('branch_statuses_submitted')) {
                app(ProductAvailabilityService::class)->syncProduct(
                    $product,
                    $this->authorizedBranchStatuses($request)
                );
            }
            $this->syncProductSizes($product, $request);

            if ($request->has('toppings')) {
                $product->toppings()->sync($request->input('toppings'));
            } else {
                $product->toppings()->detach();
            }
        });

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

        if ($this->productHasUnfinishedOrders($product)) {
            return redirect()
                ->route('admin.products.index', $this->returnPageParameters(request()))
                ->with('error', 'Không thể xóa sản phẩm vì vẫn còn đơn hàng chưa hoàn thành.');
        }

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
        $categoryIds = $categories->pluck('id')->map(fn($id) => (string) $id)->all();

        $productsQuery = Product::onlyTrashed()
            ->with('category')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($builder) use ($filters) {
                    $this->applyProductSearchKeyword($builder, $filters['q']);
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
            ->filter(fn($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))
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

        if ($this->productHasUnfinishedOrders($product)) {
            return redirect()->route('admin.products.trash')
                ->with('error', 'Không thể xóa vĩnh viễn vì sản phẩm vẫn còn đơn hàng chưa hoàn thành.');
        }

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
            ->map(fn($file) => $file->store('products/gallery', 'public'))
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
            request()->only(['q', 'category', 'status', 'availability', 'branch_id', 'sort']),
            $page > 1 ? ['page' => $page] : []
        ), fn($value) => $value !== null && $value !== '');
    }

    private function applyProductSearchKeyword($query, string $keyword): void
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return;
        }

        $priceKeyword = preg_replace('/[^\d]/', '', $keyword) ?: '';

        $query->where(function ($builder) use ($keyword, $priceKeyword) {
            $builder
                ->where('name', 'like', '%' . $keyword . '%')
                ->orWhere('slug', 'like', '%' . $keyword . '%')
                ->orWhere('description', 'like', '%' . $keyword . '%')
                ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                    $categoryQuery->where('name', 'like', '%' . $keyword . '%');
                });

            if (Schema::hasColumn('products', 'sku')) {
                $builder->orWhere('sku', 'like', '%' . $keyword . '%');
            }

            if ($priceKeyword !== '') {
                $builder->orWhereRaw(
                    "REPLACE(REPLACE(CAST(price AS CHAR), '.', ''), ',', '') LIKE ?",
                    ['%' . $priceKeyword . '%']
                );
            }
        });
    }

    private function authorizedBranchStatuses(Request $request): array
    {
        $statuses = (array) $request->input('branch_statuses', []);
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $allowedBranchIds = Branch::query()->where('status', true)->pluck('id')->map(fn ($id) => (string) $id);

            return collect($statuses)->only($allowedBranchIds)->all();
        }

        if (! $user->branch_id) {
            abort(403, 'Tài khoản chưa được gán chi nhánh.');
        }

        return array_key_exists((string) $user->branch_id, $statuses)
            ? [$user->branch_id => $statuses[$user->branch_id]]
            : [];
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

    private function productHasUnfinishedOrders(Product $product): bool
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasTable('orders')) {
            return false;
        }

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $product->id);

        if (Schema::hasColumn('orders', 'status')) {
            $query->whereRaw(
                'LOWER(COALESCE(orders.status, "")) NOT IN (?, ?)',
                [OrderStatus::COMPLETED, OrderStatus::CANCELLED]
            );
        }

        return $query->exists();
    }

    private function syncProductSizes(Product $product, Request $request): void
    {
        $sizeData = [];
        $sizeS = \App\Models\Size::where('name', 'S')->first();
        if ($sizeS) {
            $sizeData[$sizeS->id] = ['price' => 0];
        }

        $sizesInput = (array) $request->input('sizes', []);
        $sizePricesInput = (array) $request->input('size_prices', []);

        foreach ($sizesInput as $sizeId) {
            $sizePrice = (int) ($sizePricesInput[$sizeId] ?? 0);
            $sizeData[$sizeId] = ['price' => $sizePrice];
        }

        $product->sizes()->sync($sizeData);
    }
}
