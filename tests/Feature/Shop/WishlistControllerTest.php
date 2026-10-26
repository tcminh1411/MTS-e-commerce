<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('wishlist.index'))->assertRedirect(route('login'));
    }

    public function test_toggle_adds_then_removes(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->post(route('wishlist.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->post(route('wishlist.toggle', $product))
            ->assertRedirect();

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_index_only_shows_users_products(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $p1 = $this->makeProduct(name: 'Mine');
        $p2 = $this->makeProduct(name: 'OtherUser');
        Wishlist::create(['user_id' => $user->id, 'product_id' => $p1->id]);
        Wishlist::create(['user_id' => $other->id, 'product_id' => $p2->id]);

        $this->actingAs($user)
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('OtherUser');
    }

    private function makeProduct(string $name = 'P'): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'status' => 'active',
            'price' => 100000,
        ]);
    }
}
