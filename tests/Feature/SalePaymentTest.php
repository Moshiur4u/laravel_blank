<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\User;
use App\Models\VoucherEntry;
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
            'customer_name' => 'Walk-in Invoice Customer',
            'customer_address' => '5 Walk-in Road',
            'terms_conditions' => 'Deliver within five business days.',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $walkInInvoice = SaleInvoice::query()->latest('id')->firstOrFail();
    expect($walkInInvoice->customer_id)->toBeNull()
        ->and($walkInInvoice->customer_name)->toBe('Walk-in Invoice Customer')
        ->and($walkInInvoice->customer_address)->toBe('5 Walk-in Road')
        ->and($walkInInvoice->terms_conditions)->toBe('Deliver within five business days.')
        ->and((float) $walkInInvoice->total_amount)->toBe(200.0)
        ->and((float) $walkInInvoice->amount_paid)->toBe(200.0)
        ->and((float) $walkInInvoice->due_amount)->toBe(0.0);
    $this->assertDatabaseHas('voucher_entries', [
        'voucher_type' => 'credit',
        'expense_income_type' => 'Walk-in Customer',
        'amount' => '200.00',
        'reference' => $walkInInvoice->invoice_no,
        'created_by' => $employee->id,
    ]);

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
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $customerInvoice = SaleInvoice::query()->latest('id')->firstOrFail();
    expect($customerInvoice->customer_id)->toBe($customer->id)
        ->and((float) $customerInvoice->total_amount)->toBe(100.0)
        ->and((float) $customerInvoice->amount_paid)->toBe(40.0)
        ->and((float) $customerInvoice->due_amount)->toBe(60.0);
    $this->assertDatabaseHas('voucher_entries', [
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'amount' => '40.00',
        'reference' => $customerInvoice->invoice_no,
        'created_by' => $employee->id,
    ]);
});

test('sales use entered unit prices for invoices and profit and calculate AIT', function () {
    $employee = User::factory()->create();
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Manual Sale Price Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Manual Sale Price Brand']);
    $product = Product::create([
        'productName' => 'Manual Sale Price Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'purchase_price' => '70.00',
        'price' => 100,
        'unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000003',
            'save_customer' => 'no',
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat_percentage' => 10,
            'ait_percentage' => 3,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 125],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $item = $invoice->items()->firstOrFail();

    expect((float) $invoice->subtotal)->toBe(250.0)
        ->and((float) $invoice->vat_amount)->toBe(25.0)
        ->and((float) $invoice->ait_amount)->toBe(7.5)
        ->and((float) $invoice->total_amount)->toBe(282.5)
        ->and((float) $item->unit_price)->toBe(125.0)
        ->and((float) $item->line_total)->toBe(250.0)
        ->and($item->profit)->toBe(110.0);
});
