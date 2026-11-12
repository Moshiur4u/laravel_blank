<?php

use App\Models\EmployeePaymentVoucher;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerLedgerPayment;
use App\Models\Supplier;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use App\Models\SaleInvoice;
use App\Models\User;
use App\Models\Product;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('combined voucher report shows employee debit and credit entries for a day', function () {
    $creator = User::factory()->create();
    $employee = User::factory()->create();

    EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-COMBINED-001',
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'employee_name' => $employee->name,
        'expense_type' => 'salary',
        'payment_date' => '2026-10-13',
        'amount' => '100.00',
        'payment_method' => 'cash',
    ])->forceFill(['created_at' => '2026-10-13 09:00:00'])->saveQuietly();

    VoucherEntry::create([
        'voucher_code' => 'DBV-COMBINED-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => '2026-10-13',
        'amount' => '30.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'CRV-COMBINED-001',
        'voucher_type' => 'credit',
        'expense_income_type' => 'Other income',
        'voucher_date' => '2026-10-13',
        'amount' => '10.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);

    $this->actingAs($creator)
        ->get(route('voucher-report.combined', ['period' => 'daily', 'date' => '2026-10-13']))
        ->assertOk()
        ->assertSee('EPV-COMBINED-001')
        ->assertSee('DBV-COMBINED-001')
        ->assertSee('CRV-COMBINED-001')
        ->assertSee($employee->name)
        ->assertViewHas('employeeVoucherCount', 1)
        ->assertViewHas('debitVoucherCount', 1)
        ->assertViewHas('creditVoucherCount', 1)
        ->assertViewHas('employeeTotal', 100.0)
        ->assertViewHas('debitTotal', 30.0)
        ->assertViewHas('creditTotal', 10.0)
        ->assertViewHas('cashInHandTotal', -120.0)
        ->assertSee('৳-120.00');
});

test('combined voucher report can show a selected month', function () {
    $creator = User::factory()->create();

    VoucherEntry::create([
        'voucher_code' => 'DBV-COMBINED-MONTH-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => '2026-10-03',
        'amount' => '12.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);

    $this->actingAs($creator)
        ->get(route('voucher-report.combined', ['period' => 'monthly', 'month' => '2026-10']))
        ->assertOk()
        ->assertSee('DBV-COMBINED-MONTH-001')
        ->assertViewHas('startDate', '2026-10-01')
        ->assertViewHas('endDate', '2026-10-31')
        ->assertViewHas('debitTotal', 12.0);
});

test('combined voucher report filters vouchers by voucher number and creator', function () {
    $creator = User::factory()->create(['name' => 'Voucher Creator']);
    $otherCreator = User::factory()->create(['name' => 'Other Creator']);

    VoucherEntry::create([
        'voucher_code' => 'DBV-FILTER-MATCH-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => '2026-10-13',
        'amount' => '30.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'DBV-FILTER-MATCH-002',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => '2026-10-13',
        'amount' => '20.00',
        'created_by' => $otherCreator->id,
        'created_by_name' => $otherCreator->name,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'CRV-FILTER-OTHER-001',
        'voucher_type' => 'credit',
        'expense_income_type' => 'Other income',
        'voucher_date' => '2026-10-13',
        'amount' => '10.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);

    $this->actingAs($creator)
        ->get(route('voucher-report.combined', [
            'period' => 'daily',
            'date' => '2026-10-13',
            'voucher' => 'FILTER-MATCH',
            'created_by' => $creator->id,
        ]))
        ->assertOk()
        ->assertSee('DBV-FILTER-MATCH-001')
        ->assertDontSee('DBV-FILTER-MATCH-002')
        ->assertDontSee('CRV-FILTER-OTHER-001')
        ->assertViewHas('debitVoucherCount', 1)
        ->assertViewHas('debitTotal', 30.0)
        ->assertViewHas('creditVoucherCount', 0)
        ->assertViewHas('voucherSearch', 'FILTER-MATCH')
        ->assertViewHas('selectedCreator', $creator->id);
});

test('combined voucher creator options hide admins from non-admin users', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $reportPermission = Permission::findOrCreate('voucher.report.view', 'web');
    $admin->givePermissionTo($reportPermission);
    $viewer->givePermissionTo($reportPermission);

    $viewerResponse = $this->actingAs($viewer)
        ->get(route('voucher-report.combined'))
        ->assertOk();

    expect($viewerResponse->viewData('creators')->contains('id', $admin->id))->toBeFalse()
        ->and($viewerResponse->viewData('creators')->contains('id', $viewer->id))->toBeTrue();

    $adminResponse = $this->actingAs($admin)
        ->get(route('voucher-report.combined'))
        ->assertOk();

    expect($adminResponse->viewData('creators')->contains('id', $admin->id))->toBeTrue()
        ->and($adminResponse->viewData('creators')->contains('id', $viewer->id))->toBeTrue();
});

test('voucher viewers without delete permission see the confirmation and cannot delete vouchers', function () {
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $viewer->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));
    $voucher = VoucherEntry::create([
        'voucher_code' => 'DBV-COMBINED-NO-DELETE-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => today()->toDateString(),
        'amount' => '12.00',
        'created_by' => $viewer->id,
        'created_by_name' => $viewer->name,
    ]);

    $this->actingAs($viewer)
        ->get(route('voucher-report.combined', ['period' => 'daily', 'date' => today()->toDateString()]))
        ->assertOk()
        ->assertSee('data-voucher-delete-denied', false)
        ->assertSee('data-delete-message="Delete this voucher? If it is linked to a sale invoice', false)
        ->assertSee('window.location.assign(window.location.href)', false)
        ->assertSee('DBV-COMBINED-NO-DELETE-001');

    $this->delete(route('voucher-report.combined.destroy', [
        'category' => 'debit',
        'voucherId' => $voucher->id,
    ]))->assertForbidden();

    $this->assertDatabaseHas('voucher_entries', ['id' => $voucher->id]);
});

test('deleting an old invoice voucher without void permission returns to the report instead of 403', function () {
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $viewer->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));
    $viewer->givePermissionTo(Permission::findOrCreate('voucher.delete', 'web'));
    $invoice = SaleInvoice::create([
        'invoice_no' => 'COMBINED-OLD-VOID-DENIED-001',
        'user_id' => $viewer->id,
        'invoice_date' => today()->subDay(),
        'subtotal' => '100.00',
        'discount_amount' => '0.00',
        'vat_amount' => '0.00',
        'previous_due' => '0.00',
        'total_amount' => '100.00',
        'amount_paid' => '0.00',
        'due_amount' => '100.00',
    ]);
    $voucher = VoucherEntry::create([
        'voucher_code' => 'CRV-OLD-VOID-DENIED-001',
        'voucher_type' => 'credit',
        'expense_income_type' => 'Old invoice payment',
        'voucher_date' => today()->subDay(),
        'amount' => '100.00',
        'created_by' => $viewer->id,
        'created_by_name' => $viewer->name,
        'voucherable_type' => SaleInvoice::class,
        'voucherable_id' => $invoice->id,
    ]);
    $reportUrl = route('voucher-report.combined', [
        'period' => 'daily',
        'date' => today()->toDateString(),
    ]);

    $this->actingAs($viewer)
        ->delete(route('voucher-report.combined.destroy', [
            'category' => 'credit',
            'voucherId' => $voucher->id,
        ]), [
            'period' => 'daily',
            'date' => today()->toDateString(),
        ])
        ->assertRedirect($reportUrl)
        ->assertSessionHas(
            'warning',
            'You do not have permission to void this sale invoice, so the voucher was not deleted.'
        );

    $this->assertDatabaseHas('sale_invoices', ['id' => $invoice->id]);
    $this->assertDatabaseHas('voucher_entries', ['id' => $voucher->id]);
});

test('combined voucher report edits and deletes employee and standalone voucher entries', function () {
    $creator = User::factory()->create(['email_verified_at' => now()]);
    $creator->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.edit', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.delete', 'web'));
    $employee = User::factory()->create();
    $date = today()->toDateString();

    $employeeVoucher = EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-COMBINED-UPDATE-001',
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'employee_name' => $employee->name,
        'expense_type' => 'other',
        'payment_date' => $date,
        'amount' => '20.00',
        'payment_method' => 'cash',
        'notes' => 'Initial note',
    ]);
    $debitVoucher = VoucherEntry::create([
        'voucher_code' => 'DBV-COMBINED-UPDATE-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => $date,
        'amount' => '30.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);

    $this->actingAs($creator)
        ->put(route('voucher-report.combined.update', ['category' => 'employee', 'voucherId' => $employeeVoucher->id]), [
            'period' => 'daily',
            'date' => $date,
            'expense_type' => 'bonus',
            'payment_date' => $date,
            'amount' => '25.00',
            'reference' => 'EMP-REF',
            'notes' => 'Updated note',
        ])
        ->assertRedirect(route('voucher-report.combined', ['period' => 'daily', 'date' => $date]));

    $this->assertDatabaseHas('employee_payment_vouchers', [
        'id' => $employeeVoucher->id,
        'expense_type' => 'bonus',
        'amount' => '25.00',
        'reference' => 'EMP-REF',
        'notes' => 'Updated note',
    ]);

    $this->actingAs($creator)
        ->put(route('voucher-report.combined.update', ['category' => 'debit', 'voucherId' => $debitVoucher->id]), [
            'period' => 'daily',
            'date' => $date,
            'voucher_date' => $date,
            'expense_income_type' => 'Updated office expense',
            'amount' => '35.00',
            'reference' => 'DB-REF',
            'description' => 'Updated description',
        ])
        ->assertRedirect(route('voucher-report.combined', ['period' => 'daily', 'date' => $date]));

    $this->assertDatabaseHas('voucher_entries', [
        'id' => $debitVoucher->id,
        'expense_income_type' => 'Updated office expense',
        'amount' => '35.00',
        'reference' => 'DB-REF',
        'description' => 'Updated description',
    ]);

    $this->actingAs($creator)
        ->delete(route('voucher-report.combined.destroy', ['category' => 'employee', 'voucherId' => $employeeVoucher->id]), [
            'period' => 'daily',
            'date' => $date,
        ])
        ->assertRedirect(route('voucher-report.combined', ['period' => 'daily', 'date' => $date]));
    $this->assertDatabaseMissing('employee_payment_vouchers', ['id' => $employeeVoucher->id]);
});

test('combined report updates and deletes supplier-linked vouchers and ledger balances', function () {
    $creator = User::factory()->create(['email_verified_at' => now()]);
    $creator->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.edit', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.delete', 'web'));
    $supplier = Supplier::create([
        'name' => 'Combined Report Supplier',
        'email' => 'combined-supplier@example.test',
        'phone' => '01700000004',
        'address' => 'Test address',
    ]);
    $ledger = Supplierledger::create([
        'supplier_id' => $supplier->id,
        'invoice_id' => 'SUP-COMBINED-001',
        'total' => '100.00',
        'payment' => '25.00',
        'due' => '75.00',
    ]);
    $date = today()->toDateString();
    $payment = SupplierLedgerPayment::create([
        'supplierledger_id' => $ledger->id,
        'payment_date' => $date,
        'amount' => '25.00',
    ]);
    $voucher = VoucherEntry::create([
        'voucher_code' => 'DBV-COMBINED-SUPPLIER-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Supplier Payment (' . $supplier->name . ')',
        'voucher_date' => $date,
        'amount' => '25.00',
        'reference' => $ledger->invoice_id,
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
        'voucherable_type' => Supplierledger::class,
        'voucherable_id' => $ledger->id,
    ]);

    $this->actingAs($creator)
        ->put(route('voucher-report.combined.update', ['category' => 'debit', 'voucherId' => $voucher->id]), [
            'voucher_date' => $date,
            'amount' => '30.00',
        ])
        ->assertRedirect(route('voucher-report.combined'));

    expect((float) $ledger->fresh()->payment)->toBe(30.0)
        ->and((float) $ledger->fresh()->due)->toBe(70.0)
        ->and((float) $payment->fresh()->amount)->toBe(30.0)
        ->and((float) $voucher->fresh()->amount)->toBe(30.0);

    $this->actingAs($creator)
        ->delete(route('voucher-report.combined.destroy', ['category' => 'debit', 'voucherId' => $voucher->id]))
        ->assertRedirect(route('voucher-report.combined'));

    expect((float) $ledger->fresh()->payment)->toBe(0.0)
        ->and((float) $ledger->fresh()->due)->toBe(100.0)
        ->and(SupplierLedgerPayment::query()->where('supplierledger_id', $ledger->id)->exists())->toBeFalse()
        ->and(VoucherEntry::query()->whereKey($voucher->id)->exists())->toBeFalse();
});

test('combined report updates and deletes customer-linked vouchers and ledger balances', function () {
    $creator = User::factory()->create(['email_verified_at' => now()]);
    $creator->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.edit', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.delete', 'web'));
    $customer = Customer::create([
        'name' => 'Combined Report Customer',
        'customer_type' => 'temporary',
        'opening_due' => '0.00',
        'status' => 1,
    ]);
    $ledger = CustomerLedger::create([
        'customer_id' => $customer->id,
        'entry_type' => 'sale',
        'sale_date' => today()->toDateString(),
        'product_name' => 'Test product',
        'quantity' => 1,
        'unit_price' => '100.00',
        'total_amount' => '100.00',
        'payment_type' => 'due',
        'payment_amount' => '25.00',
        'due_amount' => '75.00',
    ]);
    $date = today()->toDateString();
    $payment = CustomerLedgerPayment::create([
        'customer_ledger_id' => $ledger->id,
        'payment_date' => $date,
        'amount' => '25.00',
        'received_by' => $creator->id,
    ]);
    $voucher = VoucherEntry::create([
        'voucher_code' => 'CRV-COMBINED-CUSTOMER-001',
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => $date,
        'amount' => '25.00',
        'reference' => $ledger->invoice_no,
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $ledger->id,
    ]);

    $this->actingAs($creator)
        ->put(route('voucher-report.combined.update', ['category' => 'credit', 'voucherId' => $voucher->id]), [
            'voucher_date' => $date,
            'amount' => '30.00',
        ])
        ->assertRedirect(route('voucher-report.combined'));

    expect((float) $ledger->fresh()->payment_amount)->toBe(30.0)
        ->and((float) $ledger->fresh()->due_amount)->toBe(70.0)
        ->and((float) $payment->fresh()->amount)->toBe(30.0)
        ->and((float) $voucher->fresh()->amount)->toBe(30.0);

    $this->actingAs($creator)
        ->delete(route('voucher-report.combined.destroy', ['category' => 'credit', 'voucherId' => $voucher->id]))
        ->assertRedirect(route('voucher-report.combined'));

    expect((float) $ledger->fresh()->payment_amount)->toBe(0.0)
        ->and((float) $ledger->fresh()->due_amount)->toBe(100.0)
        ->and(CustomerLedgerPayment::query()->where('customer_ledger_id', $ledger->id)->exists())->toBeFalse()
        ->and(VoucherEntry::query()->whereKey($voucher->id)->exists())->toBeFalse();
});

test('deleting a sale invoice voucher from the combined report voids the invoice and restores stock', function () {
    $creator = User::factory()->create(['email_verified_at' => now()]);
    $creator->givePermissionTo(Permission::findOrCreate('voucher.report.view', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('voucher.delete', 'web'));
    $creator->givePermissionTo(Permission::findOrCreate('Voide_anyday_Invoice', 'web'));
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Combined Void Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Combined Void Brand']);
    $product = Product::create([
        'productName' => 'Combined Void Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($creator)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000005',
            'save_customer' => 'no',
            'customer_name' => 'Combined Void Customer',
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
    $voucher = VoucherEntry::query()
        ->where('voucherable_type', SaleInvoice::class)
        ->where('voucherable_id', $invoice->id)
        ->firstOrFail();

    $this->delete(route('voucher-report.combined.destroy', ['category' => 'credit', 'voucherId' => $voucher->id]))
        ->assertRedirect(route('voucher-report.combined'))
        ->assertSessionHas('success', "Invoice {$invoice->invoice_no} was voided; its stock and payment entries were reversed.");

    $this->assertDatabaseMissing('sale_invoices', ['id' => $invoice->id]);
    $this->assertDatabaseMissing('sale_items', ['sale_invoice_id' => $invoice->id]);
    $this->assertDatabaseMissing('voucher_entries', ['id' => $voucher->id]);
    expect((float) $product->fresh()->unit)->toBe(10.0);
});
