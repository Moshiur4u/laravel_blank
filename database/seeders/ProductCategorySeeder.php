<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $Categories = [
            'Hardware & Core Components',
            'Pre-Built Systems',
            'Peripherals & Displays',
            'Networking',
            'Printers & Office',
            'Software',
            'Power & Accessories'
        ];
        foreach ($Categories as $Category) {
            ProductCategory::Create(['category_name'=>$Category]);
        };
    }
}
