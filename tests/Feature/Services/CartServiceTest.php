<?php

namespace Tests\Feature\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_creates_user_cart_on_first_call(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = app(CartService::class)->current();

        $this->assertSame($user->id, $cart->user_id);
        $this->assertSame('VND', $cart->currency);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_current_reuses_existing_user_cart(): void
    {
        $user = User::factory()->create();
        $existing = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        $this->actingAs($user);

        $cart = app(CartService::class)->current();

        $this->assertSame($existing->id, $cart->id);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_current_creates_guest_cart_from_session_token(): void
    {
        $this->withSession(['guest_cart_token' => 'guest-123']);

        $cart = app(CartService::class)->current();

        $this->assertNull($cart->user_id);
        $this->assertSame('guest-123', $cart->session_token);
    }

    public function test_add_creates_new_item_for_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);

        $item = app(CartService::class)->add($product, null, 2);

        $this->assertSame($product->id, $item->product_id);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(100000.0, (float) $item->unit_price);
    }

    public function test_add_uses_variant_price_when_provided(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);
        $variant = $this->makeVariant($product, price: 85000, sale: null);

        $item = app(CartService::class)->add($product, $variant, 1);

        $this->assertSame($variant->id, $item->variant_id);
        $this->assertSame(85000.0, (float) $item->unit_price);
    }

    public function test_add_increments_quantity_for_same_variant(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);

        $service = app(CartService::class);
        $service->add($product, null, 2);
        $item = $service->add($product, null, 3);

        $this->assertSame(5, $item->quantity);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_add_uses_sale_price_when_present(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000, sale: 80000);

        $item = app(CartService::class)->add($product, null, 1);

        $this->assertSame(80000.0, (float) $item->unit_price);
    }

    public function test_update_qty_sets_new_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct();
        $service = app(CartService::class);
        $item = $service->add($product, null, 1);

        $service->updateQty($item, 5);

        $this->assertSame(5, $item->fresh()->quantity);
    }

    public function test_update_qty_zero_deletes_item(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct();
        $service = app(CartService::class);
        $item = $service->add($product, null, 1);

        $service->updateQty($item, 0);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_remove_deletes_item(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct();
        $service = app(CartService::class);
        $item = $service->add($product, null, 1);

        $service->remove($item);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_clear_deletes_all_items_in_current_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $productA = $this->makeProduct(name: 'A');
        $productB = $this->makeProduct(name: 'B');
        $service = app(CartService::class);
        $service->add($productA, null, 1);
        $service->add($productB, null, 1);

        $service->clear();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_refresh_prices_updates_to_current_variant_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct(price: 100000);
        $service = app(CartService::class);
        $item = $service->add($product, null, 1);

        $product->update(['sale_price' => 70000]);

        $cart = Cart::where('user_id', $user->id)->first();
        $service->refreshPrices($cart);

        $this->assertSame(70000.0, (float) $item->fresh()->unit_price);
    }

    private function makeProduct(string $name = 'Test', int $price = 100000, ?int $sale = null): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'status' => 'active',
            'price' => $price,
            'sale_price' => $sale,
        ]);
    }

    private function makeVariant(Product $product, int $price = 100000, ?int $sale = null): ProductVariant
    {
        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'name' => 'Variant',
            'price' => $price,
            'sale_price' => $sale,
            'is_default' => true,
            'stock' => 10,
            'status' => 'active',
        ]);
    }
}
