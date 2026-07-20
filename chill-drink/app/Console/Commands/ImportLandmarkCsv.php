<?php

namespace App\Console\Commands;

use App\Models\Landmark;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportLandmarkCsv extends Command
{
    protected $signature = 'landmarks:import-csv {path=tools/landmark-data/outputs/thanh_hoa_500_preview.csv}';

    protected $description = 'Import preserved landmark CSV rows as initial address/road capital.';

    public function handle(): int
    {
        if (! Schema::hasTable('landmarks')) {
            $this->error('Bảng landmarks chưa tồn tại. Chạy migrate trước.');

            return self::FAILURE;
        }

        $argumentPath = (string) $this->argument('path');
        $path = preg_match('/^[A-Za-z]:[\\\\\\/]/', $argumentPath) === 1
            ? $argumentPath
            : base_path($argumentPath);
        if (! is_file($path)) {
            $this->error("Không tìm thấy CSV: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Không mở được CSV: {$path}");

            return self::FAILURE;
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            $this->error('CSV không có header.');

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;
        $headers = array_map('trim', $headers);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            if (! is_array($data)) {
                $skipped++;
                continue;
            }

            $lat = $this->floatOrNull($data['latitude'] ?? null);
            $lng = $this->floatOrNull($data['longitude'] ?? null);
            if ($lat === null || $lng === null) {
                $skipped++;
                continue;
            }

            $name = $this->landmarkName($data);
            $address = trim((string) ($data['full_address'] ?? ''));
            $status = trim((string) ($data['collection_status'] ?? 'needs_manual_review'));
            $verificationLevel = in_array($status, ['google_verified', 'poi_verified'], true)
                ? $status
                : 'needs_manual_review';

            $duplicate = Landmark::query()
                ->where('name', $name)
                ->get()
                ->first(fn (Landmark $landmark) => $this->distanceMeters(
                    (float) $landmark->latitude,
                    (float) $landmark->longitude,
                    $lat,
                    $lng,
                ) <= 20);

            if ($duplicate) {
                $skipped++;
                continue;
            }

            Landmark::create([
                'name' => $name,
                'aliases' => array_values(array_filter([
                    $data['road_name'] ?? null,
                    $data['alley_name'] ?? null,
                    $data['parent_road_name'] ?? null,
                ])),
                'latitude' => $lat,
                'longitude' => $lng,
                'address_text' => $address !== '' ? Str::limit($address, 500, '') : null,
                'type' => $data['type'] ?? 'imported_sample',
                'source_type' => 'landmark_csv',
                'verification_level' => $verificationLevel,
                'successful_delivery_count' => 0,
                'status' => 'active',
                'verified_at' => in_array($verificationLevel, ['google_verified', 'poi_verified'], true) ? now() : null,
            ]);

            $imported++;
        }

        fclose($handle);

        $this->info("Đã import {$imported} landmark, bỏ qua {$skipped} dòng trùng/thiếu tọa độ.");

        return self::SUCCESS;
    }

    private function landmarkName(array $data): string
    {
        foreach (['poi_name', 'full_address', 'alley_name', 'road_name', 'parent_road_name'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '' && ! in_array(Str::lower($value), ['chế độ xem phố', 'che do xem pho'], true)) {
                return Str::limit($value, 255, '');
            }
        }

        return 'Landmark '.Str::random(8);
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
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
