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
            'create-user',
            'create-delete',
            'edit-user-details',
            'view-user-list',
            'deactivate-user',
            'reset-user-password',
            'assign-user-role',
            'unassign-user-role',
            'view-sales-dashboard',
            'edit-sales-discount',
            'apply-sales-tax-override',
            'export-sales-report',
            'manage-sales-teams',
            'create-product',
            'edit-product-details',
            'manage-product-categories',
            'upload-product-images',
            'edit-product-pricing',
            'manage-product-inventory',
            'publish-product',
            'archive-product',
            'create-invoice',
            'view-invoice',
            'edit-invoice',
            'delete-invoice',
            'send-invoice',
            'record-invoice-payment',
            'export-invoice-report',
            'create-purchase-order',
            'view-purchase-order',
            'edit-purchase-order',
            'approve-purchase-order',
            'record-purchase-receipt',
            'manage-vendors',
            'view-store-details',
            'edit-store-information',
            'manage-store-inventory',
            'transfer-store-stock',
            'view-store-sales-data',
            'view-employee-details',
            'edit-employee-information',
            'create-employee-account',
            'deactivate-employee-account',
            'assign-employee-role',
            'manage-employee-permissions',
            'view-role-list',
            'create-role',
            'role-menu',
            'edit-role-permissions',
            'delete-role',
            'assign-role-to-user',
            'view-role-assignment',
            //Add New parmissions,
            'Dashboard',
            'Sales',
            'sales.create',
            'sales.view',
            'Customers',
            'customer.create',
            'customer.index',
            'customer.ledger',
            'customer.delete',
            'Suppliers',
            'ledger.payment',
            'ledger.edit (false)',
            'ledger.delete (false)',
            'Products',
            'Brands',
            'brand_category.create',
            'brand_category.edit',
            'brand_category.update',
            'brand_category.delete',
            'Categories',
            'Inventory',
            'All Vouchers',
            'voucher',
            'voucher.entry',
            'employee_payment_voucher.entry',
            'Employee Management',
            'employee.create',
            'employee.index',
            'employee.view',
            'employee.edit',
            'employee.inactive',
            'employee.delete',
            'Reports',
            'Role & Permission Management',
            'roles.create',
            'roles',
            'role.index',
            'role.view_all_permissions',
            'role.edit',
            'role.delete',
            'Organization Information',
            'organization info.access',
            'reports.all_access',
            // Extra granular checks back the supplier, customer-ledger, and report routes.
            'reports.vouchers',
            'customer.edit',
            'customer.update',
            'customer.ledger.payment',
            'supplier.index',
            'supplier.create',
            'supplier.edit',
            'supplier.delete',
            'supplier.ledger',
            'supplier.ledger.create',
            'supplier.ledger.edit',
            'supplier.ledger.delete'
        ];
        foreach ($permissions as $permission) {
            //পারমিশন টেবিলে ডাটা ইন্সারট করার জন্য ২টা পদবতি ব্যবহার করা যায়।
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
