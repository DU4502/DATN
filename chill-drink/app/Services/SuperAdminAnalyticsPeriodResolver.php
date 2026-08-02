<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SuperAdminAnalyticsPeriodResolver
{
    public function resolve(array $input): AnalyticsPeriodContext
    {
        $timezone = $this->timezone();
        $now = CarbonImmutable::now($timezone);

        $periodType = (string) Arr::get($input, 'analytics_period_type', 'all');
        $compareType = (string) Arr::get($input, 'analytics_compare_type', 'none');
        $branchIds = $this->normalizeBranchIds(
            Arr::get($input, 'analytics_branch_ids', Arr::get($input, 'branch_ids', []))
        );

        if ($branchIds === []) {
            $legacyBranchId = $this->nullableInt(Arr::get($input, 'analytics_branch_id'));
            if ($legacyBranchId !== null) {
                $branchIds = [$legacyBranchId];
            }
        }

        $branchId = count($branchIds) === 1 ? $branchIds[0] : null;
        $branchScopeLabel = $this->branchScopeLabel($branchIds);

        [$currentStart, $currentEnd, $displayLabel, $periodSpanDays] = $this->resolveCurrentPeriod($periodType, $input, $now, $timezone);

        if ($periodType === 'all') {
            $compareType = 'none';
        }

        [$compareStart, $compareEnd, $comparisonLabel] = $this->resolveComparison(
            $compareType,
            $periodType,
            $currentStart,
            $currentEnd,
            $periodSpanDays,
            $input,
            $now,
            $timezone
        );

        return new AnalyticsPeriodContext(
            periodType: $periodType,
            currentStart: $currentStart,
            currentEnd: $currentEnd,
            compareStart: $compareStart,
            compareEnd: $compareEnd,
            displayLabel: $displayLabel,
            comparisonLabel: $comparisonLabel,
            branchIds: $branchIds,
            branchId: $branchId,
            branchScopeLabel: $branchScopeLabel,
            normalizedQueryParameters: $this->normalizedQueryParameters($input, $periodType, $compareType, $branchIds),
            timezone: $timezone,
        );
    }

    private function resolveCurrentPeriod(string $periodType, array $input, CarbonImmutable $now, string $timezone): array
    {
        return match ($periodType) {
            'day' => $this->resolveDayPeriod($input, $now, $timezone),
            'week' => $this->resolveWeekPeriod($input, $now, $timezone),
            'month' => $this->resolveMonthPeriod($input, $now, $timezone),
            'year' => $this->resolveYearPeriod($input, $now, $timezone),
            'range' => $this->resolveRangePeriod($input, $now, $timezone),
            default => [null, null, 'Tất cả thời gian', null],
        };
    }

    private function resolveComparison(
        string $compareType,
        string $periodType,
        ?CarbonImmutable $currentStart,
        ?CarbonImmutable $currentEnd,
        ?int $periodSpanDays,
        array $input,
        CarbonImmutable $now,
        string $timezone
    ): array {
        if (! $currentStart || ! $currentEnd || $compareType === 'none') {
            return [null, null, 'Không so sánh'];
        }

        return match ($compareType) {
            'previous' => $this->resolvePreviousComparison($periodType, $currentStart, $currentEnd, $periodSpanDays),
            'previous_year' => [
                $currentStart->subYearNoOverflow(),
                $currentEnd->subYearNoOverflow(),
                'Cùng kỳ năm trước',
            ],
            'custom' => $this->resolveCustomComparison($input, $currentStart, $currentEnd, $timezone),
            default => [null, null, 'Không so sánh'],
        };
    }

    private function resolvePreviousComparison(
        string $periodType,
        CarbonImmutable $currentStart,
        CarbonImmutable $currentEnd,
        ?int $periodSpanDays
    ): array {
        return match ($periodType) {
            'day' => [
                $currentStart->subDay(),
                $currentEnd->subDay(),
                'Kỳ liền trước',
            ],
            'week' => [
                $currentStart->subWeek(),
                $currentEnd->subWeek(),
                'Kỳ liền trước',
            ],
            'month' => [
                $currentStart->subMonthNoOverflow(),
                $currentEnd->subMonthNoOverflow(),
                'Kỳ liền trước',
            ],
            'year' => [
                $currentStart->subYearNoOverflow(),
                $currentEnd->subYearNoOverflow(),
                'Kỳ liền trước',
            ],
            'range' => [
                $currentStart->subDays($periodSpanDays ?? 1),
                $currentEnd->subDays($periodSpanDays ?? 1),
                'Kỳ liền trước',
            ],
            default => [null, null, 'Không so sánh'],
        };
    }

    private function resolveCustomComparison(array $input, CarbonImmutable $currentStart, CarbonImmutable $currentEnd, string $timezone): array
    {
        $currentDurationSeconds = $currentStart->diffInSeconds($currentEnd);

        if (filled(Arr::get($input, 'analytics_compare_start_date')) && filled(Arr::get($input, 'analytics_compare_end_date'))) {
            $start = CarbonImmutable::createFromFormat('Y-m-d', (string) Arr::get($input, 'analytics_compare_start_date'), $timezone);
            $end = CarbonImmutable::createFromFormat('Y-m-d', (string) Arr::get($input, 'analytics_compare_end_date'), $timezone);

            if ($start === false || $end === false) {
                throw ValidationException::withMessages([
                    'analytics_compare_start_date' => 'Khoảng đối chiếu không hợp lệ.',
                ]);
            }

            $start = $start->startOfDay();
            $end = $this->normalizeEndDate($end, $timezone);

            $this->assertSameDuration($currentDurationSeconds, $start, $end, 'analytics_compare_start_date');

            return [$start, $end, 'Tùy chọn'];
        }

        if (filled(Arr::get($input, 'analytics_compare_date'))) {
            $date = CarbonImmutable::createFromFormat('Y-m-d', (string) Arr::get($input, 'analytics_compare_date'), $timezone);
            if ($date === false) {
                throw ValidationException::withMessages([
                    'analytics_compare_date' => 'Ngày đối chiếu không hợp lệ.',
                ]);
            }

            $start = $date->startOfDay();
            $end = $start->addSeconds($currentDurationSeconds);

            if ($end->isEndOfDay() === false && ! $end->isSameDay($start)) {
                throw ValidationException::withMessages([
                    'analytics_compare_date' => 'Khoảng đối chiếu phải có cùng độ dài với kỳ đang xem.',
                ]);
            }

            return [$start, $end, 'Tùy chọn'];
        }

        if (filled(Arr::get($input, 'analytics_compare_month'))) {
            $month = (string) Arr::get($input, 'analytics_compare_month');
            $start = CarbonImmutable::createFromFormat('Y-m', $month, $timezone);

            if ($start === false) {
                throw ValidationException::withMessages([
                    'analytics_compare_month' => 'Tháng đối chiếu không hợp lệ.',
                ]);
            }

            $start = $start->startOfMonth();
            $end = $start->addSeconds($currentDurationSeconds);

            if ($end->isAfter($start->endOfMonth())) {
                throw ValidationException::withMessages([
                    'analytics_compare_month' => 'Khoảng đối chiếu phải có cùng độ dài với kỳ đang xem.',
                ]);
            }

            return [$start, $end, 'Tùy chọn'];
        }

        if (filled(Arr::get($input, 'analytics_compare_year'))) {
            $year = (int) Arr::get($input, 'analytics_compare_year');
            $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0, $timezone);
            $end = $start->addSeconds($currentDurationSeconds);

            if ($end->isAfter($start->endOfYear())) {
                throw ValidationException::withMessages([
                    'analytics_compare_year' => 'Khoảng đối chiếu phải có cùng độ dài với kỳ đang xem.',
                ]);
            }

            return [$start, $end, 'Tùy chọn'];
        }

        throw ValidationException::withMessages([
            'analytics_compare_type' => 'Vui lòng cung cấp khoảng đối chiếu hợp lệ.',
        ]);
    }

    private function resolveDayPeriod(array $input, CarbonImmutable $now, string $timezone): array
    {
        $date = $this->parseDateOrFallback(Arr::get($input, 'analytics_date'), $now, $timezone);

        if ($date->startOfDay()->isAfter($now)) {
            throw ValidationException::withMessages([
                'analytics_date' => 'Ngày được chọn không được nằm trong tương lai.',
            ]);
        }

        $start = $date->startOfDay();
        $end = $date->isSameDay($now) ? $now : $date->endOfDay();

        return [$start, $end, 'Ngày '.$date->format('d/m/Y'), 1];
    }

    private function resolveWeekPeriod(array $input, CarbonImmutable $now, string $timezone): array
    {
        $weekValue = (string) Arr::get($input, 'analytics_week', $now->format('o-\WW'));
        if (! preg_match('/^(\d{4})-W(0[1-9]|[1-4]\d|5[0-3])$/', $weekValue, $matches)) {
            throw ValidationException::withMessages([
                'analytics_week' => 'Tuần được chọn không hợp lệ.',
            ]);
        }

        $year = (int) $matches[1];
        $week = (int) $matches[2];
        $start = CarbonImmutable::now($timezone)->setISODate($year, $week, 1)->startOfDay();
        $end = $start->addDays(6)->endOfDay();

        if ($start->isAfter($now)) {
            throw ValidationException::withMessages([
                'analytics_week' => 'Tuần được chọn không được nằm trong tương lai.',
            ]);
        }

        if ($start->isSameWeek($now)) {
            $end = $now;
        }

        return [$start, $end, 'Tuần '.$start->format('d/m/Y').' - '.$end->format('d/m/Y'), 7];
    }

    private function resolveMonthPeriod(array $input, CarbonImmutable $now, string $timezone): array
    {
        $monthValue = (string) Arr::get($input, 'analytics_month', $now->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthValue)) {
            throw ValidationException::withMessages([
                'analytics_month' => 'Tháng được chọn không hợp lệ.',
            ]);
        }

        $start = CarbonImmutable::createFromFormat('Y-m', $monthValue, $timezone)->startOfMonth();
        $end = $start->endOfMonth();

        if ($start->isAfter($now)) {
            throw ValidationException::withMessages([
                'analytics_month' => 'Tháng được chọn không được nằm trong tương lai.',
            ]);
        }

        if ($start->isSameMonth($now)) {
            $end = $now;
        }

        return [$start, $end, 'Tháng '.$start->format('m/Y'), $start->daysInMonth];
    }

    private function resolveYearPeriod(array $input, CarbonImmutable $now, string $timezone): array
    {
        $yearValue = Arr::get($input, 'analytics_year', $now->format('Y'));
        if (! is_numeric($yearValue)) {
            throw ValidationException::withMessages([
                'analytics_year' => 'Năm được chọn không hợp lệ.',
            ]);
        }

        $year = (int) $yearValue;
        if ($year > (int) $now->format('Y')) {
            throw ValidationException::withMessages([
                'analytics_year' => 'Năm được chọn không được nằm trong tương lai.',
            ]);
        }

        $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0, $timezone);
        $end = $start->endOfYear();

        if ($start->isSameYear($now)) {
            $end = $now;
        }

        return [$start, $end, 'Năm '.$year, 365];
    }

    private function resolveRangePeriod(array $input, CarbonImmutable $now, string $timezone): array
    {
        $startValue = Arr::get($input, 'analytics_start_date');
        $endValue = Arr::get($input, 'analytics_end_date');

        if (! is_string($startValue) || ! is_string($endValue) || $startValue === '' || $endValue === '') {
            throw ValidationException::withMessages([
                'analytics_start_date' => 'Vui lòng chọn ngày bắt đầu và ngày kết thúc.',
            ]);
        }

        $start = CarbonImmutable::createFromFormat('Y-m-d', $startValue, $timezone);
        $end = CarbonImmutable::createFromFormat('Y-m-d', $endValue, $timezone);

        if ($start === false || $end === false) {
            throw ValidationException::withMessages([
                'analytics_start_date' => 'Khoảng ngày không hợp lệ.',
            ]);
        }

        $start = $start->startOfDay();
        $end = $this->normalizeEndDate($end, $timezone);

        if ($start->isAfter($end)) {
            throw ValidationException::withMessages([
                'analytics_start_date' => 'Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc.',
            ]);
        }

        if ($start->isAfter($now)) {
            throw ValidationException::withMessages([
                'analytics_start_date' => 'Khoảng ngày không được bắt đầu trong tương lai.',
            ]);
        }

        if ($end->isAfter($now)) {
            throw ValidationException::withMessages([
                'analytics_end_date' => 'Ngày kết thúc không được nằm trong tương lai.',
            ]);
        }

        if ($end->isSameDay($now)) {
            $end = $now;
        }

        $days = max(1, $start->startOfDay()->diffInDays($end->startOfDay()) + 1);

        return [$start, $end, 'Từ '.$start->format('d/m/Y').' đến '.$end->format('d/m/Y'), $days];
    }

    private function assertSameDuration(int $currentDurationSeconds, CarbonImmutable $start, CarbonImmutable $end, string $errorKey): void
    {
        if ($start->diffInSeconds($end) !== $currentDurationSeconds) {
            throw ValidationException::withMessages([
                $errorKey => 'Khoảng đối chiếu phải có cùng độ dài với kỳ đang xem.',
            ]);
        }
    }

    private function normalizeEndDate(CarbonImmutable $date, string $timezone): CarbonImmutable
    {
        $today = CarbonImmutable::now($timezone);

        return $date->isSameDay($today) ? $today : $date->endOfDay();
    }

    private function parseDateOrFallback(mixed $value, CarbonImmutable $fallback, string $timezone): CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return $fallback;
        }

        $parsed = CarbonImmutable::createFromFormat('Y-m-d', $value, $timezone);

        if ($parsed === false) {
            throw ValidationException::withMessages([
                'analytics_date' => 'Ngày được chọn không hợp lệ.',
            ]);
        }

        return $parsed;
    }

    private function normalizedQueryParameters(array $input, string $periodType, string $compareType, array $branchIds): array
    {
        $payload = [
            'analytics_period_type' => $periodType,
            'analytics_date' => Arr::get($input, 'analytics_date'),
            'analytics_week' => Arr::get($input, 'analytics_week'),
            'analytics_month' => Arr::get($input, 'analytics_month'),
            'analytics_year' => Arr::get($input, 'analytics_year'),
            'analytics_start_date' => Arr::get($input, 'analytics_start_date'),
            'analytics_end_date' => Arr::get($input, 'analytics_end_date'),
            'analytics_compare_type' => $compareType,
            'analytics_compare_date' => Arr::get($input, 'analytics_compare_date'),
            'analytics_compare_month' => Arr::get($input, 'analytics_compare_month'),
            'analytics_compare_year' => Arr::get($input, 'analytics_compare_year'),
            'analytics_compare_start_date' => Arr::get($input, 'analytics_compare_start_date'),
            'analytics_compare_end_date' => Arr::get($input, 'analytics_compare_end_date'),
            'analytics_product_sort' => Arr::get($input, 'analytics_product_sort', 'quantity'),
        ];

        if ($branchIds !== []) {
            $payload['analytics_branch_ids'] = array_values($branchIds);
        }

        return array_filter($payload, static fn ($value) => $value !== null && $value !== '');
    }

    private function timezone(): string
    {
        $timezone = (string) config('app.timezone');

        return $timezone !== '' ? $timezone : 'Asia/Ho_Chi_Minh';
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
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

        $normalized = collect($branchIds)
            ->filter(static fn ($value) => $value !== null && $value !== '' && is_numeric($value))
            ->map(static fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $normalized;
    }

    private function branchScopeLabel(array $branchIds): string
    {
        $count = count($branchIds);

        return match ($count) {
            0 => 'Tất cả chi nhánh',
            1 => '1 chi nhánh được chọn',
            default => $count.' chi nhánh được chọn',
        };
    }
}
