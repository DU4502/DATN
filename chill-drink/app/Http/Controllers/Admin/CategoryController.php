<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        
        // Query with search filter
        $categories = Category::withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                    
                    // Search by slug if exists
                    if (Schema::hasColumn('categories', 'slug')) {
                        $subQuery->orWhere('slug', 'like', '%' . $search . '%');
                    }
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation dữ liệu đầu vào (Đã bổ sung ràng buộc cho status và slug)
        $validated = $request->validate([
            'name'   => 'required|string|max:255|unique:categories,name',
            'slug'   => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'status' => 'nullable|in:0,1',
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['status'] = $request->boolean('status');

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        // Tạo danh mục (Dữ liệu status lấy trực tiếp từ giá trị select option)
        Category::create($validated);

        // Đồng bộ route name có tiền tố admin.
        return redirect()->route('admin.categories.index')
            ->with('success', 'Thêm danh mục thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        // Validation khi cập nhật (Bỏ qua trùng tên và trùng slug của chính bản ghi hiện tại)
        $validated = $request->validate([
            'name'   => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug'   => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'status' => 'nullable|in:0,1',
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['status'] = $request->boolean('status');

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        // Cập nhật toàn bộ mảng dữ liệu đã qua kiểm tra bảo mật
        $category->update($validated);

        // Đồng bộ định tuyến về trang quản trị admin
        return redirect()->route('admin.categories.index')
            ->with('success', 'Cập nhật danh mục thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Kiểm tra nếu danh mục đang có sản phẩm (kể cả trong thùng rác) thì không cho xóa
        if ($category->products()->withTrashed()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Không thể xóa! Danh mục này đang chứa sản phẩm (kể cả sản phẩm trong thùng rác). Vui lòng xóa hết sản phẩm trước.');
        }

        // Soft delete instead of permanent delete
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được chuyển vào thùng rác!');
    }

    public function trash(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        
        $categories = Category::onlyTrashed()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                    
                    if (Schema::hasColumn('categories', 'slug')) {
                        $subQuery->orWhere('slug', 'like', '%' . $search . '%');
                    }
                });
            })
            ->latest('deleted_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.categories.trash', compact('categories'));
    }

    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();

        return redirect()->route('admin.categories.trash')
            ->with('success', 'Đã khôi phục danh mục thành công!');
    }

    public function forceDelete($id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        // Kiểm tra nếu danh mục đang có sản phẩm thì không cho xóa vĩnh viễn
        if ($category->products()->withTrashed()->exists()) {
            return redirect()->route('admin.categories.trash')
                ->with('error', 'Không thể xóa vĩnh viễn! Danh mục này đang chứa sản phẩm (kể cả trong thùng rác).');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->forceDelete();

        return redirect()->route('admin.categories.trash')
            ->with('success', 'Đã xóa vĩnh viễn danh mục!');
    }
}
