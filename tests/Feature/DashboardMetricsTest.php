<?php

use App\Models\EmployeePaymentVoucher;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Supplierledger;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('dashboard navigation does not show recent invoices', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(Permission::findOrCreate('Dashboard', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('sales.view', 'web'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Recent Invoices');
});

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

test('dashboard top products excludes returned quantity and recalculates net sales and profit', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo(Permission::findOrCreate('Dashboard', 'web'));

    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Dashboard Return Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Dashboard Return Brand']);
    $product = Product::create([
        'productName' => 'Dashboard Returned Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'purchase_price' => 30,
        'price' => 100,
        'unit' => '8',
        'stock_unit' => 'Unit',
    ]);
    $invoice = SaleInvoice::create([
        'invoice_no' => 'DASHBOARD-RETURN-001',
        'user_id' => $user->id,
        'invoice_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'vat_amount' => 0,
        'ait_amount' => 0,
        'previous_due' => 0,
        'total_amount' => 200,
        'amount_paid' => 200,
        'due_amount' => 0,
    ]);
    $saleItem = SaleItem::create([
        'sale_invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'product_name' => $product->productName,
        'quantity' => 2,
        'purchase_price' => 30,
        'unit_price' => 100,
        'line_total' => 200,
    ]);
    ProductReturn::create([
        'return_no' => 'RET-DASHBOARD-RETURN-001',
        'sale_invoice_id' => $invoice->id,
        'sale_item_id' => $saleItem->id,
        'product_id' => $product->id,
        'returned_by' => $user->id,
        'invoice_no' => $invoice->invoice_no,
        'product_name' => $product->productName,
        'quantity' => 1,
        'return_date' => today(),
        'return_amount' => 100,
        'refund_amount' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboardbody'))
        ->assertOk()
        ->assertViewHas('topProducts', function ($topProducts) {
            $product = $topProducts->first();

            return $topProducts->count() === 1
                && $product->product_name === 'Dashboard Returned Product'
                && (int) $product->quantity_sold === 1
                && (float) $product->sales_amount === 100.0
                && (float) $product->profit === 70.0;
        });
});
