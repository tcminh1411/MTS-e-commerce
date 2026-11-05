<?php

namespace Tests\Feature\Admin;

use App\Mail\StockAvailableMail;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_inventory(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.inventories.index'))
            ->assertForbidden();
    }

    public function test_index_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.inventories.index'))
            ->assertOk();
    }

    public function test_deleted_product_is_hidden_from_inventory_and_stock_entry_options(): void
    {
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 5);
        $product = $variant->product;
        $product->update(['name' => 'Deleted inventory product']);
        $product->delete();

        $this->actingAs($admin)
            ->get(route('admin.inventories.index'))
            ->assertOk()
            ->assertDontSee($variant->sku)
            ->assertDontSee($variant->product->name);

        $this->actingAs($admin)
            ->get(route('admin.inventories.create'))
            ->assertOk()
            ->assertDontSee($variant->sku)
            ->assertDontSee('N/A');

        $this->assertDatabaseHas('inventories', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    }

    public function test_deleted_product_variant_cannot_be_used_for_manual_inventory_adjustment(): void
    {
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 5);
        $variant->product->delete();

        $this->actingAs($admin)
            ->post(route('admin.inventories.store'), [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'in',
                'quantity' => 10,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertSame(5, $warehouse->inventories()->first()->quantity);
        $this->assertDatabaseMissing('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'in',
            'reference_type' => 'manual',
        ]);
    }

    public function test_import_increases_inventory_and_variant_stock(): void
    {
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 5);

        $this->actingAs($admin)
            ->post(route('admin.inventories.store'), [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'in',
                'quantity' => 10,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(15, Inventory::first()->quantity);
        $this->assertSame(15, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'in',
            'quantity' => 10,
            'reference_type' => 'manual',
        ]);
    }

    public function test_export_decreases_inventory(): void
    {
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 10);

        $this->actingAs($admin)
            ->post(route('admin.inventories.store'), [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'out',
                'quantity' => 3,
            ])
            ->assertRedirect();

        $this->assertSame(7, Inventory::first()->quantity);
    }

    public function test_export_rejects_when_stock_insufficient(): void
    {
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 2);

        $this->actingAs($admin)
            ->post(route('admin.inventories.store'), [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'out',
                'quantity' => 5,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(2, Inventory::first()->quantity);
    }

    public function test_adjust_sets_absolute_quantity(): void
    {
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 10);

        $this->actingAs($admin)
            ->post(route('admin.inventories.store'), [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'adjust',
                'quantity' => 42,
            ])
            ->assertRedirect();

        $this->assertSame(42, Inventory::first()->quantity);
        $this->assertSame(42, $variant->fresh()->stock);
    }

    public function test_zero_to_positive_sends_stock_alert(): void
    {
        Mail::fake();
        $admin = $this->admin();
        [$warehouse, $variant] = $this->setupInventory(qty: 0);
        StockAlert::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'email' => 'buyer@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventories.store'), [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'in',
                'quantity' => 10,
            ]);

        Mail::assertSent(StockAvailableMail::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function setupInventory(int $qty): array
    {
        $warehouse = Warehouse::create([
            'name' => 'W', 'code' => 'W-'.Str::random(4), 'is_active' => true,
        ]);
        $p = Product::create([
            'name' => 'P', 'slug' => 'p-'.Str::random(6), 'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $p->id,
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => 'D', 'is_default' => true, 'stock' => $qty, 'status' => 'active',
        ]);
        Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => $qty,
        ]);

        return [$warehouse, $variant];
    }
}
