<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
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
        $product = new Product([
            'price' => 0,
            'stock' => 0,
            'status' => true,
        ]);

        return view('admin.products.create', compact('categories', 'product'));
    }

    public function store(Request $request)
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

    public function update(Request $request, string $product)
    {
        $product = $this->findProduct($product);
        $product->update($this->productData($request));

        return redirect()
            ->route('admin.products.show', $product->id)
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
        ];
    }

    private function findProduct(string $product): Product
    {
        return Product::query()
            ->whereKey($product)
            ->orWhere('slug', $product)
            ->firstOrFail();
    }
}
