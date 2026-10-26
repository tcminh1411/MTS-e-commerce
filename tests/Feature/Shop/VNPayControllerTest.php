<?php

namespace Tests\Feature\Shop;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class VNPayControllerTest extends TestCase
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

    public function test_return_with_invalid_signature_redirects_with_error(): void
    {
        $this->get(route('vnpay.return', [
            'vnp_TxnRef' => 'ORD-1',
            'vnp_SecureHash' => 'deadbeef',
        ]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_return_success_marks_order_paid_and_confirmed(): void
    {
        $order = $this->makeOrder(total: 100000);

        $params = [
            'vnp_TxnRef' => $order->order_number,
            'vnp_Amount' => 100000 * 100,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->get(route('vnpay.return', $params))
            ->assertRedirect(route('checkout.success', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_return_failure_cancels_order_and_restores_stock(): void
    {
        [$order, $variant, $warehouse] = $this->makeOrderWithStockDeducted(qty: 2);

        $params = [
            'vnp_TxnRef' => $order->order_number,
            'vnp_Amount' => $order->total * 100,
            'vnp_ResponseCode' => '24',
            'vnp_TransactionStatus' => '02',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->get(route('vnpay.return', $params))
            ->assertRedirect(route('cart.index'));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $warehouse->inventories()->first()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
        ]);
    }

    public function test_ipn_with_invalid_signature_returns_97(): void
    {
        $this->getJson(route('vnpay.ipn', [
            'vnp_TxnRef' => 'ORD-X',
            'vnp_SecureHash' => 'bad',
        ]))
            ->assertOk()
            ->assertJson(['RspCode' => '97']);
    }

    public function test_ipn_order_not_found_returns_01(): void
    {
        $params = [
            'vnp_TxnRef' => 'NO-SUCH-ORDER',
            'vnp_Amount' => 100000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->getJson(route('vnpay.ipn', $params))
            ->assertOk()
            ->assertJson(['RspCode' => '01']);
    }

    public function test_ipn_amount_mismatch_returns_04(): void
    {
        $order = $this->makeOrder(total: 100000);
        $params = [
            'vnp_TxnRef' => $order->order_number,
            'vnp_Amount' => 999,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->getJson(route('vnpay.ipn', $params))
            ->assertOk()
            ->assertJson(['RspCode' => '04']);
    }

    public function test_ipn_success_marks_order_paid(): void
    {
        $order = $this->makeOrder(total: 100000);
        $params = [
            'vnp_TxnRef' => $order->order_number,
            'vnp_Amount' => 100000 * 100,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->getJson(route('vnpay.ipn', $params))
            ->assertOk()
            ->assertJson(['RspCode' => '00']);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_ipn_idempotent_when_already_paid(): void
    {
        $order = $this->makeOrder(total: 100000, paid: true);
        $params = [
            'vnp_TxnRef' => $order->order_number,
            'vnp_Amount' => 100000 * 100,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];
        $params['vnp_SecureHash'] = $this->sign($params);

        $this->getJson(route('vnpay.ipn', $params))
            ->assertOk()
            ->assertJson(['RspCode' => '02']);
    }

    private function makeOrder(int $total = 100000, bool $paid = false): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'status' => $paid ? 'confirmed' : 'pending',
            'payment_status' => $paid ? 'paid' : 'unpaid',
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

    private function makeOrderWithStockDeducted(int $qty): array
    {
        $warehouse = Warehouse::create(['name' => 'W', 'code' => 'W', 'is_active' => true]);
        $product = Product::create([
            'name' => 'P', 'slug' => 'p-'.Str::random(6), 'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'D', 'is_default' => true, 'stock' => 8, 'status' => 'active',
        ]);
        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 8,
        ]);
        $order = $this->makeOrder(total: 100000);
        $order->update(['status' => 'confirmed']);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => 'D',
            'quantity' => $qty,
            'unit_price' => 50000,
            'line_total' => 50000 * $qty,
        ]);
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -$qty,
            'stock_before' => 10,
            'stock_after' => 8,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        return [$order->fresh('items'), $variant, $warehouse];
    }

    private function sign(array $params): string
    {
        $params = array_filter(
            $params,
            fn ($k) => str_starts_with($k, 'vnp_'),
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
