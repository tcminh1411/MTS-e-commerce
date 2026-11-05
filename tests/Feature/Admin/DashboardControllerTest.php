<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_revenue_excludes_refunded_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $paidOrder = $this->makeOrder(40000, 'paid');
        $refundedOrder = $this->makeOrder(100000, 'refunded');
        OrderItem::create([
            'order_id' => $paidOrder->id,
            'product_name' => 'Paid product',
            'unit_price' => 20000,
            'quantity' => 2,
            'line_total' => 40000,
        ]);
        OrderItem::create([
            'order_id' => $refundedOrder->id,
            'product_name' => 'Refunded product',
            'unit_price' => 100000,
            'quantity' => 7,
            'line_total' => 100000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('40.000 ₫')
            ->assertDontSee('140.000 ₫')
            ->assertViewHas('totalProductsSold', 2)
            ->assertViewHas('topProductsLabels', ['Paid product'])
            ->assertViewHas('topProductsData', [2])
            ->assertViewHas(
                'revenueData',
                fn (array $revenueData): bool => in_array(40000, $revenueData, true)
                    && ! in_array(100000, $revenueData, true)
            );
    }

    private function makeOrder(int $total, string $paymentStatus): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'status' => 'completed',
            'payment_status' => $paymentStatus,
            'payment_method' => 'cod',
            'subtotal' => $total,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'shipping_rate_name' => 'Standard',
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'placed_at' => now(),
        ]);
    }
}
