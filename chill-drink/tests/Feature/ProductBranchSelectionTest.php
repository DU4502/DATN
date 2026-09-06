<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBranchSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_change_the_branch_used_by_the_product_catalog(): void
    {
        $firstBranch = Branch::create([
            'name' => 'Chi nhánh Quận 1',
            'code' => 'CATALOG-Q1',
            'address' => '01 Nguyễn Huệ',
            'status' => true,
        ]);
        $secondBranch = Branch::create([
            'name' => 'Chi nhánh Quận 3',
            'code' => 'CATALOG-Q3',
            'address' => '03 Võ Văn Tần',
            'status' => true,
        ]);

        $this
            ->withSession(['nearest_branch_id' => $firstBranch->id])
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Đang mua tại')
            ->assertSee('Chi nhánh Quận 1')
            ->assertSee('Đổi chi nhánh')
            ->assertSee('id="branchSwitchModal"', false)
            ->assertSee('Chi nhánh Quận 3');

        $this
            ->withSession(['nearest_branch_id' => $firstBranch->id])
            ->from(route('products.index', ['sort' => 'newest']))
            ->post(route('branch.select'), ['branch_id' => $secondBranch->id])
            ->assertRedirect(route('products.index', ['sort' => 'newest']))
            ->assertSessionHas('nearest_branch_id', $secondBranch->id)
            ->assertSessionHas('branch_selection_mode', 'manual');

        $this
            ->withSession([
                'nearest_branch_id' => $secondBranch->id,
                'branch_selection_mode' => 'manual',
            ])
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Đang mua tại')
            ->assertSee('Chi nhánh Quận 3');
    }

    public function test_inactive_branch_cannot_be_selected_for_the_product_catalog(): void
    {
        $inactiveBranch = Branch::create([
            'name' => 'Chi nhánh đã đóng',
            'code' => 'CATALOG-CLOSED',
            'status' => false,
        ]);

        $this
            ->from(route('products.index'))
            ->post(route('branch.select'), ['branch_id' => $inactiveBranch->id])
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors('branch_id');

        $this->assertNull(session('nearest_branch_id'));
    }

    public function test_checkout_can_synchronize_its_selected_branch_with_the_header(): void
    {
        $branch = Branch::create([
            'name' => 'Chi nhánh 4',
            'code' => 'CHECKOUT-BRANCH-4',
            'status' => true,
        ]);

        $this
            ->postJson(route('branch.select'), ['branch_id' => $branch->id])
            ->assertOk()
            ->assertJsonPath('branch.id', $branch->id)
            ->assertJsonPath('branch.display_name', 'Chi nhánh 4')
            ->assertSessionHas('nearest_branch_id', $branch->id)
            ->assertSessionHas('branch_selection_mode', 'manual');
    }
}
