<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('walk-in sales receive the full invoice amount and existing customers can pay partially', function () {
    $employee = User::factory()->create();
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Payment Test Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Payment Test Brand']);
    $product = Product::create([
        'productName' => 'Payment Test Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000001',
            'save_customer' => 'no',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
        ->assertRedirect();

    $walkInInvoice = SaleInvoice::query()->latest('id')->firstOrFail();
    expect($walkInInvoice->customer_id)->toBeNull()
        ->and((float) $walkInInvoice->total_amount)->toBe(200.0)
        ->and((float) $walkInInvoice->amount_paid)->toBe(200.0)
        ->and((float) $walkInInvoice->due_amount)->toBe(0.0);

    $customer = Customer::create([
        'name' => 'Partial Payment Customer',
        'phone' => '01700000002',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => $customer->phone,
            'save_customer' => 'no',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 40,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect();

    $customerInvoice = SaleInvoice::query()->latest('id')->firstOrFail();
    expect($customerInvoice->customer_id)->toBe($customer->id)
        ->and((float) $customerInvoice->total_amount)->toBe(100.0)
        ->and((float) $customerInvoice->amount_paid)->toBe(40.0)
        ->and((float) $customerInvoice->due_amount)->toBe(60.0);
});
