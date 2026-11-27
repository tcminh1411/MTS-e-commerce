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

    public function test_product_detail_allows_selecting_an_active_variant_with_zero_stock(): void
    {
        $product = Product::create([
            'name' => 'Out of stock product',
            'slug' => 'out-of-stock-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'OUT-OF-STOCK',
            'name' => 'Blue',
            'price' => 100000,
            'stock' => 0,
            'status' => 'active',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IN-STOCK',
            'name' => 'Red',
            'price' => 100000,
            'stock' => 2,
            'status' => 'active',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('selectVariant(variant.id)', false)
            ->assertDontSee('x-bind:disabled="variant.stock <= 0"', false)
            ->assertSee('this.variants.find(item => item.id === id)', false)
            ->assertSee("selectedVariant.stock > 0 ? 'Còn hàng: ' + selectedVariant.stock : 'Hết hàng'", false)
            ->assertSee('get canBuyNow()', false)
            ->assertSee('x-bind:disabled="!canBuyNow"', false)
            ->assertSee('x-bind:disabled="!canAddToCart"', false)
            ->assertSee('Thêm vào giỏ');
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

    public function test_variable_product_without_variants_is_not_available_for_purchase(): void
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
            ->assertViewHas('hasVariants', true)
            ->assertSee('Hết hàng')
            ->assertSee('x-bind:disabled="!canAddToCart"', false)
            ->assertSee('x-bind:disabled="!canBuyNow"', false)
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

    public function test_product_listing_goes_directly_to_checkout_for_a_single_in_stock_variant(): void
    {
        $product = Product::create([
            'name' => 'Single variant product',
            'slug' => 'single-variant-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SINGLE-VARIANT',
            'name' => 'Blue',
            'price' => 100000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Blue')
            ->assertSee('name="variant_id" value="'.$variant->id.'"', false)
            ->assertDontSee("open-modal', 'buy-now-modal-{$product->id}'", false);
    }

    public function test_product_listing_disables_buy_now_for_a_single_out_of_stock_variant(): void
    {
        $product = Product::create([
            'name' => 'Out of stock listing product',
            'slug' => 'out-of-stock-listing-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'OUT-OF-STOCK-LISTING',
            'name' => 'Blue',
            'price' => 100000,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Hết hàng')
            ->assertSee('type="button" disabled', false)
            ->assertDontSee('name="variant_id" value="'.$variant->id.'"', false);
    }

    public function test_product_quick_view_groups_attributes_and_keeps_out_of_stock_values_selectable(): void
    {
        $product = Product::create([
            'name' => 'Quick view attribute product',
            'slug' => 'quick-view-attribute-product',
            'product_type' => 'variable',
            'price' => 100000,
            'status' => 'active',
        ]);
        $ram = Attribute::create([
            'name' => 'RAM',
            'slug' => 'quick-view-ram',
            'type' => 'select',
        ]);
        $ram8 = $ram->values()->create(['value' => '8 GB', 'slug' => 'quick-view-8-gb']);
        $ram16 = $ram->values()->create(['value' => '16 GB', 'slug' => 'quick-view-16-gb']);
        $color = Attribute::create([
            'name' => 'Màu sắc',
            'slug' => 'quick-view-color',
            'type' => 'select',
        ]);
        $blue = $color->values()->create(['value' => 'Xanh', 'slug' => 'quick-view-blue']);
        $product->productAttributes()->createMany([
            ['attribute_id' => $ram->id, 'is_variation' => true],
            ['attribute_id' => $color->id, 'is_variation' => true],
        ]);

        $outOfStockVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'QUICK-VIEW-OOS',
            'name' => '8 GB xanh',
            'price' => 100000,
            'stock' => 0,
            'status' => 'active',
        ]);
        $outOfStockVariant->attributeValues()->sync([$ram8->id, $blue->id]);
        $inStockVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'QUICK-VIEW-IN-STOCK',
            'name' => '16 GB xanh',
            'price' => 110000,
            'stock' => 2,
            'status' => 'active',
        ]);
        $inStockVariant->attributeValues()->sync([$ram16->id, $blue->id]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('x-for="group in groups"', false)
            ->assertSee('pick(group.id, value.id)', false)
            ->assertSee('variant.stock > 0 ? \'Còn hàng: \' + variant.stock : \'Hết hàng\'', false)
            ->assertSee('selectedVariant.stock < 1', false)
            ->assertSee('selectedVariantId === null || selectedVariant.stock < 1', false)
            ->assertSee('Phân loại này đã hết hàng, vui lòng chọn phân loại còn hàng.')
            ->assertSee('groupName', false)
            ->assertSee('8 GB')
            ->assertSee('Xanh')
            ->assertSee('16 GB')
            ->assertSee(route('checkout.buy-now'));
    }
}
