<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\BranchSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSlideManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_restore_slide_with_an_active_duplicate_sort_order(): void
    {
        $branch = Branch::create([
            'name' => 'Chi nhánh Slide Test',
            'code' => 'SLIDE-TEST',
            'address' => 'Quận 1',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        BranchSlide::create($this->slideData($branch->id, 'Slide đang hoạt động'));
        $deletedSlide = BranchSlide::create($this->slideData($branch->id, 'Slide trong thùng rác'));
        $deletedSlide->delete();

        $this->actingAs($admin)
            ->post(route('admin.slides.restore', $deletedSlide->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSoftDeleted('branch_slides', ['id' => $deletedSlide->id]);
    }

    private function slideData(int $branchId, string $title): array
    {
        return [
            'branch_id' => $branchId,
            'product_name' => 'Trà sữa test',
            'title' => $title,
            'price' => '30.000đ',
            'description' => 'Slide test',
            'bg_color' => '#5d9c59',
            'sort_order' => 1,
            'is_active' => true,
            'image' => '/images/test-slide.png',
        ];
    }
}
