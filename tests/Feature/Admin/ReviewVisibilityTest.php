<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_hide_and_restore_review_on_product_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $review = $this->makeReview();

        $this->actingAs($admin)
            ->patch(route('admin.reviews.hide', $review))
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã ẩn đánh giá #'.$review->id.'.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_visible' => false,
        ]);
        $this->assertSame(0, $review->product->fresh()->rating_count);
        $this->get(route('products.show', $review->product->slug))
            ->assertOk()
            ->assertDontSee('Đánh giá kiểm thử');

        $this->actingAs($admin)
            ->patch(route('admin.reviews.show', $review))
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã hiển thị đánh giá #'.$review->id.'.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_visible' => true,
        ]);
        $this->assertSame(1, $review->product->fresh()->rating_count);
        $this->get(route('products.show', $review->product->slug))
            ->assertOk()
            ->assertSee('Đánh giá kiểm thử');
    }

    public function test_staff_cannot_hide_review(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $review = $this->makeReview();

        $this->actingAs($staff)
            ->patch(route('admin.reviews.hide', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_visible' => true,
        ]);
    }

    private function makeReview(): Review
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Test product',
            'slug' => 'test-product-'.Str::lower(Str::random(8)),
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'status' => 'completed',
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
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Đánh giá kiểm thử',
            'is_visible' => true,
        ]);
        $product->recalculateRating();

        return $review;
    }
}
