<?php

namespace Tests\Feature\Seeders;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoPersonalDataSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\DemoStockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShippingSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonalDataSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDeps(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(AttributeSeeder::class);
        $this->seed(DemoCatalogSeeder::class);
        $this->seed(WarehouseSeeder::class);
        $this->seed(DemoProductSeeder::class);
        $this->seed(DemoStockSeeder::class);
        $this->seed(ShippingSeeder::class);
    }

    public function test_seeds_tcminh_with_data(): void
    {
        $this->seedDeps();
        $this->seed(DemoPersonalDataSeeder::class);

        $tcminh = User::where('email', 'tcminh@example.com')->first();
        $this->assertNotNull($tcminh);
        $this->assertTrue($tcminh->hasRole('customer'));
        $this->assertSame('Thái Cao Minh', $tcminh->full_name);

        $this->assertGreaterThanOrEqual(8, Order::where('user_id', $tcminh->id)->count());
        $this->assertGreaterThanOrEqual(8, Wishlist::where('user_id', $tcminh->id)->count());
        $this->assertGreaterThanOrEqual(1, Review::where('user_id', $tcminh->id)->count());
    }

    public function test_seeds_admin_with_orders(): void
    {
        $this->seedDeps();
        $this->seed(DemoPersonalDataSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertGreaterThanOrEqual(5, Order::where('user_id', $admin->id)->count());
        $this->assertGreaterThanOrEqual(5, Wishlist::where('user_id', $admin->id)->count());
    }

    public function test_idempotent_rerun(): void
    {
        $this->seedDeps();
        $this->seed(DemoPersonalDataSeeder::class);

        $tcminh = User::where('email', 'tcminh@example.com')->first();
        $ordersBefore = Order::where('user_id', $tcminh->id)->count();
        $wishlistBefore = Wishlist::where('user_id', $tcminh->id)->count();
        $reviewsBefore = Review::where('user_id', $tcminh->id)->count();

        $this->seed(DemoPersonalDataSeeder::class);

        $this->assertSame($ordersBefore, Order::where('user_id', $tcminh->id)->count());
        $this->assertSame($wishlistBefore, Wishlist::where('user_id', $tcminh->id)->count());
        $this->assertSame($reviewsBefore, Review::where('user_id', $tcminh->id)->count());
    }

    public function test_preserves_existing_user_password_and_name(): void
    {
        $this->seedDeps();

        $custom = User::create([
            'email' => 'tcminh@example.com',
            'full_name' => 'Đã Đổi Tên',
            'password' => Hash::make('mật khẩu'),
            'status' => 'active',
        ]);
        $oldHash = $custom->password;

        $this->seed(DemoPersonalDataSeeder::class);

        $fresh = $custom->fresh();
        $this->assertSame($oldHash, $fresh->password, 'Password phải giữ nguyên');
        $this->assertSame('Đã Đổi Tên', $fresh->full_name, 'Full name phải giữ nguyên');
        $this->assertTrue($fresh->hasRole('customer'), 'Role customer phải được assign');
    }
}
