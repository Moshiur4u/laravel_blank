<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        // পারমিশন সিডার কল করা হলো ডাটাবেজে পারমিশন টেবিলের জন্য
        $this->call([
            PermissionSeederTable::class,
            // ইউজার সিডার কল করা হলো ডাটাবেজে ইউজার টেবিলের জন্য
            UserSeederTable::class,
            // প্রোডাক্ট ক্যাটাগরি সিডার কল করা হলো ডাটাবেজে প্রোডাক্ট ক্যাটাগরি টেবিলের জন্য
            ProductCategorySeeder::class,
        ]);
    }
}
