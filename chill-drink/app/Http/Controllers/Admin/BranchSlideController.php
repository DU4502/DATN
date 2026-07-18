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
        $slides = $branch ? $branch->slides()->get() : collect();

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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096',
        ];

        if ($user->isSuperAdmin()) {
            $rules['branch_id'] = 'required|integer|exists:branches,id';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('slides', 'public');
            $validated['image'] = $path;
        }

        $validated['branch_id'] = $branchId;
        $validated['is_active'] = $request->has('is_active');

        BranchSlide::create($validated);

        return redirect()->back()->with('success', 'Thêm slide mới thành công!');
    }

    public function update(Request $request, BranchSlide $slide)
    {
        $user = auth()->user();

        // Check permission
        if (!$user->isSuperAdmin() && $slide->branch_id !== $user->branch_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa slide của chi nhánh khác.');
        }

        $branchId = $slide->branch_id;

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
                function ($attribute, $value, $fail) use ($branchId, $slide) {
                    $exists = BranchSlide::where('branch_id', $branchId)
                        ->where('sort_order', $value)
                        ->where('id', '!=', $slide->id)
                        ->exists();
                    if ($exists) {
                        $fail('Thứ tự hiển thị ' . $value . ' đã được sử dụng. Vui lòng chọn số khác.');
                    }
                },
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('image')) {
            // Delete old image if it's not a pre-seeded public image
            if ($slide->image && !str_starts_with($slide->image, '/')) {
                Storage::disk('public')->delete($slide->image);
            }
            $path = $request->file('image')->store('slides', 'public');
            $validated['image'] = $path;
        }

        $validated['is_active'] = $request->has('is_active');

        $slide->update($validated);

        return redirect()->back()->with('success', 'Cập nhật slide thành công!');
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

        return redirect()->back()->with('success', 'Slide đã được chuyển vào thùng rác!');
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
        $slides = $branch ? $branch->slides()->onlyTrashed()->get() : collect();

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
