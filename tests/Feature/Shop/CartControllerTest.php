<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_product_to_cart(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_authenticated_user_adds_to_their_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertSame(1, $cart->items()->count());
    }

    public function test_add_with_variant_stores_variant_id(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $this->actingAs($user)
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['variant_id' => $variant->id]);
    }

    public function test_add_returns_json_when_wants_json(): void
    {
        $product = $this->makeProduct();

        $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJson(['ok' => true, 'count' => 3]);
    }

    public function test_update_changes_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        $item = CartItem::create([
            'cart_id' => $cart->id, 'product_id' => $product->id,
            'quantity' => 1, 'unit_price' => 100000,
        ]);

        $this->patch(route('cart.update', $item), ['quantity' => 5])
            ->assertRedirect();

        $this->assertSame(5, $item->fresh()->quantity);
    }

    public function test_destroy_removes_item(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        $item = CartItem::create([
            'cart_id' => $cart->id, 'product_id' => $product->id,
            'quantity' => 1, 'unit_price' => 100000,
        ]);

        $this->delete(route('cart.destroy', $item))->assertRedirect();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_clear_removes_all_items(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $cart = Cart::create(['user_id' => $user->id, 'currency' => 'VND']);
        foreach (['A', 'B'] as $n) {
            $p = $this->makeProduct(name: $n);
            CartItem::create([
                'cart_id' => $cart->id, 'product_id' => $p->id,
                'quantity' => 1, 'unit_price' => 100000,
            ]);
        }

        $this->delete(route('cart.clear'))->assertRedirect();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_cart_is_merged_on_login(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);

        $guestToken = session('guest_cart_token');
        $this->assertNotNull($guestToken);
        $guestCart = Cart::where('session_token', $guestToken)->first();
        $this->assertNotNull($guestCart);
        $this->assertSame(2, $guestCart->items()->sum('quantity'));

        $user = User::factory()->create(['password' => bcrypt('password')]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseMissing('carts', ['session_token' => $guestToken]);
        $userCart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($userCart);
        $this->assertSame(2, $userCart->items()->sum('quantity'));
    }

    private function makeProduct(string $name = 'Product'): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'status' => 'active',
            'price' => 100000,
        ]);
    }

    private function makeVariant(Product $product): ProductVariant
    {
        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'D', 'is_default' => true,
            'stock' => 10, 'status' => 'active', 'price' => 100000,
        ]);
    }
}
