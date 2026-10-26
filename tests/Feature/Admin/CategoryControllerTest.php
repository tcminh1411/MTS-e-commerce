<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_categories(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_search_matches_name_or_slug(): void
    {
        $admin = $this->admin();
        Category::create(['name' => 'Điện thoại', 'slug' => 'dien-thoai']);
        Category::create(['name' => 'Laptop', 'slug' => 'laptop']);

        $this->actingAs($admin)
            ->get(route('admin.categories.index', ['q' => 'laptop']))
            ->assertOk()
            ->assertSee('Laptop')
            ->assertDontSee('Điện thoại');
    }

    public function test_store_generates_unique_slug(): void
    {
        $admin = $this->admin();
        Category::create(['name' => 'A', 'slug' => 'abc']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Abc', 'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['slug' => 'abc-2']);
    }

    public function test_parent_id_cannot_be_self_on_update(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'A', 'slug' => 'a']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $cat), [
                'name' => 'A', 'slug' => 'a',
                'parent_id' => $cat->id,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_destroy_blocked_when_has_children(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'P', 'slug' => 'p']);
        Category::create(['name' => 'C', 'slug' => 'c', 'parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_destroy_blocked_when_has_products(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'A', 'slug' => 'a']);
        Product::create([
            'name' => 'P', 'slug' => 'p-'.Str::random(4),
            'status' => 'active', 'category_id' => $cat->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $cat))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_destroy_succeeds_when_empty(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'A', 'slug' => 'a']);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $cat))
            ->assertRedirect();

        $this->assertSoftDeleted('categories', ['id' => $cat->id]);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }
}
