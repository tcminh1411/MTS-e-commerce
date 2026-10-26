<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory;
// Tạo tài khoản người dùng mẫu cho môi trường demo.

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Tạo 30 customer demo với email @demo.local.
     * Idempotent: bỏ qua user đã tồn tại.
     * Password tất cả: "password".
     */
    public function run(): void
    {
        $faker = Factory::create('vi_VN');
        $password = Hash::make('password');
        $created = 0;

        for ($i = 1; $i <= 30; $i++) {
            $email = "customer{$i}@demo.local";

            if (User::where('email', $email)->exists()) {
                continue;
            }

            $user = User::create([
                'full_name' => $faker->name(),
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            // B4: rải created_at 1..365 ngày trước — dashboard "user mới" có data trải đều
            $daysAgo = random_int(1, 365);
            $createdAt = now()->subDays($daysAgo)->setTime(
                random_int(7, 22), random_int(0, 59), random_int(0, 59)
            );
            DB::table('users')->where('id', $user->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'email_verified_at' => $createdAt,
            ]);
            $user->refresh();

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('customer');
            }

            $created++;
        }

        $this->command->info("✅ DemoUserSeeder: tạo {$created} users (@demo.local / password).");
    }
}
