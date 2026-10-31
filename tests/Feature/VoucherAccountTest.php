<?php

use App\Models\User;
use App\Models\VoucherAccount;
use App\Models\VoucherEntry;
use Spatie\Permission\Models\Permission;

test('voucher accounts can be created updated and deleted while voucher names remain saved', function () {
    $operator = User::factory()->create();
    $permissions = collect([
        'voucher.account.index',
        'voucher.account.create',
        'voucher.account.edit',
        'voucher.account.update',
        'voucher.account.delete',
    ])->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));
    $operator->givePermissionTo($permissions);

    $this->actingAs($operator)
        ->post(route('voucher-accounts.store'), ['name' => 'Office rent'])
        ->assertRedirect(route('voucher-accounts.index'))
        ->assertSessionHasNoErrors();

    $account = VoucherAccount::query()->sole();
    $voucher = VoucherEntry::create([
        'voucher_type' => 'debit',
        'expense_income_type' => $account->name,
        'voucher_account_id' => $account->id,
        'voucher_date' => '2026-10-12',
        'amount' => '125.00',
        'created_by_name' => $operator->name,
    ]);

    $this->put(route('voucher-accounts.update', $account), ['name' => 'Office costs'])
        ->assertRedirect(route('voucher-accounts.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('voucher_accounts', [
        'id' => $account->id,
        'name' => 'Office costs',
    ]);

    $this->delete(route('voucher-accounts.destroy', $account))
        ->assertRedirect(route('voucher-accounts.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('voucher_accounts', ['id' => $account->id]);
    $this->assertDatabaseHas('voucher_entries', [
        'id' => $voucher->id,
        'voucher_account_id' => null,
        'expense_income_type' => 'Office rent',
    ]);
});
