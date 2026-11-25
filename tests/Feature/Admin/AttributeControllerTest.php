<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_attribute_with_values_and_search_attributes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.attributes.create'))
            ->assertOk()
            ->assertSee('name="new_values[0][value]"', false);

        $this->actingAs($admin)
            ->post(route('admin.attributes.store'), [
                'name' => 'Màu sắc',
                'type' => 'color',
                'new_values' => [
                    ['value' => 'Đỏ', 'color_code' => '#ff0000'],
                    ['value' => 'Xanh', 'color_code' => '#0000ff'],
                ],
            ])
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHasNoErrors();

        $attribute = Attribute::where('name', 'Màu sắc')->firstOrFail();
        $this->assertSame('mau-sac', $attribute->slug);
        $this->assertDatabaseHas('attribute_values', [
            'attribute_id' => $attribute->id,
            'value' => 'Đỏ',
            'slug' => 'do',
            'color_code' => '#ff0000',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attributes.edit', $attribute))
            ->assertOk()
            ->assertSee('Màu sắc')
            ->assertSee('Đỏ');

        $this->actingAs($admin)
            ->get(route('admin.attributes.index', ['q' => 'mau-sac']))
            ->assertOk()
            ->assertSee('Màu sắc')
            ->assertSee('(1)');
    }

    public function test_admin_can_update_attribute_and_manage_unused_values(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
        ]);
        $red = $attribute->values()->create(['value' => 'Red', 'slug' => 'red']);

        $this->actingAs($admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => 'Màu sắc',
                'type' => 'color',
                'values' => [
                    $red->id => ['value' => 'Red', 'color_code' => null],
                ],
                'delete_value_ids' => [$red->id],
                'new_values' => [
                    ['value' => 'Xanh', 'color_code' => '#0000ff'],
                ],
            ])
            ->assertRedirect(route('admin.attributes.edit', $attribute))
            ->assertSessionHasNoErrors();

        $this->assertSame('Màu sắc', $attribute->fresh()->name);
        $this->assertDatabaseMissing('attribute_values', ['id' => $red->id]);
        $this->assertDatabaseHas('attribute_values', [
            'attribute_id' => $attribute->id,
            'value' => 'Xanh',
            'slug' => 'xanh',
            'color_code' => '#0000ff',
        ]);
    }

    public function test_admin_cannot_delete_attribute_values_used_by_variants_or_attributes_assigned_to_products(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create(['value' => 'M', 'slug' => 'm']);
        $product = Product::create([
            'name' => 'Product',
            'sku' => 'ATTRIBUTE-PRODUCT',
            'slug' => 'attribute-product',
            'price' => 100000,
            'status' => 'active',
        ]);
        $product->productAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_variation' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'ATTRIBUTE-VARIANT',
            'name' => 'M',
            'price' => 100000,
            'status' => 'active',
        ]);
        $variant->attributeValues()->sync([$value->id]);

        $this->actingAs($admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => $attribute->name,
                'type' => $attribute->type,
                'delete_value_ids' => [$value->id],
            ])
            ->assertSessionHasErrors([
                'delete_value_ids' => 'Không thể xoá giá trị đang được dùng bởi biến thể.',
            ]);

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertOk()
            ->assertSee('Không thể xoá thuộc tính đang được dùng bởi sản phẩm hoặc biến thể.');

        $this->assertModelExists($attribute);
        $this->assertModelExists($value);
        $this->assertDatabaseHas('variant_attribute_values', [
            'variant_id' => $variant->id,
            'attribute_value_id' => $value->id,
        ]);
    }

    public function test_admin_can_delete_unused_attribute(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create([
            'name' => 'Unused',
            'slug' => 'unused',
            'type' => 'select',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($attribute);
    }

    public function test_attribute_index_escapes_names_and_rejects_unexpected_value_fields(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create([
            'name' => '<script>alert(1)</script>',
            'slug' => 'script-name',
            'type' => 'select',
        ]);
        $value = $attribute->values()->create([
            'value' => 'Safe value',
            'slug' => 'safe-value',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($admin)
            ->put(route('admin.attributes.update', $attribute), [
                'name' => $attribute->name,
                'type' => $attribute->type,
                'values' => [
                    $value->id => [
                        'value' => $value->value,
                        'color_code' => null,
                        'sort_order' => 99,
                    ],
                ],
            ])
            ->assertSessionHasErrors('values.'.$value->id);

        $this->assertSame(0, $value->fresh()->sort_order);
    }

    public function test_staff_cannot_access_attribute_management(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.attributes.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_attribute_management(): void
    {
        $this->get(route('admin.attributes.index'))
            ->assertRedirect(route('login'));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
