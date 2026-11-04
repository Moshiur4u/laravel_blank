<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductReturnPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $returnPermission = Permission::firstOrCreate([
            'name' => 'product.return',
            'guard_name' => 'web',
        ]);
        $reportPermission = Permission::firstOrCreate([
            'name' => 'product.return.report',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($returnPermission, $reportPermission): void {
                $role->givePermissionTo([$returnPermission, $reportPermission]);
            });
    }
}
