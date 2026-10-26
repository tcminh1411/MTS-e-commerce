<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function staff(): User
    {
        $u = User::factory()->create();
        $u->assignRole('staff');

        return $u;
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-TST-'.uniqid(),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 100000,
            'shipping_rate_name' => 'Standard',
            'recipient_name' => 'Test',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test addr',
            'placed_at' => now(),
        ], $overrides));
    }

    public function test_admin_can_mark_unpaid_as_paid(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->admin())
            ->patch(route('admin.orders.updatePaymentStatus', $order), [
                'payment_status' => 'paid',
                'note' => 'Đã nhận tiền COD',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $h = $order->statusHistory()->latest('id')->first();
        $this->assertNotNull($h);
        $this->assertStringStartsWith('[Payment]', $h->note);
        $this->assertStringContainsString('unpaid → paid', $h->note);
        $this->assertStringContainsString('Đã nhận tiền COD', $h->note);
    }

    public function test_admin_can_refund(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid']);
        $this->actingAs($this->admin())
            ->patch(route('admin.orders.updatePaymentStatus', $order), ['payment_status' => 'refunded'])
            ->assertRedirect();
        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_staff_cannot_change_payment_status(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->staff())
            ->patch(route('admin.orders.updatePaymentStatus', $order), ['payment_status' => 'paid'])
            ->assertForbidden();
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_same_status_returns_error(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid']);
        $this->actingAs($this->admin())
            ->patch(route('admin.orders.updatePaymentStatus', $order), ['payment_status' => 'paid'])
            ->assertSessionHas('error');
    }

    public function test_invalid_status_rejected(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->admin())
            ->patch(route('admin.orders.updatePaymentStatus', $order), ['payment_status' => 'xxx'])
            ->assertSessionHasErrors('payment_status');
    }
}
