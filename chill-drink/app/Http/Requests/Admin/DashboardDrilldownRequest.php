<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardDrilldownRequest extends FormRequest
{
    private const MAX_EXPLICIT_RANGE_DAYS = 3660;

    public const METRICS = [
        'revenue',
        'orders',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'cancellation_rate',
        'customers',
        'new_customers',
        'items_sold',
        'product_sales',
        'product_revenue',
        'product_cancellation_rate',
        'product_reviews',
        'average_order_value',
        'products',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && ($user->isAdmin() || $user->isSuperAdmin()));
    }

    public function rules(): array
    {
        return [
            'metric' => ['required', Rule::in(self::METRICS)],
            'from' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'to' => ['nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer', 'min:1', 'exists:branches,id'],
            'product_id' => ['required_if:metric,product_sales,product_revenue,product_cancellation_rate,product_reviews', 'nullable', 'integer', 'min:1', 'exists:products,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', Rule::in([6, 20, 30, 50])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        $data['search'] = trim((string) ($data['search'] ?? ''));
        $data['per_page'] = (int) ($data['per_page'] ?? 20);
        $this->replace($data);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $from = $this->input('from');
            $to = $this->input('to');

            if (! is_string($from) || ! is_string($to)) {
                return;
            }

            try {
                $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $from, config('app.timezone'));
                $end = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $to, config('app.timezone'));
            } catch (\Throwable) {
                return;
            }

            if ($start->diffInDays($end) > self::MAX_EXPLICIT_RANGE_DAYS) {
                $validator->errors()->add('to', 'Khoảng thời gian tra cứu không được vượt quá 10 năm.');
            }
        });
    }
}
