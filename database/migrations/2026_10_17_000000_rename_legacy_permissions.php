<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void { // rename ও pivot merge একসঙ্গে atomicভাবে সম্পন্ন হয়।
            $this->movePermissions($this->permissionMap());
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions(); // নতুন নামের permission cache থেকে পুনরায় পড়া হবে।
    }

    public function down(): void
    {
        DB::transaction(function (): void { // rollback-এর rename ও pivot merge atomicভাবে সম্পন্ন হয়।
            $this->movePermissions($this->reversePermissionMap());
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions(); // rollback-এর পর পুরোনো permission cache পরিষ্কার করা হবে।
    }

    private function permissionMap(): array
    {
        return [
            'Products' => ['product.index'], // পুরোনো product list নাম।
            'Brands' => ['brand.index'], // পুরোনো brand list নাম।
            'Categories' => ['category.index'], // পুরোনো category list নাম।
            'create-product' => ['product.create'], // পুরোনো product create নাম।
            'edit-product-details' => ['product.edit'], // পুরোনো product edit নাম।
            'archive-product' => ['product.delete'], // পুরোনো product delete নাম।
            'brand_category.create' => ['brand.create', 'product.category.create'], // পুরোনো permission brand ও category উভয় action চালাত।
            'brand_category.edit' => ['brand.edit', 'product.category.edit'], // পুরোনো edit permission দুই module-এ ভাগ করা হয়েছে।
            'brand_category.update' => ['brand.update', 'product.category.update'], // পুরোনো update permission দুই module-এ ভাগ করা হয়েছে।
            'brand_category.delete' => ['brand.delete', 'product.category.delete'], // পুরোনো delete permission দুই module-এ ভাগ করা হয়েছে।
            'customer.update' => ['customer.edit'], // update action এখন customer.edit permission ব্যবহার করে।
            'customer.ledger.payment' => ['Customer.ledger.payment'], // নতুন seeder-এর casing অনুসরণ করা হয়েছে।
            'supplier.ledger.create' => ['Supplier.ledger.create'], // নতুন seeder-এর casing অনুসরণ করা হয়েছে।
            'supplier.ledger.payment' => ['Supplier.ledger.payment'], // নতুন seeder-এর casing অনুসরণ করা হয়েছে।
            'ledger.payment' => ['Supplier.ledger.payment'], // পুরোনো supplier payment permission।
            'supplier.ledger.edit' => ['Supplier.ledger.edit'], // নতুন seeder-এর casing অনুসরণ করা হয়েছে।
            'supplier.ledger.delete' => ['Supplier.ledger.delete'], // নতুন seeder-এর casing অনুসরণ করা হয়েছে।
            'employee.view' => ['view-employee-details'], // পুরোনো employee details permission।
            'role-menu' => ['role.index'], // পুরোনো role list permission।
            'view-role-list' => ['role.index'], // পুরোনো role list permission-এর আরেক নাম।
            'create-role' => ['roles.create'], // পুরোনো role create permission।
            'edit-role-permissions' => ['role.edit'], // পুরোনো role edit permission।
            'delete-role' => ['role.delete'], // পুরোনো role delete permission।
            'assign-user-role' => ['employee.role.assign'], // পুরোনো employee role assignment permission।
            'assign-role-to-user' => ['employee.role.assign'], // পুরোনো employee role assignment permission-এর আরেক নাম।
            'reports.vouchers' => ['voucher.report.view', 'voucher.report.employee', 'voucher.report.miscellaneous'], // পুরোনো broad voucher report permission।
            'All Vouchers' => ['voucher.report.view', 'voucher.report.miscellaneous'], // পুরোনো general voucher report permission।
            'reports.inventory' => ['product.report.inventory'], // পুরোনো inventory report permission।
            'view-sales-dashboard' => ['sales.report'], // পুরোনো sales report permission।
        ];
    }

    private function reversePermissionMap(): array
    {
        $reverse = [];

        foreach ($this->permissionMap() as $oldName => $newNames) {
            foreach ($newNames as $newName) {
                $reverse[$newName] ??= [$oldName]; // rollback-এ একাধিক পুরোনো alias এক permission-এ ফেরত আসে।
            }
        }

        return $reverse;
    }

    private function movePermissions(array $permissionMap): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        throw_if(empty($tableNames), 'Error: permission table names are not configured.');

        $permissionTable = $tableNames['permissions'];
        $rolePermissionTable = $tableNames['role_has_permissions'];
        $modelPermissionTable = $tableNames['model_has_permissions'];
        $permissionPivot = $columnNames['permission_pivot_key'] ?? 'permission_id';

        foreach ($permissionMap as $oldName => $newNames) {
            $sources = DB::table($permissionTable)->where('name', $oldName)->get();

            foreach ($sources as $source) {
                $sourceId = (int) $source->id;
                $sourceKeepsOldName = true;

                foreach (array_unique($newNames) as $newName) {
                    if ($newName === $oldName) {
                        continue;
                    }

                    $target = DB::table($permissionTable)
                        ->where('name', $newName)
                        ->where('guard_name', $source->guard_name)
                        ->first();

                    if ($target !== null && (int) $target->id === $sourceId) { // case-insensitive collation-এ একই row মিললে শুধু নাম বদলায়।
                        DB::table($permissionTable)
                            ->where('id', $sourceId)
                            ->update(['name' => $newName]);
                        $targetId = $sourceId;
                        $sourceKeepsOldName = false;
                    } elseif ($target === null && $sourceKeepsOldName) {
                        DB::table($permissionTable)
                            ->where('id', $sourceId)
                            ->update(['name' => $newName]);
                        $targetId = $sourceId;
                        $sourceKeepsOldName = false;
                    } elseif ($target === null) {
                        $targetId = (int) DB::table($permissionTable)->insertGetId([
                            'name' => $newName,
                            'guard_name' => $source->guard_name,
                            'created_at' => $source->created_at,
                            'updated_at' => now(),
                        ]);
                    } else {
                        $targetId = (int) $target->id;
                    }

                    if ($targetId !== $sourceId) {
                        $this->copyAssignments($rolePermissionTable, $permissionPivot, $sourceId, $targetId);
                        $this->copyAssignments($modelPermissionTable, $permissionPivot, $sourceId, $targetId);
                    }
                }

                if ($sourceKeepsOldName) {
                    DB::table($rolePermissionTable)->where($permissionPivot, $sourceId)->delete();
                    DB::table($modelPermissionTable)->where($permissionPivot, $sourceId)->delete();
                    DB::table($permissionTable)->where('id', $sourceId)->delete();
                }
            }
        }
    }

    private function copyAssignments(string $table, string $permissionPivot, int $sourceId, int $targetId): void
    {
        foreach (DB::table($table)->where($permissionPivot, $sourceId)->get() as $assignment) {
            $assignment = (array) $assignment;
            $assignment[$permissionPivot] = $targetId;
            DB::table($table)->insertOrIgnore($assignment);
        }
    }
};
