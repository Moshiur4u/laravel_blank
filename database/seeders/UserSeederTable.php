<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Here we Create user in Our User DB
        $supperAdmin = User::create([
            'name'=>'Supper Admin',
            'email'=>'supadmin@example.com',
            'password'=>Hash::make('password')

        ]);
        $admin = User::create([
            'name'=>'Admin',
            'email'=>'admin@example.com',
            // for make hash function
            'password'=>Hash::make('password')

        ]);
        // Now here we create role using spatie options
        $role = Role::create(['name'=>'admin']);

        // Here we call permissions model
        $permissions = Permission::pluck('id')->all();

        // New Syc with Permission and role
        $role->syncPermissions($permissions);

        // Assign role with variable Note: 2 Assign working at time thatWhy  we use syn function
        $supperAdmin->assignRole($role);
        $admin->syncRoles($role);

    }
}
