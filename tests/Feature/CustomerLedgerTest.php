<?php

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerLedgerPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function customerLedgerUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create(['email_verified_at' => now()]); // verified user customer ledger route-এ প্রবেশ করতে পারে।

    foreach ($permissions as $permission) { // test user-কে প্রয়োজনীয় route permission দেয়।
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web')); // Spatie permission registry-তে permission নিশ্চিত করে।
    }

    return $user; // test request-এ ব্যবহারের user ফেরায়।
}

test('customer ledger records sale payments and prevents payments above the outstanding balance', function () {
    $user = customerLedgerUserWithPermissions(['customer.ledger', 'Customer.ledger.payment']); // ledger দেখা ও payment লেখার access দেয়।
    $customer = Customer::create(['name' => 'Ledger Test Customer', 'customer_type' => 'temporary', 'status' => 1]); // payment flow-এর customer তৈরি করে।

    $this->actingAs($user)->post(route('customer.ledger.store'), [ // due sale entry তৈরি করে।
        'customer_id' => $customer->id, // entry-টিকে নির্দিষ্ট customer-এর সঙ্গে যুক্ত করে।
        'entry_type' => 'sale', // ledger entry-কে sale হিসেবে চিহ্নিত করে।
        'sale_date' => now()->toDateString(), // sale-এর তারিখ পাঠায়।
        'product_name' => 'Test Product', // invoice-এ দেখানোর পণ্য।
        'quantity' => 2, // দুই unit বিক্রি করে।
        'unit_price' => 50, // প্রতি unit ৫০ টাকা।
        'payment_type' => 'due', // আংশিক/বাকি sale হিসেবে রেকর্ড করে।
        'payment_amount' => 30, // sale-এর সময় ৩০ টাকা জমা।
    ])->assertRedirect(route('customer.ledger', $customer->id)); // সফল entry customer ledger-এ ফেরায়।

    $ledger = CustomerLedger::query()->where('customer_id', $customer->id)->firstOrFail(); // তৈরি sale row আনে।
    expect((float) $ledger->total_amount)->toBe(100.0) // sale total ১০০ টাকা যাচাই করে।
        ->and((float) $ledger->payment_amount)->toBe(30.0) // initial payment ৩০ টাকা যাচাই করে।
        ->and((float) $ledger->due_amount)->toBe(70.0); // বাকি due ৭০ টাকা যাচাই করে।

    $this->actingAs($user)->post(route('customer.ledger.payments.store', $ledger->id), [ // sale due-তে অতিরিক্ত payment নেয়।
        'payment_date' => now()->toDateString(), // payment date দেয়।
        'amount' => 20, // ২০ টাকা কিস্তি নেয়।
    ])->assertRedirect(route('customer.ledger', $customer->id)); // payment শেষে ledger page-এ ফেরায়।

    expect((float) $ledger->fresh()->payment_amount)->toBe(50.0) // aggregate received ৫০ টাকা হয়।
        ->and((float) $ledger->fresh()->due_amount)->toBe(50.0) // অবশিষ্ট sale due ৫০ টাকা হয়।
        ->and(CustomerLedgerPayment::where('customer_ledger_id', $ledger->id)->count())->toBe(1); // payment history row সংরক্ষিত হয়।

    $this->actingAs($user)->get(route('customer.ledger.edit', $ledger->id)) // edit permission-সহ edit form খোলে।
        ->assertOk() // edit page সফলভাবে render হয়।
        ->assertSee('Edit Ledger Entry'); // edit form-এর শিরোনাম যাচাই করে।

    $this->actingAs($user)->post(route('customer.ledger.store'), [ // due-এর বেশি standalone payment চেষ্টা করে।
        'customer_id' => $customer->id, // একই customer-এর balance লক্ষ্য করে।
        'entry_type' => 'payment', // standalone receipt entry চায়।
        'sale_date' => now()->toDateString(), // payment date দেয়।
        'payment_amount' => 51, // outstanding ৫০ টাকার বেশি পাঠায়।
        'received_by' => $user->id, // receiver নির্ধারণ করে।
    ])->assertSessionHasErrors('payment_amount'); // overpayment validation error নিশ্চিত করে।

    $this->actingAs($user)->get(route('customer.ledger.invoice', $ledger->id)) // sale invoice print view আনে।
        ->assertOk() // route access সফল হয়।
        ->assertSee('Payment History') // invoice-এ saved payment history দেখা যায়।
        ->assertSee('Test Product'); // invoice-এ sale product দেখা যায়।
});

test('customer ledger edits preserve payment totals and deleting a sale cascades its payment history', function () {
    $user = customerLedgerUserWithPermissions(['customer.ledger', 'Customer.ledger.edit', 'Customer.ledger.delete']); // view/edit/delete access দেয়।
    $customer = Customer::create(['name' => 'Ledger Edit Customer', 'customer_type' => 'temporary', 'status' => 1]); // edit scenario-র customer তৈরি করে।
    $ledger = CustomerLedger::create([ // বিদ্যমান sale row তৈরি করে।
        'customer_id' => $customer->id, // sale-কে customer-এর সঙ্গে যুক্ত করে।
        'entry_type' => 'sale', // entry-র ধরন sale।
        'sale_date' => now()->toDateString(), // sale date।
        'product_name' => 'Editable Product', // edit form-এর পণ্যের নাম।
        'quantity' => 2, // initial quantity।
        'unit_price' => 50, // initial unit price।
        'total_amount' => 100, // initial total।
        'payment_type' => 'due', // sale-এর payment classification।
        'payment_amount' => 60, // already received amount।
        'due_amount' => 40, // current due।
    ]);
    CustomerLedgerPayment::create([ // history row sale-এর received aggregate-এর অংশ।
        'customer_ledger_id' => $ledger->id, // history-কে sale row-তে যুক্ত করে।
        'payment_date' => now()->toDateString(), // received date।
        'amount' => 20, // saved installment।
    ]);

    $this->actingAs($user)->put(route('customer.ledger.update', $ledger->id), [ // ইতিমধ্যে আদায়ের নিচে sale total নামাতে চেষ্টা করে।
        'sale_date' => now()->toDateString(), // অপরিবর্তিত sale date।
        'product_name' => 'Editable Product', // অপরিবর্তিত product।
        'quantity' => 1, // quantity কমায়।
        'unit_price' => 40, // new total ৪০ টাকা, paid amount ৬০ টাকার নিচে।
    ])->assertSessionHasErrors('unit_price'); // negative due ঠেকানোর validation নিশ্চিত করে।

    $this->actingAs($user)->put(route('customer.ledger.update', $ledger->id), [ // valid sale total দিয়ে update করে।
        'sale_date' => now()->toDateString(), // sale date বজায় রাখে।
        'product_name' => 'Updated Product', // product label পরিবর্তন করে।
        'quantity' => 1, // quantity পরিবর্তন করে।
        'unit_price' => 120, // total paid-এর বেশি রাখে।
    ])->assertRedirect(route('customer.ledger', $customer->id)); // update শেষে customer ledger-এ ফেরায়।

    expect((float) $ledger->fresh()->total_amount)->toBe(120.0) // নতুন total সংরক্ষিত হয়।
        ->and((float) $ledger->fresh()->due_amount)->toBe(60.0); // aggregate payment অপরিবর্তিত রেখে due গণনা হয়।

    $this->actingAs($user)->delete(route('customer.ledger.destroy', $ledger->id)) // ledger row DELETE route দিয়ে মুছে।
        ->assertRedirect(route('customer.ledger', $customer->id)); // delete-এর পর সংশ্লিষ্ট ledger-এ ফেরায়।

    expect(CustomerLedger::find($ledger->id))->toBeNull() // sale row মুছে গেছে।
        ->and(CustomerLedgerPayment::where('customer_ledger_id', $ledger->id)->exists())->toBeFalse(); // cascade-এ payment history মুছে গেছে।
});
