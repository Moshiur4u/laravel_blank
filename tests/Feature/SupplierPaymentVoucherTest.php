<?php

use App\Models\Supplier;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
