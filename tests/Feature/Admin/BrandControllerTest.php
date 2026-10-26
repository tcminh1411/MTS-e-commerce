<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrandControllerTest extends TestCase
{
    use RefreshDatabase;

    private const MINIMAL_JPEG_BASE64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_brands(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.brands.index'))
            ->assertForbidden();
    }

    public function test_search_matches_name_or_slug(): void
    {
        $admin = $this->admin();
        Brand::create(['name' => 'Apple', 'slug' => 'apple']);
        Brand::create(['name' => 'Samsung', 'slug' => 'samsung']);

        $this->actingAs($admin)
            ->get(route('admin.brands.index', ['q' => 'apple']))
            ->assertOk()
            ->assertSee('Apple')
            ->assertDontSee('Samsung');
    }

    public function test_store_generates_slug_when_empty(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.brands.store'), [
                'name' => 'Sony Electronics',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('brands', ['slug' => 'sony-electronics']);
    }

    public function test_store_deduplicates_slug(): void
    {
        $admin = $this->admin();
        Brand::create(['name' => 'Apple', 'slug' => 'apple']);

        $this->actingAs($admin)
            ->post(route('admin.brands.store'), [
                'name' => 'Apple',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('brands', ['slug' => 'apple-2']);
    }

    public function test_update_accepts_same_slug(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'A', 'slug' => 'a']);

        $this->actingAs($admin)
            ->put(route('admin.brands.update', $brand), [
                'name' => 'A2', 'slug' => 'a', 'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_update_uploads_logo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'A', 'slug' => 'a']);

        $this->actingAs($admin)
            ->put(route('admin.brands.update', $brand), [
                'name' => 'A', 'slug' => 'a',
                'logo' => $this->fakeJpegImage('logo.jpg'),
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertNotNull($brand->fresh()->logo_url);
        Storage::disk('public')->assertExists($brand->fresh()->logo_url);
    }

    public function test_destroy_blocked_when_brand_has_products(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'A', 'slug' => 'a']);
        Product::create([
            'name' => 'P', 'slug' => 'p-'.Str::random(4),
            'status' => 'active', 'brand_id' => $brand->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_destroy_succeeds_when_empty(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'A', 'slug' => 'a']);

        $this->actingAs($admin)
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function fakeJpegImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::MINIMAL_JPEG_BASE64),
        );
    }
}
