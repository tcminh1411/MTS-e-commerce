<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Mục đích: chạy dữ liệu production và demo khi môi trường local.
// Phụ thuộc: các migration đã chạy; demo chạy sau ProductionSeeder.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
