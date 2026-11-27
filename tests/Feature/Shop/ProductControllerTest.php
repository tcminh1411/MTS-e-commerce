<?php

namespace Tests\Feature\Shop;

use App\Models\Attribute;
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

    public function test_product_detail_renders_a_selector_for_each_variant_attribute(): void
    {
        $product = Product::create([
            'name' => 'Three attribute product',
            'slug' => 'three-attribute-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $attributes = collect([
            [
                'name' => 'RAM',
                'slug' => 'ram',
                'value' => '16 GB',
                'value_slug' => '16-gb',
                'other_value' => '8 GB',
                'other_value_slug' => '8-gb',
            ],
            [
                'name' => 'Bộ nhớ',
                'slug' => 'storage',
                'value' => '512 GB',
                'value_slug' => '512-gb',
                'other_value' => '256 GB',
                'other_value_slug' => '256-gb',
            ],
            [
                'name' => 'Màu sắc',
                'slug' => 'color',
                'value' => 'Đen',
                'value_slug' => 'black',
                'other_value' => 'Bạc',
                'other_value_slug' => 'silver',
            ],
        ])->map(function (array $data) use ($product) {
            $attribute = Attribute::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'type' => 'select',
            ]);
            $value = $attribute->values()->create([
                'value' => $data['value'],
                'slug' => $data['value_slug'],
            ]);
            $otherValue = $attribute->values()->create([
                'value' => $data['other_value'],
                'slug' => $data['other_value_slug'],
            ]);
            $product->productAttributes()->create([
                'attribute_id' => $attribute->id,
                'is_variation' => true,
            ]);

            return [$attribute, $value, $otherValue];
        });
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'RAM-STORAGE-COLOR',
            'name' => 'Black',
            'price' => 120000,
            'stock' => 5,
            'status' => 'active',
        ]);
        $variant->attributeValues()->sync($attributes->pluck(1)->pluck('id')->all());
        $otherVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'OTHER-RAM-STORAGE-COLOR',
            'name' => 'Silver',
            'price' => 110000,
            'stock' => 2,
            'status' => 'active',
        ]);
        $otherVariant->attributeValues()->sync($attributes->pluck(2)->pluck('id')->all());

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('selectedVariantId', null)
            ->assertViewHas('variantOptions', function ($variants) use ($attributes): bool {
                $options = $variants->first()['options'];

                return $variants->count() === 2
                    && $variants->every(fn ($variant): bool => count($variant['options']) === 3)
                    && count($options) === 3
                    && collect($options)->pluck('groupName')->sort()->values()->all()
                        === $attributes->pluck(0)->pluck('name')->sort()->values()->all()
                    && collect($options)->pluck('label')->sort()->values()->all()
                        === $attributes->pluck(1)->pluck('value')->sort()->values()->all();
            })
            ->assertSee('x-for="group in groups"', false)
            ->assertSee('pick(group.id, value.id)', false)
            ->assertSee('name="variant_id"', false)
            ->assertSee('RAM')
            ->assertSee('Bộ nhớ')
            ->assertSee('Màu sắc');
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
