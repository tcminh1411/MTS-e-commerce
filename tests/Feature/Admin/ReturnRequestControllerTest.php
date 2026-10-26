<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
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

    public function test_mark_refunded_updates_order_payment_status(): void
    {
        $admin = $this->admin();
        $return = $this->makeReturnRequest('approved', orderTotal: 100000);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.markRefunded', $return))
            ->assertRedirect();

        $this->assertSame('refunded', $return->fresh()->status);
        $this->assertSame('refunded', $return->order->fresh()->payment_status);
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
