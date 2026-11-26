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
            ->assertViewHas('selectedVariantId', null)
            ->assertViewHas('variantOptions', function ($variants) use ($variant): bool {
                $selectedVariant = $variants->firstWhere('id', $variant->id);

                return $variants->count() === 2
                    && $selectedVariant['priceLabel'] === '120,000đ'
                    && $selectedVariant['salePriceLabel'] === '110,000đ'
                    && $selectedVariant['stock'] === 4
                    && $selectedVariant['image'] === 'https://example.com/blue.jpg';
            })
            ->assertSee('selectVariant(variant.id)', false)
            ->assertSee('Mua ngay');
    }

    public function test_product_detail_builds_public_url_for_local_variant_image(): void
    {
        $product = Product::create([
            'name' => 'Local image product',
            'slug' => 'local-image-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'LOCAL-IMAGE',
            'name' => 'Blue',
            'price' => 120000,
            'image_url' => 'variants/blue.jpg',
            'is_default' => true,
            'stock' => 1,
            'status' => 'active',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('variantOptions', function ($variants) use ($variant): bool {
                return $variants->firstWhere('id', $variant->id)['image']
                    === asset('storage/variants/blue.jpg');
            });
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
            ->assertSee('Thêm vào giỏ')
            ->assertSee('Mua ngay');
    }

    public function test_product_listing_renders_a_quick_view_without_preselecting_a_variant(): void
    {
        $product = Product::create([
            'name' => 'Quick view product',
            'slug' => 'quick-view-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $product->rating_avg = 4.5;
        $product->rating_count = 8;
        $product->sold_count = 42;
        $product->save();
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'QUICK-VIEW-BLUE',
            'name' => 'Blue',
            'price' => 100000,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'QUICK-VIEW-RED',
            'name' => 'Red',
            'price' => 110000,
            'status' => 'active',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Mua ngay')
            ->assertSee('selectedVariantId: null', false)
            ->assertSee('Quick view product')
            ->assertSee('Blue')
            ->assertSee('Red')
            ->assertSee('Đánh giá 4.5 trên 5 sao')
            ->assertSee('(8)')
            ->assertSee('Đã bán 42')
            ->assertSee(asset('storage/Logo.png'))
            ->assertSee(asset('storage/Favicon.png'));
    }
}
