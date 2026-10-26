<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_creates_30_demo_users(): void
    {
        $this->seed(DemoUserSeeder::class);

        $count = User::where('email', 'like', '%@demo.local')->count();
        $this->assertSame(30, $count);
    }

    public function test_created_at_spread_across_365_days(): void
    {
        $this->seed(DemoUserSeeder::class);

        $users = User::where('email', 'like', '%@demo.local')
            ->orderBy('created_at')
            ->get();

        // All within 365 days ago
        $min = $users->min('created_at');
        $max = $users->max('created_at');
        $this->assertTrue(
            $min->gte(now()->subDays(366)),
            "Min created_at {$min} phải >= now()-366d"
        );
        $this->assertTrue(
            $max->lte(now()),
            "Max created_at {$max} phải <= now()"
        );

        // 30 random over 365 days → phải có >=20 distinct dates
        $distinct = $users->pluck('created_at')->map(fn ($d) => $d->toDateString())->unique()->count();
        $this->assertGreaterThanOrEqual(
            20,
            $distinct,
            "Chỉ có $distinct distinct dates — spread không đủ"
        );
    }

    public function test_email_verified_at_matches_created_at(): void
    {
        $this->seed(DemoUserSeeder::class);

        $users = User::where('email', 'like', '%@demo.local')->get();
        foreach ($users as $u) {
            $this->assertNotNull($u->email_verified_at);
            $this->assertTrue(
                abs($u->email_verified_at->diffInSeconds($u->created_at)) <= 1,
                "User {$u->email}: verified_at {$u->email_verified_at} phải == created_at {$u->created_at}"
            );
        }
    }

    public function test_non_demo_users_untouched(): void
    {
        $custom = User::factory()->create([
            'email' => 'custom@example.com',
        ]);
        $originalCreated = $custom->created_at->toDateTimeString();

        $this->seed(DemoUserSeeder::class);

        $fresh = $custom->fresh();
        $this->assertSame(
            $originalCreated,
            $fresh->created_at->toDateTimeString(),
            'User không phải @demo.local không được đụng created_at'
        );
    }

    public function test_idempotent_rerun(): void
    {
        $this->seed(DemoUserSeeder::class);

        $snapshot = User::where('email', 'like', '%@demo.local')
            ->orderBy('id')
            ->get()
            ->map(fn ($u) => $u->id.'|'.$u->created_at->toDateTimeString())
            ->all();

        $this->seed(DemoUserSeeder::class);

        $after = User::where('email', 'like', '%@demo.local')
            ->orderBy('id')
            ->get()
            ->map(fn ($u) => $u->id.'|'.$u->created_at->toDateTimeString())
            ->all();

        $this->assertSame($snapshot, $after, 'Chạy lại không được đổi created_at');
    }

    public function test_at_least_some_users_in_last_30_days(): void
    {
        $this->seed(DemoUserSeeder::class);

        $recent = User::where('email', 'like', '%@demo.local')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // 30 random over 365 days → expect ~2-3 in last 30 days
        $this->assertGreaterThanOrEqual(
            1,
            $recent,
            'Cần >=1 user trong 30 ngày để dashboard chart có cột'
        );
    }
}
