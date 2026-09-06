<?php

namespace Tests\Feature;

use App\Exceptions\WeatherUnavailableException;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Services\ProductAvailabilityService;
use App\Services\WeatherContext;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class HomepageRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Size $size;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.weather.demo_mode', false);
        config()->set('services.weather.demo_scenario', 'normal');
        Http::preventStrayRequests();

        $this->category = Category::query()->create([
            'name' => 'Homepage recommendation category',
            'slug' => 'homepage-recommendation-category',
            'status' => true,
        ]);
        $this->size = Size::query()->create([
            'name' => 'Homepage M',
            'multiplier' => 1,
        ]);
    }

    public function test_weather_mode_renders_real_recommendation_order_without_fixed_sku_query(): void
    {
        $branch = $this->createBranch('A');
        $hot = $this->createProduct(1, 'Homepage Hot Drink', 'hot');
        $cold = $this->createProduct(2, 'Homepage Cold Drink', 'cold');
        $this->setAvailability($hot, $branch, true);
        $this->setAvailability($cold, $branch, true);
        $this->bindCurrentBranch($branch);

        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('currentForBranch')
            ->once()
            ->with(Mockery::on(fn (Branch $received): bool => $received->is($branch)))
            ->andReturn($this->weather(36.5));
        $this->app->instance(WeatherService::class, $weatherService);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('href="'.route('products.index').'" class="btn-order"', false)
            ->assertSee('href="#featured-products" class="btn-suggest"', false)
            ->assertSee('id="featured-products"', false)
            ->assertSee('36.5°C', false)
            ->assertSee('Hôm nay khá nóng! Thử một món mát lạnh nhé.')
            ->assertSeeInOrder([$cold->name, $hot->name])
            ->assertDontSee('CD-TS-001');

        $html = $response->getContent();
        $heroPosition = strpos($html, 'id="mainSlider"');
        $recommendationPosition = strpos($html, 'id="featured-products"');
        $trustStripPosition = strpos($html, 'class="home-trust"');

        $this->assertSame(1, substr_count($html, 'id="featured-products"'));
        $this->assertIsInt($heroPosition);
        $this->assertIsInt($recommendationPosition);
        $this->assertIsInt($trustStripPosition);
        $this->assertTrue($heroPosition < $trustStripPosition);
        $this->assertTrue($trustStripPosition < $recommendationPosition);
    }

    public function test_hot_demo_prioritizes_cold_products_and_cannot_be_overridden_by_query_string(): void
    {
        $branch = $this->createBranch('HOT');
        $hot = $this->createProduct(1, 'Demo Hot Drink', 'hot');
        $cold = $this->createProduct(2, 'Demo Cold Drink', 'cold');
        $this->setAvailability($hot, $branch, true);
        $this->setAvailability($cold, $branch, true);
        $this->bindCurrentBranch($branch);
        $this->enableDemo('hot');

        $response = $this->get(route('home', ['weather' => 'cold']));

        $response->assertOk()
            ->assertSee('36°C', false)
            ->assertSee('Hôm nay khá nóng! Thử một món mát lạnh nhé.')
            ->assertSeeInOrder([$cold->name, $hot->name])
            ->assertDontSee('WEATHER_DEMO_MODE');
        Http::assertNothingSent();
    }

    public function test_rain_demo_ranks_hot_both_and_cold_with_the_existing_formula(): void
    {
        $branch = $this->createBranch('RAIN');
        $hot = $this->createProduct(1, 'Rain Hot Drink', 'hot');
        $both = $this->createProduct(2, 'Rain Both Drink', 'both');
        $cold = $this->createProduct(3, 'Rain Cold Drink', 'cold');
        foreach ([$hot, $both, $cold] as $product) {
            $this->setAvailability($product, $branch, true);
        }
        $this->bindCurrentBranch($branch);
        $this->enableDemo('rain');

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('24°C', false)
            ->assertSee('Trời đang mưa, Chill Drink đã chọn một vài món phù hợp cho bạn.')
            ->assertSeeInOrder([$hot->name, $both->name, $cold->name]);
        Http::assertNothingSent();
    }

    public function test_cold_demo_ranks_hot_above_cold_without_popularity_advantage(): void
    {
        $branch = $this->createBranch('COLD');
        $cold = $this->createProduct(1, 'Cold Scenario Cold Drink', 'cold');
        $hot = $this->createProduct(2, 'Cold Scenario Hot Drink', 'hot');
        $this->setAvailability($cold, $branch, true);
        $this->setAvailability($hot, $branch, true);
        $this->bindCurrentBranch($branch);
        $this->enableDemo('cold');

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('17°C', false)
            ->assertSee('Hôm nay khá mát, Chill Drink đã chọn một vài món phù hợp cho bạn.')
            ->assertSeeInOrder([$hot->name, $cold->name]);
        Http::assertNothingSent();
    }

    public function test_normal_demo_uses_existing_scores_and_stable_tie_breaking(): void
    {
        $branch = $this->createBranch('NORMAL');
        $hot = $this->createProduct(1, 'Normal Hot Drink', 'hot');
        $cold = $this->createProduct(2, 'Normal Cold Drink', 'cold');
        $both = $this->createProduct(3, 'Normal Both Drink', 'both');
        foreach ([$hot, $cold, $both] as $product) {
            $this->setAvailability($product, $branch, true);
        }
        $this->bindCurrentBranch($branch);
        $this->enableDemo('normal');

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('28°C', false)
            ->assertSee('Thời tiết hôm nay khá dễ chịu, đây là vài gợi ý dành cho bạn.');
        $this->assertSame(
            [$both->name, $cold->name, $hot->name],
            $response->viewData('recommendationResult')['recommendations']
                ->pluck('product.name')
                ->all()
        );
        Http::assertNothingSent();
    }

    public function test_weather_failure_keeps_homepage_200_and_renders_safe_popularity_fallback(): void
    {
        Log::spy();
        $branch = $this->createBranch('A');
        $product = $this->createProduct(1, 'Homepage Fallback Drink', 'cold');
        $this->setAvailability($product, $branch, true);
        $this->bindCurrentBranch($branch);

        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('currentForBranch')
            ->once()
            ->andThrow(new WeatherUnavailableException('Raw provider API key failure'));
        $this->app->instance(WeatherService::class, $weatherService);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('id="featured-products"', false)
            ->assertSee($product->name)
            ->assertSee('Những món đang được yêu thích tại chi nhánh này.')
            ->assertDontSee('Raw provider API key failure')
            ->assertDontSee('WeatherUnavailableException');
    }

    public function test_homepage_recommendations_are_isolated_to_the_current_branch_availability(): void
    {
        $branchA = $this->createBranch('A');
        $branchB = $this->createBranch('B');
        $productA = $this->createProduct(1, 'Branch A Recommendation', 'cold');
        $productB = $this->createProduct(2, 'Branch B Recommendation', 'cold');
        $this->setAvailability($productA, $branchA, true);
        $this->setAvailability($productA, $branchB, false);
        $this->setAvailability($productB, $branchA, false);
        $this->setAvailability($productB, $branchB, true);

        $availabilityService = Mockery::mock(ProductAvailabilityService::class);
        $availabilityService->shouldReceive('currentBranch')->twice()->andReturn($branchA, $branchB);
        $this->app->instance(ProductAvailabilityService::class, $availabilityService);
        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('currentForBranch')->twice()->andReturn($this->weather(28));
        $this->app->instance(WeatherService::class, $weatherService);

        $branchAResponse = $this->get(route('home'));
        $branchBResponse = $this->get(route('home'));

        $branchAResponse->assertOk()
            ->assertSee($productA->name)
            ->assertDontSee($productB->name);
        $branchBResponse->assertOk()
            ->assertSee($productB->name)
            ->assertDontSee($productA->name);
    }

    public function test_empty_candidates_hide_section_and_never_request_weather(): void
    {
        $branch = $this->createBranch('A');
        $this->bindCurrentBranch($branch);
        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldNotReceive('currentForBranch');
        $this->app->instance(WeatherService::class, $weatherService);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('href="#featured-products" class="btn-suggest"', false)
            ->assertDontSee('id="featured-products"', false);
    }

    public function test_homepage_query_count_does_not_scale_with_recommendation_card_count(): void
    {
        $branchWithOne = $this->createBranch('ONE');
        $branchWithTen = $this->createBranch('TEN');
        foreach (range(1, 10) as $sequence) {
            $product = $this->createProduct($sequence, "Query Count Drink {$sequence}", 'cold');
            $this->setAvailability($product, $branchWithOne, $sequence === 1);
            $this->setAvailability($product, $branchWithTen, true);
        }

        $availabilityService = Mockery::mock(ProductAvailabilityService::class);
        $availabilityService->shouldReceive('currentBranch')->times(3)->andReturn(
            $branchWithOne,
            $branchWithOne,
            $branchWithTen
        );
        $this->app->instance(ProductAvailabilityService::class, $availabilityService);
        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('currentForBranch')->times(3)->andReturn($this->weather(28));
        $this->app->instance(WeatherService::class, $weatherService);

        // Warm the framework/view caches so only request-time query behavior is compared.
        $this->get(route('home'))->assertOk();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $oneCardResponse = $this->get(route('home'));
        $oneCardQueryCount = count(DB::getQueryLog());

        DB::flushQueryLog();
        $tenCardResponse = $this->get(route('home'));
        $tenCardQueryCount = count(DB::getQueryLog());

        $oneCardResponse->assertOk();
        $tenCardResponse->assertOk();
        $this->assertSame($oneCardQueryCount, $tenCardQueryCount);
    }

    private function bindCurrentBranch(Branch $branch): void
    {
        $availabilityService = Mockery::mock(ProductAvailabilityService::class);
        $availabilityService->shouldReceive('currentBranch')->once()->andReturn($branch);
        $this->app->instance(ProductAvailabilityService::class, $availabilityService);
    }

    private function enableDemo(string $scenario): void
    {
        Http::fake();
        config()->set('services.weather.demo_mode', true);
        config()->set('services.weather.demo_scenario', $scenario);
        config()->set('services.weather.key');
    }

    private function createBranch(string $suffix): Branch
    {
        return Branch::query()->create([
            'name' => "Homepage branch {$suffix}",
            'code' => "HOME-{$suffix}",
            'status' => true,
        ]);
    }

    private function createProduct(int $sequence, string $name, string $servingTemperature): Product
    {
        $product = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => "homepage-recommendation-{$sequence}",
            'sku' => "HOME-REC-{$sequence}",
            'price' => 30000 + $sequence,
            'description' => 'Homepage recommendation test product.',
            'serving_temperature' => $servingTemperature,
            'status' => true,
        ]);
        ProductSize::query()->create([
            'product_id' => $product->id,
            'size_id' => $this->size->id,
            'price' => 30000 + $sequence,
        ]);

        return $product;
    }

    private function setAvailability(Product $product, Branch $branch, bool $available): void
    {
        BranchProductStatus::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'is_available' => $available,
        ]);
    }

    private function weather(float $temperature): WeatherContext
    {
        return new WeatherContext(
            temperatureC: $temperature,
            conditionCode: 1000,
            conditionText: 'Sunny',
            isRaining: false,
            precipitationMm: 0,
            humidity: 65,
            feelsLikeC: null,
        );
    }
}
