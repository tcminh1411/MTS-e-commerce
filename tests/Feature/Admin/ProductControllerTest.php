<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_admin_can_assign_attributes_to_a_product(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create([
            'name' => 'Màu sắc',
            'slug' => 'mau-sac',
            'type' => 'color',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Product with attributes',
                'sku' => 'ATTR-PRODUCT',
                'price' => 120000,
                'status' => 'active',
                'attribute_ids' => [$attribute->id],
                'variation_attribute_ids' => [$attribute->id],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::where('sku', 'ATTR-PRODUCT')->firstOrFail();
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);
    }

    public function test_edit_product_form_displays_assigned_attribute_values_for_variant_forms(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create(['value' => 'Blue', 'slug' => 'blue']);
        $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'FORM-ATTRIBUTE-VARIANT',
            'name' => 'Blue',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);
        $variant->attributeValues()->sync([$value->id]);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="attribute_ids[]"', false)
            ->assertSee('name="attribute_values[]"', false)
            ->assertSee('x-model.number="assignedIds"', false)
            ->assertSee('x-for="attribute in selectedVariationAttributes"', false)
            ->assertSee('name="variation_attribute_ids[]"', false)
            ->assertSee('data-auto-variant-name', false)
            ->assertSee('Blue');
    }

    public function test_product_cannot_unassign_an_attribute_used_by_a_variant(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create(['value' => 'M', 'slug' => 'm']);
        $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'USED-ATTRIBUTE',
            'name' => 'M',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);
        $variant->attributeValues()->sync([$value->id]);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'Changed product name',
                'sku' => $product->sku,
                'price' => 100000,
                'status' => 'active',
            ])
            ->assertSessionHasErrors([
                'attribute_ids' => 'Không thể bỏ thuộc tính đang được dùng bởi biến thể.',
            ]);

        $this->assertSame($product->name, $product->fresh()->name);
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
        ]);
        $this->assertDatabaseHas('variant_attribute_values', [
            'variant_id' => $variant->id,
            'attribute_value_id' => $value->id,
        ]);
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

    public function test_admin_can_create_variant_and_auto_generate_its_name_from_selected_attributes(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Màu sắc',
            'slug' => 'mau-sac',
            'type' => 'color',
        ]);
        $value = $attribute->values()->create([
            'value' => 'Đỏ',
            'slug' => 'do',
            'color_code' => '#ff0000',
        ]);
        $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => '',
                'sku' => 'ATTR-VARIANT',
                'price' => 120000,
                'status' => 'active',
                'attribute_values' => [$value->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $variant = ProductVariant::where('sku', 'ATTR-VARIANT')->firstOrFail();
        $this->assertSame('Đỏ', $variant->name);
        $this->assertDatabaseHas('variant_attribute_values', [
            'variant_id' => $variant->id,
            'attribute_value_id' => $value->id,
        ]);
    }

    public function test_admin_can_assign_attributes_and_create_variant_without_saving_product_first(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create(['value' => 'Blue', 'slug' => 'blue']);

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'attribute_ids' => [$attribute->id],
                'variation_attribute_ids' => [$attribute->id],
                'attribute_values' => [$value->id],
                'name' => '',
                'sku' => 'UNSAVED-ATTRIBUTE-VARIANT',
                'price' => 120000,
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $variant = ProductVariant::query()->where('sku', 'UNSAVED-ATTRIBUTE-VARIANT')->firstOrFail();

        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);
        $this->assertSame('Blue', $variant->name);
        $this->assertDatabaseHas('variant_attribute_values', [
            'variant_id' => $variant->id,
            'attribute_value_id' => $value->id,
        ]);
    }

    public function test_invalid_variant_does_not_save_unsaved_product_attributes(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create(['value' => 'Blue', 'slug' => 'blue']);

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'attribute_ids' => [$attribute->id],
                'variation_attribute_ids' => [$attribute->id],
                'attribute_values' => [$value->id],
                'name' => '',
                'sku' => 'INVALID-UNSAVED-ATTRIBUTE-VARIANT',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('price');

        $this->assertDatabaseMissing('product_attributes', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
        ]);
        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
            'sku' => 'INVALID-UNSAVED-ATTRIBUTE-VARIANT',
        ]);
    }

    public function test_admin_can_update_variant_attribute_values_and_generated_name(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
        ]);
        $red = $attribute->values()->create(['value' => 'Red', 'slug' => 'red']);
        $blue = $attribute->values()->create(['value' => 'Blue', 'slug' => 'blue']);
        $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'EDIT-ATTRIBUTE-VARIANT',
            'name' => 'Red',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);
        $variant->attributeValues()->sync([$red->id]);

        $this->actingAs($admin)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'name' => 'Red',
                'auto_name' => '1',
                'sku' => 'EDIT-ATTRIBUTE-VARIANT',
                'price' => 110000,
                'status' => 'active',
                'attribute_values' => [$blue->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Blue', $variant->fresh()->name);
        $this->assertDatabaseMissing('variant_attribute_values', [
            'variant_id' => $variant->id,
            'attribute_value_id' => $red->id,
        ]);
        $this->assertDatabaseHas('variant_attribute_values', [
            'variant_id' => $variant->id,
            'attribute_value_id' => $blue->id,
        ]);
    }

    public function test_variant_cannot_use_an_attribute_value_not_assigned_to_its_product(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $attribute = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create(['value' => 'M', 'slug' => 'm']);

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'M',
                'sku' => 'UNASSIGNED-VALUE',
                'price' => 120000,
                'status' => 'active',
                'attribute_values' => [$value->id],
            ])
            ->assertSessionHasErrors([
                'attribute_values' => 'Giá trị phải thuộc thuộc tính biến thể đã gán cho sản phẩm.',
            ]);

        $this->assertDatabaseMissing('product_variants', ['sku' => 'UNASSIGNED-VALUE']);
    }

    public function test_variant_requires_one_value_for_each_variation_attribute(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $color = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
        ]);
        $size = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
        ]);
        $red = $color->values()->create(['value' => 'Red', 'slug' => 'red']);
        $size->values()->create(['value' => 'M', 'slug' => 'm']);
        $product->productAttributes()->createMany([
            ['attribute_id' => $color->id, 'is_variation' => true],
            ['attribute_id' => $size->id, 'is_variation' => true],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Red',
                'sku' => 'INCOMPLETE-VARIANT',
                'price' => 120000,
                'status' => 'active',
                'attribute_values' => [$red->id],
            ])
            ->assertSessionHasErrors([
                'attribute_values' => 'Hãy chọn đúng một giá trị cho mỗi thuộc tính biến thể.',
            ]);

        $this->assertDatabaseMissing('product_variants', ['sku' => 'INCOMPLETE-VARIANT']);
    }

    public function test_admin_can_create_variant_with_uploaded_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Red',
                'sku' => 'IMAGE-RED',
                'price' => 120000,
                'status' => 'active',
                'image_file' => UploadedFile::fake()->image('variant.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $variant = ProductVariant::where('sku', 'IMAGE-RED')->firstOrFail();

        $this->assertStringStartsWith('variants/', $variant->image_url);
        Storage::disk('public')->assertExists($variant->image_url);
    }

    public function test_admin_can_create_variant_with_external_image_url(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Red',
                'sku' => 'URL-RED',
                'price' => 120000,
                'status' => 'active',
                'image_url' => 'https://example.com/red.jpg',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'URL-RED',
            'image_url' => 'https://example.com/red.jpg',
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

    public function test_admin_can_replace_variant_image_and_old_local_file_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('variants/old.jpg', 'old image');
        $admin = $this->admin();
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IMAGE-REPLACE',
            'name' => 'Blue',
            'price' => 120000,
            'image_url' => 'variants/old.jpg',
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'name' => 'Blue',
                'sku' => 'IMAGE-REPLACE',
                'price' => 120000,
                'status' => 'active',
                'image_file' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $variant->refresh();
        $this->assertStringStartsWith('variants/', $variant->image_url);
        $this->assertNotSame('variants/old.jpg', $variant->image_url);
        Storage::disk('public')->assertExists($variant->image_url);
        Storage::disk('public')->assertMissing('variants/old.jpg');
    }

    public function test_admin_can_replace_local_variant_image_with_external_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('variants/old.jpg', 'old image');
        $admin = $this->admin();
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IMAGE-URL',
            'name' => 'Blue',
            'price' => 120000,
            'image_url' => 'variants/old.jpg',
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'name' => 'Blue',
                'sku' => 'IMAGE-URL',
                'price' => 120000,
                'status' => 'active',
                'image_url' => 'https://example.com/blue.jpg',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('https://example.com/blue.jpg', $variant->fresh()->image_url);
        Storage::disk('public')->assertMissing('variants/old.jpg');
    }

    public function test_updating_variant_without_new_image_keeps_current_image(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IMAGE-KEEP',
            'name' => 'Blue',
            'price' => 120000,
            'image_url' => 'https://example.com/blue.jpg',
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'name' => 'Blue updated',
                'sku' => 'IMAGE-KEEP',
                'price' => 125000,
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('https://example.com/blue.jpg', $variant->fresh()->image_url);
    }

    public function test_variant_image_fields_are_available_on_create_and_edit_forms(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IMAGE-FORM',
            'name' => 'Blue',
            'price' => 120000,
            'image_url' => 'https://example.com/blue.jpg',
            'is_default' => true,
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image_file"', false)
            ->assertSee('name="image_url"', false)
            ->assertSee('https://example.com/blue.jpg')
            ->assertSee(route('admin.products.variants.update', [$product, $variant]));
    }

    public function test_variant_image_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Invalid',
                'sku' => 'IMAGE-INVALID',
                'price' => 120000,
                'status' => 'active',
                'image_file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors([
                'image_file' => 'File biến thể phải là ảnh.',
            ]);

        $this->assertDatabaseMissing('product_variants', ['sku' => 'IMAGE-INVALID']);
        $this->assertSame([], Storage::disk('public')->allFiles('variants'));
    }

    public function test_variant_image_url_rejects_non_http_protocols(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Invalid URL',
                'sku' => 'IMAGE-INVALID-URL',
                'price' => 120000,
                'status' => 'active',
                'image_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors([
                'image_url' => 'URL ảnh phải bắt đầu bằng http hoặc https.',
            ]);

        $this->assertDatabaseMissing('product_variants', ['sku' => 'IMAGE-INVALID-URL']);
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
