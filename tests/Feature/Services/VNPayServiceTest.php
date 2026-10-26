<?php

namespace Tests\Feature\Services;

use App\Models\Order;
use App\Services\VNPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class VNPayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('vnpay', [
            'tmn_code' => 'TESTTMN',
            'hash_secret' => 'TESTSECRET',
            'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'return_url' => 'https://example.test/vnpay/return',
            'ipn_url' => 'https://example.test/vnpay/ipn',
            'locale' => 'vn',
            'version' => '2.1.0',
            'command' => 'pay',
            'order_type' => 'other',
            'curr_code' => 'VND',
        ]);
    }

    public function test_build_payment_url_contains_required_params(): void
    {
        $order = $this->makeOrder(total: 100000);

        $url = app(VNPayService::class)->buildPaymentUrl($order, '127.0.0.1');

        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?', $url);
        $this->assertStringContainsString('vnp_TmnCode=TESTTMN', $url);
        $this->assertStringContainsString('vnp_TxnRef='.$order->order_number, $url);
        $this->assertStringContainsString('vnp_Amount=10000000', $url);
        $this->assertStringContainsString('vnp_SecureHash=', $url);
    }

    public function test_verify_signature_accepts_valid_hash(): void
    {
        $params = [
            'vnp_TxnRef' => 'ORD-1',
            'vnp_Amount' => 10000000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->assertTrue(app(VNPayService::class)->verifySignature($params));
    }

    public function test_verify_signature_rejects_invalid_hash(): void
    {
        $params = [
            'vnp_TxnRef' => 'ORD-1',
            'vnp_Amount' => 10000000,
            'vnp_ResponseCode' => '00',
            'vnp_SecureHash' => 'deadbeef',
        ];

        $this->assertFalse(app(VNPayService::class)->verifySignature($params));
    }

    public function test_verify_signature_rejects_missing_hash(): void
    {
        $params = ['vnp_TxnRef' => 'ORD-1'];

        $this->assertFalse(app(VNPayService::class)->verifySignature($params));
    }

    public function test_verify_signature_ignores_non_vnp_params(): void
    {
        $params = [
            'vnp_TxnRef' => 'ORD-1',
            'vnp_Amount' => 10000000,
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $withNoise = array_merge($params, ['some_field' => 'noise']);

        $this->assertTrue(app(VNPayService::class)->verifySignature($withNoise));
    }

    public function test_is_success_response_requires_both_codes_00(): void
    {
        $service = app(VNPayService::class);

        $this->assertTrue($service->isSuccessResponse([
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ]));
        $this->assertFalse($service->isSuccessResponse([
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '07',
        ]));
        $this->assertFalse($service->isSuccessResponse([
            'vnp_ResponseCode' => '24',
            'vnp_TransactionStatus' => '00',
        ]));
        $this->assertFalse($service->isSuccessResponse([]));
    }

    private function makeOrder(int $total = 100000): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'vnpay',
            'subtotal' => $total,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'shipping_rate_name' => 'Std',
            'recipient_name' => 'X',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'X',
            'placed_at' => now(),
        ]);
    }

    private function sign(array $params): string
    {
        $params = array_filter(
            $params,
            fn ($key) => str_starts_with($key, 'vnp_'),
            ARRAY_FILTER_USE_KEY
        );
        ksort($params);

        $hashData = '';
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i === 1) {
                $hashData .= '&'.urlencode($k).'='.urlencode($v);
            } else {
                $hashData .= urlencode($k).'='.urlencode($v);
                $i = 1;
            }
        }

        return hash_hmac('sha512', $hashData, config('vnpay.hash_secret'));
    }
}
