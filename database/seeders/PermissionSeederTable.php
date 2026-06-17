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
                'view-role-assignment'
        ];
        foreach ($permissions as $permission) {
            Permission::create(['name'=> $permission]);
        }
    }
}
