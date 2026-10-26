<?php

namespace Tests\Feature\Admin;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WarehouseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_warehouses(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.warehouses.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list(): void
    {
        $admin = $this->admin();
        Warehouse::create(['name' => 'Kho A', 'code' => 'A', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.warehouses.index'))
            ->assertOk()
            ->assertSee('Kho A');
    }

    public function test_search_by_code_and_name(): void
    {
        $admin = $this->admin();
        Warehouse::create(['name' => 'Main', 'code' => 'HN-01', 'is_active' => true]);
        Warehouse::create(['name' => 'Other', 'code' => 'HCM-01', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.warehouses.index', ['q' => 'HN-01']))
            ->assertOk()
            ->assertSee('HN-01')
            ->assertDontSee('HCM-01');
    }

    public function test_store_validates_unique_code(): void
    {
        $admin = $this->admin();
        Warehouse::create(['name' => 'A', 'code' => 'DUP', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.warehouses.store'), [
                'name' => 'B',
                'code' => 'DUP',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_store_creates_warehouse(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.warehouses.store'), [
                'name' => 'Kho mới',
                'code' => 'NEW-01',
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('warehouses', [
            'name' => 'Kho mới',
            'code' => 'NEW-01',
            'is_active' => true,
        ]);
    }

    public function test_update_changes_warehouse(): void
    {
        $admin = $this->admin();
        $w = Warehouse::create(['name' => 'Old', 'code' => 'X', 'is_active' => true]);

        $this->actingAs($admin)
            ->put(route('admin.warehouses.update', $w), [
                'name' => 'New',
                'code' => 'X',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('New', $w->fresh()->name);
    }

    public function test_destroy_blocked_when_stock_remains(): void
    {
        $admin = $this->admin();
        $w = Warehouse::create(['name' => 'W', 'code' => 'W', 'is_active' => true]);
        $variant = $this->makeVariant();
        Inventory::create([
            'warehouse_id' => $w->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.warehouses.destroy', $w))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('warehouses', ['id' => $w->id]);
    }

    public function test_destroy_succeeds_when_all_stock_is_zero(): void
    {
        $admin = $this->admin();
        $w = Warehouse::create(['name' => 'W', 'code' => 'W', 'is_active' => true]);
        $variant = $this->makeVariant();
        Inventory::create([
            'warehouse_id' => $w->id,
            'product_variant_id' => $variant->id,
            'quantity' => 0,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.warehouses.destroy', $w))
            ->assertRedirect();

        $this->assertDatabaseMissing('warehouses', ['id' => $w->id]);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function makeVariant(): ProductVariant
    {
        $p = Product::create([
            'name' => 'P', 'slug' => 'p-'.Str::random(6), 'status' => 'active',
        ]);

        return ProductVariant::create([
            'product_id' => $p->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'D', 'is_default' => true, 'stock' => 0, 'status' => 'active',
        ]);
    }
}
