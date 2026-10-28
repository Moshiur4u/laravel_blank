<?php

use App\Models\EmployeePaymentVoucher;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\SaleInvoice;
use App\Models\Supplier;
use App\Models\Supplierledger;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('dashboard calculates profit from sales purchases and voucher totals', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo(Permission::findOrCreate('Dashboard', 'web'));
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
    $supplierLedger = Supplierledger::create([
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
        'voucher_code' => 'DBV-EMPLOYEE-MOBILE-BILL-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Employee',
        'voucher_date' => $today,
        'amount' => '12.00',
        'description' => 'Mobile Bill',
        'created_by_name' => $user->name,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'DBV-EMPLOYEE-PRINTER-SERVICING-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Employee',
        'voucher_date' => $today,
        'amount' => '3.00',
        'description' => 'Printer Servicing',
        'created_by_name' => $user->name,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'DBV-SUPPLIER-DASHBOARD-METRIC-001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Supplier Payment (Dashboard Metrics Supplier)',
        'voucher_date' => $today,
        'amount' => '5.00',
        'created_by_name' => $user->name,
        'voucherable_type' => Supplierledger::class,
        'voucherable_id' => $supplierLedger->id,
    ]);
    VoucherEntry::create([
        'voucher_code' => 'CRV-DASHBOARD-METRIC-001',
        'voucher_type' => 'credit',
        'expense_income_type' => 'Other income',
        'voucher_date' => $today,
        'amount' => '2.00',
        'created_by_name' => $user->name,
    ]);
    $customer = Customer::create([
        'name' => 'Dashboard Metrics Customer',
        'customer_type' => 'temporary',
        'opening_due' => '0.00',
        'status' => 1,
    ]);
    $customerInvoice = SaleInvoice::create([
        'invoice_no' => 'DASHBOARD-CUSTOMER-METRIC-001',
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'invoice_date' => now(),
        'subtotal' => '50.00',
        'discount_amount' => '0.00',
        'vat_amount' => '0.00',
        'previous_due' => '0.00',
        'total_amount' => '50.00',
        'amount_paid' => '10.00',
        'due_amount' => '40.00',
    ]);
    $customerLedger = CustomerLedger::create([
        'customer_id' => $customer->id,
        'sale_invoice_id' => $customerInvoice->id,
        'entry_type' => 'sale',
        'sale_date' => $today,
        'product_name' => 'Test product',
        'quantity' => 1,
        'unit_price' => '50.00',
        'total_amount' => '50.00',
        'payment_type' => 'due',
        'payment_amount' => '10.00',
        'due_amount' => '40.00',
    ]);
    VoucherEntry::create([
        'voucher_code' => 'CRV-CUSTOMER-DASHBOARD-METRIC-001',
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => $today,
        'amount' => '10.00',
        'created_by_name' => $user->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $customerLedger->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboardbody'))
        ->assertOk()
        ->assertViewHas('metrics', function (array $metrics) {
            foreach (['daily', 'weekly', 'monthly'] as $period) {
                if (
                    $metrics[$period]['revenue'] !== 147.0
                    || $metrics[$period]['due'] !== 65.0
                    || $metrics[$period]['purchase'] !== 20.0
                    || $metrics[$period]['expenses'] !== 37.0
                    || $metrics[$period]['profit'] !== 95.0
                ) {
                    return false;
                }
            }

            return true;
        })
        ->assertViewHas('profitChart', function (array $chart) {
            return $chart['daily']['labels'] === [today()->format('d M')]
                && $chart['daily']['revenue'] === [147.0]
                && $chart['daily']['profit'] === [95.0]
                && count($chart['weekly']['labels']) === 7
                && count($chart['monthly']['labels']) === today()->daysInMonth
                && count($chart['yearly']['labels']) === 12;
        });
});
