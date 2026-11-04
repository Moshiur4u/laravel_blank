<?php

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Database\Seeders\PermissionSeederTable;
use Database\Seeders\ProductReturnPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('product returns restore stock and proportionally adjust due and refund voucher', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo([
        Permission::findOrCreate('product.return', 'web'),
        Permission::findOrCreate('product.return.report', 'web'),
        Permission::findOrCreate('sales.create', 'web'),
        Permission::findOrCreate('sales.report', 'web'),
        Permission::findOrCreate('voucher.edit', 'web'),
        Permission::findOrCreate('voucher.delete', 'web'),
    ]);
    $customer = Customer::create([
        'name' => 'Return Test Customer',
        'phone' => '01700000009',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Return Test Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Return Test Brand']);
    $product = Product::create([
        'productName' => 'Return Test Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '8',
        'stock_unit' => '10',
    ]);
    $invoice = SaleInvoice::create([
        'invoice_no' => 'INV-RETURN-TEST',
        'customer_id' => $customer->id,
        'customer_phone' => $customer->phone,
        'customer_name' => $customer->name,
        'user_id' => $user->id,
        'invoice_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 20,
        'vat_amount' => 18,
        'ait_amount' => 0,
        'previous_due' => 0,
        'total_amount' => 198,
        'amount_paid' => 50,
        'due_amount' => 148,
    ]);
    $walkInInvoice = SaleInvoice::create([
        'invoice_no' => 'INV-WALK-IN-TEST',
        'customer_id' => null,
        'customer_phone' => null,
        'customer_name' => null,
        'user_id' => $user->id,
        'invoice_date' => now(),
        'subtotal' => 0,
        'discount_amount' => 0,
        'vat_amount' => 0,
        'ait_amount' => 0,
        'previous_due' => 0,
        'total_amount' => 0,
        'amount_paid' => 0,
        'due_amount' => 0,
    ]);
    $saleItem = SaleItem::create([
        'sale_invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'product_name' => $product->productName,
        'quantity' => 2,
        'unit_price' => 100,
        'line_total' => 200,
    ]);
    $ledger = CustomerLedger::create([
        'customer_id' => $customer->id,
        'sale_invoice_id' => $invoice->id,
        'employee_id' => $user->id,
        'received_by' => $user->id,
        'entry_type' => 'sale',
        'payment_method' => 'cash',
        'sale_date' => today(),
        'invoice_no' => $invoice->invoice_no,
        'product_name' => $product->productName.' × 2',
        'quantity' => 2,
        'unit_price' => 100,
        'total_amount' => 198,
        'payment_type' => 'due',
        'payment_amount' => 50,
        'due_amount' => 148,
    ]);
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => today(),
        'amount' => '50.00',
        'reference' => $invoice->invoice_no,
        'description' => 'Invoice payment',
        'created_by' => $user->id,
        'created_by_name' => $user->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $ledger->id,
    ]);

    $this->actingAs($user)
        ->get(route('product-returns.search', ['q' => 'Return Test']))
        ->assertOk()
        ->assertJsonPath('customers.0.name', $customer->name);

    $this->actingAs($user)
        ->get(route('product-returns.customer-history', ['customer_id' => $customer->id]))
        ->assertOk()
        ->assertJsonPath('invoices.0.invoice_no', $invoice->invoice_no)
        ->assertJsonPath('invoices.0.items.0.remaining_quantity', 2);
    $this->actingAs($user)
        ->get(route('product-returns.customer-history', ['name' => 'Walk-in Customer']))
        ->assertOk()
        ->assertJsonPath('invoices.0.invoice_no', $walkInInvoice->invoice_no);
    $this->actingAs($user)
        ->get(route('product-returns.search', ['q' => 'Walk-in']))
        ->assertOk()
        ->assertJsonPath('customers.0.name', 'Walk-in Customer');

    $this->actingAs($user)
        ->post(route('product-returns.store'), [
            'sale_item_id' => $saleItem->id,
            'quantity' => 1,
            'return_date' => today()->toDateString(),
            'reason' => 'First partial return',
        ])
        ->assertRedirect(route('product-returns.index', ['invoice_id' => $invoice->id]));

    $firstReturn = ProductReturn::query()->latest('id')->firstOrFail();
    expect((float) $firstReturn->return_amount)->toBe(99.0)
        ->and((float) $firstReturn->refund_amount)->toBe(0.0)
        ->and((float) $product->fresh()->unit)->toBe(9.0)
        ->and((float) $ledger->fresh()->due_amount)->toBe(49.0);
    $this->actingAs($user)
        ->get(route('sales.report', [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('totalSalesAmount', 99.0)
        ->assertViewHas('totalDue', 49.0)
        ->assertViewHas('topProducts', fn ($topProducts) => (int) $topProducts->sum('quantity_sold') === 1
            && (float) $topProducts->sum('revenue') === 100.0);

    $this->actingAs($user)
        ->post(route('product-returns.store'), [
            'sale_item_id' => $saleItem->id,
            'quantity' => 1,
            'return_date' => today()->toDateString(),
            'reason' => 'Final return',
        ])
        ->assertRedirect();

    $lastReturn = ProductReturn::query()->latest('id')->firstOrFail();
    expect((float) $lastReturn->return_amount)->toBe(99.0)
        ->and((float) $lastReturn->refund_amount)->toBe(50.0)
        ->and((float) $product->fresh()->unit)->toBe(10.0)
        ->and((float) $ledger->fresh()->total_amount)->toBe(0.0)
        ->and((float) $ledger->fresh()->payment_amount)->toBe(0.0)
        ->and((float) $ledger->fresh()->due_amount)->toBe(0.0);
    $this->actingAs($user)
        ->get(route('sales.report', [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('totalSalesAmount', 0.0)
        ->assertViewHas('totalDue', 0.0)
        ->assertViewHas('topProducts', fn ($topProducts) => $topProducts->sum('quantity_sold') === 0);

    $this->assertDatabaseHas('voucher_entries', [
        'voucher_type' => 'debit',
        'amount' => '50.00',
        'reference' => $lastReturn->return_no,
        'voucherable_type' => ProductReturn::class,
        'voucherable_id' => $lastReturn->id,
    ]);
    $refundVoucher = VoucherEntry::query()->where('reference', $lastReturn->return_no)->firstOrFail();

    $this->actingAs($user)
        ->put(route('voucher-report.combined.update', ['debit', $refundVoucher->id]), [
            'voucher_date' => today()->toDateString(),
            'amount' => 60,
        ])
        ->assertSessionHasErrors('voucher');

    $this->delete(route('voucher-report.combined.destroy', ['debit', $refundVoucher->id]))
        ->assertSessionHasErrors('voucher');
    $this->assertDatabaseHas('voucher_entries', ['id' => $refundVoucher->id, 'amount' => '50.00']);

    $this->actingAs($user)
        ->get(route('product-returns.report', [
            'from_date' => today()->toDateString(),
            'to_date' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('totalQuantity', 2)
        ->assertViewHas('totalReturnAmount', 198.0)
        ->assertViewHas('totalRefundAmount', 50.0);

    $this->actingAs($user)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertRedirect(route('sales.index'));

    expect((float) $product->fresh()->unit)->toBe(10.0);
    $this->assertDatabaseMissing('product_returns', ['sale_invoice_id' => $invoice->id]);
    $this->assertDatabaseMissing('voucher_entries', ['reference' => $lastReturn->return_no]);
});

test('a user without product return permission cannot open the return menu or submit a return', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('product-returns.index'))
        ->assertForbidden();

    $this->post(route('product-returns.store'))
        ->assertForbidden();
});

test('only the admin role receives product return permissions by default', function () {
    $this->seed([
        PermissionSeederTable::class,
        RolePermissionSeeder::class,
        ProductReturnPermissionSeeder::class,
    ]);

    expect(Role::findByName('admin')->hasPermissionTo('product.return'))->toBeTrue()
        ->and(Role::findByName('admin')->hasPermissionTo('product.return.report'))->toBeTrue()
        ->and(Role::findByName('manager')->hasPermissionTo('product.return'))->toBeFalse()
        ->and(Role::findByName('sales')->hasPermissionTo('product.return'))->toBeFalse();
});
