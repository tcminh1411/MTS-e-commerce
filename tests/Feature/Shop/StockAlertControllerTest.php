<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockAlertControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_email(): void
    {
        $product = $this->makeProduct();

        $this->post(route('stock-alerts.store', $product), [
            'email' => 'buyer@example.com',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stock_alerts', [
            'product_id' => $product->id,
            'email' => 'buyer@example.com',
            'variant_id' => null,
        ]);
    }

    public function test_validation_requires_valid_email(): void
    {
        $product = $this->makeProduct();

        $this->post(route('stock-alerts.store', $product), [
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');
    }

    public function test_duplicate_registration_is_idempotent(): void
    {
        $product = $this->makeProduct();
        StockAlert::create([
            'product_id' => $product->id,
            'email' => 'buyer@example.com',
        ]);

        $this->post(route('stock-alerts.store', $product), [
            'email' => 'buyer@example.com',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, StockAlert::count());
    }

    public function test_variant_must_belong_to_product(): void
    {
        $product = $this->makeProduct();
        $otherProduct = $this->makeProduct(name: 'Other');
        $foreignVariant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'D', 'is_default' => true, 'stock' => 0, 'status' => 'active',
        ]);

        $this->post(route('stock-alerts.store', $product), [
            'email' => 'buyer@example.com',
            'variant_id' => $foreignVariant->id,
        ])->assertSessionHasErrors('variant_id');
    }

    public function test_authenticated_user_has_user_id_saved(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->post(route('stock-alerts.store', $product), [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('stock_alerts', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
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
