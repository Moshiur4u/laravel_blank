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
                'sales.document.watermark',
                'brand.index', // পুরোনো Brands-এর বদলে নতুন brand list permission।
                'brand.create', // নতুন brand create permission।
                'brand.edit', // নতুন brand edit permission।
                'brand.update', // নতুন brand update permission।
                'brand.delete', // নতুন brand delete permission।
                'category.index', // পুরোনো Categories-এর বদলে category list permission।
                'product.category.create', // নতুন category create permission।
                'product.category.edit', // নতুন category edit permission।
                'product.category.update', // নতুন category update permission।
                'product.category.delete', // নতুন category delete permission।
                'voucher.report.view', // পুরোনো reports.vouchers এবং All Vouchers-এর বদলে report permission।
                'voucher',
                'voucher.entry',
                'voucher.edit',
                'voucher.delete',
                'employee_payment_voucher.entry',
                'voucher.report.employee', // employee payment report দেখার permission।
                'voucher.report.miscellaneous', // miscellaneous voucher report permission।
                'voucher.account.index',
                'voucher.account.create',
                'voucher.account.edit',
                'voucher.account.update',
                'voucher.account.delete',
                'supplier.index',
                'supplier.create',
                'supplier.edit',
                'supplier.ledger',
                'Supplier.ledger.payment', // পুরোনো ledger.payment-এর বদলে নির্দিষ্ট permission।
                'employee.create',
                'employee.index',
                'view-employee-details', // পুরোনো employee.view-এর বদলে নতুন permission।
                // 'view-employee-details-permission',
                'employee.edit',
                'employee.role.assign', // Manager-কে সীমাবদ্ধ ও অনুমোদিত role update করার সুযোগ।
                'role.index', // পুরোনো role-menu-এর বদলে role list permission।
                'roles.create', // পুরোনো create-role-এর বদলে নতুন role create permission।
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
