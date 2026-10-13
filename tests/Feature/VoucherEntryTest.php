<?php

use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a debit voucher saves its generated code, entered type, and creator snapshot', function () {
    $creator = User::factory()->create(['name' => 'Accounts Operator']);

    $this->actingAs($creator)
        ->post(route('voucher-entries.store'), [
            'voucher_type' => 'debit',
            'expense_income_type' => 'Office rent',
            'voucher_date' => '2026-10-12',
            'amount' => '1250.50',
            'reference' => 'RENT-12',
            'description' => 'October office rent',
        ])
        ->assertRedirect(route('voucher-entries.index'))
        ->assertSessionHasNoErrors();

    $voucher = VoucherEntry::query()->sole();

    expect($voucher->voucher_code)->toMatch('/^DBV-20261012-\d{6}$/');
    $this->assertDatabaseHas('voucher_entries', [
        'id' => $voucher->id,
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office rent',
        'voucher_date' => '2026-10-12',
        'amount' => '1250.50',
        'reference' => 'RENT-12',
        'description' => 'October office rent',
        'created_by' => $creator->id,
        'created_by_name' => 'Accounts Operator',
    ]);
});

test('a credit voucher receives its own automatic code prefix', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('voucher-entries.store'), [
            'voucher_type' => 'credit',
            'expense_income_type' => 'Product sales',
            'voucher_date' => '2026-10-12',
            'amount' => '800.00',
        ])
        ->assertRedirect(route('voucher-entries.index'))
        ->assertSessionHasNoErrors();

    $voucher = VoucherEntry::query()->sole();

    expect($voucher->voucher_code)->toMatch('/^CRV-20261012-\d{6}$/');
    $this->assertSame('credit', $voucher->voucher_type);
});

test('daily voucher report filters by date and creator', function () {
    $creator = User::factory()->create(['name' => 'Report Operator']);
    $otherCreator = User::factory()->create();

    $matchingVoucher = VoucherEntry::create([
        'voucher_code' => 'DBV-20261012-000001',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office rent',
        'voucher_date' => '2026-10-12',
        'amount' => '125.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);

    VoucherEntry::create([
        'voucher_code' => 'CRV-20261012-000002',
        'voucher_type' => 'credit',
        'expense_income_type' => 'Product sales',
        'voucher_date' => '2026-10-12',
        'amount' => '75.00',
        'created_by' => $otherCreator->id,
        'created_by_name' => $otherCreator->name,
    ]);

    VoucherEntry::create([
        'voucher_code' => 'DBV-20261011-000003',
        'voucher_type' => 'debit',
        'expense_income_type' => 'Office rent',
        'voucher_date' => '2026-10-11',
        'amount' => '50.00',
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);

    $this->actingAs($creator)
        ->get(route('voucher-entries.report', [
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
        ->assertViewHas('debitTotal', 125.0)
        ->assertViewHas('creditTotal', 0.0)
        ->assertViewHas('dailySummaries', function ($summaries) use ($creator) {
            return $summaries->count() === 1
                && $summaries->first()->created_by_name === $creator->name
                && $summaries->first()->voucher_type === 'debit'
                && (int) $summaries->first()->voucher_count === 1;
        });
});
