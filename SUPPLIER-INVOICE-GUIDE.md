# Supplier ও Sales Invoice — শেখার নোট

এই নোটে Supplier Ledger row থেকে print করা invoice এবং Sales Invoice-এর মাসভিত্তিক নম্বরের নিয়ম বর্ণনা করা হয়েছে। Supplier invoice-এ বিদ্যমান ledger row-র তথ্য দেখানো হয়; নতুন database field, ledger calculation বা payment method যোগ করা হয়নি। Sales invoice-এর নম্বর `QT-YYYYMM-0001` format-এ তৈরি হয়, যেমন `QT-202611-0001`।

## Controller (Code and Explanation)

### Supplier Ledger Invoice

**ফাইল:** `app/Http/Controllers/SupplierledgerController.php`

```php
public function invoice($id)
{
    $ledger = Supplierledger::with([
        'supplier',
        'product',
        'payments.createdBy',
    ])->findOrFail($id);

    $invoiceNumber = $ledger->invoice_id
        ?: 'SUP-' . str_pad((string) $ledger->id, 6, '0', STR_PAD_LEFT);

    return view(
        'backend.Supplier.supplierInvoice',
        compact('ledger', 'invoiceNumber')
    );
}
```

Route থেকে পাওয়া ID দিয়ে একটি ledger entry ও তার supplier, product এবং payment history load হয়। ID পাওয়া না গেলে `findOrFail` 404 response দেয়। Ledger-এর `invoice_id` থাকলে সেটি invoice number; না থাকলে `SUP-` prefix ও ledger ID দিয়ে শুধু display-এর জন্য স্থায়ী fallback বানানো হয়। Fallback database-এ save হয় না, আর এই GET action ledger পরিবর্তন করে না।

### Sales Invoice নম্বর

**ফাইল:** `app/Http/Controllers/SaleController.php`

```php
$currentMonth = now()->format('Ym');
$latestInvoice = SaleInvoice::query()
    ->where('invoice_no', 'like', "QT-{$currentMonth}-%")
    ->lockForUpdate()
    ->latest('id')
    ->first();

$nextNumber = 1;
if ($latestInvoice) {
    $parts = explode('-', $latestInvoice->invoice_no);
    if (isset($parts[2]) && is_numeric($parts[2])) {
        $nextNumber = (int) $parts[2] + 1;
    }
}

$invoiceNumber = "QT-{$currentMonth}-"
    . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

$invoice = SaleInvoice::create([
    'invoice_no' => $invoiceNumber,
    // অন্যান্য invoice তথ্য
]);
```

Sales তৈরির transaction-এর ভেতরে বর্তমান মাসের (`YYYYMM`) সর্বশেষ sales invoice নম্বর দেখে serial এক বাড়ানো হয়। ওই মাসে আগের invoice না থাকলে serial `0001` থেকে শুরু হয়। `str_pad` serial-কে অন্তত চার অঙ্কে দেখায়। এই পরিবর্তন শুধু Sales Invoice-এর numbering-এ; Supplier Ledger-এর `invoice_id` বা `SUP-` fallback নিয়ম বদলায়নি।

**পরীক্ষা:** `tests/Feature/SalePaymentTest.php`-এর payment test প্রথম ও দ্বিতীয় Sales Invoice-এর নম্বর যথাক্রমে বর্তমান মাসের `QT-YYYYMM-0001` ও `QT-YYYYMM-0002` হয় কি না যাচাই করে। SQLite PHP driver না থাকায় এই পরিবেশে feature test চালানো যায়নি।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য কোনো migration যোগ বা পরিবর্তন করা হয়নি। Supplier Invoice বিদ্যমান Supplier Ledger field ব্যবহার করে। Sales Invoice নম্বর `sale_invoices.invoice_no` field-এ রাখা হয়; বিদ্যমান schema-তেই এই field আছে এবং unique constraint রয়েছে।

```php
// নতুন migration বা database column যোগ করা হয়নি।
```

## Route (Code and Explanation)

**ফাইল:** `routes/web.php`

```php
Route::get('/supplier/ledger/{id}/invoice', [
    SupplierledgerController::class,
    'invoice',
])->middleware('permission:supplier.ledger')
  ->name('supplier.ledger.invoice');
```

এই authenticated route permission middleware দিয়ে সুরক্ষিত। Ledger row-র ID দিয়ে print view খোলে; GET request-এ কোনো তথ্য save বা update হয় না। Sales Invoice numbering-এর জন্য নতুন route লাগেনি; বিদ্যমান sales store route-ই ব্যবহার হয়।

## Model (Code and Explanation)

### Supplier Ledger

**ফাইল:** `app/Models/Supplierledger.php`

```php
protected $fillable = [
    'supplier_id',
    'invoice_id',
    'product_id',
    'quantity',
    'price',
    'total',
    'payment',
    'due',
];

public function supplier()
{
    return $this->belongsTo(Supplier::class);
}

public function product()
{
    return $this->belongsTo(Product::class, 'product_id');
}

public function payments(): HasMany
{
    return $this->hasMany(SupplierLedgerPayment::class, 'supplierledger_id')
        ->orderByDesc('payment_date')
        ->orderByDesc('id');
}
```

Invoice-এর জন্য Model-এ কোনো পরিবর্তন দরকার হয়নি। বিদ্যমান relation দিয়ে supplier, product ও তারিখভিত্তিক payments দেখানো হয়। Model-এ payment method field নেই, তাই invoice-এ payment method দেখানো বা অনুমান করে যোগ করা হয়নি; Amount Paid ও Due বিদ্যমান ledger মান থেকে আসে।

Sales invoice number `SaleInvoice`-এর বিদ্যমান `invoice_no` attribute-এ সংরক্ষিত হয়; Sales Invoice Model-এও কোনো পরিবর্তন করা হয়নি।

## Blade Template (Code and Explanation)

### Supplier Ledger তালিকার Print link

**ফাইল:** `resources/views/backend/Supplier/supplierledger.blade.php`

```blade
<a href="{{ route('supplier.ledger.invoice', $entry->id) }}"
    class="btn btn-sm btn-outline-success" title="Print Invoice">
    <i class="bx bx-printer"></i>
</a>
```

প্রতিটি ledger row-র printer icon সেই entry-র ID দিয়ে printযোগ্য Supplier Invoice খোলে।

### Supplier Invoice

**ফাইল:** `resources/views/backend/Supplier/supplierInvoice.blade.php`

```blade
<div class="border-0 shadow-sm card invoice-print-area">
    {{-- supplier তথ্য, product, payment history ও হিসাব --}}
    <span>Invoice: <strong>{{ $invoiceNumber }}</strong></span>
    <span>{{ $ledger->product->productName ?? 'N/A' }}</span>
    <span>৳{{ number_format($ledger->total ?? 0, 2) }}</span>
    <span>৳{{ number_format($ledger->payment ?? 0, 2) }}</span>
    <span>৳{{ number_format($ledger->due ?? 0, 2) }}</span>
</div>
```

View-তে supplier-এর নাম, logo, ঠিকানা, email ও phone এবং ledger row-র product, quantity, unit purchase price, total, amount paid ও due দেখানো হয়। Logo না থাকলে logo element দেখানো হয় না; logo থাকলে `public/uploads/suppliers/`-এর asset path ব্যবহার করা হয়। Payment history থাকলে payment date, জমাদানকারীর নাম এবং amount-সহ আলাদা তালিকা দেখায়।

`Print Invoice` button `window.print()` চালায়। Print CSS dashboard navigation এবং print button লুকিয়ে invoice-টুকু print করে। `invoice-print-area` class shared invoice print style-এর সঙ্গে যুক্ত।

### Sales Invoice নম্বর দেখানো

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

```blade
<div class="text-muted">
    Invoice: <strong>{{ $invoice->invoice_no }}</strong>
</div>
```

Sales Invoice view-তে Controller-এ তৈরি নম্বর সরাসরি দেখানো হয়। যেমন `QT-202611-0001`; Blade-এ আলাদা করে নম্বর তৈরি বা পরিবর্তন করা হয় না।
