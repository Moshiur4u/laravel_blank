# লেজার পেমেন্ট ও ভাউচার পরিবর্তনের নথি

এই নথিতে গ্রাহক/সরবরাহকারী লেজারের ভিউ, পেমেন্ট থেকে স্বয়ংক্রিয় ভাউচার তৈরি, সরবরাহকারী পেমেন্টের দায়িত্বপ্রাপ্ত ব্যবহারকারীর নাম, এবং সংশ্লিষ্ট ডাটাবেস পরিবর্তন ব্যাখ্যা করা হয়েছে। স্বয়ংক্রিয় ভাউচার বিদ্যমান `voucher_entries` টেবিলে সংরক্ষিত হয়; তাই দৈনিক Debit/Credit Voucher Report-এ তারিখ, ভাউচার হেড, ধরন ও পরিমাণসহ দেখা যায়।

## Controller (Code and explanation) —

### `app/Http/Controllers/SupplierledgerController.php`

সরবরাহকারী লেজারে প্রাথমিক পেমেন্ট থাকলে সেটি Debit voucher হিসেবে লেখা হয়। আলাদা payment action-এ payment history, ledger-এর payment/due এবং voucher একই database transaction-এর মধ্যে লেখা হয়:

```php
if ((float) $ledger->payment > 0) {
    $supplier = Supplier::findOrFail($ledger->supplier_id);
    VoucherEntry::createWithCode([
        'voucher_type' => 'debit',
        'expense_income_type' => 'Supplier Payment ('.$supplier->name.')',
        'voucher_date' => today(),
        'amount' => number_format((float) $ledger->payment, 2, '.', ''),
        'reference' => $ledger->invoice_id,
        'description' => 'Payment to supplier '.$supplier->name,
        'created_by' => $creator?->id,
        'created_by_name' => $creator?->name ?? 'System',
    ]);
}
```

অতিরিক্ত payment-এর ক্ষেত্রে তারিখটি voucher date, payment-টির অঙ্কটি voucher amount, এবং supplier-এর নামসহ `Supplier Payment (নাম)` voucher head হিসেবে ব্যবহৃত হয়। payment history-তে লগ-ইন করা ব্যবহারকারীর ID-ও রাখা হয়। Invoice তৈরির সময় `payments.createdBy` eager-load করা হয়, যাতে print view-তে প্রতিটি payment-এর নাম দেখানো যায়।

Supplier ledger মুছে দেওয়ার সময় সেই ledger-এর সব linked voucher এবং পুরোনো, link-ছাড়া voucher-গুলোও একই database transaction-এ মুছে যায়। পুরোনো record মেলাতে supplier, voucher head, payment-এর তারিখ/পরিমাণ ও reference মিলিয়ে দেখা হয়।

এই অতিরিক্ত payment-এর voucher তৈরির অংশ:

```php
VoucherEntry::createWithCode([
    'voucher_type' => 'debit',
    'expense_income_type' => 'Supplier Payment ('.$supplier->name.')',
    'voucher_date' => $validated['payment_date'],
    'amount' => number_format($amountInCents / 100, 2, '.', ''),
    'reference' => $ledger->invoice_id,
    'description' => 'Payment to supplier '.$supplier->name,
    'created_by' => $creator?->id,
    'created_by_name' => $creator?->name ?? 'System',
]);
```

### `app/Http/Controllers/CustomerController.php`

Customer ledger entry-তে payment amount শূন্যের বেশি হলে Credit voucher তৈরি হয়। ফলে প্রাথমিক cash/full payment, standalone receipt এবং sale-এর বকেয়ায় পরে পাওয়া payment—সবগুলোই report-এ অন্তর্ভুক্ত হয়:

```php
if ($paymentCents > 0) {
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => $validated['sale_date'],
        'amount' => number_format($paymentCents / 100, 2, '.', ''),
        'reference' => $validated['invoice_no'] ?? null,
        'description' => 'Payment received from customer '.$customer->name,
        'created_by' => $creator?->id,
        'created_by_name' => $creator?->name ?? 'System',
    ]);
}
```

Sale-এর বকেয়ায় পরবর্তী payment-এর ক্ষেত্রে `$validated['payment_date']` এবং `$amountCents` ব্যবহার করে একইভাবে Credit voucher লেখা হয়:

```php
VoucherEntry::createWithCode([
    'voucher_type' => 'credit',
    'expense_income_type' => $customer->name,
    'voucher_date' => $validated['payment_date'],
    'amount' => number_format($amountCents / 100, 2, '.', ''),
    'reference' => $ledger->invoice_no,
    'description' => 'Payment received from customer '.$customer->name,
    'created_by' => $creator?->id,
    'created_by_name' => $creator?->name ?? 'System',
]);
```

প্রাথমিক ledger entry-তে একই কাঠামোতে `sale_date`, `invoice_no` এবং হিসাব করা `paymentCents` ব্যবহার করা হয়। কোনো payment না থাকলে voucher তৈরি হয় না। Ledger ও voucher একই transaction-এ থাকায় একটি লেখা ব্যর্থ হলে অন্যটি একা সংরক্ষিত থাকে না।

Customer ledger মুছলে তার payment history-সহ সব linked voucher একই transaction-এ মুছে যায়। Migration-এর আগের voucher-ও customer, head, reference এবং payment-এর তারিখ/পরিমাণ মিলে গেলে সরানো হয়।

### `app/Http/Controllers/SaleController.php`

Sales মডিউলে invoice payment positive হলে Credit voucher লেখা হয়। সংরক্ষিত customer থাকলে তার নাম voucher head হয়; নামবিহীন walk-in sale-এ `Walk-in Customer` ব্যবহৃত হয়। Voucher-এর reference হিসেবে invoice নম্বর থাকে। এই কাজ invoice, sale items ও customer ledger লেখার একই transaction-এ সম্পন্ন হয়।

```php
if ($paymentAmount > 0) {
    $customerName = $customer?->name ?? $validated['customer_name'] ?? 'Walk-in Customer';
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customerName,
        'voucher_date' => today(),
        'amount' => number_format($paymentAmount, 2, '.', ''),
        'reference' => $invoice->invoice_no,
        'description' => 'Payment received from customer '.$customerName,
        'created_by' => $creator?->id,
        'created_by_name' => $creator?->name ?? 'System',
    ]);
}
```

### `app/Http/Controllers/VoucherEntryController.php`

সাধারণভাবে তৈরি voucher-ও একই code-generation পদ্ধতি ব্যবহার করে:

```php
$voucher = DB::transaction(function () use ($validated, $creator) {
    return VoucherEntry::createWithCode([
        ...$validated,
        'amount' => number_format((float) $validated['amount'], 2, '.', ''),
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
    ]);
});
```

এতে হাতে তৈরি এবং স্বয়ংক্রিয়—দুই ধরনের voucher-এই একই `DBV`/`CRV` code format বজায় থাকে।

## Migration (Code and explanation) —

### `database/migrations/2026_10_26_000001_add_created_by_to_supplier_ledger_payments_table.php`

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_ledger_payments', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('amount')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_ledger_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
```

### `database/migrations/2026_10_26_000003_add_voucherable_to_voucher_entries_table.php`

Voucher-কে তার উৎস ledger row বা sales invoice-এর সঙ্গে সরাসরি যুক্ত করার nullable polymorphic key যোগ করে। পুরোনো voucher-গুলোর key `null` থাকে; deletion flow সেগুলোর জন্য সীমিত metadata-based matching ব্যবহার করে।

```php
Schema::table('voucher_entries', function (Blueprint $table) {
    $table->nullableMorphs('voucherable');
});
```

`created_by` সরবরাহকারীর প্রতিটি payment-এর জন্য সংশ্লিষ্ট `users.id` ধরে রাখে। ব্যবহারকারী পরে মুছে গেলেও payment history থাকে, শুধু foreign key ফাঁকা হয়। Migration rollback-এ `dropConstrainedForeignId('created_by')` কলাম ও constraint সরায়। পুরোনো payment row-গুলোর জন্য কলামটি nullable রাখা হয়েছে।

### `database/migrations/2026_10_26_000002_expand_voucher_head_column.php`

`voucher_entries.expense_income_type` কলামের দৈর্ঘ্য ১০০ থেকে ২৫৫ করা হয়েছে, যাতে customer-এর দীর্ঘ নাম অথবা `Supplier Payment (Supplier Name)` সম্পূর্ণভাবে সংরক্ষিত হয়। Rollback-এ কলামটি আগের দৈর্ঘ্যে ফেরে।

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->string('expense_income_type', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->string('expense_income_type', 100)->change();
        });
    }
};
```

## Model (Code and explanation) —

### `app/Models/VoucherEntry.php`

```php
public static function createWithCode(array $attributes): self
{
    $voucher = static::create([
        ...$attributes,
        'voucher_code' => null,
    ]);

    $prefix = $voucher->voucher_type === 'debit' ? 'DBV' : 'CRV';
    $voucher->update([
        'voucher_code' => $prefix.'-'.Carbon::parse($voucher->voucher_date)->format('Ymd')
            .'-'.str_pad((string) $voucher->id, 6, '0', STR_PAD_LEFT),
    ]);

    return $voucher;
}
```

প্রথমে voucher row তৈরি করে তার primary key-ভিত্তিক অনন্য code তৈরি করা হয়। Debit-এ `DBV`, Credit-এ `CRV` prefix থাকে; তারিখ ও ID-ও code-এ থাকে। `voucherable_type` এবং `voucherable_id` payment-এর উৎস সংরক্ষণ করে। `deleteForSource()` সরাসরি linked voucher-গুলো এবং পুরোনো voucher-এর জন্য নির্দিষ্ট তারিখ, পরিমাণ, head, description ও reference মেলা row-গুলো মুছে দেয়।

```php
public function voucherable(): MorphTo
{
    return $this->morphTo();
}
```

### `app/Models/SupplierLedgerPayment.php`

`created_by` mass-assignable করা হয়েছে এবং ব্যবহারকারীর নাম পাওয়ার জন্য সম্পর্ক যোগ হয়েছে:

```php
protected $fillable = [
    'supplierledger_id',
    'payment_date',
    'amount',
    'created_by',
    'voucherable_type',
    'voucherable_id',
];

public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}
```

### Seeder

কোনো seeder পরিবর্তন করা হয়নি। ভাউচার সংরক্ষণ ও রিপোর্টে দেখানোর জন্য role, permission, lookup data বা seed data পরিবর্তন প্রয়োজন ছিল না।

## Blade (Code and explanation) —

### `resources/views/backend/customer/customerLedger.blade.php`

Customer ledger-এর summary card-এ supplier ledger-এর মতো initials avatar এবং responsive summary cards যোগ করা হয়েছে। Entry form ও ledger history-কে পূর্ণ-প্রস্থ কার্ডে সাজানো হয়েছে। Ledger table-এ supplier view-এর মতো striped/bordered table, dark header, row number এবং customer-সহ “Ledger History” শিরোনাম আছে। Customer-specific sale/payment, receiver, invoice, edit/delete actions অক্ষত রাখা হয়েছে।

```blade
<div class="text-center col-md-1">
    <div style="width: 80px; height: 80px; border-radius: 50%; ...">
        {{ strtoupper(substr($customer->name, 0, 2)) }}
    </div>
</div>
...
<table id="myTable" class="table table-striped table-bordered display align-middle">
    <thead class="table-dark">
        <tr>
            <th>No</th>
            <th>Date</th>
            ...
        </tr>
    </thead>
</table>
```

### `resources/views/backend/Supplier/supplierInvoice.blade.php`

প্রিন্টযোগ্য Payment History-তে পরিশোধকারী ব্যবহারকারীর নামের কলাম দেখানো হয়:

```blade
<th>Payment Date</th>
<th>Paid By</th>
<th class="text-end">Amount Paid</th>
```

প্রতিটি row-তে নামটি `createdBy` relation থেকে আসে; আগের payment-এ ব্যবহারকারীর ID না থাকলে `N/A` দেখায়:

```blade
<td>{{ $paymentRecord->payment_date->format('d M Y') }}</td>
<td>{{ $paymentRecord->createdBy->name ?? 'N/A' }}</td>
<td class="text-end">৳{{ number_format((float) $paymentRecord->amount, 2) }}</td>
```

### Seeders

Seeder-এ কোনো কোড যোগ বা পরিবর্তন করা হয়নি।

## পরীক্ষা

`tests/Feature/CustomerLedgerTest.php` এবং `tests/Feature/SalePaymentTest.php`-এ customer payment ও sales receipt থেকে Credit voucher তৈরির assertion যোগ হয়েছে। `tests/Feature/SupplierPaymentVoucherTest.php` supplier payment-এর Debit voucher, voucher report-এ উপস্থিতি এবং print view-তে payment-কারী ব্যবহারকারীর নাম যাচাই করে।
