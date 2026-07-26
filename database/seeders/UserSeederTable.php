<?php

namespace Database\Seeders;

use App\Models\User;
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
        // এখানে উজার তৈরি করা হলো ডাটাবেজের ইউজার টেবিলে সুপার এডমিন এবং এডমিন উজার তৈরি করা হলো
        $supperAdmin = User::create([
            'name' => 'Supper Admin',
            'email' => '[EMAIL_ADDRESS]',
            // পাসওয়ার্ড হ্যাশ করার জন্য ফাসস ফংশন ব্যবহার করা হলো
            'password' => Hash::make('password'),

        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            // পাসওয়ার্ড হ্যাশ করার জন্য ফাসস ফংশন ব্যবহার করা হলো
            'password' => Hash::make('password'),

        ]);
        // রোল তৈরি করা হলো স্পাটিয়ার ব্যবহার করে ডাটাবেজে রোল টেবিলে
        // স্পাটিয়ার package ব্যবহার করার জন্য Role এবং Permission মডেল ইম্পোর্ট করা হলো
        $role = Role::create(['name' => 'admin']);

        // পারমিশন মডেল কল করা হলো প্লক ফাংশন ব্যবহার করে আই ডী নিয়ে আসা হলো
        $permissions = Permission::pluck('id')->all();

        // পারমিশন এবং রোলকে সিঙ্ক করা হলো
        $role->syncPermissions($permissions);

        // রোল এসাইন করা হলো সুপার এডমিন এবং এডমিনের সিঙ্ক করা হলও কারন এক সাথে দুটা উজার রোল এসাইন করা যায় না ।
        $supperAdmin->assignRole($role);
        $admin->syncRoles($role);

    }
}
