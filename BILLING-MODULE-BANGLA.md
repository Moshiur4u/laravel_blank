# BillingEntry ও BillingRecord মডিউল

এই নথিতে নতুন billing module-এর কোডের মূল অংশ, ফাইলের অবস্থান এবং কাজের ব্যাখ্যা দেওয়া হলো। প্রতিটি ফাইলের লিংকে সম্পূর্ণ বাস্তবায়ন দেখা যাবে।

## AppServiceProvider (Code and Explanation)

ফাইল: [AppServiceProvider.php](app/Providers/AppServiceProvider.php)

```php
View::composer('dashboard.dashboard', function ($view) {
    $organization = Organization::query()->find(1);
    $billingSettings = BillingSetting::query()->first();
    $overdueBillingEntries = $organization
        ? BillingEntry::query()
            ->where('organization_id', $organization->id)
            ->whereDate('due_date', '<=', today()->subMonths(3)->toDateString())
            ->with('activeRecords')
            ->orderBy('billing_month')
            ->get()
            ->filter(fn (BillingEntry $entry) => $entry->outstandingAmount() > 0)
            ->values()
        : collect();

    $view->with([
        'organization' => $organization,
        'billingSettings' => $billingSettings,
        'overdueBillingEntries' => $overdueBillingEntries,
        // dashboard-এর আগের expiry ও stock notification-ও একইভাবে সরবরাহ হয়।
    ]);
});
```

Dashboard view composer বর্তমান organization-এর তিন মাস বা তার বেশি সময় ধরে বকেয়া থাকা bill এবং marquee settings-কে view-তে পাঠায়। `outstandingAmount()`-এ সক্রিয় payment-গুলো বাদ পড়ে; সম্পূর্ণ পরিশোধিত বা revert করা bill বকেয়া হিসেবে দেখানো হয় না।

## TestFeature (Code and Explanation)

ফাইল: [BillingTest.php](tests/Feature/BillingTest.php)

```php
$record = BillingRecord::query()->sole();
$this->actingAs($user)
    ->put(route('billing.records.update', $record), [
        'amount' => '1200.00',
        'received_at' => '2026-11-18',
        'payment_method' => 'Cash',
        'reference' => 'Corrected receipt',
        'note' => 'Corrected amount and date',
    ])
    ->assertRedirect(route('billing.records'));

$this->actingAs($user)
    ->post(route('billing.records.revert', $record))
    ->assertRedirect(route('billing.records'));

expect($entry->fresh()->outstandingAmount())->toBe(1250.0);
```

এটি [BillingTest.php](tests/Feature/BillingTest.php)-এর receipt correction/reversal test-এর অংশ। একই ফাইলে monthly bill generation, duplicate প্রতিরোধ, permission boundary, Access_Off, marquee toggle, profile setup redirect এবং billing-month report filtering-ও পরীক্ষা করা হয়েছে।

## Controller (Code and Explanation)

ফাইল: [BillingController.php](app/Http/Controllers/BillingController.php)

```php
$billingMonth = Carbon::createFromFormat('Y-m', $validated['billing_month'])->startOfMonth();

if (BillingEntry::query()
    ->where('organization_id', $organization->id)
    ->whereDate('billing_month', $billingMonth->toDateString())
    ->exists()) {
    throw ValidationException::withMessages([
        'billing_month' => 'A bill already exists for this month.',
    ]);
}
```

Admin মাস ও amount নির্ধারণ করে একটি action-এ bill তৈরি করেন। একই organization-এর একই মাসে দ্বিতীয় bill তৈরি করা যায় না; database unique index-ও এই নিয়ম রক্ষা করে। Organization profile না থাকলে billing page 404 না দিয়ে Organization Information setup-এ সতর্কতাসহ পাঠায়।

Payment গ্রহণের সময় entry row lock করে বাকি পাওনা হিসাব হয়। Payment-এর পরিমাণ outstanding-এর বেশি হতে পারে না। আংশিক payment সমর্থিত; সব payment-এর যোগফল bill amount-এ পৌঁছালে bill পরিশোধিত হয়। BillingRecord থেকে receipt-এর amount/date/details সম্পাদনা এবং `reversed_at`/`reversed_by` দিয়ে সেটি revert করা যায়—পুরোনো receipt মুছে যায় না।

`accessOff()` কেবল admin-কে অনুমতি দেয় এবং অন্তত একটি bill বকেয়া থাকলেই সব non-admin user-এর `status` শূন্য করে। Organization বিদ্যমান project convention অনুযায়ী `organizations.id = 1` থেকে নেওয়া হয়।

```php
abort_unless($request->user()->hasRole('admin'), 403);

$record->update([
    'reversed_at' => now(),
    'reversed_by' => $request->user()->id,
]);
```

Revert action-এর আগে bill ও receipt row lock করা হয় এবং একই receipt দ্বিতীয়বার revert করা আটকানো হয়।
Organization profile অনুপস্থিত হলে Billing controller `organization.edit`-এ redirect করে; সতর্কবার্তাটি [addOrganization.blade.php](resources/views/frontend/organization/addOrganization.blade.php)-তে দেখানো হয়।

`transactions()`-এ `from_month`/`to_month` filter `billing_month`-এ প্রয়োগ হয়। Filtered bill-গুলোর total billed, received ও outstanding balance হিসাব হয়। Unpaid bill-ও অন্তর্ভুক্ত থাকে; payment receipt-এর তারিখ selected billing month-এর বাইরে হলেও bill-এর সঙ্গে দেখানো হয়।

```php
if ($fromDate) {
    $query->whereDate('billing_month', '>=', $fromDate->toDateString());
}

if ($toDate) {
    $query->whereDate('billing_month', '<=', $toDate->toDateString());
}

$entries = $query->get();
$totalPaidAmount = $entries->sum(fn (BillingEntry $entry) => $entry->paidAmount());
$totalDueAmount = $entries->sum(fn (BillingEntry $entry) => $entry->outstandingAmount());
```

## Migration (Code and Explanation)

ফাইল: [2026_11_19_000001_create_billing_tables.php](database/migrations/2026_11_19_000001_create_billing_tables.php)

```php
Schema::create('billing_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->date('billing_month');
    $table->decimal('amount', 12, 2);
    $table->date('due_date');
    $table->text('description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->unique(['organization_id', 'billing_month']);
});
```

একই migration-এ `billing_records` receipt, edit এবং reversal audit রাখে; `billing_settings` custom marquee text ও `show_marquee_alert` রাখে। Revert করা payment record-এ সংরক্ষিত থাকে, তাই history অক্ষুণ্ণ থাকে।

## Route (Code and Explanation)

ফাইল: [web.php](routes/web.php)

```php
Route::get('/billing-entry', [BillingController::class, 'entry'])
    ->middleware('permission:billing.entry')->name('billing.entry');
Route::post('/billing-entry/generate', [BillingController::class, 'generate'])
    ->middleware('permission:billing.entry')->name('billing.generate');
Route::post('/billing-entry/{billingEntry}/payments', [BillingController::class, 'storePayment'])
    ->middleware('permission:billing.entry')->name('billing.payments.store');
Route::post('/billing-entry/settings', [BillingController::class, 'updateSettings'])
    ->middleware('permission:billing.entry')->name('billing.settings.update');
Route::post('/billing-entry/access-off', [BillingController::class, 'accessOff'])
    ->middleware('permission:billing.entry')->name('billing.access-off');
Route::get('/billing-records', [BillingController::class, 'records'])
    ->middleware('permission:billing.record')->name('billing.records');
Route::get('/billing-transactions', [BillingController::class, 'transactions'])
    ->middleware('permission:billing.record')->name('billing.transactions');
Route::put('/billing-records/{billingRecord}', [BillingController::class, 'updateRecord'])
    ->middleware('permission:billing.record')->name('billing.records.update');
Route::post('/billing-records/{billingRecord}/revert', [BillingController::class, 'revertRecord'])
    ->middleware('permission:billing.record')->name('billing.records.revert');
```

সব billing route-এ authentication ও email verification আছে। Settings এবং Access_Off-ও `billing.entry` permission-এ সুরক্ষিত; Access_Off action-এ controller অতিরিক্তভাবে admin role যাচাই করে। Sidebar link-ও সংশ্লিষ্ট permission থাকলেই দেখা যায় ([dashboard.blade.php](resources/views/dashboard/dashboard.blade.php))।

Permission seed: [PermissionSeederTable.php](database/seeders/PermissionSeederTable.php)-তে `billing.entry` এবং `billing.record` যোগ হয়েছে। Fresh installation-এ বিদ্যমান `RolePermissionSeeder` admin role-কে সব permission দেয়। চলমান installation-এ permission তৈরি করে তারপর Role & Permission menu দিয়ে admin role-এ assign করুন:

```powershell
php artisan db:seed --class=PermissionSeederTable
php artisan permission:cache-reset
```

`RolePermissionSeeder` বিদ্যমান standard role-গুলোর permission-ও synchronize করে; তাই live system-এ custom role grants না বুঝে সেটি পুনরায় চালাবেন না।

## Model (Code and Explanation)

ফাইল: [BillingEntry.php](app/Models/BillingEntry.php), [BillingRecord.php](app/Models/BillingRecord.php), [BillingSetting.php](app/Models/BillingSetting.php)

```php
public function activeRecords(): HasMany
{
    return $this->hasMany(BillingRecord::class)->whereNull('reversed_at');
}

public function outstandingAmount(): float
{
    return max(0, round((float) $this->amount - $this->paidAmount(), 2));
}
```

`BillingEntry` মাসিক charge, organization এবং payment relationship রাখে। `BillingRecord` একেকটি receipt এবং কে গ্রহণ/সম্পাদনা/revert করেছেন তা সংরক্ষণ করে। `BillingSetting` singleton marquee text ও display toggle cast করে boolean হিসেবে দেয়।

## Blade Template (Code and Explanation)

ফাইলসমূহ: [entry.blade.php](resources/views/backend/billing/entry.blade.php), [records.blade.php](resources/views/backend/billing/records.blade.php), [transactions.blade.php](resources/views/backend/billing/transactions.blade.php), [dashboard.blade.php](resources/views/dashboard/dashboard.blade.php)

```blade
<form action="{{ route('billing.generate') }}" method="POST">
    @csrf
    <input type="month" name="billing_month" required>
    <input type="number" name="amount" min="0.01" step="0.01" required>
    <input type="date" name="due_date" required>
    <button type="submit">Generate Bill</button>
</form>
```

`BillingEntry` view-তে মাস/amount/due date দিয়ে bill তৈরি, `BillPaid` payment capture, marquee text/toggle সম্পাদনা এবং বকেয়া bill থাকলে admin-এর `Access_Off` action আছে। `BillingRecord` view-তে bill-গুলো মাসভিত্তিক সাজানো; receipt edit বা revert করা যায় এবং revert history থেকে মুছে ফেলা হয় না।

`transactions.blade.php`-তে From/To billing month filter, bill/paid/due summary এবং শুধু billing entry ও সংশ্লিষ্ট receipt details-এর তালিকা আছে; unpaid bill-ও দেখায়।

```blade
<form action="{{ route('billing.transactions') }}" method="GET">
    <input type="month" name="from_month" value="{{ $fromMonth }}">
    <input type="month" name="to_month" value="{{ $toMonth }}">
    <button type="submit">Filter</button>
</form>
```

Dashboard marquee-তে তিন মাসের due date পার হওয়া balance স্বয়ংক্রিয়ভাবে যোগ হয়:

```blade
@if ($marqueeEnabled)
    <marquee behavior="scroll" direction="left" scrollamount="5"
        style="color: red; font-weight: bold;">
        @if (filled($marqueeMessage))
            {{ $marqueeMessage }}
        @endif
        @foreach ($overdueBillingEntries ?? collect() as $overdueEntry)
            &nbsp;&nbsp;|&nbsp;&nbsp;{{ $overdueEntry->billing_month->format('F Y') }} bill overdue:
            ৳{{ number_format($overdueEntry->outstandingAmount(), 2) }}
        @endforeach
    </marquee>
@endif
```

`ShowMarqueeAlert` বন্ধ থাকলে custom message এবং স্বয়ংক্রিয় due notice—দুটিই লুকানো থাকে। কোনো settings record না থাকলে আগের dashboard message-টিই প্রাথমিক বার্তা হিসেবে থাকে।

### Migration ও যাচাই

```powershell
php artisan migrate
php artisan test tests/Feature/BillingTest.php
```

বর্তমান PHP runtime-এ `pdo_sqlite` driver অনুপস্থিত; তাই `BillingTest` চালানো যায়নি। Configured MySQL database-এ test চালানো হয়নি, কারণ test suite-এর `RefreshDatabase` ওই database পরিবর্তন/মুছে ফেলতে পারে।
