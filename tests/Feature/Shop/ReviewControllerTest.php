<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_order_displays_clear_review_links(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'completed');
        $product = $this->makeProduct();
        $this->makeOrderItem($order, $product);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Đánh giá sản phẩm')
            ->assertSee(route('orders.show', $order).'#order-products');

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Đơn hàng đã hoàn thành')
            ->assertSee(route('orders.reviews.create', [
                'order' => $order,
                'product_id' => $product->id,
            ]))
            ->assertSee('Viết đánh giá');
    }

    public function test_customer_can_submit_review_for_product_in_completed_order(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'completed');
        $product = $this->makeProduct();
        $this->makeOrderItem($order, $product);

        $this->actingAs($user)
            ->post(route('orders.reviews.store', $order), [
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => 'Sản phẩm tốt.',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Sản phẩm tốt.',
            'is_visible' => true,
        ]);
        $this->assertSame(1, $product->fresh()->rating_count);
        $this->assertSame('5.00', $product->fresh()->rating_avg);

        $this->actingAs($user)
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Sản phẩm tốt.');

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Xem đơn hàng')
            ->assertDontSee(route('orders.show', $order).'#order-products');
    }

    public function test_incomplete_order_explains_when_review_becomes_available(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'shipping');
        $product = $this->makeProduct();
        $this->makeOrderItem($order, $product);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Bạn có thể đánh giá sản phẩm sau khi đơn hàng hoàn thành.')
            ->assertDontSee('Viết đánh giá');
    }

    public function test_customer_cannot_review_another_users_order(): void
    {
        $order = $this->makeOrder(User::factory()->create(), 'completed');
        $product = $this->makeProduct();
        $this->makeOrderItem($order, $product);

        $this->actingAs(User::factory()->create())
            ->get(route('orders.reviews.create', [
                'order' => $order,
                'product_id' => $product->id,
            ]))
            ->assertForbidden();
    }

    private function makeOrder(User $user, string $status): Order
    {
        return Order::create([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => 'paid',
            'payment_method' => 'cod',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 1000,
            'shipping_rate_name' => 'Standard',
            'recipient_name' => 'Test customer',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'placed_at' => now(),
        ]);
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'name' => 'Test product',
            'slug' => 'test-product-'.Str::lower(Str::random(8)),
            'status' => 'active',
        ]);
    }

    private function makeOrderItem(Order $order, Product $product): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
        ]);
    }
}
