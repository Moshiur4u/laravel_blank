<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('sales report filters invoices and ranks products by revenue', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Sales Report Customer',
        'phone' => '01712345678',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);
    $otherCustomer = Customer::create([
        'name' => 'Other Sales Customer',
        'phone' => '01812345678',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);

    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Report Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Report Brand']);
    $createProduct = function (string $name) use ($categoryId, $brandId): Product {
        return Product::create([
            'productName' => $name,
            'product_categorie_id' => $categoryId,
            'brand_id' => $brandId,
            'price' => 100,
            'unit' => '50',
            'stock_unit' => '50',
        ]);
    };
    $topProduct = $createProduct('Top Report Product');
    $otherProduct = $createProduct('Other Report Product');

    $createInvoice = function (array $attributes, array $items): SaleInvoice {
        $invoice = SaleInvoice::create($attributes);
        foreach ($items as [$product, $quantity, $lineTotal]) {
            SaleItem::create([
                'sale_invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'product_name' => $product->productName,
                'quantity' => $quantity,
                'unit_price' => $lineTotal / $quantity,
                'line_total' => $lineTotal,
            ]);
        }

        return $invoice;
    };
    $invoiceAttributes = fn (string $number, User $user, Customer $invoiceCustomer, string $date, float $subtotal) => [
        'invoice_no' => $number,
        'customer_id' => $invoiceCustomer->id,
        'customer_name' => $invoiceCustomer->name,
        'customer_phone' => $invoiceCustomer->phone,
        'user_id' => $user->id,
        'invoice_date' => $date,
        'subtotal' => $subtotal,
        'discount_amount' => 5,
        'vat_amount' => 0,
        'previous_due' => 20,
        'include_previous_due' => true,
        'total_amount' => $subtotal + 15,
        'amount_paid' => 10,
        'due_amount' => $subtotal + 5,
    ];

    $matchingInvoice = $createInvoice(
        $invoiceAttributes('SALES-REPORT-001', $employee, $customer, today()->toDateString(), 100),
        [[$topProduct, 1, 100], [$otherProduct, 1, 20]]
    );
    $createInvoice(
        $invoiceAttributes('SALES-REPORT-002', $otherEmployee, $otherCustomer, today()->toDateString(), 200),
        [[$topProduct, 1, 150]]
    );
    $createInvoice(
        $invoiceAttributes('SALES-REPORT-003', $employee, $customer, today()->subDay()->toDateString(), 300),
        [[$topProduct, 1, 200]]
    );

    $response = $this->actingAs($employee)->get(route('sales.report', [
        'start_date' => today()->toDateString(),
        'end_date' => today()->toDateString(),
        'product_id' => $topProduct->id,
        'employee_id' => $employee->id,
        'customer_id' => $customer->id,
    ]));

    $response->assertOk()
        ->assertSee('SALES-REPORT-001')
        ->assertDontSee('SALES-REPORT-002')
        ->assertDontSee('SALES-REPORT-003')
        ->assertViewHas('totalSalesCount', 1)
        ->assertViewHas('totalSalesAmount', 95.0)
        ->assertViewHas('totalDue', 105.0)
        ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 1
            && $invoices->first()->is($matchingInvoice))
        ->assertViewHas('topProducts', fn ($products) => $products->count() === 1
            && $products->first()->product_name === 'Top Report Product'
            && (float) $products->first()->revenue === 100.0);

    $this->actingAs($employee)->get(route('sales.report', [
        'start_date' => today()->toDateString(),
        'end_date' => today()->toDateString(),
        'employee_id' => $employee->id,
        'customer_id' => $customer->id,
    ]))
        ->assertOk()
        ->assertViewHas('topProducts', fn ($products) => $products->pluck('product_name')->all() === [
            'Top Report Product',
            'Other Report Product',
        ]);
});

test('sales report defaults to today and validates date and filter values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('sales.report'))
        ->assertOk()
        ->assertViewHas('startDate', today()->toDateString())
        ->assertViewHas('endDate', today()->toDateString());

    $this->get(route('sales.report', [
        'start_date' => today()->toDateString(),
        'end_date' => today()->subDay()->toDateString(),
    ]))->assertSessionHasErrors('end_date');

    $this->get(route('sales.report', ['product_id' => 999999]))
        ->assertSessionHasErrors('product_id');
});

test('sales report allocates invoice VAT to products by their share of invoice sales', function () {
    $user = User::factory()->create();
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'VAT Report Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'VAT Report Brand']);
    $products = collect(['VAT Product A', 'VAT Product B'])->map(fn (string $name) => Product::create([
        'productName' => $name,
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 150,
        'unit' => '50',
        'stock_unit' => '50',
    ]));
    $invoice = SaleInvoice::create([
        'invoice_no' => 'SALES-REPORT-VAT-001',
        'user_id' => $user->id,
        'invoice_date' => today(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'vat_amount' => 20,
        'ait_amount' => 10,
        'total_amount' => 230,
        'amount_paid' => 230,
        'due_amount' => 0,
    ]);

    foreach ([[$products[0], 50], [$products[1], 150]] as [$product, $lineTotal]) {
        SaleItem::create([
            'sale_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'product_name' => $product->productName,
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'line_total' => $lineTotal,
        ]);
    }

    $this->actingAs($user)
        ->get(route('sales.report'))
        ->assertOk()
        ->assertViewHas('topProducts', fn ($topProducts) => $topProducts->count() === 2
            && (float) $topProducts->firstWhere('product_name', 'VAT Product A')->vat_amount === 5.0
            && (float) $topProducts->firstWhere('product_name', 'VAT Product B')->vat_amount === 15.0
            && (float) $topProducts->sum('vat_amount') === 20.0
            && (float) $topProducts->firstWhere('product_name', 'VAT Product A')->ait_amount === 2.5
            && (float) $topProducts->firstWhere('product_name', 'VAT Product B')->ait_amount === 7.5
            && (float) $topProducts->sum('ait_amount') === 10.0)
        ->assertViewHas('totalSalesAmount', 230.0);
});
