<?php

namespace App\Http\Requests\Admin;

use App\Services\AnalyticsPeriodContext;
use App\Services\SuperAdminAnalyticsPeriodResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuperAdminAnalyticsRequest extends FormRequest
{
    private ?AnalyticsPeriodContext $analyticsPeriodContext = null;

    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $periodType = $this->input('analytics_period_type', 'all');
        $analyticsBranchIds = $this->normalizeBranchIds(
            $this->input('analytics_branch_ids', $this->input('branch_ids', []))
        );

        $defaults = [
            'analytics_period_type' => $periodType,
            'analytics_compare_type' => $this->input('analytics_compare_type', $periodType === 'all' ? 'none' : 'previous'),
            'analytics_product_sort' => $this->input('analytics_product_sort', 'quantity'),
        ];

        if ($periodType === 'day' && ! $this->filled('analytics_date')) {
            $defaults['analytics_date'] = now()->format('Y-m-d');
        }

        if ($periodType === 'week' && ! $this->filled('analytics_week')) {
            $defaults['analytics_week'] = now()->format('o-\WW');
        }

        if ($periodType === 'month' && ! $this->filled('analytics_month')) {
            $defaults['analytics_month'] = now()->format('Y-m');
        }

        if ($periodType === 'year' && ! $this->filled('analytics_year')) {
            $defaults['analytics_year'] = now()->format('Y');
        }

        if ($periodType === 'range') {
            if (! $this->filled('analytics_start_date')) {
                $defaults['analytics_start_date'] = now()->startOfMonth()->format('Y-m-d');
            }

            if (! $this->filled('analytics_end_date')) {
                $defaults['analytics_end_date'] = now()->format('Y-m-d');
            }
        }

        $defaults['analytics_branch_ids'] = $analyticsBranchIds;

        $this->merge($defaults);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'analytics_period_type' => ['required', Rule::in(['all', 'day', 'week', 'month', 'year', 'range'])],
            'analytics_date' => ['nullable', 'date_format:Y-m-d'],
            'analytics_week' => ['nullable', 'regex:/^\d{4}-W(0[1-9]|[1-4]\d|5[0-3])$/'],
            'analytics_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'analytics_year' => ['nullable', 'integer', 'min:2000', 'max:' . now()->addYear()->year],
            'analytics_start_date' => ['nullable', 'date_format:Y-m-d'],
            'analytics_end_date' => ['nullable', 'date_format:Y-m-d'],
            'analytics_compare_type' => ['required', Rule::in(['none', 'previous', 'previous_year', 'custom'])],
            'analytics_product_sort' => ['nullable', Rule::in(['quantity', 'revenue'])],
            'analytics_compare_date' => ['nullable', 'date_format:Y-m-d'],
            'analytics_compare_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'analytics_compare_year' => ['nullable', 'integer', 'min:2000', 'max:' . now()->addYear()->year],
            'analytics_compare_start_date' => ['nullable', 'date_format:Y-m-d'],
            'analytics_compare_end_date' => ['nullable', 'date_format:Y-m-d'],
            'analytics_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'analytics_branch_ids' => ['nullable', 'array', 'max:100'],
            'analytics_branch_ids.*' => ['integer', 'exists:branches,id'],
            'analytics_detail_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'analytics_focus_product_id' => ['nullable', 'integer', 'min:1'],
            'analytics_focus_product_sort' => ['nullable', Rule::in(['quantity', 'revenue'])],
            'analytics_focus_product_query' => ['nullable', 'string', 'max:120'],
            'analytics_focus_branch_search' => ['nullable', 'string', 'max:120'],
            'analytics_focus_branch_page' => ['nullable', 'integer', 'min:1'],
            'branch_search' => ['nullable', 'string', 'max:120'],
            'branch_sort' => ['nullable', Rule::in(['revenue', 'orders', 'average_order_value', 'items_sold', 'growth', 'cancellation_rate', 'name'])],
            'branch_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'branch_performance' => ['nullable', Rule::in(['all', 'increased', 'decreased', 'unchanged', 'new_activity', 'no_orders'])],
            'branch_page' => ['nullable', 'integer', 'min:1'],
            'branch_ids' => ['nullable', 'array', 'max:100'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'analytics_period_type.required' => 'Vui lòng chọn khoảng thời gian.',
            'analytics_period_type.in' => 'Khoảng thời gian không hợp lệ.',
            'analytics_date.date_format' => 'Ngày không hợp lệ.',
            'analytics_week.regex' => 'Tuần không hợp lệ.',
            'analytics_month.regex' => 'Tháng không hợp lệ.',
            'analytics_year.integer' => 'Năm không hợp lệ.',
            'analytics_year.min' => 'Năm không hợp lệ.',
            'analytics_start_date.date_format' => 'Ngày bắt đầu không hợp lệ.',
            'analytics_end_date.date_format' => 'Ngày kết thúc không hợp lệ.',
            'analytics_compare_type.required' => 'Vui lòng chọn kiểu so sánh.',
            'analytics_compare_type.in' => 'Kiểu so sánh không hợp lệ.',
            'analytics_product_sort.in' => 'Kiểu xếp hạng sản phẩm không hợp lệ.',
            'analytics_compare_date.date_format' => 'Ngày so sánh không hợp lệ.',
            'analytics_compare_month.regex' => 'Tháng so sánh không hợp lệ.',
            'analytics_compare_year.integer' => 'Năm so sánh không hợp lệ.',
            'analytics_compare_year.min' => 'Năm so sánh không hợp lệ.',
            'analytics_compare_start_date.date_format' => 'Ngày bắt đầu so sánh không hợp lệ.',
            'analytics_compare_end_date.date_format' => 'Ngày kết thúc so sánh không hợp lệ.',
            'analytics_branch_id.integer' => 'Chi nhánh không hợp lệ.',
            'analytics_branch_id.exists' => 'Chi nhánh không tồn tại.',
            'analytics_detail_branch_id.integer' => 'Chi nhánh chi tiết không hợp lệ.',
            'analytics_detail_branch_id.exists' => 'Chi nhánh chi tiết không tồn tại.',
            'analytics_focus_product_id.integer' => 'Sản phẩm không hợp lệ.',
            'analytics_focus_product_id.min' => 'Sản phẩm không hợp lệ.',
            'analytics_focus_product_sort.in' => 'Kiểu xếp hạng sản phẩm chi tiết không hợp lệ.',
            'analytics_focus_product_query.max' => 'Từ khóa tìm sản phẩm quá dài.',
            'analytics_focus_branch_search.max' => 'Từ khóa tìm chi nhánh quá dài.',
            'analytics_focus_branch_page.integer' => 'Trang chi nhánh không hợp lệ.',
            'analytics_focus_branch_page.min' => 'Trang chi nhánh không hợp lệ.',
            'branch_search.max' => 'Từ khóa tìm chi nhánh quá dài.',
            'branch_sort.in' => 'Kiểu sắp xếp chi nhánh không hợp lệ.',
            'branch_direction.in' => 'Hướng sắp xếp không hợp lệ.',
            'branch_performance.in' => 'Bộ lọc hiệu suất chi nhánh không hợp lệ.',
            'branch_page.integer' => 'Trang chi nhánh không hợp lệ.',
            'branch_page.min' => 'Trang chi nhánh không hợp lệ.',
            'branch_ids.array' => 'Danh sách chi nhánh không hợp lệ.',
            'branch_ids.*.integer' => 'Chi nhánh không hợp lệ.',
            'branch_ids.*.exists' => 'Chi nhánh không tồn tại.',
        ];
    }

    protected function passedValidation(): void
    {
        $this->analyticsPeriodContext = app(SuperAdminAnalyticsPeriodResolver::class)->resolve($this->validated());
    }

    public function analyticsPeriodContext(): AnalyticsPeriodContext
    {
        return $this->analyticsPeriodContext ??= app(SuperAdminAnalyticsPeriodResolver::class)->resolve($this->validated());
    }

    /**
     * @return array<int>
     */
    private function normalizeBranchIds(mixed $branchIds): array
    {
        if (is_string($branchIds) && $branchIds !== '') {
            $branchIds = explode(',', $branchIds);
        }

        if (! is_array($branchIds)) {
            $branchIds = $branchIds === null || $branchIds === '' ? [] : [$branchIds];
        }

        return collect($branchIds)
            ->filter(static fn ($value) => $value !== null && $value !== '' && is_numeric($value))
            ->map(static fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
