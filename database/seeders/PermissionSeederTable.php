<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            //Add New parmissions,
            'Dashboard',
            'Sales',
            'sales.create',
            'sales.view',
            'sales.report',
            //customer all parmissions,
            'customer.index',
            'customer.create',
            'customer.details',
            'customer.edit',
            'customer.delete',
            'customer.ledger',
            'Customer.ledger.payment',
            'Customer.ledger.payment.edit',
            'Customer.ledger.edit',
            'Customer.ledger.delete',
            //Suppliers all parmissions,
            'supplier.index',
            'supplier.create',
            'supplier.details',
            'supplier.edit',
            'supplier.delete',
            'supplier.ledger',
            'Supplier.ledger.create', // নতুন supplier ledger entry তৈরির permission।
            'Supplier.ledger.payment',
            'Supplier.ledger.payment.edit',
            'Supplier.ledger.edit',
            'Supplier.ledger.delete',
            //Product all parmissions,
            'product.index',
            'product.create',
            'product.edit',
            'product.update',
            'product.delete',
            //Brand all parmissions,
            'brand.index',
            'brand.create',
            'brand.edit',
            'brand.update',
            'brand.delete',
            //Category all parmissions,
            'category.index',
            'product.category.create',
            'product.category.edit',
            'product.category.update',
            'product.category.delete',
            'product.report.inventory',
            //Employee all parmissions,
            'employee.create',
            'employee.index',
            'view-employee-details',
            'view-employee-details-permission',
            'employee.edit',
            'employee.inactive',
            'employee.delete',
            'employee_ledger',
            'Employee.ledger.payment',
            'Employee.ledger.edit',
            'Employee.ledger.delete',
            //Voucher all parmissions,
            'voucher',
            'voucher.entry',
            'employee_payment_voucher.entry',
            'voucher.edit',
            'voucher.delete',
            'voucher.report.view',
            'voucher.report.employee',
            'voucher.report.miscellaneous',
            'reports.all_access', // সব report-এ পূর্ণ প্রবেশের permission।
            'employee.role.assign', // কর্মীর জন্য অনুমোদিত role বরাদ্দের permission।
            'user-activity-log.view', // User Activity Log দেখার dedicated permission।
            //Role & Permission Management all parmissions,
            'Role & Permission Management',
            'roles.create',
            'roles',
            'role.index',
            'role.view_all_permissions',
            'role.edit',
            'role.delete',
            'Organization Information',
            'organization info.access',
            'user.activity.logs'

        ];
        foreach ($permissions as $permission) {
            //পারমিশন টেবিলে ডাটা ইন্সারট করার জন্য ২টা পদবতি ব্যবহার করা যায়।
            //১ নং---
            Permission::firstOrCreate(['name' => $permission]);
            // $permission = Permission::firstOrCreate([
            // ২নং----
            // Permission::firstOrCreate([
            // 'name' => $permission,
            // 'guard_name' => 'web'
            // ]);

        }
    }
}
