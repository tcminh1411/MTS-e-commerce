<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerControllerTest extends TestCase
{
    use RefreshDatabase;

    // JPEG 1x1 trắng hợp lệ — dùng để test upload mà không cần extension GD
    private const MINIMAL_JPEG_BASE64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_access_banners(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->get(route('admin.banners.index'))
            ->assertForbidden();
    }

    public function test_index_renders(): void
    {
        $admin = $this->admin();
        Banner::create([
            'title' => 'Banner A', 'image' => 'banners/a.jpg',
            'position' => 'home_top', 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Banner A');
    }

    public function test_search_matches_title_or_link(): void
    {
        $admin = $this->admin();
        Banner::create([
            'title' => 'iPhone Promo', 'image' => 'https://example.com/x.jpg',
            'link' => 'https://example.com/iphone',
            'position' => 'home_top', 'sort_order' => 1, 'is_active' => true,
        ]);
        Banner::create([
            'title' => 'Other', 'image' => 'https://example.com/o.jpg',
            'link' => 'https://example.com/other',
            'position' => 'home_top', 'sort_order' => 2, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.banners.index', ['q' => 'iphone']))
            ->assertOk()
            ->assertSee('iPhone Promo')
            ->assertDontSee('Other');
    }

    public function test_filter_by_position(): void
    {
        $admin = $this->admin();
        Banner::create([
            'title' => 'Top banner', 'image' => 'https://example.com/t.jpg',
            'position' => 'home_top', 'sort_order' => 1, 'is_active' => true,
        ]);
        Banner::create([
            'title' => 'Middle banner', 'image' => 'https://example.com/m.jpg',
            'position' => 'home_middle', 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.banners.index', ['position' => 'home_middle']))
            ->assertOk()
            ->assertSee('Middle banner');
    }

    public function test_store_requires_image_or_url(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'position' => 'home_top',
            ])
            ->assertSessionHasErrors(['image_file', 'image_url']);
    }

    public function test_store_with_uploaded_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'New Banner',
                'image_file' => $this->fakeJpegImage('banner.jpg'),
                'position' => 'home_top',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $banner = Banner::first();
        $this->assertNotNull($banner);
        $this->assertNotNull($banner->image);
        $this->assertStringStartsWith('banners/', $banner->image);
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_store_with_external_url(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'URL Banner',
                'image_url' => 'https://example.com/ext.jpg',
                'position' => 'home_top',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(
            'https://example.com/ext.jpg',
            Banner::first()->image
        );
    }

    public function test_update_replaces_uploaded_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        Storage::disk('public')->put('banners/old.jpg', 'x');
        $banner = Banner::create([
            'title' => 'Old', 'image' => 'banners/old.jpg',
            'position' => 'home_top', 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.banners.update', $banner), [
                'title' => 'New',
                'image_file' => $this->fakeJpegImage('new.jpg'),
                'position' => 'home_top',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        Storage::disk('public')->assertMissing('banners/old.jpg');
        $this->assertNotSame('banners/old.jpg', $banner->fresh()->image);
    }

    public function test_destroy_removes_local_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        Storage::disk('public')->put('banners/gone.jpg', 'x');
        $banner = Banner::create([
            'title' => 'Delete me', 'image' => 'banners/gone.jpg',
            'position' => 'home_top', 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect();

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('banners/gone.jpg');
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
