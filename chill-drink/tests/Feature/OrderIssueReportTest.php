<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderIssueReport;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Notifications\OrderIssueReportCreatedNotification;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class OrderIssueReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-21 12:00:00');
        config(['filesystems.default' => 'local']);
        Storage::fake('local');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_creates_issue_with_text_and_multiple_evidence_files(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-CREATE');
        $branchAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $branchCskh = User::factory()->create(['role_id' => 4, 'branch_id' => $branch->id]);
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);
        $otherBranchAdmin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $this->createBranch('ISSUE-NOTIFY-OTHER')->id,
        ]);

        $this->actingAs($customer)
            ->post(route('orders.issues.store', $order), $this->validIssuePayload())
            ->assertRedirect(route('orders.issues.create', $order));

        $report = OrderIssueReport::firstOrFail();
        $this->assertTrue($order->fresh()->issueReports->first()->is($report));
        $this->assertSame('missing_item', $report->type);
        $this->assertSame('open', $report->status);
        $this->assertCount(2, $report->evidence_files);
        Storage::disk('local')->assertExists($report->evidence_files[0]['path']);
        Storage::disk('local')->assertExists($report->evidence_files[1]['path']);
        $this->assertDatabaseHas('conversations', ['user_id' => $customer->id, 'order_id' => $order->id]);
        Notification::assertSentTo([$customer, $branchAdmin, $branchCskh, $superAdmin], OrderIssueReportCreatedNotification::class);
        Notification::assertNotSentTo($otherBranchAdmin, OrderIssueReportCreatedNotification::class);
    }

    public function test_pending_issue_badge_and_feed_are_scoped_to_admin_branch(): void
    {
        [$firstCustomer, $firstOrder, $firstBranch] = $this->createCustomerOrder('ISSUE-BADGE-A');
        [$secondCustomer, $secondOrder] = $this->createCustomerOrder('ISSUE-BADGE-B');
        $this->createIssue($firstOrder, $firstCustomer);
        $this->createIssue($secondOrder, $secondCustomer);
        $this->createIssue($firstOrder, $firstCustomer, [
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_type' => 'other',
        ]);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $firstBranch->id]);
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);

        $this->actingAs($admin)
            ->getJson(route('admin.order-issues.pending-count'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('message', 'Khách '.$firstCustomer->name.' vừa gửi yêu cầu cho đơn ISSUE-BADGE-A.');

        $this->actingAs($admin)
            ->get(route('admin.order-issues.index'))
            ->assertOk()
            ->assertSee('id="sidebar-order-issue-badge"', false)
            ->assertSee('>1</span>', false);

        $this->actingAs($superAdmin)
            ->getJson(route('admin.order-issues.pending-count'))
            ->assertOk()
            ->assertJsonPath('count', 2);
    }

    public function test_customer_and_guest_cannot_report_an_order_they_do_not_own(): void
    {
        [, $order] = $this->createCustomerOrder('ISSUE-OWNER');
        $otherCustomer = User::factory()->create(['role_id' => 1]);

        $this->actingAs($otherCustomer)
            ->post(route('orders.issues.store', $order), $this->validIssuePayload())
            ->assertForbidden();

        auth()->logout();
        $this->post(route('orders.issues.store', $order), $this->validIssuePayload())
            ->assertRedirect('/login');

        $this->assertDatabaseCount('order_issue_reports', 0);
    }

    public function test_only_recently_completed_orders_can_be_reported(): void
    {
        [$customer, $processingOrder] = $this->createCustomerOrder('ISSUE-STATUS', [
            'status' => OrderStatus::PREPARING,
        ]);

        $this->actingAs($customer)
            ->post(route('orders.issues.store', $processingOrder), $this->validIssuePayload())
            ->assertRedirect(route('orders.index'))
            ->assertSessionHas('error');

        [, $oldOrder] = $this->createCustomerOrder('ISSUE-OLD', [
            'user_id' => $customer->id,
            'status_changed_at' => now()->subHours(2),
        ]);

        $this->actingAs($customer)
            ->post(route('orders.issues.store', $oldOrder), $this->validIssuePayload())
            ->assertRedirect(route('orders.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('order_issue_reports', 0);
    }

    public function test_completed_order_can_be_reported_before_the_two_hour_deadline(): void
    {
        [$customer, $order] = $this->createCustomerOrder('ISSUE-WITHIN-WINDOW', [
            'status_changed_at' => now()->subMinutes(119),
        ]);

        $this->actingAs($customer)
            ->post(route('orders.issues.store', $order), $this->validIssuePayload())
            ->assertRedirect(route('orders.issues.create', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('order_issue_reports', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_duplicate_active_issue_is_rejected(): void
    {
        [$customer, $order] = $this->createCustomerOrder('ISSUE-DUPLICATE');
        $this->createIssue($order, $customer);

        $this->actingAs($customer)
            ->post(route('orders.issues.store', $order), $this->validIssuePayload())
            ->assertRedirect(route('orders.issues.create', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('order_issue_reports', 1);
    }

    public function test_admin_processing_sets_handler_and_enforces_branch_isolation(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-BRANCH');
        $issue = $this->createIssue($order, $customer);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $otherBranch = $this->createBranch('ISSUE-OTHER-BRANCH');
        $otherAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => $otherBranch->id]);

        $this->actingAs($otherAdmin)
            ->patch(route('admin.order-issues.update', $issue), $this->adminPayload('processing'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.order-issues.update', $issue), $this->adminPayload('processing'))
            ->assertRedirect();

        $issue->refresh();
        $this->assertSame('processing', $issue->status);
        $this->assertSame($admin->id, $issue->handled_by);
        $this->assertNotNull($issue->processing_at);
        $this->assertTrue($issue->handler->is($admin));
    }

    public function test_super_admin_can_view_issues_across_branches(): void
    {
        [$firstCustomer, $firstOrder] = $this->createCustomerOrder('ISSUE-ROOT-A');
        [$secondCustomer, $secondOrder] = $this->createCustomerOrder('ISSUE-ROOT-B');
        $this->createIssue($firstOrder, $firstCustomer);
        $this->createIssue($secondOrder, $secondCustomer);
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.order-issues.index'))
            ->assertOk()
            ->assertSee('ISSUE-ROOT-A')
            ->assertSee('ISSUE-ROOT-B');
    }

    public function test_invalid_workflow_transition_is_blocked(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-WORKFLOW');
        $issue = $this->createIssue($order, $customer);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);

        $this->actingAs($admin)
            ->from(route('admin.order-issues.index'))
            ->patch(route('admin.order-issues.update', $issue), $this->adminPayload('resolved', 'other'))
            ->assertRedirect(route('admin.order-issues.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame('open', $issue->fresh()->status);
    }

    public function test_rejected_issue_is_rendered_as_a_detailed_read_only_result(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-REJECTED');
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $this->createIssue($order, $customer, [
            'status' => 'rejected',
            'resolution_value' => 'Hình ảnh chưa chứng minh được sản phẩm bị lỗi.',
            'admin_note' => 'Vui lòng cung cấp hình ảnh rõ tem và toàn bộ ly trong lần yêu cầu tiếp theo.',
            'handled_by' => $admin->id,
            'rejected_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.order-issues.index'))
            ->assertOk()
            ->assertSee('Yêu cầu đã bị từ chối')
            ->assertSee('Hình ảnh chưa chứng minh được sản phẩm bị lỗi.')
            ->assertSee('Phản hồi gửi khách:')
            ->assertSee('Vui lòng cung cấp hình ảnh rõ tem và toàn bộ ly trong lần yêu cầu tiếp theo.')
            ->assertDontSee('Lưu phương án');
    }

    public function test_resolution_voucher_is_created_once(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-VOUCHER');
        $issue = $this->createIssue($order, $customer, ['status' => 'processing', 'processing_at' => now()]);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $payload = $this->adminPayload('awaiting_confirmation', 'voucher');

        $this->actingAs($admin)->patch(route('admin.order-issues.update', $issue), $payload)->assertRedirect();
        $voucherId = $issue->fresh()->voucher_coupon_id;
        $this->actingAs($admin)->patch(route('admin.order-issues.update', $issue), $payload)->assertRedirect();

        $this->assertNotNull($voucherId);
        $this->assertSame($voucherId, $issue->fresh()->voucher_coupon_id);
        $this->assertDatabaseCount('coupons', 1);
        $this->assertDatabaseCount('user_vouchers', 1);
        $this->assertDatabaseHas('user_vouchers', ['user_id' => $customer->id, 'coupon_id' => $voucherId]);

        $voucherCode = \App\Models\Voucher::findOrFail($voucherId)->code;
        $this->actingAs($admin)
            ->get(route('admin.order-issues.index'))
            ->assertOk()
            ->assertSee('Chờ khách xác nhận')
            ->assertSee($voucherCode)
            ->assertSee('Lưu phương án');
    }

    public function test_rejection_requires_a_clear_reason_and_clears_compensation_fields(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-REJECT-RULE');
        $issue = $this->createIssue($order, $customer);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);

        $this->actingAs($admin)
            ->patch(route('admin.order-issues.update', $issue), [
                'status' => 'rejected',
                'resolution_type' => 'voucher',
                'resolution_value' => 'Không cấp',
                'admin_note' => 'Ngắn',
            ])
            ->assertSessionHasErrors('admin_note');

        $this->actingAs($admin)
            ->patch(route('admin.order-issues.update', $issue), [
                'status' => 'rejected',
                'resolution_type' => 'voucher',
                'resolution_value' => 'Không cấp',
                'admin_note' => 'Hình ảnh không thể hiện đúng sản phẩm thuộc đơn hàng này.',
            ])
            ->assertSessionHasNoErrors();

        $issue->refresh();
        $this->assertSame('rejected', $issue->status);
        $this->assertNull($issue->resolution_type);
        $this->assertNull($issue->resolution_value);
        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_non_voucher_resolution_requires_specific_details(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-DETAIL-RULE');
        $issue = $this->createIssue($order, $customer, ['status' => 'processing']);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);

        $this->actingAs($admin)
            ->patch(route('admin.order-issues.update', $issue), [
                'status' => 'awaiting_confirmation',
                'resolution_type' => 'redelivery',
                'resolution_value' => '',
                'estimated_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'admin_note' => 'Đã xác minh yêu cầu của khách hàng.',
            ])
            ->assertSessionHasErrors('resolution_value');

        $this->assertSame('processing', $issue->fresh()->status);
    }

    public function test_customer_confirmation_cannot_bypass_resolution_workflow(): void
    {
        [$customer, $order] = $this->createCustomerOrder('ISSUE-CONFIRM');
        $issue = $this->createIssue($order, $customer);

        $this->actingAs($customer)
            ->post(route('orders.issues.confirm', [$order, $issue]))
            ->assertSessionHas('error');
        $this->assertSame('open', $issue->fresh()->status);

        $issue->update([
            'status' => 'awaiting_confirmation',
            'resolution_type' => 'other',
            'resolution_value' => 'Đã hỗ trợ trực tiếp',
            'approved_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)
            ->post(route('orders.issues.confirm', [$order, $issue]))
            ->assertSessionHas('success');

        $issue->refresh();
        $this->assertNotNull($issue->customer_confirmed_at);
        $this->assertSame('resolved', $issue->status);
        $this->assertNotNull($issue->resolved_at);
    }

    public function test_cskh_can_process_only_issues_from_their_branch(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-CSKH');
        $issue = $this->createIssue($order, $customer);
        $cskh = User::factory()->create(['role_id' => 4, 'branch_id' => $branch->id]);
        $otherCskh = User::factory()->create(['role_id' => 4, 'branch_id' => $this->createBranch('ISSUE-CSKH-OTHER')->id]);

        $this->actingAs($cskh)
            ->get(route('admin.chat.order-issues.index'))
            ->assertOk()
            ->assertSee('ISSUE-CSKH');

        $this->actingAs($otherCskh)
            ->patch(route('admin.chat.order-issues.update', $issue), $this->adminPayload('processing'))
            ->assertForbidden();

        $this->actingAs($cskh)
            ->patch(route('admin.chat.order-issues.update', $issue), $this->adminPayload('processing'))
            ->assertRedirect();

        $this->assertSame($cskh->id, $issue->fresh()->handled_by);
    }

    public function test_redelivery_requires_a_future_estimate_before_customer_confirmation(): void
    {
        [$customer, $order, $branch] = $this->createCustomerOrder('ISSUE-REDELIVERY');
        $issue = $this->createIssue($order, $customer, ['status' => 'processing']);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $product = Product::factory()->create();
        $size = Size::create(['name' => 'M', 'multiplier' => 1]);
        $productSize = ProductSize::create(['product_id' => $product->id, 'size_id' => $size->id, 'price' => 32000]);
        $originalItem = OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_size_id' => $productSize->id, 'ice_level' => 100, 'sugar_level' => 100, 'quantity' => 2, 'unit_price' => 32000, 'total_price' => 64000]);

        $payload = $this->adminPayload('awaiting_confirmation', 'redelivery');
        $payload['redelivery_items'] = [$originalItem->id => 1];
        $this->actingAs($admin)->patch(route('admin.order-issues.update', $issue), $payload)
            ->assertSessionHasErrors('estimated_at');

        $payload['estimated_at'] = now()->addHour()->format('Y-m-d H:i:s');
        $this->actingAs($admin)->patch(route('admin.order-issues.update', $issue), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $issue->refresh();
        $this->assertSame('awaiting_confirmation', $issue->status);
        $this->assertNotNull($issue->approved_at);
        $this->assertNotNull($issue->remedy_started_at);
        $this->assertNotNull($issue->estimated_at);
        $this->assertNotNull($issue->redelivery_order_id);
        $this->assertDatabaseHas('orders', ['id' => $issue->redelivery_order_id, 'support_issue_id' => $issue->id, 'total' => 0, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('order_items', ['order_id' => $issue->redelivery_order_id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 0]);

        $redeliveryOrder = Order::findOrFail($issue->redelivery_order_id);
        $this->actingAs($customer)->get(route('orders.issues.create', $order))
            ->assertOk()
            ->assertSee($redeliveryOrder->displayCode())
            ->assertSee('Theo dõi đơn giao bù');

        $this->actingAs($customer)->post(route('orders.issues.confirm', [$order, $issue]))
            ->assertSessionHas('error');
        $this->assertSame('awaiting_confirmation', $issue->fresh()->status);

        $redeliveryOrder->update(['status' => OrderStatus::DELIVERED]);
        $this->actingAs($customer)->post(route('orders.confirm-received', $redeliveryOrder))
            ->assertSessionHas('success');
        $this->assertSame('resolved', $issue->fresh()->status);
        $this->assertSame(OrderStatus::COMPLETED, $redeliveryOrder->fresh()->status);
        $this->assertNotNull($issue->fresh()->customer_confirmed_at);
    }

    public function test_staff_and_shipper_cannot_access_admin_issue_routes(): void
    {
        foreach ([User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $roleId) {
            $actor = User::factory()->create(['role_id' => $roleId]);

            $this->actingAs($actor)
                ->get(route('admin.order-issues.index'))
                ->assertRedirect(route('home'));
        }
    }

    public function test_issue_smoke_data_rolls_back_cleanly(): void
    {
        [$customer, $order] = $this->createCustomerOrder('ISSUE-ROLLBACK');

        try {
            DB::transaction(function () use ($customer, $order): void {
                $this->createIssue($order, $customer);
                throw new RuntimeException('Rollback issue smoke data');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('order_issue_reports', 0);
    }

    private function validIssuePayload(): array
    {
        return [
            'type' => 'missing_item',
            'description' => 'Đơn hàng bị thiếu một món đã thanh toán.',
            'evidence' => [
                $this->fakeEvidence('evidence-one.png'),
                $this->fakeEvidence('evidence-two.png'),
            ],
        ];
    }

    private function fakeEvidence(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
    }

    private function adminPayload(string $status, ?string $resolutionType = null): array
    {
        return [
            'status' => $status,
            'resolution_type' => $resolutionType,
            'resolution_value' => $resolutionType ? 'Phương án hỗ trợ phù hợp' : null,
            'admin_note' => 'Đã kiểm tra yêu cầu.',
        ];
    }

    private function createIssue(Order $order, User $customer, array $overrides = []): OrderIssueReport
    {
        return $order->issueReports()->create(array_merge([
            'user_id' => $customer->id,
            'type' => 'missing_item',
            'description' => 'Đơn hàng bị thiếu một món đã thanh toán.',
            'status' => 'open',
            'received_at' => now(),
        ], $overrides));
    }

    private function createCustomerOrder(string $code, array $overrides = []): array
    {
        $branch = $this->createBranch($code.'-BR');
        $customer = User::factory()->create(['role_id' => 1, 'branch_id' => null]);
        $order = Order::create(array_merge([
            'order_code' => $code,
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 20000,
            'discount' => 0,
            'total' => 120000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => OrderStatus::COMPLETED,
            'status_changed_at' => now(),
        ], $overrides));

        return [$customer, $order, $branch];
    }

    private function createBranch(string $code): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'status' => true,
        ]);
    }
}
