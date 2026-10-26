<?php

namespace Tests\Feature\Seeders;

use App\Models\Post;
use Database\Seeders\DemoPostCategorySeeder;
use Database\Seeders\DemoPostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPostSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoPostCategorySeeder::class);
        $this->seed(DemoPostSeeder::class);
    }

    public function test_published_posts_have_view_count(): void
    {
        $published = Post::where('status', Post::STATUS_PUBLISHED)->get();
        $this->assertGreaterThan(0, $published->count());

        foreach ($published as $p) {
            $this->assertGreaterThanOrEqual(
                100,
                $p->view_count,
                "Published post '{$p->slug}' phải có view_count >= 100"
            );
            $this->assertLessThanOrEqual(
                5000,
                $p->view_count,
                "Published post '{$p->slug}' phải có view_count <= 5000"
            );
        }
    }

    public function test_draft_posts_have_zero_view_count(): void
    {
        $draft = Post::where('status', Post::STATUS_DRAFT)->get();
        foreach ($draft as $p) {
            $this->assertSame(0, $p->view_count, "Draft '{$p->slug}' phải có view_count = 0");
        }
    }

    public function test_view_counts_are_diverse(): void
    {
        $counts = Post::where('status', Post::STATUS_PUBLISHED)
            ->pluck('view_count')
            ->all();

        $distinct = count(array_unique($counts));
        $this->assertGreaterThanOrEqual(
            min(5, count($counts)),
            $distinct,
            'View counts phải đa dạng (không trùng hết)'
        );
    }

    public function test_reseed_preserves_accumulated_views(): void
    {
        $post = Post::where('status', Post::STATUS_PUBLISHED)->first();
        $post->update(['view_count' => 9999]);

        // Re-seed
        $this->seed(DemoPostSeeder::class);

        $fresh = $post->fresh();
        $this->assertSame(
            9999,
            $fresh->view_count,
            'Re-seed không được reset view_count đã tích luỹ'
        );
    }

    public function test_seed_total_views_reasonable(): void
    {
        $total = Post::sum('view_count');
        $publishedCount = Post::where('status', Post::STATUS_PUBLISHED)->count();

        // Mỗi published ít nhất 100, tối đa 5000
        $this->assertGreaterThanOrEqual(100 * $publishedCount, $total);
        $this->assertLessThanOrEqual(5000 * $publishedCount, $total);
    }
}
