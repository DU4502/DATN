<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileAddressController extends Controller
{
    public function index(): View
    {
        $addresses = auth()->user()->addresses()->orderByDesc('is_default')->latest('id')->get();
        return view('profile.addresses.index', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'receiver_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'detail' => 'required|string|max:500',
            'ward' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $isDefault = $request->boolean('is_default');

        if ($isDefault || $user->addresses()->count() === 0) {
            $user->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        } else {
            $validated['is_default'] = false;
        }

        $user->addresses()->create($validated);

        return redirect()->route('profile.addresses.index')->with('success', 'Thêm địa chỉ giao hàng thành công!');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'receiver_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'detail' => 'required|string|max:500',
            'ward' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $address->update($validated);

        return redirect()->route('profile.addresses.index')->with('success', 'Cập nhật địa chỉ thành công!');
    }

    public function destroy(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $first = auth()->user()->addresses()->first();
            $first?->update(['is_default' => true]);
        }

        return redirect()->route('profile.addresses.index')->with('success', 'Đã xóa địa chỉ!');
    }

    public function setDefault(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        auth()->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('profile.addresses.index')->with('success', 'Đã đặt làm địa chỉ mặc định!');
    }
}
