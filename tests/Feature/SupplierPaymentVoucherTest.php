<?php

use App\Models\Supplier;
use App\Models\Product;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('supplier payments create debit vouchers and print the paying user name', function () {
    $user = User::factory()->create([
        'name' => 'Supplier Payment Operator',
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo(Permission::findOrCreate('supplier.ledger', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('Supplier.ledger.payment', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('Supplier.ledger.delete', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('voucher.report.miscellaneous', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));

    $supplier = Supplier::create([
        'name' => 'Voucher Test Supplier',
        'email' => 'supplier@example.test',
        'phone' => '01700000003',
        'address' => 'Test address',
    ]);
    $ledger = Supplierledger::create([
        'supplier_id' => $supplier->id,
        'invoice_id' => 'SUP-TEST-001',
        'total' => '100.00',
        'payment' => '0.00',
        'due' => '100.00',
    ]);

    $this->actingAs($user)
        ->post(route('supplier.ledger.payments.store', $ledger->id), [
            'payment_date' => now()->toDateString(),
            'amount' => '25.00',
        ])
        ->assertRedirect(route('supplier.ledger', $supplier->id))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('supplier_ledger_payments', [
        'supplierledger_id' => $ledger->id,
        'amount' => '25.00',
        'created_by' => $user->id,
    ]);
    $this->assertDatabaseHas('voucher_entries', [
        'voucher_type' => 'debit',
        'expense_income_type' => 'Supplier Payment ('.$supplier->name.')',
        'voucher_date' => now()->toDateString(),
        'amount' => '25.00',
        'reference' => $ledger->invoice_id,
        'created_by' => $user->id,
        'voucherable_type' => Supplierledger::class,
        'voucherable_id' => $ledger->id,
    ]);

    $payment = SupplierLedgerPayment::query()->where('supplierledger_id', $ledger->id)->sole();
    $this->actingAs($user)
        ->put(route('supplier.ledger.payments.update', $payment->id), [
            'payment_date' => now()->toDateString(),
            'amount' => '30.00',
        ])
        ->assertForbidden();

    $user->givePermissionTo(Permission::findOrCreate('Supplier.ledger.payment.edit', 'web'));
    $this->actingAs($user)
        ->put(route('supplier.ledger.payments.update', $payment->id), [
            'payment_date' => now()->toDateString(),
            'amount' => '35.00',
        ])
        ->assertRedirect(route('supplier.ledger', $supplier->id))
        ->assertSessionHasNoErrors();

    expect((float) $ledger->fresh()->payment)->toBe(35.0)
        ->and((float) $ledger->fresh()->due)->toBe(65.0)
        ->and((float) $payment->fresh()->amount)->toBe(35.0);
    $this->assertDatabaseHas('voucher_entries', [
        'voucherable_type' => Supplierledger::class,
        'voucherable_id' => $ledger->id,
        'voucher_type' => 'debit',
        'amount' => '35.00',
    ]);

    $this->actingAs($user)
        ->get(route('supplier.ledger.invoice', $ledger->id))
        ->assertOk()
        ->assertSee('Payment History')
        ->assertSee($user->name);

    $this->actingAs($user)
        ->get(route('voucher-entries.report', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('Supplier Payment ('.$supplier->name.')')
        ->assertSee('Debit')
        ->assertSee($payment->amount);

    $voucher = VoucherEntry::query()->where('reference', $ledger->invoice_id)->sole();
    $this->actingAs($user)
        ->get(route('supplier.ledger.destroy', $ledger->id))
        ->assertRedirect(route('supplier.ledger', $supplier->id));

    $this->assertDatabaseMissing('voucher_entries', ['id' => $voucher->id]);
    $this->assertDatabaseMissing('supplierledgers', ['id' => $ledger->id]);
    $this->actingAs($user)
        ->get(route('voucher-report.combined', ['period' => 'daily', 'date' => now()->toDateString()]))
        ->assertOk()
        ->assertDontSee($voucher->voucher_code);
});

test('deleting a supplier ledger restores product stock and removes zero-stock products from the product index', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(Permission::findOrCreate('Supplier.ledger.delete', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('product.index', 'web'));
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Ledger Delete Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Ledger Delete Brand']);
    $supplier = Supplier::create([
        'name' => 'Ledger Delete Supplier',
        'email' => 'ledger-delete@example.test',
        'phone' => '01700000009',
        'address' => 'Test address',
    ]);
    $product = Product::create([
        'productName' => 'Ledger Delete Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => '20.00',
        'unit' => '3',
    ]);
    $ledger = Supplierledger::create([
        'supplier_id' => $supplier->id,
        'invoice_id' => 'SUP-DELETE-STOCK-001',
        'product_id' => (string) $product->id,
        'quantity' => '3',
        'price' => '10.00',
        'total' => '30.00',
        'payment' => '0.00',
        'due' => '30.00',
    ]);

    $this->actingAs($user)
        ->get(route('supplier.ledger.destroy', $ledger->id))
        ->assertRedirect(route('supplier.ledger', $supplier->id));

    expect((float) $product->fresh()->unit)->toBe(0.0);
    $this->assertDatabaseMissing('supplierledgers', ['id' => $ledger->id]);
    $this->actingAs($user)
        ->get(route('product.index'))
        ->assertOk()
        ->assertDontSee('Ledger Delete Product')
        ->assertViewHas('Products', fn ($products) => ! $products->contains('id', $product->id));
});

test('supplier ledger deletion is blocked when its quantity exceeds available product stock', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(Permission::findOrCreate('Supplier.ledger.delete', 'web'));
    $supplier = Supplier::create([
        'name' => 'Insufficient Stock Supplier',
        'email' => 'insufficient-stock@example.test',
        'phone' => '01700000010',
        'address' => 'Test address',
    ]);
    $product = Product::create([
        'productName' => 'Partially Sold Product',
        'unit' => '1',
    ]);
    $ledger = Supplierledger::create([
        'supplier_id' => $supplier->id,
        'product_id' => (string) $product->id,
        'quantity' => '2',
        'total' => '20.00',
        'payment' => '0.00',
        'due' => '20.00',
    ]);

    $this->actingAs($user)
        ->from(route('supplier.ledger', $supplier->id))
        ->get(route('supplier.ledger.destroy', $ledger->id))
        ->assertRedirect(route('supplier.ledger', $supplier->id))
        ->assertSessionHasErrors('supplierledger_id');

    $this->assertDatabaseHas('supplierledgers', ['id' => $ledger->id]);
    expect((float) $product->fresh()->unit)->toBe(1.0);
});
