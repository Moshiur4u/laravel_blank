# Combined Voucher Report-এর অতিরিক্ত ফিল্টার

## Controller (Code and Explanation)

ফাইল: `app/Http/Controllers/CombinedVoucherReportController.php`

### Filter যাচাই ও রিপোর্টে প্রয়োগ

```php
'voucher' => ['nullable', 'string', 'max:100'],
'created_by' => ['nullable', 'integer', 'exists:users,id'],
```

`index()` এবং `export()`—দুই জায়গাতেই এই validation যোগ করা হয়েছে। Voucher No. আংশিক মিলিয়ে খোঁজা যায়; Created by-তে শুধু `users` টেবিলে থাকা ব্যবহারকারীর ID গ্রহণ করা হয়।

```php
$voucherSearch = $filters['voucher'] ?? null;
$createdBy = isset($filters['created_by']) ? (int) $filters['created_by'] : null;
$reportQuery = fn () => $this->combinedVoucherQuery(
    $startDate,
    $endDate,
    $voucherSearch,
    $createdBy
);
```

একই filter মূল voucher list এবং summary totals-এ প্রয়োগ হয়। ফলে তালিকা, মোট সংখ্যা ও টাকার হিসাব একই নির্বাচিত ফলাফলের সঙ্গে মেলে। Export-ও একই filter দিয়ে তৈরি হয়। Edit/Delete-এর পরে রিপোর্টে ফেরার সময় নির্বাচিত filter-গুলো URL-এ রাখা হয়।

Created by dropdown তৈরির সময় non-admin ব্যবহারকারীর তালিকা থেকে admin role-যুক্ত user বাদ দেওয়া হয়। Admin নিজে লগ-ইন করলে সব user, admin-সহ, তালিকায় থাকে:

```php
$creatorsQuery = User::query();
if (! auth()->user()->hasRole('admin')) {
    $creatorsQuery->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'));
}

'creators' => $creatorsQuery->orderBy('name')->get(['id', 'name']),
```

Union query-তে দুই ধরনের voucher-এর creator ID একই নামে যোগ করা হয়:

```php
->addSelect('employee_payment_vouchers.created_by as created_by_id')
// General voucher query:
->addSelect('created_by as created_by_id')
```

এরপর সম্মিলিত query-তে filter প্রয়োগ হয়:

```php
if ($voucherSearch !== null && $voucherSearch !== '') {
    $query->where('voucher_code', 'like', '%'.$voucherSearch.'%');
}

if ($createdBy !== null) {
    $query->where('created_by_id', $createdBy);
}
```

Date filter-এর বর্তমান Daily/Monthly আচরণ অপরিবর্তিত রাখা হয়েছে।

নতুন feature test-এ voucher number ও creator একসঙ্গে filter করলে শুধু মিলে যাওয়া voucher এবং তারই summary থাকে—তা যাচাই করা হয়েছে:

```php
->get(route('voucher-report.combined', [
    'period' => 'daily',
    'date' => '2026-10-13',
    'voucher' => 'FILTER-MATCH',
    'created_by' => $creator->id,
]))
->assertSee('DBV-FILTER-MATCH-001')
->assertDontSee('DBV-FILTER-MATCH-002')
->assertViewHas('debitTotal', 30.0);
```

আরেকটি test যাচাই করে non-admin-এর dropdown-এ admin নেই, কিন্তু Admin-এর dropdown-এ admin ও অন্য user দুজনই আছেন।

## Migration (Code and Explanation)

```text
কোনো migration যোগ বা পরিবর্তন করা হয়নি।
```

Date, voucher number এবং creator filter-এর জন্য নতুন database column দরকার নেই। Creator ID আগে থেকেই দুই voucher table-এ আছে।

## Route (Code and Explanation)

ফাইল: `routes/web.php`। বিদ্যমান report ও export route-ই ব্যবহার করা হয়েছে; route পরিবর্তন করা হয়নি।

```php
Route::get('/voucher-report/combined/export', [CombinedVoucherReportController::class, 'export'])
    ->middleware('permission:voucher.report.view|reports.all_access')
    ->name('voucher-report.combined.export');
Route::get('/voucher-report/combined', [CombinedVoucherReportController::class, 'index'])
    ->middleware('permission:voucher.report.view|reports.all_access')
    ->name('voucher-report.combined');
```

নতুন filter-গুলো GET query parameter হিসেবে report এবং export-এ পাঠানো হয়, তাই নতুন route-এর প্রয়োজন হয়নি।

## Model (Code and Explanation)

```text
কোনো model যোগ বা পরিবর্তন করা হয়নি।
```

বিদ্যমান `User` model থেকে Created by dropdown-এর ID ও নাম নেওয়া হয়। Non-admin-এর dropdown query-তে admin role বাদ দিতে বিদ্যমান `roles` relation ব্যবহৃত হয়। Voucher query বিদ্যমান `created_by` column ব্যবহার করে; নতুন relation বা model behavior যোগ করার দরকার হয়নি।

## Blade Template (Code and Explanation)

ফাইল: `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`

```blade
<label for="voucher" class="form-label">Voucher No.</label>
<input type="text" name="voucher" id="voucher" class="form-control" maxlength="100"
    value="{{ $voucherSearch }}" placeholder="Search voucher number">

<label for="created_by" class="form-label">Created by</label>
<select name="created_by" id="created_by" class="form-select">
    <option value="">All users</option>
    @foreach ($creators as $creator)
        <option value="{{ $creator->id }}" @selected((string) $selectedCreator === (string) $creator->id)>
            {{ $creator->name }}
        </option>
    @endforeach
</select>
```

আগের Date (Daily/Monthly) control রাখা হয়েছে। নতুন Voucher No. input আংশিক নম্বর দিয়ে খোঁজে, আর Created by dropdown নির্দিষ্ট ব্যবহারকারীর voucher দেখায়। Non-admin-দের dropdown-এ admin account দেখা যায় না; Admin সব account দেখতে পারে। Export link-এ বর্তমান query parameter আগে থেকেই পাঠানো হয়; edit/delete form-এও filter-এর মান লুকানো field হিসেবে রাখা হয়েছে, যাতে কাজ শেষে একই filter-সহ report ফিরে আসে।
