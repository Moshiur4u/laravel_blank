<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VoucherAccountPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            'voucher.account.index',
            'voucher.account.create',
            'voucher.account.edit',
            'voucher.account.update',
            'voucher.account.delete',
        ])->map(fn (string $name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]));

        Role::query()
            ->whereIn('name', ['admin', 'manager'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));
    }
}
