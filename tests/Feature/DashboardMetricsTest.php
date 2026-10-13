<?php

use App\Models\EmployeePaymentVoucher;
use App\Models\SaleInvoice;
use App\Models\Supplier;
use App\Models\Supplierledger;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard calculates profit from sales purchases and voucher totals', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $today = today()->toDateString();

    SaleInvoice::create([
        'invoice_no' => 'DASHBOARD-METRIC-001',
        'user_id' => $user->id,
        'invoice_date' => now(),
        'subtotal' => '100.00',
        'discount_amount' => '10.00',
        'vat_amount' => '5.00',
        'previous_due' => '20.00',
        'total_amount' => '115.00',
        'amount_paid' => '90.00',
        'due_amount' => '25.00',
    ]);

    $supplier = Supplier::create([
        'name' => 'Dashboard Metrics Supplier',
        'email' => 'dashboard-metrics@example.test',
        'phone' => '01700000000',
        'address' => 'Test address',
    ]);
    Supplierledger::create([
        'supplier_id' => $supplier->id,
        'quantity' => '1',
        'price' => '20.00',
        'total' => '20.00',
        'payment' => '0.00',
        'due' => '20.00',
    ]);

    EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-DASHBOARD-METRIC-001',
        'employee_id' => $user->id,
        'employee_name' => $user->name,
        'expense_type' => 'salary',
        'payment_date' => $today,
        'amount' => '10.00',
        'payment_method' => 'cash',
    ]);

    VoucherEntry::create([
        'voucher_code' => 'DBV-DASHBOARD-METRIC-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office expense',
        'voucher_date' => $today,
        'amount' => '7.00',
        'created_by_name' => $user->name,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'CRV-DASHBOARD-METRIC-001',
        'voucher_type' => 'credit',
        'expense_income_type' => 'Other income',
        'voucher_date' => $today,
        'amount' => '2.00',
        'created_by_name' => $user->name,
    ]);

    $this->actingAs($user)
        ->get(route('dashboardbody'))
        ->assertOk()
        ->assertViewHas('metrics', function (array $metrics) {
            foreach (['daily', 'weekly', 'monthly'] as $period) {
                if (
                    $metrics[$period]['revenue'] !== 95.0
                    || $metrics[$period]['due'] !== 25.0
                    || $metrics[$period]['purchase'] !== 20.0
                    || $metrics[$period]['profit'] !== 60.0
                ) {
                    return false;
                }
            }

            return true;
        });
});
