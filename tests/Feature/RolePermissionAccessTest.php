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
        ->and(Permission::where('name', 'Voide_today_Invoice')->exists())->toBeTrue()
        ->and(Permission::where('name', 'Voide_anyday_Invoice')->exists())->toBeTrue()
        ->and($sales->hasPermissionTo('Voide_today_Invoice'))->toBeFalse()
        ->and($sales->hasPermissionTo('Voide_anyday_Invoice'))->toBeFalse()
        ->and($sales->hasPermissionTo('money-receipt'))->toBeTrue()
        ->and($sales->hasPermissionTo('supplier.inactive'))->toBeFalse()
        ->and($sales->hasPermissionTo('customer.ledger'))->toBeTrue()
        ->and($sales->hasPermissionTo('customer.delete'))->toBeFalse()
        ->and($manager->hasPermissionTo('brand.delete'))->toBeTrue() // পুরোনো category/brand permission-এর নতুন brand নাম।
        ->and($manager->hasPermissionTo('voucher.report.view'))->toBeTrue() // নতুন voucher report permission।
        ->and($manager->hasPermissionTo('money-receipt'))->toBeTrue()
        ->and($manager->hasPermissionTo('supplier.inactive'))->toBeTrue()
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

test('non-admin user lists hide admin accounts while admin lists retain them', function () {
    $admin = User::factory()->create(); // তালিকায় থাকা admin user তৈরি করে।
    $admin->assignRole('admin'); // user-টিকে admin role দেয়।
    $viewer = User::factory()->create(); // সাধারণ non-admin viewer তৈরি করে।

    $this->actingAs($viewer); // পরের query-গুলোর বর্তমান user হিসেবে viewer নির্ধারণ করে।
    $visibleUsers = User::query()->visibleToCurrentUser()->pluck('id')->all(); // viewer-এর জন্য অনুমোদিত user id আনে।
    expect($visibleUsers)->not->toContain($admin->id); // non-admin তালিকায় admin user নেই নিশ্চিত করে।

    $this->actingAs($admin); // বর্তমান user-কে admin হিসেবে পরিবর্তন করে।
    $visibleUsers = User::query()->visibleToCurrentUser()->pluck('id')->all(); // admin-এর জন্য user id-গুলো আনে।
    expect($visibleUsers)->toContain($admin->id)->toContain($viewer->id); // admin উভয় account দেখতে পারে নিশ্চিত করে।
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

    $editResponse = $this->actingAs($admin)
        ->get(route('user.edit', $employee->id))
        ->assertOk()
        ->assertSee('name="roles"', false)
        ->assertSee('value="admin"', false)
        ->assertSee('value="manager" selected', false);
    expect(preg_match(
        '/<option\b(?=[^>]*\bvalue="manager")(?=[^>]*\bdisabled)[^>]*>/s',
        $editResponse->getContent()
    ))->toBe(0);

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

test('an administrator can assign the admin role and all other roles', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->assignRole('sales');

    $this->actingAs($admin)
        ->get(route('user.create'))
        ->assertOk()
        ->assertSee('value="admin"', false)
        ->assertSee('value="manager"', false);

    $this->actingAs($admin)
        ->put(route('user.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'roles' => 'admin',
        ])
        ->assertRedirect(route('user.index'));

    expect($employee->fresh()->hasRole('admin'))->toBeTrue();
});

test('all employee editors see every non-admin role in add and edit forms', function () {
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->assignRole('sales');

    $extraPermission = Permission::create(['name' => 'custom.role.permission', 'guard_name' => 'web']);
    $customRole = Role::create(['name' => 'custom-role', 'guard_name' => 'web']);
    $customRole->givePermissionTo($extraPermission);

    $editorRole = Role::create(['name' => 'employee-editor', 'guard_name' => 'web']);
    $editorRole->givePermissionTo(['employee.create', 'employee.edit']);
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->assignRole($editorRole);

    $createResponse = $this->actingAs($editor)
        ->get(route('user.create'))
        ->assertOk()
        ->assertSee('value="sales"', false)
        ->assertSee('value="custom-role"', false)
        ->assertSee('value="manager"', false)
        ->assertDontSee('value="admin"', false);
    $this->assertMatchesRegularExpression(
        '/<option\b(?=[^>]*\bvalue="manager")(?=[^>]*\bdisabled)[^>]*>/s',
        $createResponse->getContent()
    );

    $editResponse = $this->actingAs($editor)
        ->get(route('user.edit', $employee->id))
        ->assertOk()
        ->assertSee('value="sales"', false)
        ->assertSee('value="custom-role"', false)
        ->assertSee('value="manager"', false)
        ->assertDontSee('value="admin"', false);
    $this->assertMatchesRegularExpression(
        '/<option\b(?=[^>]*\bvalue="manager")(?=[^>]*\bdisabled)[^>]*>/s',
        $editResponse->getContent()
    );
});

test('an employee editor cannot assign roles that grant employee role assignment permission', function () {
    $editorRole = Role::create(['name' => 'employee-editor', 'guard_name' => 'web']);
    $editorRole->givePermissionTo('employee.edit');
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->assignRole($editorRole);
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->assignRole('sales');
    $ordinaryRole = Role::create(['name' => 'ordinary-role', 'guard_name' => 'web']);

    $editResponse = $this->actingAs($editor)
        ->get(route('user.edit', $employee->id))
        ->assertOk()
        ->assertSee('name="roles"', false)
        ->assertSee('value="manager"', false)
        ->assertDontSee('value="admin"', false);
    $this->assertMatchesRegularExpression(
        '/<option\b(?=[^>]*\bvalue="manager")(?=[^>]*\bdisabled)[^>]*>/s',
        $editResponse->getContent()
    );

    $this->actingAs($editor)
        ->put(route('user.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'roles' => 'admin',
        ])
        ->assertSessionHasErrors('roles');

    expect($employee->fresh()->hasRole('sales'))->toBeTrue();

    $this->actingAs($editor)
        ->put(route('user.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'roles' => $ordinaryRole->name,
        ])
        ->assertRedirect(route('user.index'));

    expect($employee->fresh()->hasRole($ordinaryRole->name))->toBeTrue()
        ->and($employee->fresh()->hasRole('sales'))->toBeFalse();
});

test('a user with employee role assignment permission can assign protected roles', function () {
    $editorRole = Role::create(['name' => 'role-assignment-editor', 'guard_name' => 'web']);
    $editorRole->givePermissionTo(['employee.edit', 'employee.role.assign']);
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->assignRole($editorRole);
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->assignRole('sales');

    $editResponse = $this->actingAs($editor)
        ->get(route('user.edit', $employee->id))
        ->assertOk()
        ->assertSee('value="manager"', false)
        ->assertDontSee('value="admin"', false);
    expect(preg_match(
        '/<option\b(?=[^>]*\bvalue="manager")(?=[^>]*\bdisabled)[^>]*>/s',
        $editResponse->getContent()
    ))->toBe(0);

    $this->actingAs($editor)
        ->put(route('user.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'roles' => 'manager',
        ])
        ->assertRedirect(route('user.index'));

    expect($employee->fresh()->hasRole('manager'))->toBeTrue();
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

test('role permission changes are selectable and persist when saved', function () {
    $editor = User::factory()->create(['email_verified_at' => now()]);
    $editor->givePermissionTo([
        Permission::findOrCreate('role.edit', 'web'),
        Permission::findOrCreate('product.index', 'web'),
    ]);

    $existingPermission = Permission::findOrCreate('supplier.index', 'web');
    $role = Role::create(['name' => 'inventory-editor', 'guard_name' => 'web']);
    $role->givePermissionTo($existingPermission);
    $assignablePermission = Permission::findByName('product.index', 'web');

    $this->actingAs($editor)
        ->get(route('roles.edit', $role->id))
        ->assertOk()
        ->assertSee('product.index')
        ->assertSee('supplier.index')
        ->assertDontSee('voucher.delete');

    $this->actingAs($editor)
        ->put(route('roles.update', $role->id), [
            'name' => $role->name,
            'permission' => [$assignablePermission->id],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->fresh()->hasPermissionTo($assignablePermission))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo($existingPermission))->toBeFalse();
});

test('an administrator can restore a permission removed from the admin role', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');
    $adminRole = Role::findByName('admin');
    $purchaseReturn = Permission::findByName('purchase.return');
    $remainingPermissionIds = $adminRole->permissions
        ->reject(fn (Permission $permission) => $permission->id === $purchaseReturn->id)
        ->modelKeys();

    $this->actingAs($admin)
        ->put(route('roles.update', $adminRole->id), [
            'name' => $adminRole->name,
            'permission' => $remainingPermissionIds,
        ])
        ->assertRedirect(route('roles.index'));

    expect($adminRole->fresh()->hasPermissionTo('purchase.return'))->toBeFalse();

    $this->actingAs($admin->fresh())
        ->get(route('roles.edit', $adminRole->id))
        ->assertOk()
        ->assertSee('purchase.return');

    $restoredPermissionIds = array_merge($remainingPermissionIds, [$purchaseReturn->id]);
    $this->actingAs($admin->fresh())
        ->put(route('roles.update', $adminRole->id), [
            'name' => $adminRole->name,
            'permission' => $restoredPermissionIds,
        ])
        ->assertRedirect(route('roles.index'));

    expect($adminRole->fresh()->hasPermissionTo('purchase.return'))->toBeTrue();
});
