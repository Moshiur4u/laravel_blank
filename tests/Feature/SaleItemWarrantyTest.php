<?php

use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function warrantyTestProduct(): Product
{
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Warranty Test Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Warranty Test Brand']);

    return Product::create([
        'productName' => 'Warranty Test Device',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);
}

test('warranty data is saved per sale item, shown on invoice, and updated with the invoice', function () {
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->givePermissionTo(Permission::findOrCreate('sales.create', 'web'));
    $employee->givePermissionTo(Permission::findOrCreate('Update_today_Invoice', 'web'));
    $product = warrantyTestProduct();

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000991',
            'save_customer' => 'no',
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'warranty_cover' => 1,
                    'warranty_year' => 2,
                    'serial_mac' => 'SN-ORIGINAL-001',
                ],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $item = $invoice->items()->firstOrFail();

    expect($item->warranty_cover)->toBeTrue()
        ->and($item->warranty_year)->toBe(2)
        ->and($item->serial_mac)->toBe('SN-ORIGINAL-001');

    $this->actingAs($employee)
        ->get(route('sales.invoice', $invoice->id))
        ->assertOk()
        ->assertSee('Warranty: 2 Year(s)')
        ->assertSee('Serial/MAC: SN-ORIGINAL-001');

    $this->actingAs($employee)
        ->get(route('sales.invoice.edit', $invoice->id))
        ->assertOk()
        ->assertSee('warrantyCover')
        ->assertSee('value="2"', false)
        ->assertSee('SN-ORIGINAL-001');

    $this->actingAs($employee)
        ->put(route('sales.invoice.update', $invoice->id), [
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat_percentage' => 0,
            'ait_percentage' => 0,
            'amount_paid' => 100,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'warranty_cover' => 1,
                    'warranty_year' => 3,
                    'serial_mac' => 'MAC-UPDATED-002',
                ],
            ],
        ])
        ->assertRedirect(route('sales.invoice', $invoice->id));

    $updatedItem = $invoice->fresh()->items()->firstOrFail();
    expect($updatedItem->warranty_cover)->toBeTrue()
        ->and($updatedItem->warranty_year)->toBe(3)
        ->and($updatedItem->serial_mac)->toBe('MAC-UPDATED-002');

    $this->actingAs($employee)
        ->put(route('sales.invoice.update', $invoice->id), [
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat_percentage' => 0,
            'ait_percentage' => 0,
            'amount_paid' => 100,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ])
        ->assertRedirect(route('sales.invoice', $invoice->id));

    $clearedItem = $invoice->fresh()->items()->firstOrFail();
    expect($clearedItem->warranty_cover)->toBeFalse()
        ->and($clearedItem->warranty_year)->toBeNull()
        ->and($clearedItem->serial_mac)->toBeNull();
});

test('warranty year and serial are required when warranty cover is selected', function () {
    $employee = User::factory()->create(['email_verified_at' => now()]);
    $employee->givePermissionTo(Permission::findOrCreate('sales.create', 'web'));
    $product = warrantyTestProduct();

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000992',
            'save_customer' => 'no',
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'warranty_cover' => 1,
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'items.0.warranty_year',
            'items.0.serial_mac',
        ]);
});
