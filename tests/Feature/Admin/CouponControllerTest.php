<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponCondition;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CouponControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_coupons(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $admin = $this->admin();
        Coupon::create([
            'code' => 'SALE10', 'name' => '10%',
            'type' => 'percent', 'value' => 10,
            'max_discount_amount' => 50000,
            'used_count' => 0, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coupons.index'))
            ->assertOk()
            ->assertSee('SALE10');
    }

    public function test_admin_can_view_coupon_condition_options(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        $brand = Brand::create(['name' => 'Asus', 'slug' => 'asus']);
        $product = Product::create([
            'name' => 'Laptop Asus',
            'slug' => 'laptop-asus',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertSee('condition_categories[]')
            ->assertSee('condition_brands[]')
            ->assertSee('condition_products[]')
            ->assertSee('Laptop')
            ->assertSee('Asus')
            ->assertSee('Laptop Asus');
    }

    public function test_admin_can_view_existing_coupon_condition_selections(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        $coupon = Coupon::create([
            'code' => 'LAPTOP',
            'name' => 'Laptop',
            'type' => 'fixed',
            'value' => 10000,
            'used_count' => 0,
            'is_active' => true,
        ]);
        CouponCondition::create([
            'coupon_id' => $coupon->id,
            'condition_type' => 'category',
            'condition_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coupons.edit', $coupon))
            ->assertOk()
            ->assertViewHas('selectedCategories', [$category->id])
            ->assertSee('value="'.$category->id.'"', false)
            ->assertSee('selected', false);
    }

    public function test_admin_can_filter_active_coupons(): void
    {
        $admin = $this->admin();
        Coupon::create([
            'code' => 'ACTIVE', 'name' => 'A',
            'type' => 'fixed', 'value' => 10000,
            'used_count' => 0, 'is_active' => true,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
        ]);
        Coupon::create([
            'code' => 'INACTIVE', 'name' => 'I',
            'type' => 'fixed', 'value' => 10000,
            'used_count' => 0, 'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coupons.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('ACTIVE')
            ->assertDontSee('INACTIVE');
    }

    public function test_admin_can_search_by_code_or_name(): void
    {
        $admin = $this->admin();
        Coupon::create([
            'code' => 'FINDME', 'name' => 'A',
            'type' => 'fixed', 'value' => 10000, 'used_count' => 0, 'is_active' => true,
        ]);
        Coupon::create([
            'code' => 'OTHER', 'name' => 'B',
            'type' => 'fixed', 'value' => 10000, 'used_count' => 0, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coupons.index', ['q' => 'FINDME']))
            ->assertOk()
            ->assertSee('FINDME')
            ->assertDontSee('OTHER');
    }

    public function test_store_requires_max_discount_for_percent_type(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'P10',
                'name' => 'Percent 10',
                'type' => 'percent',
                'value' => 10,
                'max_discount_amount' => null,
            ])
            ->assertSessionHasErrors('max_discount_amount');

        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_store_rejects_percent_value_above_100(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'P200',
                'name' => 'Bad',
                'type' => 'percent',
                'value' => 200,
                'max_discount_amount' => 50000,
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_store_creates_coupon_with_uppercased_code(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'sale20',
                'name' => 'Sale 20',
                'type' => 'percent',
                'value' => 20,
                'max_discount_amount' => 100000,
                'min_order_amount' => 0,
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('coupons', ['code' => 'SALE20']);
    }

    public function test_store_creates_coupon_conditions_for_categories_brands_and_products(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        $brand = Brand::create(['name' => 'Asus', 'slug' => 'asus']);
        $product = Product::create([
            'name' => 'Laptop Asus',
            'slug' => 'laptop-asus',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'LAPTOP10',
                'name' => 'Laptop sale',
                'type' => 'percent',
                'value' => 10,
                'max_discount_amount' => 50000,
                'limit_conditions' => 1,
                'condition_categories' => [$category->id],
                'condition_brands' => [$brand->id],
                'condition_products' => [$product->id],
            ])
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHasNoErrors();

        $coupon = Coupon::where('code', 'LAPTOP10')->firstOrFail();

        $this->assertDatabaseHas('coupon_conditions', [
            'coupon_id' => $coupon->id,
            'condition_type' => 'category',
            'condition_id' => $category->id,
        ]);
        $this->assertDatabaseHas('coupon_conditions', [
            'coupon_id' => $coupon->id,
            'condition_type' => 'brand',
            'condition_id' => $brand->id,
        ]);
        $this->assertDatabaseHas('coupon_conditions', [
            'coupon_id' => $coupon->id,
            'condition_type' => 'product',
            'condition_id' => $product->id,
        ]);
    }

    public function test_update_replaces_existing_coupon_conditions(): void
    {
        $admin = $this->admin();
        $oldCategory = Category::create(['name' => 'Old', 'slug' => 'old']);
        $newCategory = Category::create(['name' => 'New', 'slug' => 'new']);
        $coupon = Coupon::create([
            'code' => 'CHANGE',
            'name' => 'Change',
            'type' => 'fixed',
            'value' => 10000,
            'used_count' => 0,
            'is_active' => true,
        ]);
        $oldCondition = CouponCondition::create([
            'coupon_id' => $coupon->id,
            'condition_type' => 'category',
            'condition_id' => $oldCategory->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'CHANGE',
                'name' => 'Change',
                'type' => 'fixed',
                'value' => 10000,
                'limit_conditions' => 1,
                'condition_categories' => [$newCategory->id],
                'condition_brands' => [],
                'condition_products' => [],
            ])
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($oldCondition);
        $this->assertDatabaseHas('coupon_conditions', [
            'coupon_id' => $coupon->id,
            'condition_type' => 'category',
            'condition_id' => $newCategory->id,
        ]);
        $this->assertDatabaseCount('coupon_conditions', 1);
    }

    public function test_update_removes_conditions_when_coupon_is_unlimited(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        $coupon = Coupon::create([
            'code' => 'UNLIMITED',
            'name' => 'Unlimited',
            'type' => 'fixed',
            'value' => 10000,
            'used_count' => 0,
            'is_active' => true,
        ]);
        CouponCondition::create([
            'coupon_id' => $coupon->id,
            'condition_type' => 'category',
            'condition_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'UNLIMITED',
                'name' => 'Unlimited',
                'type' => 'fixed',
                'value' => 10000,
                'limit_conditions' => 0,
                'condition_categories' => [$category->id],
            ])
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('coupon_conditions', 0);
    }

    public function test_store_rejects_nonexistent_coupon_condition_records(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.coupons.create'))
            ->post(route('admin.coupons.store'), [
                'code' => 'INVALID',
                'name' => 'Invalid conditions',
                'type' => 'fixed',
                'value' => 10000,
                'limit_conditions' => 1,
                'condition_categories' => [999999],
                'condition_brands' => [999999],
                'condition_products' => [999999],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'condition_categories.0',
                'condition_brands.0',
                'condition_products.0',
            ]);

        $this->assertDatabaseMissing('coupons', ['code' => 'INVALID']);
        $this->assertDatabaseCount('coupon_conditions', 0);
    }

    public function test_store_rejects_enabled_coupon_conditions_without_a_selection(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'EMPTY',
                'name' => 'Empty conditions',
                'type' => 'fixed',
                'value' => 10000,
                'limit_conditions' => 1,
                'condition_categories' => [],
                'condition_brands' => [],
                'condition_products' => [],
            ])
            ->assertSessionHasErrors('condition_categories');

        $this->assertDatabaseMissing('coupons', ['code' => 'EMPTY']);
    }

    public function test_store_forces_max_discount_null_for_fixed_type(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'FIX',
                'name' => 'Fixed',
                'type' => 'fixed',
                'value' => 30000,
                'max_discount_amount' => 50000,
                'min_order_amount' => 0,
            ])
            ->assertRedirect();

        $this->assertNull(Coupon::where('code', 'FIX')->first()->max_discount_amount);
    }

    public function test_destroy_blocked_when_coupon_has_usages(): void
    {
        $admin = $this->admin();
        $coupon = Coupon::create([
            'code' => 'USED', 'name' => 'U', 'type' => 'fixed', 'value' => 1000,
            'used_count' => 1, 'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 100000,
            'shipping_rate_name' => 'Std',
            'recipient_name' => 'Test',
            'recipient_phone' => '0900000000',
            'shipping_address' => 'Test',
        ]);
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    public function test_destroy_deletes_coupon_without_usages(): void
    {
        $admin = $this->admin();
        $coupon = Coupon::create([
            'code' => 'FREE', 'name' => 'F', 'type' => 'fixed', 'value' => 1000,
            'used_count' => 0, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect();

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }
}
