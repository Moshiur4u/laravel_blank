<?php

use App\Models\EmployeePaymentVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('daily voucher report filters by creation date and creator', function () {
    $creator = User::factory()->create();
    $otherCreator = User::factory()->create();
    $employee = User::factory()->create();

    $matchingVoucher = EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-REPORT-001',
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'employee_name' => $employee->name,
        'expense_type' => 'conveyance',
        'payment_date' => '2026-10-12',
        'amount' => '125.00',
        'payment_method' => 'cash',
    ]);
    $matchingVoucher->forceFill(['created_at' => '2026-10-12 10:00:00'])->saveQuietly();

    $outsideDateVoucher = EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-REPORT-002',
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'employee_name' => $employee->name,
        'expense_type' => 'bonus',
        'payment_date' => '2026-10-12',
        'amount' => '50.00',
        'payment_method' => 'bank',
    ]);
    $outsideDateVoucher->forceFill(['created_at' => '2026-10-11 10:00:00'])->saveQuietly();

    $otherCreatorVoucher = EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-REPORT-003',
        'employee_id' => $employee->id,
        'created_by' => $otherCreator->id,
        'employee_name' => $employee->name,
        'expense_type' => 'bonus',
        'payment_date' => '2026-10-12',
        'amount' => '75.00',
        'payment_method' => 'bank',
    ]);
    $otherCreatorVoucher->forceFill(['created_at' => '2026-10-12 11:00:00'])->saveQuietly();

    $this->actingAs($creator)
        ->get(route('employee-payment.report', [
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'created_by' => $creator->id,
        ]))
        ->assertOk()
        ->assertViewHas('vouchers', function ($vouchers) use ($matchingVoucher) {
            return $vouchers->total() === 1
                && $vouchers->getCollection()->sole()->is($matchingVoucher);
        })
        ->assertViewHas('totalCount', 1)
        ->assertViewHas('totalAmount', 125.0)
        ->assertViewHas('dailySummaries', function ($summaries) use ($creator) {
            return $summaries->count() === 1
                && $summaries->first()->creator_name === $creator->name
                && (int) $summaries->first()->voucher_count === 1;
        });
});

test('daily voucher report filters by employee name', function () {
    $creator = User::factory()->create();
    $selectedEmployee = User::factory()->create(['phone' => '01700000000']);
    $otherEmployee = User::factory()->create();

    $matchingVoucher = EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-EMPLOYEE-001',
        'employee_id' => $selectedEmployee->id,
        'created_by' => $creator->id,
        'employee_name' => $selectedEmployee->name,
        'expense_type' => 'conveyance',
        'payment_date' => '2026-10-12',
        'amount' => '125.00',
        'payment_method' => 'cash',
    ]);
    $otherVoucher = EmployeePaymentVoucher::create([
        'voucher_no' => 'EPV-EMPLOYEE-002',
        'employee_id' => $otherEmployee->id,
        'created_by' => $creator->id,
        'employee_name' => $otherEmployee->name,
        'expense_type' => 'bonus',
        'payment_date' => '2026-10-12',
        'amount' => '75.00',
        'payment_method' => 'bank',
    ]);

    $this->actingAs($creator)
        ->get(route('employee-payment.report', [
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'employee_id' => $selectedEmployee->id,
        ]))
        ->assertOk()
        ->assertViewHas('vouchers', function ($vouchers) use ($matchingVoucher, $otherVoucher) {
            return $vouchers->total() === 1
                && $vouchers->getCollection()->sole()->is($matchingVoucher)
                && ! $vouchers->getCollection()->contains('id', $otherVoucher->id);
        })
        ->assertViewHas('dailySummaries', function ($summaries) use ($selectedEmployee) {
            return $summaries->count() === 1
                && $summaries->first()->employee_name === $selectedEmployee->name;
        });
});

test('daily voucher report hides admin employees from non-admin users', function () {
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $reportPermission = Permission::findOrCreate('voucher.report.employee', 'web');
    $viewer->givePermissionTo($reportPermission);

    $admin = User::factory()->create(['name' => 'Admin User', 'email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $adminEmployee = User::factory()->create(['name' => 'Admin User', 'email_verified_at' => now()]);
    $adminEmployee->assignRole(Role::findOrCreate('admin', 'web'));
    $employee = User::factory()->create(['name' => 'Regular Employee']);

    foreach ([
        ['voucher_no' => 'EPV-ADMIN-VISIBILITY-001', 'employee' => $employee, 'amount' => '25.00'],
        ['voucher_no' => 'EPV-ADMIN-VISIBILITY-002', 'employee' => $adminEmployee, 'amount' => '50.00'],
    ] as $voucherData) {
        EmployeePaymentVoucher::create([
            'voucher_no' => $voucherData['voucher_no'],
            'employee_id' => $voucherData['employee']->id,
            'created_by' => $viewer->id,
            'employee_name' => $voucherData['employee']->name,
            'expense_type' => 'conveyance',
            'payment_date' => today()->toDateString(),
            'amount' => $voucherData['amount'],
            'payment_method' => 'cash',
        ]);
    }

    $response = $this->actingAs($viewer)
        ->get(route('employee-payment.report'))
        ->assertOk()
        ->assertSee('EPV-ADMIN-VISIBILITY-001')
        ->assertDontSee('EPV-ADMIN-VISIBILITY-002')
        ->assertViewHas('employees', fn ($employees) => ! $employees->contains('id', $adminEmployee->id))
        ->assertViewHas('creators', fn ($creators) => ! $creators->contains('id', $admin->id));

    // non-admin দিয়ে Admin employee ID সরাসরি পাঠালেও তার voucher report-এ আসে না।
    $this->actingAs($viewer)
        ->get(route('employee-payment.report', ['employee_id' => $adminEmployee->id]))
        ->assertOk()
        ->assertDontSee('EPV-ADMIN-VISIBILITY-002')
        ->assertViewHas('employees', fn ($employees) => ! $employees->contains('id', $adminEmployee->id));

    $admin->givePermissionTo($reportPermission);
    $this->actingAs($admin)
        ->get(route('employee-payment.report', ['employee_id' => $adminEmployee->id]))
        ->assertOk()
        ->assertSee('EPV-ADMIN-VISIBILITY-002')
        ->assertViewHas('employees', fn ($employees) => $employees->contains('id', $adminEmployee->id))
        ->assertViewHas('creators', fn ($creators) => $creators->contains('id', $admin->id));
});

test('new employee payment vouchers record the authenticated creator', function () {
    $creator = User::factory()->create();
    $employee = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('employee-payment.store'), [
            'employee_id' => $employee->id,
            'expense_type' => 'conveyance',
            'payment_date' => today()->toDateString(),
            'amount' => '45.50',
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('employee-payment.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('employee_payment_vouchers', [
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'employee_name' => $employee->name,
        'amount' => '45.50',
    ]);
});
