<?php

use App\Models\BillingEntry;
use App\Models\BillingRecord;
use App\Models\BillingSetting;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function billingOrganization(): Organization
{
    return Organization::query()->create(['name' => 'Test Organization']);
}

function billingUser(array $permissions = []): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

test('monthly billing can be generated once and payment receipts can be corrected or reverted', function () {
    $organization = billingOrganization();
    $user = billingUser(['billing.entry', 'billing.record']);

    $this->actingAs($user)
        ->post(route('billing.generate'), [
            'billing_month' => '2026-11',
            'amount' => '1250.00',
            'due_date' => '2026-11-30',
            'description' => 'Monthly subscription',
        ])
        ->assertRedirect(route('billing.entry'));

    $entry = BillingEntry::query()->sole();
    expect($entry->billing_month->format('Y-m-d'))->toBe('2026-11-01');

    $this->actingAs($user)
        ->post(route('billing.generate'), [
            'billing_month' => '2026-11',
            'amount' => '1250.00',
            'due_date' => '2026-11-30',
        ])
        ->assertSessionHasErrors('billing_month');

    $this->actingAs($user)
        ->post(route('billing.payments.store', $entry), [
            'amount' => '1250.00',
            'received_at' => '2026-11-19',
            'payment_method' => 'Bank transfer',
            'reference' => 'TX-001',
            'note' => 'Initial receipt',
        ])
        ->assertRedirect(route('billing.entry'));

    $record = BillingRecord::query()->sole();
    expect($entry->fresh()->outstandingAmount())->toBe(0.0);

    $this->actingAs($user)
        ->put(route('billing.records.update', $record), [
            'amount' => '1200.00',
            'received_at' => '2026-11-18',
            'payment_method' => 'Cash',
            'reference' => 'Corrected receipt',
            'note' => 'Corrected amount and date',
        ])
        ->assertRedirect(route('billing.records'));

    expect($entry->fresh()->outstandingAmount())->toBe(50.0);

    $this->actingAs($user)
        ->post(route('billing.records.revert', $record))
        ->assertRedirect(route('billing.records'));

    expect($entry->fresh()->outstandingAmount())->toBe(1250.0)
        ->and($record->fresh()->reversed_at)->not->toBeNull();
});

test('billing entry and billing record pages require their separate permissions', function () {
    billingOrganization();
    $entryViewer = billingUser(['billing.entry']);
    $recordViewer = billingUser(['billing.record']);

    $this->actingAs($entryViewer)
        ->get(route('billing.entry'))
        ->assertOk();
    $this->actingAs($entryViewer)
        ->get(route('billing.records'))
        ->assertForbidden();
    $this->actingAs($entryViewer)
        ->get(route('billing.transactions'))
        ->assertForbidden();

    $this->actingAs($recordViewer)
        ->get(route('billing.records'))
        ->assertOk();
    $this->actingAs($recordViewer)
        ->get(route('billing.transactions'))
        ->assertOk();
    $this->actingAs($recordViewer)
        ->get(route('billing.entry'))
        ->assertForbidden();
});

test('billing transaction report filters by billing month and includes due balances', function () {
    $organization = billingOrganization();
    $user = billingUser(['billing.record']);
    $inRange = BillingEntry::query()->create([
        'organization_id' => $organization->id,
        'billing_month' => '2026-04-01',
        'amount' => '1000.00',
        'due_date' => '2026-04-15',
        'description' => 'April organization charge',
    ]);
    $inRange->records()->create([
        'amount' => '250.00',
        'received_at' => '2026-06-05',
        'payment_method' => 'Cash',
        'reference' => 'APR-TX-01',
    ]);
    BillingEntry::query()->create([
        'organization_id' => $organization->id,
        'billing_month' => '2026-03-01',
        'amount' => '500.00',
        'due_date' => '2026-03-15',
        'description' => 'March organization charge',
    ]);

    $this->actingAs($user)
        ->get(route('billing.transactions', [
            'from_month' => '2026-04',
            'to_month' => '2026-04',
        ]))
        ->assertOk()
        ->assertSee('April 2026')
        ->assertSee('April organization charge')
        ->assertSee('750.00')
        ->assertSee('05 Jun 2026')
        ->assertSee('APR-TX-01')
        ->assertDontSee('March organization charge');

    $this->actingAs($user)
        ->get(route('billing.transactions', [
            'from_month' => '2026-05',
            'to_month' => '2026-04',
        ]))
        ->assertSessionHasErrors('to_month');
});

test('billing pages direct users to organization setup when no profile exists', function () {
    $user = billingUser(['billing.entry', 'organization info.access']);

    $this->actingAs($user)
        ->get(route('billing.entry'))
        ->assertRedirect(route('organization.edit'))
        ->assertSessionHas('warning');
});

test('access off disables non-admin users only when a bill is outstanding', function () {
    $organization = billingOrganization();
    $admin = billingUser(['billing.entry']);
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $employee = User::factory()->create();
    $entry = BillingEntry::query()->create([
        'organization_id' => $organization->id,
        'billing_month' => now()->startOfMonth(),
        'amount' => '500.00',
        'due_date' => now()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->post(route('billing.access-off'))
        ->assertRedirect(route('billing.entry'));

    expect($employee->fresh()->status)->toBeFalse()
        ->and($admin->fresh()->status)->toBeTrue();

    $entry->records()->create([
        'amount' => '500.00',
        'received_at' => now()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->post(route('billing.access-off'))
        ->assertSessionHasErrors('access_off');
});

test('dashboard marquee shows three-month overdue bills and the admin toggle hides every message', function () {
    $organization = billingOrganization();
    $user = billingUser(['Dashboard']);
    $oldDate = now()->subMonths(4);
    BillingEntry::query()->create([
        'organization_id' => $organization->id,
        'billing_month' => $oldDate->copy()->startOfMonth(),
        'amount' => '800.00',
        'due_date' => $oldDate->toDateString(),
    ]);
    BillingSetting::query()->create([
        'alert_message' => 'Custom billing notice',
        'show_marquee_alert' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Custom billing notice')
        ->assertSee('bill overdue');

    BillingSetting::query()->first()->update(['show_marquee_alert' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Custom billing notice')
        ->assertDontSee('bill overdue');
});
