<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Lấy danh sách kèm số lượng sản phẩm, phân trang 12 mục
        $categories = Category::withCount('products')->latest()->paginate(12);

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
            'slug'   => 'required|string|max:255|unique:categories,slug',
            'status' => 'required|in:0,1',
        ]);

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
            'slug'   => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'status' => 'required|in:0,1',
        ]);

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
        // Kiểm tra nếu danh mục đang có sản phẩm thì không cho xóa
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Không thể xóa! Danh mục này đang chứa sản phẩm.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Xóa danh mục thành công!');
    }
}
