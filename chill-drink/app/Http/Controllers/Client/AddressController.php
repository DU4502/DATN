<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $address = DB::transaction(function () use ($request, $data) {
            if ($data['is_default']) {
                Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
            }
            $address = Address::create($data + ['user_id' => $request->user()->id, 'created_at' => now()]);
            $this->syncDefaultToUser($request, $address);
            return $address;
        });

        return response()->json(['message' => 'Đã lưu địa chỉ.', 'address' => $this->payload($address)], 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $address, $data) {
            if ($data['is_default']) {
                Address::where('user_id', $request->user()->id)->whereKeyNot($address->id)->update(['is_default' => false]);
            }
            $address->update($data);
            $this->syncDefaultToUser($request, $address);
        });

        return response()->json(['message' => 'Đã cập nhật địa chỉ.', 'address' => $this->payload($address->fresh())]);
    }

    private function validated(Request $request): array
    {
        // Giao diện dùng camelCase, API/database dùng snake_case.
        if (! $request->has('is_default') && $request->has('isDefault')) {
            $request->merge(['is_default' => $request->boolean('isDefault')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'area' => ['required', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'is_default' => ['required', 'boolean'],
        ]);

        $street = trim((string) $validated['street']);
        $houseNumber = trim((string) ($validated['house_number'] ?? ''));

        return [
            'receiver_name' => $validated['name'], 'phone' => $validated['phone'],
            'province' => $validated['area'], 'detail' => trim($houseNumber.' '.$street),
            'label' => $validated['type'], 'is_default' => $validated['is_default'],
        ];
    }

    private function syncDefaultToUser(Request $request, Address $address): void
    {
        if (! $address->is_default) return;
        $request->user()->update([
            'name' => $address->receiver_name,
            'phone' => $address->phone,
            'address' => $address->detail,
            'area' => collect([$address->ward, $address->district, $address->province])->filter()->implode(', '),
        ]);
    }

    private function payload(Address $address): array
    {
        [$houseNumber, $street] = $this->splitHouseNumberAndStreet((string) ($address->detail ?? ''));

        return [
            'id' => (string) $address->id, 'name' => $address->receiver_name,
            'phone' => $address->phone, 'house_number' => $houseNumber, 'street' => $street,
            'area' => collect([$address->ward, $address->district, $address->province])->filter()->implode(', '),
            'type' => $address->label, 'isDefault' => (bool) $address->is_default,
        ];
    }

    private function splitHouseNumberAndStreet(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [null, null];
        }

        if (preg_match('/^(?:so\s*)?(\d+[a-zA-Z]?(?:\/\d+[a-zA-Z]?)*)(?:\s+|-|,)+(.*)$/iu', $value, $matches)) {
            $houseNumber = trim((string) ($matches[1] ?? ''));
            $street = trim((string) ($matches[2] ?? ''));

            return [
                $houseNumber !== '' ? $houseNumber : null,
                $street !== '' ? $street : null,
            ];
        }

        return [null, $value];
    }
}
