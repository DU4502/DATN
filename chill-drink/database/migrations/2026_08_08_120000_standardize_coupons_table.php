<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        $canonicalTypes = [
            'value' => ['integer', 'int'],
            'min_order' => ['integer', 'int'],
            'max_discount' => ['decimal', 'numeric'],
        ];

        foreach ($canonicalTypes as $column => $allowedTypes) {
            if (! Schema::hasColumn('coupons', $column)) {
                throw new RuntimeException("Cannot validate canonical coupons schema: missing [{$column}] column.");
            }

            $actualType = Schema::getColumnType('coupons', $column);

            if (! in_array($actualType, $allowedTypes, true)) {
                throw new RuntimeException(
                    "Refusing to rewrite coupons. Expected [{$column}] to be "
                    .implode(' or ', $allowedTypes).", got [{$actualType}]."
                );
            }
        }

        // The base coupons migration already creates the canonical schema.
        // Existing values are intentionally left untouched.
    }

    public function down(): void
    {
        // up() only validates the canonical schema and does not mutate it.
    }
};
