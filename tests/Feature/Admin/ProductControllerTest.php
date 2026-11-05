<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_creating_simple_product_also_creates_default_variant(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Simple product',
                'sku' => 'SIMPLE-001',
                'price' => 120000,
                'sale_price' => 110000,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::where('sku', 'SIMPLE-001')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $this->assertSame('simple', $product->product_type);
        $this->assertSame('Mặc định', $variant->name);
        $this->assertSame('SIMPLE-001', $variant->sku);
        $this->assertSame(120000.0, (float) $variant->price);
        $this->assertSame(110000.0, (float) $variant->sale_price);
        $this->assertTrue($variant->is_default);
    }

    public function test_admin_can_create_product_with_same_name_after_soft_deleting_previous_product(): void
    {
        $admin = $this->admin();
        $deletedProduct = Product::create([
            'name' => 'Same name',
            'sku' => 'OLD-SKU',
            'slug' => 'same-name',
            'price' => 100000,
            'status' => 'active',
        ]);
        $deletedProduct->delete();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Same name',
                'slug' => '',
                'sku' => 'NEW-SKU',
                'price' => 100000,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Same name',
            'sku' => 'NEW-SKU',
            'slug' => 'same-name-1',
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_reuse_slug_from_soft_deleted_product(): void
    {
        $admin = $this->admin();
        $deletedProduct = Product::create([
            'name' => 'Archived product',
            'sku' => 'ARCHIVED-SKU',
            'slug' => 'reserved-slug',
            'price' => 100000,
            'status' => 'active',
        ]);
        $deletedProduct->delete();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'New product',
                'slug' => 'reserved-slug',
                'sku' => 'NEW-SLUG-SKU',
                'price' => 100000,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseMissing('products', [
            'sku' => 'NEW-SLUG-SKU',
        ]);
    }

    public function test_backfill_command_creates_default_variant_for_simple_product_without_variants(): void
    {
        $product = Product::create([
            'name' => 'Legacy simple product',
            'sku' => 'LEGACY-SIMPLE',
            'slug' => 'legacy-simple-product',
            'product_type' => 'simple',
            'price' => 90000,
            'status' => 'active',
        ]);

        $this->artisan('products:ensure-simple-variants')
            ->assertSuccessful();

        $variant = $product->variants()->firstOrFail();
        $this->assertSame('Mặc định', $variant->name);
        $this->assertTrue($variant->is_default);
        $this->assertSame(90000.0, (float) $variant->price);
    }

    public function test_adding_variant_changes_product_type_to_variable(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IMAGE-DEFAULT',
            'name' => 'Default',
            'price' => 100000,
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Red',
                'sku' => 'IMAGE-RED',
                'price' => 120000,
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('variable', $product->fresh()->product_type);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'IMAGE-RED',
            'name' => 'Red',
        ]);
    }

    public function test_admin_can_update_variant_details_and_make_it_the_default(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $defaultVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'EDIT-DEFAULT',
            'name' => 'Mặc định',
            'price' => 100000,
            'is_default' => true,
            'stock' => 4,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'EDIT-OLD',
            'name' => 'Đỏ',
            'price' => 120000,
            'is_default' => false,
            'stock' => 7,
            'status' => 'inactive',
        ]);

        $editResponse = $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk();
        $editResponse->assertSee(route('admin.products.variants.update', [$product, $variant]))
            ->assertSee('Sửa');

        $this->actingAs($admin)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'name' => 'Đỏ / L',
                'sku' => 'EDIT-NEW',
                'price' => 150000,
                'sale_price' => 140000,
                'is_default' => '1',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $variant->refresh();
        $defaultVariant->refresh();

        $this->assertSame('Đỏ / L', $variant->name);
        $this->assertSame('EDIT-NEW', $variant->sku);
        $this->assertSame(150000.0, (float) $variant->price);
        $this->assertSame(140000.0, (float) $variant->sale_price);
        $this->assertTrue($variant->is_default);
        $this->assertFalse($defaultVariant->is_default);
        $this->assertSame(7, $variant->stock);
    }

    public function test_admin_cannot_update_variant_with_an_existing_sku(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-TAKEN',
            'name' => 'Đỏ',
            'price' => 100000,
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-AVAILABLE',
            'name' => 'Xanh',
            'price' => 100000,
            'is_default' => false,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'name' => 'Xanh',
                'sku' => 'SKU-TAKEN',
                'price' => 100000,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('sku');

        $this->assertSame('SKU-AVAILABLE', $variant->fresh()->sku);
    }

    public function test_saving_simple_product_after_deleting_its_only_variant_creates_a_new_default_variant(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $product->update(['product_type' => 'simple']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => 'Mặc định',
            'price' => 100000,
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);

        $editResponse = $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk();
        $editResponse->assertSee('data-delete-variant="'.$variant->id.'"', false)
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk();

        $this->assertModelExists($variant);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => 100000,
                'status' => 'active',
                'delete_variant_ids' => [$variant->id],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($variant);
        $newDefaultVariant = $product->variants()->where('is_default', true)->firstOrFail();
        $this->assertSame('Mặc định', $newDefaultVariant->name);
        $this->assertNotSame($variant->sku, $newDefaultVariant->sku);
    }

    public function test_admin_update_deletes_product_image_from_its_storage_path(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->makeProduct();
        $image = $product->images()->create([
            'url' => 'products/'.$product->id.'/front.jpg',
            'alt' => $product->name,
            'sort_order' => 1,
        ]);
        Storage::disk('public')->put($image->getRawOriginal('url'), 'image');

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => 100000,
                'status' => 'active',
                'remove_images' => [$image->id],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('products/'.$product->id.'/front.jpg');
        $this->assertModelMissing($image);
    }

    public function test_admin_destroy_deletes_product_image_from_its_storage_path(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->makeProduct();
        $image = $product->images()->create([
            'url' => 'products/'.$product->id.'/front.jpg',
            'alt' => $product->name,
            'sort_order' => 1,
        ]);
        Storage::disk('public')->put($image->getRawOriginal('url'), 'image');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        Storage::disk('public')->assertMissing('products/'.$product->id.'/front.jpg');
        $this->assertSoftDeleted($product);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'name' => 'Image product',
            'sku' => 'IMAGE-PRODUCT',
            'slug' => 'image-product',
            'price' => 100000,
            'status' => 'active',
        ]);
    }
}
