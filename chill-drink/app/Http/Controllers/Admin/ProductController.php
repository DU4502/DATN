<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
    public function store(ProductRequest $request)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('image')) {
            $data['image'] = (string) $request->input('image');
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.show', $product)->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('category')->loadCount('orderItems');

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
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('image')) {
            $data['image'] = (string) $request->input('image');
        }

        $product->update($data);

        return redirect()->route('admin.products.show', $product)->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->deleteStoredImage($product->image);

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Xóa sản phẩm thành công!');
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if (! $imagePath || str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }
}
