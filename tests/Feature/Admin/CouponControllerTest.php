<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        CouponUsage::create([
            'coupon_id' => $coupon->id,
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
