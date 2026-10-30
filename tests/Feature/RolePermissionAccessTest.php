<?php

use App\Models\User;
use Database\Seeders\PermissionSeederTable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        PermissionSeederTable::class,
        RolePermissionSeeder::class,
    ]);
});

test('standard roles receive only their intended module permissions', function () {
    $sales = Role::findByName('sales');
    $manager = Role::findByName('manager');
    $projector = Role::findByName('projector');
    $admin = Role::findByName('admin');

    expect($sales->hasPermissionTo('Dashboard'))->toBeTrue()
        ->and($sales->hasPermissionTo('sales.create'))->toBeTrue()
        ->and($sales->hasPermissionTo('customer.ledger'))->toBeTrue()
        ->and($sales->hasPermissionTo('customer.delete'))->toBeFalse()
        ->and($manager->hasPermissionTo('brand.delete'))->toBeTrue() // পুরোনো category/brand permission-এর নতুন brand নাম।
        ->and($manager->hasPermissionTo('voucher.report.view'))->toBeTrue() // নতুন voucher report permission।
        ->and($manager->hasPermissionTo('view-employee-details-permission'))->toBeTrue()
        ->and(Permission::where('name', 'view-employee-details-permission')->exists())->toBeTrue()
        ->and($manager->hasPermissionTo('supplier.delete'))->toBeFalse()
        ->and($manager->hasPermissionTo('Supplier.ledger.edit'))->toBeFalse() // manager-এর ledger edit অধিকার আগের মতো বন্ধ।
        ->and($manager->hasPermissionTo('employee.delete'))->toBeFalse()
        ->and($manager->hasPermissionTo('role.edit'))->toBeFalse() // নতুন role edit permission manager-কে দেওয়া হয়নি।
        ->and($manager->hasPermissionTo('organization info.access'))->toBeFalse()
        ->and($projector->hasPermissionTo('reports.all_access'))->toBeTrue()
        ->and($projector->hasPermissionTo('supplier.index'))->toBeFalse()
        ->and($admin->hasPermissionTo('organization info.access'))->toBeTrue()
        ->and($admin->hasPermissionTo('Supplier.ledger.delete'))->toBeTrue(); // নতুন casing-এ admin-এর ledger delete অধিকার।
});

test('legacy permissions migrate to the new names without losing role assignments', function () {
    $manager = Role::findByName('manager');
    $legacyPermission = Permission::create(['name' => 'brand_category.create', 'guard_name' => 'web']);
    $manager->givePermissionTo($legacyPermission);

    $migration = require database_path('migrations/2026_10_17_000000_rename_legacy_permissions.php'); // একই data migration পরীক্ষা করা হচ্ছে।
    $migration->up();

    $manager->refresh();
    expect($manager->hasPermissionTo('brand.create'))->toBeTrue() // পুরোনো permission-এর Brand grant বজায় থাকে।
        ->and($manager->hasPermissionTo('product.category.create'))->toBeTrue() // পুরোনো permission-এর Category grant-ও বজায় থাকে।
        ->and(Permission::where('name', 'brand_category.create')->exists())->toBeFalse(); // legacy permission row সরানো হয়।
});

test('an administrator can change an employee role from the user edit form', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->assignRole('manager');

    $this->actingAs($admin)
        ->get(route('user.edit', $employee->id))
        ->assertOk()
        ->assertSee('name="roles"', false)
        ->assertSee('value="manager" selected', false);

    $this->actingAs($admin)
        ->put(route('user.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'roles' => 'sales',
        ])
        ->assertRedirect(route('user.index'));

    expect($employee->fresh()->hasRole('sales'))->toBeTrue()
        ->and($employee->fresh()->hasRole('manager'))->toBeFalse();
});

test('an employee editor without role assignment permission cannot silently submit a role change', function () {
    $editorRole = Role::create(['name' => 'employee-editor', 'guard_name' => 'web']);
    $editorRole->givePermissionTo('employee.edit');
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->assignRole($editorRole);
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->assignRole('sales');

    $this->actingAs($editor)
        ->put(route('user.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'roles' => 'manager',
        ])
        ->assertForbidden();

    expect($employee->fresh()->hasRole('sales'))->toBeTrue()
        ->and($employee->fresh()->hasRole('manager'))->toBeFalse();
});

test('the dedicated activity log permission grants access without full report access', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo('user-activity-log.view');

    $this->actingAs($user)
        ->get(route('user-activity-logs.index'))
        ->assertOk();
});

test('sales cannot access modules or mutate a customer ledger without permission', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('sales');

    $this->actingAs($user)
        ->get(route('organization.edit'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('supplier.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('customer.ledger.store'))
        ->assertForbidden();
});

test('manager cannot edit or delete roles or delete suppliers', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('manager');

    $this->actingAs($user)
        ->get(route('roles.edit', 1))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('roles.destroy', 1))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('supplier.destroy', 1))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('supplier.ledger.edit', 1))
        ->assertForbidden();
});
