<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VnpayPaymentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.vnpay', [
            'tmn_code' => 'TESTCODE',
            'hash_secret' => 'test-secret',
            'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'return_url' => 'https://example.ngrok-free.app/vnpay/return',
            'ipn_url' => 'https://example.ngrok-free.app/vnpay/ipn',
        ]);
    }

    public function test_order_owner_can_start_vnpay_payment_with_valid_signature(): void
    {
        $user = $this->customer();
        $order = $this->vnpayOrder($user);

        $response = $this->actingAs($user)->get(route('vnpay.payment', $order));

        $response->assertRedirectContains('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?');

        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);
        $secureHash = $params['vnp_SecureHash'];
        unset($params['vnp_SecureHash']);

        $this->assertSame('TESTCODE', $params['vnp_TmnCode']);
        $this->assertSame((string) ($order->total * 100), $params['vnp_Amount']);
        $this->assertSame(
            hash_hmac('sha512', $this->hashData($params), 'test-secret'),
            $secureHash
        );
    }

    public function test_user_cannot_pay_another_users_order(): void
    {
        $owner = $this->customer();
        $otherUser = $this->customer();
        $order = $this->vnpayOrder($owner);

        $this->actingAs($otherUser)
            ->get(route('vnpay.payment', $order))
            ->assertForbidden();
    }

    public function test_vnpay_return_is_public_and_shows_thank_you_page_after_payment(): void
    {
        $user = $this->customer();
        $order = $this->vnpayOrder($user);
        $params = $this->signedParams([
            'vnp_Amount' => $order->total * 100,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionNo' => '14933727',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => "order_{$order->id}_1710000000",
        ]);

        $this->get(route('vnpay.return', $params))
            ->assertOk()
            ->assertSee('Cảm ơn bạn đã thanh toán')
            ->assertSee('Mã đơn hàng')
            ->assertDontSee('Đăng nhập tài khoản');

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('14933727', $order->vnpay_transaction_id);
    }

    public function test_invalid_vnpay_return_shows_result_page_instead_of_login(): void
    {
        $this->get(route('vnpay.return', [
            'vnp_TxnRef' => 'order_999_1710000000',
            'vnp_SecureHash' => 'invalid',
        ]))
            ->assertOk()
            ->assertSee('Không thể xác nhận thanh toán')
            ->assertDontSee('Đăng nhập tài khoản');
    }

    public function test_vnpay_ipn_rejects_invalid_signature(): void
    {
        $this->get(route('vnpay.ipn', [
            'vnp_TxnRef' => 'order_999_1710000000',
            'vnp_SecureHash' => 'invalid',
        ]))->assertOk()->assertExactJson([
            'RspCode' => '97',
            'Message' => 'Invalid signature',
        ]);
    }

    public function test_vnpay_ipn_marks_successful_order_as_paid(): void
    {
        $user = $this->customer();
        $order = $this->vnpayOrder($user);
        $params = $this->signedParams([
            'vnp_Amount' => $order->total * 100,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionNo' => '14933728',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => "order_{$order->id}_1710000001",
        ]);

        $this->get(route('vnpay.ipn', $params))
            ->assertOk()
            ->assertExactJson([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'processing',
            'vnpay_transaction_id' => '14933728',
        ]);
    }

    private function signedParams(array $params): array
    {
        $params['vnp_SecureHash'] = hash_hmac('sha512', $this->hashData($params), 'test-secret');

        return $params;
    }

    private function hashData(array $params): string
    {
        ksort($params);

        return collect($params)
            ->map(fn ($value, $key) => urlencode((string) $key).'='.urlencode((string) $value))
            ->implode('&');
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'VNPay Customer',
            'email' => 'vnpay-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => 1,
        ]);
    }

    private function vnpayOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'subtotal' => 100000,
            'shipping_fee' => 22000,
            'discount' => 0,
            'total' => 122000,
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }
}
