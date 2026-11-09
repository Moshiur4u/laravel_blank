<?php

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Supplierledger;
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('product module permissions can access the purchase return screen', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(Permission::findOrCreate('product.index', 'web'));

    $this->actingAs($user)
        ->get(route('purchase-returns.index'))
        ->assertOk()
        ->assertSee('Purchase Return');
});

test('purchase returns update supplier balances, product stock, inventory report and cash refund voucher', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo([
        Permission::findOrCreate('purchase.return', 'web'),
        Permission::findOrCreate('purchase.return.report', 'web'),
        Permission::findOrCreate('product.report.inventory', 'web'),
        Permission::findOrCreate('supplier.ledger', 'web'),
        Permission::findOrCreate('voucher.edit', 'web'),
        Permission::findOrCreate('voucher.delete', 'web'),
    ]);

    $supplier = Supplier::create([
        'name' => 'Purchase Return Supplier',
        'email' => 'purchase-return@example.test',
        'phone' => '01700000029',
        'address' => 'Test address',
    ]);
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Purchase Return Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Purchase Return Brand']);
    $product = Product::create([
        'productName' => 'Purchase Return Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'supplier_id' => $supplier->id,
        'purchase_price' => 80,
        'price' => 150,
        'unit' => '6',
    ]);
    $ledger = Supplierledger::create([
        'supplier_id' => $supplier->id,
        'invoice_id' => 'INV-PURCHASE-RETURN-TEST',
        'product_id' => (string) $product->id,
        'quantity' => '5',
        'price' => '100.00',
        'total' => '500.00',
        'payment' => '100.00',
        'due' => '400.00',
    ]);

    $this->actingAs($user)
        ->get(route('purchase-returns.search', ['q' => 'INV-PURCHASE-RETURN-TEST']))
        ->assertOk()
        ->assertJsonPath('purchases.0.supplier_name', $supplier->name);

    $this->actingAs($user)
        ->get(route('purchase-returns.supplier-history', ['supplier_id' => $supplier->id]))
        ->assertOk()
        ->assertJsonPath('purchases.0.remaining_quantity', 5)
        ->assertJsonPath('purchases.0.stock_quantity', 6)
        ->assertJsonPath('purchases.0.returnable', true);

    $this->actingAs($user)
        ->post(route('purchase-returns.store'), [
            'supplierledger_id' => $ledger->id,
            'quantity' => '2',
            'return_date' => today()->toDateString(),
            'reason' => 'Damaged units',
        ])
        ->assertRedirect(route('purchase-returns.index', ['supplier_id' => $supplier->id]));

    expect((float) $ledger->fresh()->quantity)->toBe(3.0)
        ->and((float) $ledger->fresh()->total)->toBe(300.0)
        ->and((float) $ledger->fresh()->payment)->toBe(100.0)
        ->and((float) $ledger->fresh()->due)->toBe(200.0)
        ->and((float) $product->fresh()->unit)->toBe(4.0)
        ->and((float) PurchaseReturn::query()->latest('id')->value('return_amount'))->toBe(200.0)
        ->and((float) PurchaseReturn::query()->latest('id')->value('unit_price'))->toBe(100.0);

    $this->actingAs($user)
        ->post(route('purchase-returns.store'), [
            'supplierledger_id' => $ledger->id,
            'quantity' => '3',
            'return_date' => today()->toDateString(),
            'reason' => 'Return remaining units',
        ])
        ->assertRedirect();

    $lastReturn = PurchaseReturn::query()->latest('id')->firstOrFail();
    expect((float) $lastReturn->return_amount)->toBe(300.0)
        ->and((float) $lastReturn->refund_amount)->toBe(100.0)
        ->and((float) $ledger->fresh()->total)->toBe(0.0)
        ->and((float) $ledger->fresh()->payment)->toBe(0.0)
        ->and((float) $ledger->fresh()->due)->toBe(0.0)
        ->and((float) $product->fresh()->unit)->toBe(1.0);

    $this->assertDatabaseHas('voucher_entries', [
        'voucher_type' => 'credit',
        'amount' => '100.00',
        'reference' => $lastReturn->return_no,
        'voucherable_type' => PurchaseReturn::class,
        'voucherable_id' => $lastReturn->id,
    ]);
    $refundVoucher = VoucherEntry::query()->where('reference', $lastReturn->return_no)->firstOrFail();
    $this->actingAs($user)
        ->put(route('voucher-report.combined.update', ['credit', $refundVoucher->id]), [
            'voucher_date' => today()->toDateString(),
            'amount' => 101,
        ])
        ->assertSessionHasErrors('voucher');
    $this->actingAs($user)
        ->delete(route('voucher-report.combined.destroy', ['credit', $refundVoucher->id]))
        ->assertSessionHasErrors('voucher');

    $this->actingAs($user)
        ->get(route('supplier.ledger', $supplier->id))
        ->assertOk()
        ->assertViewHas('totalAmount', 0.0)
        ->assertViewHas('totalPayment', 0.0)
        ->assertViewHas('totalDue', 0.0);

    $this->actingAs($user)
        ->get(route('product.inventory.report'))
        ->assertOk()
        ->assertViewHas('rows', fn ($rows) => $rows->firstWhere('id', $product->id)['purchased_quantity'] === 0.0
            && $rows->firstWhere('id', $product->id)['purchase_amount'] === 0.0
            && $rows->firstWhere('id', $product->id)['stock'] === 1.0);

    $this->actingAs($user)
        ->get(route('purchase-returns.report', [
            'from_date' => today()->toDateString(),
            'to_date' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('totalQuantity', 5.0)
        ->assertViewHas('totalReturnAmount', 500.0)
        ->assertViewHas('totalRefundAmount', 100.0);
});
