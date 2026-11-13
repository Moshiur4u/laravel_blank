<?php // Pest feature test file শুরু করে।

use App\Models\Quotation; // quotation count যাচাইয়ের model আনে।
use App\Models\QuotationItem; // সংরক্ষিত quotation item পরীক্ষা করতে model আনে।
use App\Models\User; // route request-এর authenticated user model আনে।
use Illuminate\Foundation\Testing\RefreshDatabase; // test database-কে migration দিয়ে refresh করার trait आনে।
use Illuminate\Support\Facades\DB; // QuantitySize test data সরাসরি insert করতে facade আনে।
use Spatie\Permission\Models\Permission; // quotation route permission তৈরি/আনার model আনে।

uses(RefreshDatabase::class); // প্রতিটি test-এর জন্য পরিষ্কার database schema নিশ্চিত করে।

function quotationUserWithSalesPermission(): User // sales.create permission-সহ test user তৈরির helper ঘোষণা করে।
{ // helper method শুরু করে।
    $user = User::factory()->create(['email_verified_at' => now()]); // verified quotation user তৈরি করে।
    $permission = Permission::findOrCreate('sales.create', 'web'); // quotation route-এর permission নিশ্চিত করে।
    $user->givePermissionTo($permission); // user-কে quotation তৈরি করার অনুমতি দেয়।

    return $user; // authenticated request-এ ব্যবহারের user ফেরায়।
} // helper method শেষ করে।

test('quotation form loads unit options from quantity sizes', function () {
    $user = quotationUserWithSalesPermission(); // quotation form খোলার অনুমতিসহ user নেয়।
    DB::table('quantity_sizes')->insert([ // QuantitySize table-এ dropdown-এর sample unit যোগ করে।
        ['name' => 'Box', 'created_at' => now(), 'updated_at' => now()], // প্রথম unit record যোগ করে।
        ['name' => 'Piece', 'created_at' => now(), 'updated_at' => now()], // দ্বিতীয় unit record যোগ করে।
    ]); // unit record insert শেষ করে।

    $this->actingAs($user) // route-এ user-কে authenticated করে।
        ->get(route('quotation.create')) // Create Quotation form খোলে।
        ->assertOk() // page সফলভাবে লোড হয়েছে যাচাই করে।
        ->assertSee('name="items[0][quantity_size]"', false) // প্রথম quotation item-এর unit select আছে যাচাই করে।
        ->assertSee('Box') // database থেকে আসা প্রথম unit option দেখায় কি না যাচাই করে।
        ->assertSee('Piece') // database থেকে আসা দ্বিতীয় unit option দেখায় কি না যাচাই করে।
        ->assertViewHas('quantitySizes', fn ($sizes) => $sizes->pluck('name')->all() === ['Box', 'Piece']); // controller sorted QuantitySize records পাঠায় কি না যাচাই করে।
});

test('quotation saves the selected quantity size and displays its saved unit', function () {
    $user = quotationUserWithSalesPermission(); // quotation তৈরি ও দেখা যাবে এমন user নেয়।
    DB::table('quantity_sizes')->insert([ // valid unit database-এ যোগ করে।
        'name' => 'Carton', // dropdown ও validation-এ ব্যবহৃত unit-এর নাম দেয়।
        'created_at' => now(), // record-এর creation timestamp দেয়।
        'updated_at' => now(), // record-এর update timestamp দেয়।
    ]); // unit record insert শেষ করে।

    $this->actingAs($user) // form submission-এ user-কে authenticate করে।
        ->post(route('quotation.store'), [ // quotation তৈরির request পাঠায়।
            'customer_name' => 'Unit Test Customer', // quotation-এর customer নাম দেয়।
            'customer_phone' => '01700000000', // required customer phone দেয়।
            'quotation_date' => today()->toDateString(), // আজকের quotation date দেয়।
            'items' => [ // quotation-এর item list শুরু করে।
                [ // একটি item-এর input শুরু করে।
                    'product_name' => 'Unit Test Product', // item-এর product name দেয়।
                    'quantity' => 2, // item-এর quantity দেয়।
                    'quantity_size' => 'Carton', // QuantitySize table থেকে নির্বাচিত unit পাঠায়।
                    'unit_price' => '25.00', // item-এর unit price দেয়।
                ], // item input শেষ করে।
            ], // item list শেষ করে।
        ]) // request পাঠানো শেষ করে।
        ->assertSessionHasNoErrors(); // validation-এ কোনো error নেই নিশ্চিত করে।

    $quotationItem = QuotationItem::query()->sole(); // তৈরি হওয়া একমাত্র quotation item আনে।
    expect($quotationItem->quantity_size)->toBe('Carton'); // নির্বাচিত unit item-এ সংরক্ষিত হয়েছে যাচাই করে।

    $this->actingAs($user) // quotation document দেখার জন্য user-কে authenticate করে।
        ->get(route('quotation.show', $quotationItem->quotation_id)) // তৈরি quotation-এর document খোলে।
        ->assertOk() // document সফলভাবে দেখানো হয়েছে যাচাই করে।
        ->assertSee('Carton'); // document-এর Unit column-এ সংরক্ষিত unit দেখায় যাচাই করে।
});

test('quotation rejects a unit that is not in the quantity sizes table', function () {
    $user = quotationUserWithSalesPermission(); // quotation route-এ প্রবেশের user নেয়।

    $this->actingAs($user) // request-এ user-কে authenticate করে।
        ->post(route('quotation.store'), [ // invalid unit-সহ quotation পাঠায়।
            'customer_name' => 'Unit Test Customer', // required customer name দেয়।
            'customer_phone' => '01700000000', // required customer phone দেয়।
            'quotation_date' => today()->toDateString(), // required quotation date দেয়।
            'items' => [ // item input list শুরু করে।
                [ // invalid unit-সহ item শুরু করে।
                    'product_name' => 'Unit Test Product', // required product name দেয়।
                    'quantity' => 1, // required quantity দেয়।
                    'quantity_size' => 'Unknown Unit', // QuantitySize table-এ নেই এমন unit পাঠায়।
                    'unit_price' => '10.00', // required unit price দেয়।
                ], // item input শেষ করে।
            ], // item list শেষ করে।
        ]) // invalid request পাঠানো শেষ করে।
        ->assertSessionHasErrors('items.0.quantity_size'); // table-এ নেই এমন unit reject হয়েছে যাচাই করে।

    expect(Quotation::query()->count())->toBe(0); // invalid unit-এ quotation তৈরি হয়নি নিশ্চিত করে।
});