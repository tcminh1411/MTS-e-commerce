<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_exposes_active_variant_price_stock_and_image_for_selection(): void
    {
        $product = Product::create([
            'name' => 'Variant product',
            'slug' => 'variant-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-BLUE',
            'name' => 'Blue',
            'price' => 120000,
            'sale_price' => 110000,
            'image_url' => 'https://example.com/blue.jpg',
            'is_default' => true,
            'stock' => 4,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-RED',
            'name' => 'Red',
            'price' => 125000,
            'stock' => 2,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-HIDDEN',
            'name' => 'Hidden',
            'price' => 130000,
            'stock' => 9,
            'status' => 'inactive',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('selectedVariantId', $variant->id)
            ->assertViewHas('variantOptions', function ($variants) use ($variant): bool {
                $selectedVariant = $variants->firstWhere('id', $variant->id);

                return $variants->count() === 2
                    && $selectedVariant['priceLabel'] === '120,000đ'
                    && $selectedVariant['salePriceLabel'] === '110,000đ'
                    && $selectedVariant['stock'] === 4
                    && $selectedVariant['image'] === 'https://example.com/blue.jpg';
            })
            ->assertSee('selectVariant(variant.id)', false);
    }

    public function test_variable_product_without_variants_still_renders_for_purchase(): void
    {
        $product = Product::create([
            'name' => 'Product without variants',
            'slug' => 'product-without-variants',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('variantOptions', fn ($variants): bool => $variants->isEmpty())
            ->assertViewHas('hasVariants', false)
            ->assertSee('Thêm vào giỏ');
    }
}
