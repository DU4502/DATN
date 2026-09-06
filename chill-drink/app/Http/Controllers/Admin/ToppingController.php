<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToppingController extends Controller
{
    public function index(Request $request)
    {
        $query = Topping::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . trim($request->search) . '%');
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        $toppings = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $totalToppings = Topping::count();
        $activeToppings = Topping::where('status', true)->count();

        return view('admin.toppings.index', compact('toppings', 'totalToppings', 'activeToppings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:toppings,name',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->boolean('status');

        Topping::create($validated);

        return redirect()->route('admin.toppings.index')->with('success', 'Thêm mới Topping thành công!');
    }

    public function update(Request $request, Topping $topping)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:toppings,name,' . $topping->id,
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->boolean('status');

        $topping->update($validated);

        return redirect()->route('admin.toppings.index')->with('success', 'Cập nhật Topping thành công!');
    }

    public function destroy(Topping $topping)
    {
        $hasOrderHistory = DB::table('order_item_toppings')
            ->where('topping_id', $topping->id)
            ->exists();

        if ($hasOrderHistory) {
            return redirect()->route('admin.toppings.index')
                ->with('error', 'Không thể xóa Topping đã xuất hiện trong lịch sử đơn hàng. Vui lòng chuyển sang ngưng bán.');
        }

        $topping->products()->detach();
        $topping->delete();

        return redirect()->route('admin.toppings.index')->with('success', 'Xóa Topping thành công!');
    }
}
