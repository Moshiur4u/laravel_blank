<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Create the application's standard role presets and synchronize their access.
     */
    public function run(): void
    {
        $presets = [
            'sales' => [
                'Dashboard',
                'sales.create',
                'customer.create',
                'customer.index',
                'customer.ledger',
            ],
            'manager' => [
                'Dashboard',
                'sales.create',
                'Brands',
                'brand_category.create',
                'brand_category.edit',
                'brand_category.update',
                'brand_category.delete',
                'Categories',
                'reports.vouchers',
                'All Vouchers',
                'voucher',
                'voucher.entry',
                'employee_payment_voucher.entry',
                'supplier.index',
                'supplier.create',
                'supplier.edit',
                'supplier.ledger',
                'ledger.payment',
                'employee.create',
                'employee.index',
                'employee.view',
                'employee.edit',
                'role-menu',
                'create-role',
                'role.view_all_permissions',
            ],
            'projector' => [
                'Dashboard',
                'reports.all_access',
            ],
            'admin' => Permission::query()->pluck('name')->all(),
        ];

        foreach ($presets as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $permissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->get();

            $role->syncPermissions($permissions);
        }
    }
}
