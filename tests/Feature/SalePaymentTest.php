<?php

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerLedgerPayment;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

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
        ->and($walkInInvoice->invoice_no)->toBe('QT-' . now()->format('Ym') . '-0001')
        ->and($walkInInvoice->customer_name)->toBe('Walk-in Invoice Customer')
        ->and($walkInInvoice->customer_address)->toBe('5 Walk-in Road')
        ->and($walkInInvoice->terms_conditions)->toBe('Deliver within five business days.')
        ->and((float) $walkInInvoice->total_amount)->toBe(200.0)
        ->and((float) $walkInInvoice->amount_paid)->toBe(200.0)
        ->and((float) $walkInInvoice->due_amount)->toBe(0.0);
    $this->actingAs($employee)
        ->get(route('sales.invoice', $walkInInvoice->id))
        ->assertOk()
        ->assertSee('Taka two hundred only.');
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
        ->and($customerInvoice->invoice_no)->toBe('QT-' . now()->format('Ym') . '-0002')
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

test('today invoice updates synchronize stock, walk-in voucher, and dashboard totals', function () {
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->givePermissionTo(Permission::findOrCreate('Update_today_Invoice', 'web'));
    $employee->givePermissionTo(Permission::findOrCreate('sales.create', 'web'));
    $employee->givePermissionTo(Permission::findOrCreate('Dashboard', 'web'));
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Invoice Update Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Invoice Update Brand']);
    $product = Product::create([
        'productName' => 'Invoice Update Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'purchase_price' => 60,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000006',
            'save_customer' => 'no',
            'customer_name' => 'Update Walk-in Customer',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'ait_amount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $voucher = VoucherEntry::query()->where('reference', $invoice->invoice_no)->firstOrFail();

    $this->actingAs($employee)
        ->get(route('sales.invoice.edit', $invoice->id))
        ->assertOk()
        ->assertViewIs('backend.Sales.salesIndex')
        ->assertSee('Update Invoice')
        ->assertSee('name="payment_method" id="payment_method" class="form-select"', false)
        ->assertDontSee('name="payment_method" id="payment_method" class="form-select" disabled', false);

    $this->actingAs($employee)
        ->put(route('sales.invoice.update', $invoice->id), [
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat_percentage' => 0,
            'ait_percentage' => 0,
            'amount_paid' => 300,
            'payment_method' => 'bank',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 150],
            ],
        ])
        ->assertRedirect(route('sales.invoice', $invoice->id))
        ->assertSessionHas('success');

    expect((float) $invoice->fresh()->subtotal)->toBe(450.0)
        ->and((float) $invoice->fresh()->total_amount)->toBe(450.0)
        ->and((float) $invoice->fresh()->amount_paid)->toBe(300.0)
        ->and($invoice->fresh()->payment_method)->toBe('bank')
        ->and((float) $invoice->fresh()->due_amount)->toBe(150.0)
        ->and((int) $invoice->items()->sum('quantity'))->toBe(3)
        ->and((float) $product->fresh()->unit)->toBe(7.0)
        ->and((float) $voucher->fresh()->amount)->toBe(300.0);

    $this->actingAs($employee)
        ->get(route('sales.invoice.edit', $invoice->id))
        ->assertOk()
        ->assertSee('name="items[0][unit_price]"', false)
        ->assertSee('value="150.00"', false);

    $this->actingAs($employee)
        ->get(route('dashboardbody'))
        ->assertOk()
        ->assertViewHas('metrics', fn(array $metrics) => $metrics['daily']['revenue'] === 450.0);

    $customer = Customer::create([
        'name' => 'Update Payment Customer',
        'phone' => '01700000007',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);
    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => $customer->phone,
            'save_customer' => 'no',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'ait_amount' => 0,
            'amount_paid' => 40,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $customerInvoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $customerLedger = CustomerLedger::query()->where('sale_invoice_id', $customerInvoice->id)->firstOrFail();
    $initialVoucher = VoucherEntry::query()
        ->where('voucherable_type', CustomerLedger::class)
        ->where('voucherable_id', $customerLedger->id)
        ->firstOrFail();
    CustomerLedgerPayment::create([
        'customer_ledger_id' => $customerLedger->id,
        'payment_date' => today(),
        'amount' => 10,
        'received_by' => $employee->id,
    ]);
    $additionalVoucher = VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => today(),
        'amount' => '10.00',
        'reference' => $customerInvoice->invoice_no,
        'description' => 'Payment received from customer '.$customer->name,
        'created_by' => $employee->id,
        'created_by_name' => $employee->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $customerLedger->id,
    ]);
    $customerLedger->update(['payment_amount' => 50, 'due_amount' => 50]);

    $this->actingAs($employee)
        ->put(route('sales.invoice.update', $customerInvoice->id), [
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat_percentage' => 0,
            'ait_percentage' => 0,
            'amount_paid' => 50,
            'payment_method' => 'nagad',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 150],
            ],
        ])
        ->assertRedirect(route('sales.invoice', $customerInvoice->id))
        ->assertSessionHas('success');

    expect((float) $customerInvoice->fresh()->amount_paid)->toBe(50.0)
        ->and((float) $customerInvoice->fresh()->due_amount)->toBe(90.0)
        ->and((float) $customerLedger->fresh()->payment_amount)->toBe(60.0)
        ->and($customerLedger->fresh()->payment_method)->toBe('nagad')
        ->and((float) $customerLedger->fresh()->due_amount)->toBe(90.0)
        ->and((float) $initialVoucher->fresh()->amount)->toBe(50.0)
        ->and((float) $additionalVoucher->fresh()->amount)->toBe(10.0);

    $invoice->update(['invoice_date' => today()->subDay()]);
    $this->actingAs($employee)
        ->get(route('sales.invoice.edit', $invoice->id))
        ->assertForbidden();
    $this->actingAs($employee)
        ->put(route('sales.invoice.update', $invoice->id), [
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat_percentage' => 0,
            'ait_percentage' => 0,
            'amount_paid' => 300,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ])
        ->assertForbidden();
});

test('today invoice void restores stock and removes its ledger and linked vouchers', function () {
    $employee = User::factory()->create();
    $employee->givePermissionTo(Permission::findOrCreate('Voide_today_Invoice', 'web'));
    $customer = Customer::create([
        'name' => 'Void Invoice Customer',
        'phone' => '01700000004',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Void Test Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Void Test Brand']);
    $product = Product::create([
        'productName' => 'Void Test Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => $customer->phone,
            'save_customer' => 'no',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 30,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $ledger = CustomerLedger::query()->where('sale_invoice_id', $invoice->id)->firstOrFail();
    $receipt = CustomerLedgerPayment::create([
        'customer_ledger_id' => $ledger->id,
        'payment_date' => today(),
        'amount' => 10,
        'received_by' => $employee->id,
    ]);
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => today(),
        'amount' => '10.00',
        'reference' => $invoice->invoice_no,
        'description' => 'Payment received from customer '.$customer->name,
        'created_by' => $employee->id,
        'created_by_name' => $employee->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $ledger->id,
    ]);

    $this->actingAs($employee)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('sale_invoices', ['id' => $invoice->id]);
    $this->assertDatabaseMissing('sale_items', ['sale_invoice_id' => $invoice->id]);
    $this->assertDatabaseMissing('customer_ledgers', ['id' => $ledger->id]);
    $this->assertDatabaseMissing('customer_ledger_payments', ['id' => $receipt->id]);
    $this->assertDatabaseMissing('voucher_entries', ['reference' => $invoice->invoice_no]);
    expect((float) $product->fresh()->unit)->toBe(10.0);
});

test('today-only permission cannot void an earlier invoice but any-day permission can', function () {
    $employee = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Old Invoice Customer',
        'phone' => '01700000005',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Old Invoice Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Old Invoice Brand']);
    $product = Product::create([
        'productName' => 'Old Invoice Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => $customer->phone,
            'save_customer' => 'no',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 30,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $ledger = CustomerLedger::query()->where('sale_invoice_id', $invoice->id)->firstOrFail();
    $receipt = CustomerLedgerPayment::create([
        'customer_ledger_id' => $ledger->id,
        'payment_date' => today()->subDay(),
        'amount' => 10,
        'received_by' => $employee->id,
    ]);
    $ledger->update([
        'payment_amount' => 40,
        'due_amount' => 160,
    ]);
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => today()->subDay(),
        'amount' => '10.00',
        'reference' => $invoice->invoice_no,
        'description' => 'Payment received from customer '.$customer->name,
        'created_by' => $employee->id,
        'created_by_name' => $employee->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $ledger->id,
    ]);
    $invoice->update(['invoice_date' => today()->subDay()]);

    expect((float) $ledger->fresh()->due_amount)->toBe(160.0)
        ->and((float) VoucherEntry::query()
            ->where('voucherable_type', CustomerLedger::class)
            ->where('voucherable_id', $ledger->id)
            ->sum('amount'))->toBe(40.0);

    $this->actingAs($employee)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertForbidden();

    $employee->givePermissionTo(Permission::findOrCreate('Voide_today_Invoice', 'web'));

    $this->actingAs($employee)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertForbidden();

    $this->assertDatabaseHas('sale_invoices', ['id' => $invoice->id]);

    $employee->givePermissionTo(Permission::findOrCreate('Voide_anyday_Invoice', 'web'));

    $this->actingAs($employee)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('sale_invoices', ['id' => $invoice->id]);
    $this->assertDatabaseMissing('sale_items', ['sale_invoice_id' => $invoice->id]);
    $this->assertDatabaseMissing('customer_ledgers', ['id' => $ledger->id]);
    $this->assertDatabaseMissing('customer_ledger_payments', ['id' => $receipt->id]);
    $this->assertDatabaseMissing('voucher_entries', ['reference' => $invoice->invoice_no]);
    expect((float) $product->fresh()->unit)->toBe(10.0)
        ->and((float) $customer->ledgers()->sum('due_amount'))->toBe(0.0);
});
