<?php

namespace Database\Seeders;

// Tạo vai trò và quyền sử dụng trong ứng dụng.

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'category.manage',
            'brand.manage',
            'order.view',
            'order.update',
            'order.refund',
            'user.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);

        $staff->syncPermissions([
            'product.view',
            'product.create',
            'product.update',
            'order.view',
            'order.update',
        ]);
    }
}
