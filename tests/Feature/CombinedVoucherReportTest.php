<?php

use App\Models\EmployeePaymentVoucher;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->assertViewHas('debitVoucherCount', 2)
        ->assertViewHas('creditVoucherCount', 1)
        ->assertViewHas('employeeTotal', 100.0)
        ->assertViewHas('debitTotal', 30.0)
        ->assertViewHas('creditTotal', 10.0);
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
