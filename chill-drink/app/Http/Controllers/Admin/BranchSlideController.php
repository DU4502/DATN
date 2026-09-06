<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchSlideController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $branches = collect();
        $selectedBranchId = null;

        if ($user->isSuperAdmin()) {
            $branches = Branch::all();
            $selectedBranchId = $request->input('branch_id', $branches->first()?->id);
        } else {
            $selectedBranchId = $user->branch_id;
            if (!$selectedBranchId) {
                // fallback to first branch if missing branch_id
                $firstBranch = Branch::first();
                $selectedBranchId = $firstBranch?->id;
            }
        }

        $branch = Branch::find($selectedBranchId);
        $slides = $branch ? $branch->slides()->paginate(10)->withQueryString() : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        return view('admin.slides.index', compact('branches', 'selectedBranchId', 'branch', 'slides'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $branchId = $user->isSuperAdmin() ? $request->input('branch_id') : $user->branch_id;

        $rules = [
            'product_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'price' => 'required|string|max:50',
            'description' => 'required|string|max:1000',
            'bg_color' => 'required|string|max:20',
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($branchId) {
                    $exists = BranchSlide::where('branch_id', $branchId)
                        ->where('sort_order', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Thứ tự hiển thị ' . $value . ' đã được sử dụng. Vui lòng chọn số khác.');
                    }
                },
            ],
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        if ($user->isSuperAdmin()) {
            $rules['branch_id'] = 'required|integer|exists:branches,id';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('slides', 'public');
        }

        $validated['branch_id'] = $branchId;
        $validated['status'] = $request->boolean('status', true);

        BranchSlide::create($validated);

        return redirect()->route('admin.slides.index', ['branch_id' => $branchId])
            ->with('success', 'Thêm mới slide banner thành công!');
    }

    public function update(Request $request, BranchSlide $slide)
    {
        $user = auth()->user();

        // Check permission
        if (!$user->isSuperAdmin() && $slide->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa slide của chi nhánh khác.');
        }

        $rules = [
            'product_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'price' => 'required|string|max:50',
            'description' => 'required|string|max:1000',
            'bg_color' => 'required|string|max:20',
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($slide) {
                    $exists = BranchSlide::where('branch_id', $slide->branch_id)
                        ->where('sort_order', $value)
                        ->where('id', '!=', $slide->id)
                        ->exists();
                    if ($exists) {
                        $fail('Thứ tự hiển thị ' . $value . ' đã được sử dụng.');
                    }
                },
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('image')) {
            if ($slide->image) {
                Storage::disk('public')->delete($slide->image);
            }
            $validated['image'] = $request->file('image')->store('slides', 'public');
        }

        $validated['status'] = $request->boolean('status', true);

        $slide->update($validated);

        return redirect()->route('admin.slides.index', ['branch_id' => $slide->branch_id])
            ->with('success', 'Cập nhật slide banner thành công!');
    }

    public function destroy(BranchSlide $slide)
    {
        $user = auth()->user();

        // Check permission
        if (!$user->isSuperAdmin() && $slide->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền xóa slide của chi nhánh khác.');
        }

        // Soft delete instead of permanent delete
        $slide->delete();

        return redirect()->back()->with('success', 'Đã xóa slide banner thành công!');
    }

    public function trash(Request $request)
    {
        $user = auth()->user();
        $branches = collect();
        $selectedBranchId = null;

        if ($user->isSuperAdmin()) {
            $branches = Branch::all();
            $selectedBranchId = $request->input('branch_id', $branches->first()?->id);
        } else {
            $selectedBranchId = $user->branch_id;
            if (!$selectedBranchId) {
                $firstBranch = Branch::first();
                $selectedBranchId = $firstBranch?->id;
            }
        }

        $branch = Branch::find($selectedBranchId);
        $slides = $branch ? $branch->slides()->onlyTrashed()->paginate(10)->withQueryString() : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        return view('admin.slides.trash', compact('branches', 'selectedBranchId', 'branch', 'slides'));
    }

    public function restore($id)
    {
        $user = auth()->user();
        
        $slide = BranchSlide::withTrashed()->findOrFail($id);

        // Check permission
        if (!$user->isSuperAdmin() && $slide->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền khôi phục slide của chi nhánh khác.');
        }

        $sortOrderInUse = BranchSlide::query()
            ->where('branch_id', $slide->branch_id)
            ->where('sort_order', $slide->sort_order)
            ->where('id', '!=', $slide->id)
            ->exists();

        if ($sortOrderInUse) {
            return redirect()->back()->with(
                'error',
                "Không thể khôi phục! Thứ tự hiển thị {$slide->sort_order} đang được sử dụng bởi slide khác."
            );
        }

        $slide->restore();

        return redirect()->back()->with('success', 'Đã khôi phục slide thành công!');
    }

    public function forceDelete($id)
    {
        $user = auth()->user();
        
        $slide = BranchSlide::withTrashed()->findOrFail($id);

        // Check permission
        if (!$user->isSuperAdmin() && $slide->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền xóa vĩnh viễn slide của chi nhánh khác.');
        }

        // Delete image if not seeded
        if ($slide->image && !str_starts_with($slide->image, '/')) {
            Storage::disk('public')->delete($slide->image);
        }

        $slide->forceDelete();

        return redirect()->back()->with('success', 'Đã xóa vĩnh viễn slide!');
    }
}
