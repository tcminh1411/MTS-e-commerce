<?php

namespace Tests\Feature\Admin;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_returns(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.orders.returns.index'))
            ->assertForbidden();
    }

    public function test_index_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.returns.index'))
            ->assertOk();
    }

    public function test_approve_transitions_pending_to_approved(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('pending');

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.approve', $return), [
                'refund_amount' => 50000,
                'refund_method' => 'bank_transfer',
                'admin_note' => 'OK',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $return->fresh()->status);
        $this->assertSame(50000, $return->fresh()->refund_amount);
    }

    public function test_approve_rejects_non_pending(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('approved');

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.approve', $return), [
                'refund_amount' => 50000,
                'refund_method' => 'cash',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_approve_validates_refund_not_exceeding_order_total(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('pending', orderTotal: 100000);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.approve', $return), [
                'refund_amount' => 200000,
                'refund_method' => 'cash',
            ])
            ->assertSessionHasErrors('refund_amount');
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('pending');

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.reject', $return), [
                'admin_note' => '',
            ])
            ->assertSessionHasErrors('admin_note');
    }

    public function test_reject_transitions_pending_to_rejected(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('pending');

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.reject', $return), [
                'admin_note' => 'Không đủ điều kiện',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $return->fresh()->status);
    }

    public function test_mark_refunded_does_not_update_payment_status_without_order_stock_history(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('approved', orderTotal: 100000);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.markRefunded', $return))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('approved', $return->fresh()->status);
        $this->assertSame('paid', $return->order->fresh()->payment_status);
    }

    public function test_mark_refunded_restores_order_items_to_their_original_warehouse_once(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('approved', orderTotal: 100000);
        $product = Product::create([
            'name' => 'Returned product',
            'slug' => 'returned-product',
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'RETURN-SKU',
            'name' => 'Default',
            'is_default' => true,
            'stock' => 3,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Return warehouse',
            'code' => 'RETURN',
            'is_active' => true,
        ]);
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        OrderItem::create([
            'order_id' => $return->order_id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 50000,
            'line_total' => 100000,
        ]);
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'out',
            'quantity' => -2,
            'stock_before' => 5,
            'stock_after' => 3,
            'reference_type' => 'order',
            'reference_id' => $return->order_id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.markRefunded', $return))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('refunded', $return->fresh()->status);
        $this->assertSame('refunded', $return->order->fresh()->payment_status);
        $this->assertSame(5, $warehouse->inventories()->first()->quantity);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => 'return',
            'reference_id' => $return->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.markRefunded', $return))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(5, $warehouse->inventories()->first()->quantity);
        $this->assertSame(1, StockMovement::where('reference_type', 'return')
            ->where('reference_id', $return->id)
            ->where('type', 'return')
            ->count());
    }

    public function test_mark_refunded_rejects_non_approved(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('pending');

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.markRefunded', $return))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function makeReturnRequest(string $status, int $orderTotal = 100000): ReturnRequest
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
            'subtotal' => $orderTotal,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => $orderTotal,
            'shipping_rate_name' => 'Std',
            'recipient_name' => 'X',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'X',
            'placed_at' => now(),
        ]);

        return ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Sản phẩm lỗi',
            'status' => $status,
            'requested_at' => now(),
        ]);
    }
}
