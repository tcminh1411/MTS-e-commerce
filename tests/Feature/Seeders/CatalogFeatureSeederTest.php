<?php

namespace Tests\Feature\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogFeatureSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribute_seeder_can_be_repeated_without_duplicate_values(): void
    {
        $this->seed(AttributeSeeder::class);
        $this->seed(AttributeSeeder::class);

        $this->assertDatabaseCount('attributes', 5);
        $this->assertDatabaseCount('attribute_values', 33);
        $this->assertDatabaseHas('attributes', ['slug' => 'cpu']);
        $this->assertDatabaseHas('attribute_values', ['slug' => 'm3-pro']);
    }

    public function test_demo_product_variants_receive_attributes_and_images(): void
    {
        Http::preventStrayRequests();

        $this->seed([
            AttributeSeeder::class,
            DemoCatalogSeeder::class,
            DemoProductSeeder::class,
        ]);

        $this->assertDatabaseCount('product_attributes', 10);
        $this->assertDatabaseCount('variant_attribute_values', 31);
        $this->assertSame(13, ProductVariant::query()->whereHas('attributeValues')->count());

        $variant = ProductVariant::query()
            ->where('name', 'Titan Đen 256GB')
            ->firstOrFail();
        $iphone = Product::query()->where('slug', 'iphone-15-pro-max-256gb')->firstOrFail();
        $iphoneImages = $iphone->images()->orderBy('sort_order')->pluck('url')->all();

        $this->assertSame(
            ['Titan Đen', '256GB'],
            $variant->attributeValues()->orderBy('attribute_id')->pluck('value')->all()
        );
        $this->assertStringStartsWith('https://images.unsplash.com/photo-', $variant->image_url);
        $this->assertSame(3, count($iphoneImages));
        $this->assertSame(
            'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&h=800&fit=crop&q=80',
            $iphoneImages[0],
        );
        Http::assertNothingSent();
    }

    public function test_demo_product_seeder_uses_unsplash_urls_for_all_demo_products(): void
    {
        Http::preventStrayRequests();

        $this->seed([
            AttributeSeeder::class,
            DemoCatalogSeeder::class,
            DemoProductSeeder::class,
        ]);

        $imageUrls = ProductImage::query()->pluck('url');

        $this->assertCount(Product::query()->count() * 3, $imageUrls);
        foreach ($imageUrls as $imageUrl) {
            $this->assertStringStartsWith('https://images.unsplash.com/photo-', $imageUrl);
        }
        Http::assertNothingSent();
    }

    public function test_coupon_seeder_repeats_targeting_conditions_for_demo_catalog(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $this->seed(CouponSeeder::class);
        $this->seed(CouponSeeder::class);

        $this->assertDatabaseCount('coupons', 8);
        $this->assertDatabaseCount('coupon_conditions', 5);

        $saleCouponId = Coupon::query()->where('code', 'SALE20')->value('id');
        $categoryIds = Category::query()
            ->whereIn('slug', ['laptop-gaming', 'laptop-van-phong', 'macbook'])
            ->pluck('id');

        foreach ($categoryIds as $categoryId) {
            $this->assertDatabaseHas('coupon_conditions', [
                'coupon_id' => $saleCouponId,
                'condition_type' => 'category',
                'condition_id' => $categoryId,
            ]);
        }

        $techCouponId = Coupon::query()->where('code', 'TECH50K')->value('id');
        $brandIds = Brand::query()->whereIn('slug', ['apple', 'samsung'])->pluck('id');

        foreach ($brandIds as $brandId) {
            $this->assertDatabaseHas('coupon_conditions', [
                'coupon_id' => $techCouponId,
                'condition_type' => 'brand',
                'condition_id' => $brandId,
            ]);
        }
    }
}
