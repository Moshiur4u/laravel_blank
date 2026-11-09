<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PurchaseReturnPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $returnPermission = Permission::firstOrCreate([
            'name' => 'purchase.return',
            'guard_name' => 'web',
        ]);
        $reportPermission = Permission::firstOrCreate([
            'name' => 'purchase.return.report',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->whereIn('name', ['admin', 'manager'])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($returnPermission, $reportPermission): void {
                $role->givePermissionTo([$returnPermission, $reportPermission]);
            });
    }
}
