<?php

use App\Models\User;
use Database\Seeders\PermissionSeederTable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ->and($manager->hasPermissionTo('brand_category.delete'))->toBeTrue()
        ->and($manager->hasPermissionTo('reports.vouchers'))->toBeTrue()
        ->and($manager->hasPermissionTo('supplier.delete'))->toBeFalse()
        ->and($manager->hasPermissionTo('supplier.ledger.edit'))->toBeFalse()
        ->and($manager->hasPermissionTo('employee.delete'))->toBeFalse()
        ->and($manager->hasPermissionTo('edit-role-permissions'))->toBeFalse()
        ->and($manager->hasPermissionTo('organization info.access'))->toBeFalse()
        ->and($projector->hasPermissionTo('reports.all_access'))->toBeTrue()
        ->and($projector->hasPermissionTo('supplier.index'))->toBeFalse()
        ->and($admin->hasPermissionTo('organization info.access'))->toBeTrue()
        ->and($admin->hasPermissionTo('supplier.ledger.delete'))->toBeTrue();
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
