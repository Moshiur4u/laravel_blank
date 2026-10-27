# ড্যাশবোর্ড, লেজার ও ইনভেন্টরি পরিবর্তনের নথি

## Controller (code and explanation) —

### ড্যাশবোর্ডের হিসাব

ফাইল: `app/Http/Controllers/DashboardConrtoller.php`

```php
$revenue = (float) $invoiceRevenue
    + (float) $customerRevenue
    + (float) $nonCustomerCreditTotal;
$due = $invoiceDue + $customerDue;
$expenses = (float) $employeeVoucherTotal + (float) $debitVoucherTotal;
$purchaseCosts = (float) $purchaseTotal + (float) $nonSupplierDebitTotal;
$profit = round(
    (float) $revenue - $purchaseCosts,
    2
);
```

প্রতিটি হিসাব দৈনিক, সাপ্তাহিক, মাসিক ও বার্ষিক তারিখসীমায় আলাদাভাবে চলে। Revenue-তে বিক্রয় ইনভয়েস, invoice-এর সঙ্গে যুক্ত নয় এমন customer-ledger sale এবং customer ledger receipt নয় এমন credit voucher যোগ হয়; invoice-linked customer-ledger sale দ্বিতীয়বার যোগ হয় না। Total Due-তে customer ledger-এ থাকা বিক্রয়ের বর্তমান due যোগ হয়, আর customer ledger row দিয়ে ইতিমধ্যে মেলানো invoice-এর পুরোনো due দ্বিতীয়বার ধরা হয় না। Expense-এ employee payment voucher আগের মতো এবং সব debit voucher অন্তর্ভুক্ত। Net Profit: Total Revenue − (Total Purchase + Supplier Payment category/ supplier ledger-এ পোস্ট debit বাদে অন্যান্য debit voucher)। যেমন Employee category-র Mobile Bill এবং Printer Servicing profit-এর ক্রয়/ব্যয় অংশে যোগ হয়; supplier payment দুবার গণনা ঠেকাতে বাদ থাকে। Card ও chart-এ একই সূত্র ব্যবহৃত হয়েছে।

### লেজার পেমেন্ট সম্পাদনা

ফাইলসমূহ: `app/Http/Controllers/SupplierledgerController.php`, `app/Http/Controllers/CustomerController.php`

```php
$newAmountCents = (int) round((float) $validated['amount'] * 100);
$dueCents = (int) round((float) $ledger->due * 100);

if ($newAmountCents > $dueCents + $oldAmountCents) {
    throw ValidationException::withMessages([
        'amount' => 'Payment cannot be greater than the current due plus this receipt.',
    ]);
}
```

Supplier ও customer receipt update একই database transaction-এ সম্পন্ন হয়। তারিখ ও পরিমাণ যাচাই করে payment history row, ledger-এর paid/due balance এবং ওই receipt-এর সঙ্গে সম্পর্কিত voucher একসঙ্গে হালনাগাদ করা হয়। পরিবর্তিত payment-এ বকেয়া অতিক্রম করলে validation error হয়। দুই route-ই আলাদা edit permission দিয়ে সুরক্ষিত।

### ইনভেন্টরি রিপোর্ট

ফাইল: `app/Http/Controllers/ProductInventoryReportController.php`

```php
return [
    'id' => $product->id,
    'name' => $product->productName,
    // অন্যান্য inventory field
];
```

রিপোর্টে Product Index-এর মতো `SKU: #PRD-00001` বিন্যাসে product ID দেখানো হয়। একই বিন্যাস CSV export-এও ব্যবহৃত হয়।

## Migration (code and explanation) —

এই পরিবর্তনের জন্য নতুন migration প্রয়োজন হয়নি। পেমেন্টের তারিখ ও পরিমাণ রাখার schema আগেই আছে:

ফাইল: `database/migrations/2026_10_12_163000_create_supplier_ledger_payments_table.php`

```php
$table->foreignId('supplierledger_id')->constrained('supplierledgers')->cascadeOnDelete();
$table->date('payment_date');
$table->decimal('amount', 10, 2);
```

ফাইল: `database/migrations/2026_10_17_000002_create_customer_ledger_payments_table.php`

```php
$table->foreignId('customer_ledger_id')->constrained('customer_ledgers')->cascadeOnDelete();
$table->date('payment_date');
$table->decimal('amount', 15, 2);
$table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
$table->text('notes')->nullable();
```

Voucher-এর polymorphic ledger link-ও বিদ্যমান `2026_10_26_000003_add_voucherable_to_voucher_entries_table.php` migration-এ তৈরি হয়েছে। নতুন edit access বা Product ID সংরক্ষণে database schema বদলাতে হয় না।

## Model (code and explanation) —

ফাইলসমূহ: `app/Models/SupplierLedgerPayment.php`, `app/Models/CustomerLedgerPayment.php`, `app/Models/Supplierledger.php`, `app/Models/CustomerLedger.php`

```php
public function payments(): HasMany
{
    return $this->hasMany(CustomerLedgerPayment::class)
        ->orderByDesc('payment_date')
        ->orderByDesc('id');
}
```

Ledger model-এর `payments()` relation-এ receipt history থাকে; payment model-এ date ও amount-এর decimal/date cast আগে থেকেই সংজ্ঞায়িত। এই feature-এ model schema বা relation পরিবর্তন করা হয়নি; controller বিদ্যমান relation ও `VoucherEntry`-র voucherable link ব্যবহার করেছে।

## Blade (code and explanation) —

### Dashboard কার্ড

ফাইল: `resources/views/dashboard/dashboard-body.blade.php`

```blade
<h4 class="my-1 text-{{ $card['color'] }} fw-bold">
    ৳{{ number_format(round($metrics['monthly'][$metricKey])) }}
</h4>
<p class="mb-0 font-13 text-muted">
    Weekly ৳{{ number_format(round($metrics['weekly'][$metricKey])) }}
    <span class="mx-1">|</span>
    Daily ৳{{ number_format(round($metrics['daily'][$metricKey])) }}
</p>
```

সব KPI-তে এখন মাসিক মোট বড় করে এবং Weekly/Daily মোট নিচে দেখা যায়; Net Profit-ও এই একই বিন্যাসে প্রদর্শিত।

### Payment history সম্পাদনা

ফাইলসমূহ: `resources/views/backend/supplier/supplierledger.blade.php`, `resources/views/backend/customer/customerLedger.blade.php`

```blade
@can('Customer.ledger.payment.edit')
    <form action="{{ route('customer.ledger.payments.update', $payment->id) }}" method="POST">
        @csrf
        @method('PUT')
        {{-- তারিখ, পরিমাণ, receiver ও notes সম্পাদনার field --}}
    </form>
@endcan
```

ইতিহাসের receipt row-তে edit form কেবল সংশ্লিষ্ট `*.ledger.payment.edit` permission-ধারী ব্যবহারকারীর জন্য দেখা যায়। Server-side route middleware-ও একই permission যাচাই করে।

### Product ID

ফাইল: `resources/views/backend/reports/productinventory.blade.php`

```blade
<th scope="col">Product ID</th>
...
<td>SKU: #PRD-{{ str_pad((string) $row['id'], 5, '0', STR_PAD_LEFT) }}</td>
```

Inventory Details টেবিলে product name-এর পাশে Product Index-এর মতো SKU পরিচয় দেখা যায়; export-এও একই পরিচয় রাখা হয়েছে।

## Seeder (code and explanation) —

ফাইল: `database/seeders/PermissionSeederTable.php`

```php
'Customer.ledger.payment.edit',
'Supplier.ledger.payment.edit',
```

দুটি পৃথক permission যোগ হয়েছে। এগুলো Sales/Manager preset-এ স্বয়ংক্রিয়ভাবে দেওয়া হয়নি; Role & Permissions পৃষ্ঠায় প্রয়োজনীয় role-এ আলাদাভাবে বরাদ্দ করা যায়। `admin` preset permission seeder চালানোর পর সব permission পায়, আগের preset নীতির মতোই।
