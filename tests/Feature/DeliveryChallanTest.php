<?php

use App\Models\Organization;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

test('sales viewers can print a delivery challan with product quantities and units only', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('sales.view', 'web'));
    Organization::create([
        'name' => 'Challan Organization',
        'logo' => 'organization-logo.png',
    ]);
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Challan Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Challan Brand']);
    $product = Product::create([
        'productName' => 'Challan Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => 'Boxes',
    ]);
    $invoice = SaleInvoice::create([
        'invoice_no' => 'CHALLAN-001',
        'customer_name' => 'Challan Customer',
        'customer_phone' => '01700000000',
        'customer_address' => '10 Delivery Road',
        'user_id' => $user->id,
        'invoice_date' => now(),
        'total_amount' => 500,
        'amount_paid' => 0,
        'due_amount' => 500,
    ]);
    SaleItem::create([
        'sale_invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'product_name' => $product->productName,
        'quantity' => 3,
        'unit_price' => 100,
        'line_total' => 300,
    ]);
    SaleItem::create([
        'sale_invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'product_name' => $product->productName,
        'quantity' => 2,
        'unit_price' => 100,
        'line_total' => 200,
    ]);

    $this->actingAs($user)
        ->get(route('sales.delivery-challan', $invoice->id))
        ->assertOk()
        ->assertSee('Delivery Challan')
        ->assertSee('Name: Challan Customer')
        ->assertSee('Phone: 01700000000')
        ->assertSee('Address: 10 Delivery Road')
        ->assertSee('Date: ' . $invoice->invoice_date->format('d M Y'))
        ->assertSee('Challan Product')
        ->assertSee('QTY')
        ->assertSee('UNIT')
        ->assertSee('#14ADE0')
        ->assertSee('#ffff')
        ->assertSee('Boxes')
        ->assertSee('Total Quantity')
        ->assertDontSee('Print logo watermark')
        ->assertDontSee($invoice->invoice_date->format('d M Y, h:i A'))
        ->assertDontSee('Unit Selling Price')
        ->assertDontSee('Subtotal');
});

test('sale invoice shows customer details, quotation default terms, and the colored header', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('sales.report', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('sales.document.watermark', 'web'));
    Organization::create([
        'name' => 'Invoice Organization',
        'logo' => 'organization-logo.png',
    ]);
    $invoice = SaleInvoice::create([
        'invoice_no' => 'INVOICE-TERMS-001',
        'customer_name' => 'Invoice Customer',
        'customer_phone' => '01800000000',
        'customer_address' => '20 Invoice Road',
        'user_id' => $user->id,
        'invoice_date' => now(),
        'total_amount' => 100,
        'amount_paid' => 75,
        'due_amount' => 25,
    ]);

    $response = $this->actingAs($user)
        ->get(route('sales.invoice', $invoice->id));

    $response
        ->assertOk()
        ->assertSee('Invoice Customer')
        ->assertSee('01800000000')
        ->assertSee('20 Invoice Road')
        ->assertSee('Date: ' . $invoice->invoice_date->format('d M Y'))
        ->assertSee('Terms & Conditions')
        ->assertSee('This quotation is valid for 15 days from the date of issue.')
        ->assertSee('Amount Paid in Words')
        ->assertSee('Taka seventy-five only.')
        ->assertSee('#14ADE0')
        ->assertSee('#ffff')
        ->assertSee('Print logo watermark')
        ->assertSee('id="watermark-toggle"')
        ->assertDontSee($invoice->invoice_date->format('d M Y, h:i A'));

    expect(substr_count($response->getContent(), '<table'))->toBe(1);
});
