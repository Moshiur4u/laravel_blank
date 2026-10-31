# Voucher Account পরিবর্তনের নথি

## Controller (code and explanation)----

`app/Http/Controllers/VoucherAccountController.php`-এ Voucher Account-এর তালিকা, নতুন account তৈরি, account edit, update এবং delete করার action যোগ করা হয়েছে। নাম বাধ্যতামূলক, সর্বোচ্চ ১০০ অক্ষর এবং unique হিসেবে validate হয়। `app/Http/Controllers/VoucherEntryController.php`-এর voucher form-এ account-এর তালিকা পাঠানো হয়; voucher জমার সময় নির্বাচিত account যাচাই করে তার ID এবং নাম সংরক্ষণ করা হয়।

```php
// VoucherAccountController::store()
$request->validate([
    'name' => ['required', 'string', 'max:100', 'unique:voucher_accounts,name'],
]);

// VoucherEntryController::store()
$validated = $request->validate([
    'voucher_type' => ['required', 'string', 'in:debit,credit'],
    'voucher_account_id' => ['required', 'integer', 'exists:voucher_accounts,id'],
]);
$voucherAccount = VoucherAccount::query()->findOrFail($validated['voucher_account_id']);
VoucherEntry::createWithCode([
    ...$validated,
    'expense_income_type' => $voucherAccount->name,
]);
```

দ্বিতীয় validation-এ নির্বাচনটি database-এ আছে কি না যাচাই হয়। Voucher তৈরির সময় account-এর ID সম্পর্ক হিসেবে এবং নাম voucher-এর snapshot হিসেবে জমা হয়। Account delete করলে voucher-এর পুরোনো নাম নষ্ট হয় না।

## Migration (code and explanation)----

`database/migrations/2026_10_31_000001_create_voucher_accounts_table.php`-এ unique নামসহ `voucher_accounts` table তৈরি হয়েছে। `voucher_entries`-এ nullable `voucher_account_id` foreign key যোগ করা হয়েছে; account মুছলে foreign key null হবে, কিন্তু voucher তৈরির সময়কার `expense_income_type` নামের snapshot account edit/delete-এর পরও অপরিবর্তিত থাকবে।

```php
Schema::create('voucher_accounts', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100)->unique();
    $table->timestamps();
});

Schema::table('voucher_entries', function (Blueprint $table) {
    $table->foreignId('voucher_account_id')
        ->nullable()
        ->constrained('voucher_accounts')
        ->nullOnDelete();
});
```

## Model (code and explanation)----

`app/Models/VoucherAccount.php` account-এর `name` mass assignment অনুমোদন করে এবং voucher entry-গুলোর সঙ্গে one-to-many সম্পর্ক নির্ধারণ করে। `app/Models/VoucherEntry.php`-এ account ID fillable করা এবং account-এর belongs-to সম্পর্ক যোগ করা হয়েছে।

```php
// VoucherAccount
protected $fillable = ['name'];

public function voucherEntries(): HasMany
{
    return $this->hasMany(VoucherEntry::class);
}

// VoucherEntry
'voucher_account_id',

public function voucherAccount(): BelongsTo
{
    return $this->belongsTo(VoucherAccount::class);
}
```

## Blade (code and explanation)----

`resources/views/backend/Accounts Entries/voucherAccountIndex.blade.php` account তালিকা ও edit/delete action দেখায়। `voucherAccountCreate.blade.php` এবং `voucherAccountEdit.blade.php` account যোগ/পরিবর্তনের form। `voucherEntryCreate.blade.php`-এ আগের মুক্ত-লেখা Account input-এর বদলে সংরক্ষিত Voucher Account-এর dropdown যোগ হয়েছে; voucher তালিকাতেও account name দেখায়। `resources/views/dashboard/dashboard.blade.php`-এর Accounts menu-তে Voucher Accounts link যোগ হয়েছে।

```blade
<select name="voucher_account_id" id="voucher_account_id" required>
    <option value="">-- Select Account --</option>
    @foreach ($voucherAccounts as $voucherAccount)
        <option value="{{ $voucherAccount->id }}">
            {{ $voucherAccount->name }}
        </option>
    @endforeach
</select>
```

`routes/web.php`-এ authentication ও permission-নিয়ন্ত্রিত CRUD route যোগ হয়েছে। `database/seeders/PermissionSeederTable.php`-এ account-এর permission-গুলো এবং `RolePermissionSeeder.php`-এ manager role-এর জন্য ওই permission-গুলো নির্ধারণ করা হয়েছে।

## Permission ও menu সংশোধন

Voucher Account তৈরি না হওয়ার কারণ ছিল: বর্তমান database-এ `voucher.account.*` permission ছিল না। ফলে Add link এবং create/store route-এর permission gate-এ প্রবেশ আটকে যাচ্ছিল। `database/seeders/VoucherAccountPermissionSeeder.php` এখন প্রয়োজনীয় permission তৈরি করে শুধু `admin` ও `manager` role-এ যোগ করে; অন্য role-এর permissions sync বা মুছে না। `database/seeders/DatabaseSeeder.php`-এ seeder-টি যুক্ত থাকায় পরবর্তী standard database seed-এও অনুমতিগুলো তৈরি হবে।

`resources/views/dashboard/dashboard.blade.php`-এর `Administration operations > Accounts` menu-তে এখন আলাদা করে “Voucher Account Add” এবং “Voucher Account Index” link দেখায়। এই menu item দুটো যথাক্রমে `voucher.account.create` ও `voucher.account.index` দিয়ে gate করা।

## পরীক্ষা

`tests/Feature/VoucherEntryTest.php`-এ নির্বাচিত account-এর ID ও নাম voucher-এ সংরক্ষণ এবং form-এ account list দেখানোর পরীক্ষা যোগ/হালনাগাদ করা হয়েছে। `tests/Feature/VoucherAccountTest.php`-এ account create, update, delete এবং account delete-এর পর পুরোনো voucher-এ নামের snapshot অক্ষত থাকার পরীক্ষা আছে।
