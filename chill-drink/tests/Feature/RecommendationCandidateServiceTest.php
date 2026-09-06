<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Services\RecommendationCandidateService;
use App\Support\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecommendationCandidateServiceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Size $size;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::query()->create([
            'name' => 'Recommendation test category',
            'slug' => 'recommendation-test-category',
            'status' => true,
        ]);
        $this->size = Size::query()->create([
            'name' => 'Recommendation M',
            'multiplier' => 1,
        ]);
    }

    public function test_candidate_filter_enforces_the_complete_branch_availability_matrix(): void
    {
        $branchA = $this->createBranch('A');
        $branchB = $this->createBranch('B');

        $included = $this->createProduct(['serving_temperature' => null]);
        $unavailable = $this->createProduct();
        $missingStatus = $this->createProduct();
        $inactive = $this->createProduct(['status' => false]);
        $softDeleted = $this->createProduct();
        $branchBOnly = $this->createProduct();

        $this->setAvailability($included, $branchA, true);
        $this->setAvailability($unavailable, $branchA, false);
        $this->setAvailability($inactive, $branchA, true);
        $this->setAvailability($softDeleted, $branchA, true);
        $this->setAvailability($branchBOnly, $branchA, false);
        $this->setAvailability($branchBOnly, $branchB, true);
        $softDeleted->delete();

        $service = app(RecommendationCandidateService::class);
        $branchACandidates = $service->forBranch($branchA);
        $branchBCandidates = $service->forBranch($branchB);

        $this->assertSame([$included->id], $branchACandidates->pluck('product.id')->all());
        $this->assertSame([$branchBOnly->id], $branchBCandidates->pluck('product.id')->all());
        $this->assertNull($included->serving_temperature);

        $loadedProduct = $branchACandidates->first()['product'];
        $this->assertTrue($loadedProduct->relationLoaded('category'));
        $this->assertTrue($loadedProduct->relationLoaded('branchStatuses'));
        $this->assertTrue($loadedProduct->relationLoaded('sizes'));
        $this->assertSame([$branchA->id], $loadedProduct->branchStatuses->pluck('branch_id')->all());
        $this->assertSame(0, $loadedProduct->reviews_count);
        $this->assertNull($loadedProduct->reviews_avg_rating);

        $this->assertNotContains($unavailable->id, $branchACandidates->pluck('product.id'));
        $this->assertNotContains($missingStatus->id, $branchACandidates->pluck('product.id'));
        $this->assertNotContains($inactive->id, $branchACandidates->pluck('product.id'));
        $this->assertNotContains($softDeleted->id, $branchACandidates->pluck('product.id'));
    }

    public function test_popularity_uses_completed_quantity_in_inclusive_30_day_branch_window_and_candidate_set(): void
    {
        $now = CarbonImmutable::parse('2026-08-27 12:00:00', 'Asia/Ho_Chi_Minh');
        $this->travelTo($now);

        $branchA = $this->createBranch('A');
        $branchB = $this->createBranch('B');
        $soldOut = $this->createProduct();
        $bestCandidate = $this->createProduct();
        $secondCandidate = $this->createProduct();
        $newCandidate = $this->createProduct();

        $this->setAvailability($soldOut, $branchA, false);
        foreach ([$bestCandidate, $secondCandidate, $newCandidate] as $candidate) {
            $this->setAvailability($candidate, $branchA, true);
        }

        $this->recordSale($soldOut, $branchA, 100, OrderStatus::COMPLETED, $now->subDay());
        $this->recordSale($bestCandidate, $branchA, 30, OrderStatus::COMPLETED, $now->subDays(29));
        $this->recordSale($bestCandidate, $branchA, 20, OrderStatus::COMPLETED, $now->subDays(30));
        $this->recordSale($secondCandidate, $branchA, 25, OrderStatus::COMPLETED, $now->subDay());

        $this->recordSale($bestCandidate, $branchA, 50, OrderStatus::CANCELLED, $now->subDay());
        $this->recordSale($bestCandidate, $branchA, 30, OrderStatus::PENDING, $now->subDay(), 'paid');
        $this->recordSale($bestCandidate, $branchB, 100, OrderStatus::COMPLETED, $now->subDay());
        $this->recordSale($bestCandidate, $branchA, 70, OrderStatus::COMPLETED, $now->subDays(31));

        $rows = app(RecommendationCandidateService::class)
            ->forBranch($branchA)
            ->keyBy(fn (array $row): int => $row['product']->id);

        $this->assertFalse($rows->has($soldOut->id));
        $this->assertSame([50, 25], [$rows[$bestCandidate->id]['sales_30d'], $rows[$bestCandidate->id]['popularity_score']]);
        $this->assertSame([25, 13], [$rows[$secondCandidate->id]['sales_30d'], $rows[$secondCandidate->id]['popularity_score']]);
        $this->assertSame([0, 0], [$rows[$newCandidate->id]['sales_30d'], $rows[$newCandidate->id]['popularity_score']]);
    }

    public function test_no_completed_sales_scores_zero_and_a_single_selling_candidate_gets_25(): void
    {
        $branch = $this->createBranch('A');
        $selling = $this->createProduct();
        $withoutSales = $this->createProduct();
        $this->setAvailability($selling, $branch, true);
        $this->setAvailability($withoutSales, $branch, true);
        $service = app(RecommendationCandidateService::class);

        $beforeSales = $service->forBranch($branch);
        $this->assertSame([0, 0], $beforeSales->pluck('popularity_score')->all());

        $this->recordSale($selling, $branch, 5, OrderStatus::COMPLETED, now()->subDay());
        $afterSales = $service->forBranch($branch)->keyBy(fn (array $row): int => $row['product']->id);

        $this->assertSame(25, $afterSales[$selling->id]['popularity_score']);
        $this->assertSame(0, $afterSales[$withoutSales->id]['popularity_score']);
    }

    public function test_query_count_is_constant_and_does_not_scale_per_candidate(): void
    {
        $branch = $this->createBranch('A');
        foreach (range(1, 20) as $index) {
            $product = $this->createProduct(['name' => "Candidate {$index}"]);
            $this->setAvailability($product, $branch, true);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rows = app(RecommendationCandidateService::class)->forBranch($branch);

        $this->assertCount(20, $rows);
        $this->assertSame(5, count(DB::getQueryLog()));

        foreach ($rows as $row) {
            $this->assertTrue($row['product']->relationLoaded('category'));
            $this->assertTrue($row['product']->relationLoaded('branchStatuses'));
            $this->assertTrue($row['product']->relationLoaded('sizes'));
        }

        $this->assertSame(5, count(DB::getQueryLog()));
    }

    private function createBranch(string $suffix): Branch
    {
        return Branch::query()->create([
            'name' => "Branch {$suffix}",
            'code' => 'REC-'.$suffix.'-'.uniqid(),
            'status' => true,
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        static $sequence = 0;
        $sequence++;

        $product = Product::query()->create(array_merge([
            'category_id' => $this->category->id,
            'name' => "Recommendation product {$sequence}",
            'slug' => "recommendation-product-{$sequence}",
            'sku' => "REC-{$sequence}",
            'price' => 30000,
            'description' => 'Recommendation candidate test product.',
            'serving_temperature' => null,
            'status' => true,
        ], $overrides));

        ProductSize::query()->create([
            'product_id' => $product->id,
            'size_id' => $this->size->id,
            'price' => 30000,
        ]);

        return $product;
    }

    private function setAvailability(Product $product, Branch $branch, bool $isAvailable): void
    {
        BranchProductStatus::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'is_available' => $isAvailable,
        ]);
    }

    private function recordSale(
        Product $product,
        Branch $branch,
        int $quantity,
        string $status,
        mixed $createdAt,
        string $paymentStatus = 'pending',
    ): void {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'subtotal' => 30000 * $quantity,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 30000 * $quantity,
            'payment_method' => 'cod',
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
        $order->timestamps = false;
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => ProductSize::query()->where('product_id', $product->id)->value('id'),
            'ice_level' => 100,
            'sugar_level' => 100,
            'quantity' => $quantity,
            'unit_price' => 30000,
            'total_price' => 30000 * $quantity,
        ]);
    }
}
