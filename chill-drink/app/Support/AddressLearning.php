<?php

namespace App\Support;

use App\Models\Address;
use App\Models\AddressObservation;
use App\Models\Order;
use App\Models\VerifiedAddressPoint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class AddressLearning
{
    public function recordAddressBookEntry(Address $address, string $sourceType = 'address_book'): ?AddressObservation
    {
        return $this->record([
            'user_id' => $address->user_id,
            'address_id' => $address->id,
            'source_type' => $sourceType,
            'full_address' => $this->joinAddress([
                $address->detail,
                $address->ward,
                $address->district,
                $address->province,
            ]),
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'ward' => $address->ward,
            'district' => $address->district,
            'province' => $address->province,
            'status' => 'user_submitted',
            'confidence' => 0.30,
        ]);
    }

    public function recordOrderSubmitted(Order $order): ?AddressObservation
    {
        if (($order->fulfillment_type ?? 'delivery') !== 'delivery') {
            return null;
        }

        return $this->record([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'address_id' => $order->address_id ?? null,
            'source_type' => $order->isGuest() ? 'guest_order' : 'order',
            'full_address' => $this->orderAddressText($order),
            'latitude' => $order->shipping_latitude ?? null,
            'longitude' => $order->shipping_longitude ?? null,
            'status' => 'user_submitted',
            'confidence' => 0.35,
        ]);
    }

    public function markOrderDelivered(Order $order): void
    {
        if (($order->fulfillment_type ?? 'delivery') !== 'delivery') {
            return;
        }

        $observation = AddressObservation::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first() ?: $this->recordOrderSubmitted($order);

        if (! $observation) {
            return;
        }

        $observation->forceFill([
            'status' => 'delivery_success',
            'confidence' => max((float) $observation->confidence, 0.70),
            'delivered_at' => $order->delivered_at ?? now(),
        ])->save();

        $this->promoteObservation($observation);
    }

    /**
     * Ghi nhận điểm giao thực tế từ GPS shipper vào chính kho dữ liệu địa chỉ hiện có.
     * Không tạo map mới: điểm này dùng để tăng độ tin cậy cho địa chỉ khách hàng
     * và được AddressLookupController khai thác lại cho các lần đặt sau.
     */
    public function recordShipperDeliveryPoint(Order $order, float $latitude, float $longitude, ?float $accuracy = null): ?AddressObservation
    {
        if (($order->fulfillment_type ?? 'delivery') !== 'delivery') {
            return null;
        }

        $observation = $this->record([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'address_id' => $order->address_id ?? null,
            'source_type' => 'shipper_delivery_gps',
            'full_address' => $this->orderAddressText($order),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'delivery_success',
            'confidence' => $accuracy !== null && $accuracy <= 30 ? 0.90 : 0.80,
            'metadata' => [
                'gps_accuracy_m' => $accuracy,
                'captured_from' => 'shipper_delivery',
            ],
        ]);

        if (! $observation) {
            return null;
        }

        $observation->forceFill([
            'delivered_at' => $order->delivered_at ?? now(),
        ])->save();

        $this->promoteObservation($observation);

        return $observation;
    }

    public function record(array $payload): ?AddressObservation
    {
        if (! Schema::hasTable('address_observations')) {
            return null;
        }

        $fullAddress = trim((string) ($payload['full_address'] ?? ''));
        $lat = $this->nullableFloat($payload['latitude'] ?? null);
        $lng = $this->nullableFloat($payload['longitude'] ?? null);

        if ($fullAddress === '' && ($lat === null || $lng === null)) {
            return null;
        }

        [$houseNumber, $roadName] = $this->parseAddress($fullAddress);
        $normalizedKey = $this->normalizedKey(
            $houseNumber,
            $roadName,
            $payload['ward'] ?? null,
            $payload['district'] ?? null,
            $payload['province'] ?? null,
        );

        return AddressObservation::create([
            'user_id' => $payload['user_id'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'address_id' => $payload['address_id'] ?? null,
            'source_type' => $payload['source_type'] ?? 'user_input',
            'full_address' => Str::limit($fullAddress, 500, ''),
            'house_number' => $houseNumber,
            'road_name' => $roadName,
            'ward' => $payload['ward'] ?? null,
            'district' => $payload['district'] ?? null,
            'province' => $payload['province'] ?? null,
            'latitude' => $lat,
            'longitude' => $lng,
            'normalized_key' => $normalizedKey,
            'status' => $payload['status'] ?? 'user_submitted',
            'confidence' => $payload['confidence'] ?? 0.30,
            'metadata' => $payload['metadata'] ?? null,
        ]);
    }

    private function promoteObservation(AddressObservation $observation): void
    {
        if (! Schema::hasTable('verified_address_points') || $observation->latitude === null || $observation->longitude === null) {
            return;
        }

        $nearby = VerifiedAddressPoint::query()
            ->where('normalized_key', $observation->normalized_key)
            ->get()
            ->first(fn (VerifiedAddressPoint $point) => $this->distanceMeters(
                (float) $point->latitude,
                (float) $point->longitude,
                (float) $observation->latitude,
                (float) $observation->longitude,
            ) <= 30);

        if (! $nearby) {
            VerifiedAddressPoint::create([
                'full_address' => $observation->full_address,
                'house_number' => $observation->house_number,
                'road_name' => $observation->road_name,
                'ward' => $observation->ward,
                'district' => $observation->district,
                'province' => $observation->province,
                'latitude' => $observation->latitude,
                'longitude' => $observation->longitude,
                'normalized_key' => $observation->normalized_key,
                'observation_count' => 1,
                'successful_delivery_count' => $observation->status === 'delivery_success' ? 1 : 0,
                'verification_level' => $observation->status === 'delivery_success' ? 'delivery_success' : 'user_submitted',
                'confidence' => $observation->confidence,
                'last_observed_at' => now(),
                'verified_at' => $observation->status === 'delivery_success' ? now() : null,
            ]);

            return;
        }

        $successfulDeliveryCount = $nearby->successful_delivery_count + ($observation->status === 'delivery_success' ? 1 : 0);
        $observationCount = $nearby->observation_count + 1;

        $nearby->forceFill([
            'full_address' => $this->preferAddress($nearby->full_address, $observation->full_address),
            'latitude' => (($nearby->latitude * $nearby->observation_count) + $observation->latitude) / $observationCount,
            'longitude' => (($nearby->longitude * $nearby->observation_count) + $observation->longitude) / $observationCount,
            'observation_count' => $observationCount,
            'successful_delivery_count' => $successfulDeliveryCount,
            'verification_level' => $successfulDeliveryCount >= 3 ? 'high_confidence' : 'delivery_success',
            'confidence' => min(0.98, max((float) $nearby->confidence, (float) $observation->confidence) + 0.05),
            'last_observed_at' => now(),
            'verified_at' => $successfulDeliveryCount > 0 ? ($nearby->verified_at ?? now()) : null,
        ])->save();
    }

    private function orderAddressText(Order $order): string
    {
        if (Schema::hasColumn('orders', 'shipping_address_text') && filled($order->shipping_address_text)) {
            return (string) $order->shipping_address_text;
        }

        return $order->getShippingAddress();
    }

    private function parseAddress(string $address): array
    {
        $firstPart = trim(Str::before($address, ','));

        if (preg_match('/^(?:so\s*)?(\d+[a-zA-Z]?(?:\/\d+[a-zA-Z]?)*)(?:\s+|-|,)+(.*)$/iu', $firstPart, $matches)) {
            return [trim($matches[1]), $this->normalizeRoadName($matches[2])];
        }

        return [null, $this->normalizeRoadName($firstPart)];
    }

    private function normalizeRoadName(?string $value): ?string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = preg_replace('/^(duong|đường|pho|phố|ng\.?|ngo|ngõ|hem|hẻm)\s+/iu', '', $value);

        return $value !== '' ? Str::title($value) : null;
    }

    private function normalizedKey(?string $houseNumber, ?string $roadName, ?string $ward, ?string $district, ?string $province): string
    {
        $parts = [$houseNumber, $roadName, $ward, $district, $province];

        return Str::slug(implode(' ', array_filter(array_map(fn ($part) => Str::lower(trim((string) $part)), $parts))));
    }

    private function joinAddress(array $parts): string
    {
        return trim(collect($parts)->filter(fn ($part) => filled($part))->implode(', '));
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function preferAddress(string $current, string $candidate): string
    {
        return mb_strlen($candidate) > mb_strlen($current) ? $candidate : $current;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
