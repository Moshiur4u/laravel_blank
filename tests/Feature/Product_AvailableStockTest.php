<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('product index and sale form list only products with available stock', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('product.index', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('product.create', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('sales.create', 'web'));

    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Stock Visibility Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Stock Visibility Brand']);
    $inStockProduct = Product::create([
        'productName' => 'Available Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 25,
        'unit' => '3',
    ]);
    Product::create([
        'productName' => 'Out of Stock Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 25,
        'unit' => '0',
    ]);

    $this->actingAs($user)
        ->get(route('product.index'))
        ->assertOk()
        ->assertSee('Available Product')
        ->assertDontSee('Out of Stock Product')
        ->assertViewHas('Products', fn ($products) => $products->pluck('id')->all() === [$inStockProduct->id]);

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertSee('Available Product')
        ->assertDontSee('Out of Stock Product')
        ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$inStockProduct->id]);

    $this->actingAs($user)
        ->get(route('product.create'))
        ->assertOk()
        ->assertSee('list="existingProductNames"', false)
        ->assertSee('<option value="Available Product"></option>', false)
        ->assertSee('<option value="Out of Stock Product"></option>', false);
});
