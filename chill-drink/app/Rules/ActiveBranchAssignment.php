<?php

namespace App\Rules;

use App\Models\Branch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveBranchAssignment implements ValidationRule
{
    public function __construct(private readonly ?int $preservedBranchId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $branch = Branch::query()->find((int) $value);
        if (! $branch) {
            $fail('Chi nhánh đã chọn không tồn tại.');

            return;
        }

        if (! $branch->status && (int) $branch->id !== $this->preservedBranchId) {
            $fail('Không thể gán tài khoản vào chi nhánh đã ngừng hoạt động.');
        }
    }
}
