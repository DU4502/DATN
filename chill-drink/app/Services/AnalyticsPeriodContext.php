<?php

namespace App\Services;

use Carbon\CarbonImmutable;

readonly class AnalyticsPeriodContext
{
    public function __construct(
        public string $periodType,
        public ?CarbonImmutable $currentStart,
        public ?CarbonImmutable $currentEnd,
        public ?CarbonImmutable $compareStart,
        public ?CarbonImmutable $compareEnd,
        public string $displayLabel,
        public string $comparisonLabel,
        public array $branchIds,
        public ?int $branchId,
        public string $branchScopeLabel,
        public array $normalizedQueryParameters,
        public string $timezone,
    ) {
    }

    public function hasComparison(): bool
    {
        return $this->compareStart !== null && $this->compareEnd !== null;
    }

    public function hasBranchScope(): bool
    {
        return $this->branchIds !== [];
    }

    public function isAllBranches(): bool
    {
        return $this->branchIds === [];
    }

    public function containsBranch(int $branchId): bool
    {
        return in_array($branchId, $this->branchIds, true);
    }

    public function normalizedBranchIds(): array
    {
        return $this->branchIds;
    }
}
