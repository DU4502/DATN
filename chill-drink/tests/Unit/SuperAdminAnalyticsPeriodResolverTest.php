<?php

namespace Tests\Unit;

use App\Services\SuperAdminAnalyticsPeriodResolver;
use Carbon\Carbon;
use Tests\TestCase;

class SuperAdminAnalyticsPeriodResolverTest extends TestCase
{
    public function test_resolver_only_keeps_active_period_and_compare_parameters(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        /** @var SuperAdminAnalyticsPeriodResolver $resolver */
        $resolver = app(SuperAdminAnalyticsPeriodResolver::class);
        $context = $resolver->resolve([
            'analytics_period_type' => 'day',
            'analytics_date' => '2026-08-03',
            'analytics_month' => '2026-07',
            'analytics_year' => '2025',
            'analytics_compare_type' => 'none',
            'analytics_compare_date' => '2026-07-01',
            'analytics_product_sort' => 'revenue',
        ]);

        $this->assertSame([
            'analytics_period_type' => 'day',
            'analytics_compare_type' => 'none',
            'analytics_product_sort' => 'revenue',
            'analytics_date' => '2026-08-03',
        ], $context->normalizedQueryParameters);
    }
}
