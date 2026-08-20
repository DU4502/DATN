<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        $this->normalizeDecimalColumn('coupons', 'max_discount', true, null);
        $this->normalizeDecimalColumn('coupons', 'min_order', false, 0);
        $this->normalizeDecimalColumn('coupons', 'value', false, 0);

        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('max_discount', 12, 2)->nullable()->change();
            $table->decimal('min_order', 12, 2)->default(0)->change();
            $table->decimal('value', 12, 2)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        $this->normalizeIntegerColumn('coupons', 'max_discount', true, null);
        $this->normalizeIntegerColumn('coupons', 'min_order', false, 0);
        $this->normalizeIntegerColumn('coupons', 'value', false, 0);

        Schema::table('coupons', function (Blueprint $table) {
            $table->integer('max_discount')->nullable()->change();
            $table->integer('min_order')->default(0)->change();
            $table->integer('value')->change();
        });
    }

    private function normalizeDecimalColumn(string $table, string $column, bool $nullable, int|float|string|null $fallback): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select('id', $column)
            ->orderBy('id')
            ->get()
            ->each(function (object $row) use ($table, $column, $nullable, $fallback): void {
                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        $column => $this->normalizeDecimalValue($row->{$column}, $nullable, $fallback),
                    ]);
            });
    }

    private function normalizeIntegerColumn(string $table, string $column, bool $nullable, int|float|string|null $fallback): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select('id', $column)
            ->orderBy('id')
            ->get()
            ->each(function (object $row) use ($table, $column, $nullable, $fallback): void {
                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        $column => $this->normalizeIntegerValue($row->{$column}, $nullable, $fallback),
                    ]);
            });
    }

    private function normalizeDecimalValue(mixed $value, bool $nullable, int|float|string|null $fallback): ?string
    {
        $number = $this->extractNumericValue($value);

        if ($number === null) {
            if ($nullable) {
                return null;
            }

            $number = $this->extractNumericValue($fallback) ?? 0.0;
        }

        return number_format($number, 2, '.', '');
    }

    private function normalizeIntegerValue(mixed $value, bool $nullable, int|float|string|null $fallback): ?int
    {
        $number = $this->extractNumericValue($value);

        if ($number === null) {
            if ($nullable) {
                return null;
            }

            $number = $this->extractNumericValue($fallback) ?? 0.0;
        }

        return (int) round($number);
    }

    private function extractNumericValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = str_replace(["\xc2\xa0", ' '], '', $text);
        $text = preg_replace('/[^\d,.\-]/u', '', $text) ?? '';
        if ($text === '' || $text === '-' || $text === '.' || $text === ',') {
            return null;
        }

        $lastComma = strrpos($text, ',');
        $lastDot = strrpos($text, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';

            $text = str_replace($thousandSeparator, '', $text);
            if ($decimalSeparator === ',') {
                $text = str_replace(',', '.', $text);
            }
        } elseif ($lastComma !== false) {
            $text = preg_match('/,\d{1,2}$/', $text)
                ? str_replace(',', '.', $text)
                : str_replace(',', '', $text);
        } elseif ($lastDot !== false && ! preg_match('/\.\d{1,2}$/', $text)) {
            $text = str_replace('.', '', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }
};
