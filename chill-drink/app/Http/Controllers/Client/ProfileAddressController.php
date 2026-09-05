<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProfileController;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileAddressController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // If user has address in profile but none in address book, auto-create default address
        if ($user->addresses()->count() === 0 && ($user->address || $user->area)) {
            $user->addresses()->create([
                'label' => 'Nhà riêng',
                'receiver_name' => $user->name ?: 'Người nhận',
                'phone' => $user->phone ?: '',
                'detail' => ProfileController::cleanAddressString($user->address) ?: ProfileController::cleanAddressString($user->area),
                'ward' => null,
                'district' => null,
                'province' => ProfileController::cleanAddressString($user->area),
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'is_default' => true,
            ]);
        }

        $addresses = $user->addresses()->orderByDesc('is_default')->latest('id')->get();
        return view('profile.addresses.index', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $house = trim((string) $request->input('house_number', ''));
        $street = trim((string) $request->input('street', ''));
        if ($house !== '' || $street !== '') {
            $detail = trim(collect([$house, $street])->filter()->implode(' '));
        } else {
            $detail = trim((string) $request->input('detail', ''));
            if ($detail === '' && $request->filled('area')) {
                $detail = trim((string) $request->input('area'));
            }
        }
        $request->merge(['detail' => $detail]);

        if ($request->filled('area')) {
            $request->merge([
                'province' => $request->input('area'),
                'ward' => null,
                'district' => null,
            ]);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'receiver_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'detail' => 'required|string|max:500',
            'house_number' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['detail'] = ProfileController::cleanAddressString($validated['detail']);
        if (isset($validated['ward'])) {
            $validated['ward'] = ProfileController::cleanAddressString($validated['ward']);
        }
        if (isset($validated['district'])) {
            $validated['district'] = ProfileController::cleanAddressString($validated['district']);
        }
        if (isset($validated['province'])) {
            $validated['province'] = ProfileController::cleanAddressString($validated['province']);
        }

        $user = auth()->user();
        $isDefault = $request->boolean('is_default');

        if ($isDefault || $user->addresses()->count() === 0) {
            $user->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        } else {
            $validated['is_default'] = false;
        }

        $addressData = collect($validated)->except(['house_number', 'street', 'area'])->all();
        $address = $user->addresses()->create($addressData);

        if ($validated['is_default']) {
            $this->syncUserProfileAddress($user, $address);
        }

        return redirect()->route('profile.addresses.index')->with('success', 'Thêm địa chỉ giao hàng thành công!');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $house = trim((string) $request->input('house_number', ''));
        $street = trim((string) $request->input('street', ''));
        if ($house !== '' || $street !== '') {
            $detail = trim(collect([$house, $street])->filter()->implode(' '));
        } else {
            $detail = trim((string) $request->input('detail', ''));
            if ($detail === '' && $request->filled('area')) {
                $detail = trim((string) $request->input('area'));
            }
        }
        $request->merge(['detail' => $detail]);

        if ($request->filled('area')) {
            $request->merge([
                'province' => $request->input('area'),
                'ward' => null,
                'district' => null,
            ]);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'receiver_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'detail' => 'required|string|max:500',
            'house_number' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['detail'] = ProfileController::cleanAddressString($validated['detail']);
        if (isset($validated['ward'])) {
            $validated['ward'] = ProfileController::cleanAddressString($validated['ward']);
        }
        if (isset($validated['district'])) {
            $validated['district'] = ProfileController::cleanAddressString($validated['district']);
        }
        if (isset($validated['province'])) {
            $validated['province'] = ProfileController::cleanAddressString($validated['province']);
        }

        $user = auth()->user();
        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $addressData = collect($validated)->except(['house_number', 'street', 'area'])->all();
        $address->update($addressData);

        if ($address->is_default) {
            $this->syncUserProfileAddress($user, $address);
        }

        return redirect()->route('profile.addresses.index')->with('success', 'Cập nhật địa chỉ thành công!');
    }

    public function destroy(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $first = auth()->user()->addresses()->first();
            if ($first) {
                $first->update(['is_default' => true]);
                $this->syncUserProfileAddress(auth()->user(), $first);
            }
        }

        return redirect()->route('profile.addresses.index')->with('success', 'Đã xóa địa chỉ!');
    }

    public function setDefault(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $user = auth()->user();
        $user->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        $this->syncUserProfileAddress($user, $address);

        return redirect()->route('profile.addresses.index')->with('success', 'Đã đặt làm địa chỉ mặc định!');
    }

    protected function syncUserProfileAddress($user, Address $address): void
    {
        $area = collect([$address->ward, $address->district, $address->province])
            ->filter()
            ->implode(', ');

        $user->update([
            'address' => $address->detail ?: $user->address,
            'area' => $area ?: ($user->area ?? ''),
            'latitude' => $address->latitude ?? $user->latitude,
            'longitude' => $address->longitude ?? $user->longitude,
            'phone' => $address->phone ?: $user->phone,
        ]);
    }
}
