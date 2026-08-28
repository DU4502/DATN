<?php

namespace Tests\Feature;

use App\Exceptions\WeatherUnavailableException;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Product;
use App\Services\DrinkRecommendationService;
use App\Services\RecommendationCandidateService;
use App\Services\RecommendationScorer;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class DrinkRecommendationServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_popularity_fallback_still_uses_real_candidate_availability_filter(): void
    {
        Log::spy();
        $branch = Branch::query()->create([
            'name' => 'Recommendation integration branch',
            'code' => 'REC-INTEGRATION',
            'status' => true,
        ]);
        $available = $this->createProduct(1, true);
        $unavailable = $this->createProduct(2, true);
        $inactive = $this->createProduct(3, false);
        $missingStatus = $this->createProduct(4, true);

        $this->setAvailability($available, $branch, true);
        $this->setAvailability($unavailable, $branch, false);
        $this->setAvailability($inactive, $branch, true);

        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('currentForBranch')
            ->once()
            ->with($branch)
            ->andThrow(new WeatherUnavailableException('Provider unavailable'));
        $service = new DrinkRecommendationService(
            $weatherService,
            app(RecommendationCandidateService::class),
            new RecommendationScorer,
        );

        $result = $service->forBranch($branch);

        $this->assertSame('popularity_fallback', $result['mode']);
        $this->assertSame([$available->id], $result['recommendations']->pluck('product.id')->all());
        $this->assertNotContains($unavailable->id, $result['recommendations']->pluck('product.id'));
        $this->assertNotContains($inactive->id, $result['recommendations']->pluck('product.id'));
        $this->assertNotContains($missingStatus->id, $result['recommendations']->pluck('product.id'));
    }

    private function createProduct(int $sequence, bool $active): Product
    {
        return Product::query()->create([
            'name' => "Recommendation integration product {$sequence}",
            'slug' => "recommendation-integration-product-{$sequence}",
            'sku' => "REC-INT-{$sequence}",
            'price' => 30000,
            'description' => 'Recommendation integration test product.',
            'serving_temperature' => $sequence % 2 === 0 ? 'hot' : 'cold',
            'status' => $active,
        ]);
    }

    private function setAvailability(Product $product, Branch $branch, bool $isAvailable): void
    {
        BranchProductStatus::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'is_available' => $isAvailable,
        ]);
    }
}
