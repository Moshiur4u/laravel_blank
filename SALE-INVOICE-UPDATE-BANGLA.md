# আজকের Sales Invoice Update — কোড ও ব্যাখ্যা

এই নথিতে আজকের বিক্রয় ইনভয়েসে পণ্য, পরিমাণ ও বিক্রয়মূল্য পরিবর্তনের জন্য যোগ করা কোড, তার অবস্থান এবং হিসাবের নিয়ম দেওয়া হয়েছে। পরিবর্তনটি transaction-এর মধ্যে invoice items, product stock, customer ledger, walk-in receipt voucher এবং dashboard-এ ব্যবহৃত invoice totals সিঙ্ক করে।

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/SaleController.php`

```php
public function editInvoice($id)
{
    $invoice = SaleInvoice::with(['items.product', 'customer'])->findOrFail($id);
    abort_unless(
        auth()->user()?->can('Update_today_Invoice') && $invoice->invoice_date?->isToday(),
        403
    );

    $restockByProduct = $invoice->items
        ->whereNotNull('product_id')
        ->groupBy('product_id')
        ->map(fn($items) => $items->sum('quantity'));
    $products = Product::query()
        ->where(function ($query) use ($restockByProduct) {
            $query->whereRaw('CAST(unit AS DOUBLE) > 0')
                ->orWhereIn('id', $restockByProduct->keys());
        })
        ->orderBy('productName')
        ->get();
    $ledger = CustomerLedger::query()->where('sale_invoice_id', $invoice->id)->first();
    $invoices = SaleInvoice::with('employee')->latest('invoice_date')->limit(10)->get();
    $defaultTerms = self::DEFAULT_TERMS;
    $vatPercentage = (float) $invoice->subtotal > (float) $invoice->discount_amount
        ? round((float) $invoice->vat_amount / ((float) $invoice->subtotal - (float) $invoice->discount_amount) * 100, 2)
        : 0;
    $aitPercentage = (float) $invoice->subtotal > (float) $invoice->discount_amount
        ? round((float) $invoice->ait_amount / ((float) $invoice->subtotal - (float) $invoice->discount_amount) * 100, 2)
        : 0;

    return view('backend.Sales.salesIndex', compact(
        'invoice',
        'products',
        'restockByProduct',
        'ledger',
        'invoices',
        'defaultTerms',
        'vatPercentage',
        'aitPercentage'
    ));
}

public function updateInvoice(Request $request, $id)
{
    $validated = $request->validate([
        'discount_type' => 'required|in:amount,percentage',
        'discount_value' => 'required|numeric|min:0',
        'vat_percentage' => 'required|numeric|min:0|max:100',
        'ait_percentage' => 'required|numeric|min:0|max:100',
        'amount_paid' => 'required|numeric|min:0',
        'notes' => 'nullable|string|max:2000',
        'terms_conditions' => 'nullable|string|max:5000',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|integer|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0|max:9999999999999.99',
    ]);

    $invoice = app(SaleInvoiceUpdateService::class)->update(
        (int) $id,
        $validated['items'],
        $validated
    );

    return redirect()->route('sales.invoice', $invoice->id)
        ->with('success', "Invoice {$invoice->invoice_no} was updated; stock, ledger, vouchers, and dashboard totals were synchronized.");
}
```

`editInvoice` একই `resources/views/backend/Sales/salesIndex.blade.php` view-তে invoice পাঠায়; ফলে Create Sale-এর layout ও JavaScript-ই edit mode-এ ব্যবহৃত হয়। Controller পুরোনো invoice-এর product-গুলোকেও edit form-এর stock তালিকায় রাখে, বর্তমান stock শূন্য হলেও। পেজ খোলার সময় permission ও invoice-এর তারিখ যাচাই হয়; একই যাচাই update service-এ transaction-এর ভেতরও আছে। Service-এর নাম `app/Support/SaleInvoiceUpdateService.php`। সেখানে:

- পূর্বের বিক্রিত quantity মজুতে ফেরত যোগ করে নতুন quantity যাচাই এবং stock-এর নিট পরিবর্তন lock করে।
- sale item-গুলো নতুন পণ্য, quantity ও unit price দিয়ে প্রতিস্থাপন করে; form-এ দেওয়া discount, VAT ও AIT দিয়ে invoice subtotal, discount, tax, total এবং due আবার হিসাব করে।
- Amount Received-এ দেওয়া নতুন অঙ্ক দিয়ে invoice-এর amount_paid এবং due আপডেট করে; নতুন payment invoice total-এর বেশি হলে update প্রত্যাখ্যান করে।
- Customer invoice-এর ক্ষেত্রে আগের কিস্তির history অক্ষত রেখে ledger-এর মোট payment ও due পুনর্গণনা করে; শুধু invoice তৈরির সময়কার receipt voucher পরিবর্তন করে।
- Walk-in invoice-এর receipt voucher, amount received ও due একসঙ্গে সিঙ্ক করে; edit mode-এ partial amount received রাখা যায়।
- Customer ও আগের due অপরিবর্তিত থাকে; Amount Received, Notes ও Terms & Conditions সম্পাদনা করা যায়।
- Dashboard-এর KPI/chart বিদ্যমান invoice/ledger/voucher table থেকেই হিসাব হয়; তাই cache invalidation বা dashboard schema পরিবর্তন লাগে না।
- আগে product return হয়ে থাকলে সেই invoice update প্রত্যাখ্যান করে, যাতে return history ও stock ভুল না হয়। নতুন মোট ইতিমধ্যে আদায় করা টাকার চেয়ে কম হলেও update প্রত্যাখ্যান হয়।

Stock যাচাই ও পরিবর্তনের মূল অংশ:

```php
$availableStock = (float) ($product?->unit ?? 0)
    + (int) $oldQuantities->get($productId, 0);

if (! $product || $availableStock < $quantity) {
    throw ValidationException::withMessages([
        'items' => ($product?->display_name ?? 'A selected product') . ' does not have enough stock.',
    ]);
}

$product->unit = (string) (
    (float) $product->unit
    + (int) $oldQuantities->get($productId, 0)
    - (int) $newQuantities->get($productId, 0)
);
$product->save();
```

Walk-in invoice-এর linked voucher-ও নতুন invoice total-এর সঙ্গে সিঙ্ক হয়:

```php
if ($vouchers->isEmpty()) {
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customerName,
        'voucher_date' => today(),
        'amount' => number_format($paymentAmount, 2, '.', ''),
        'reference' => $invoice->invoice_no,
        'description' => $description,
        'created_by' => $creator?->id ?? $invoice->user_id,
        'created_by_name' => $creator?->name ?? $invoice->employee?->name ?? 'System',
        'voucherable_type' => SaleInvoice::class,
        'voucherable_id' => $invoice->id,
    ]);
} else {
    $firstVoucher = $vouchers->first();
    $otherAmounts = (float) $vouchers->skip(1)->sum('amount');
    $firstVoucher->update([
        'amount' => number_format($paymentAmount - $otherAmounts, 2, '.', ''),
    ]);
}
```

Amount Received পরিবর্তনের সময় customer-এর পূর্ববর্তী কিস্তি বাদ না দিয়ে যোগ করে নতুন due নির্ণয় করা হয়:

```php
$paymentAmount = round((float) ($attributes['amount_paid'] ?? $invoice->amount_paid), 2);
$additionalPayments = $ledger
    ? round((float) $ledger->payments()->sum('amount'), 2)
    : 0;
$totalPayments = round($paymentAmount + $additionalPayments, 2);

if ($totalPayments > $invoiceTotal) {
    throw ValidationException::withMessages([
        'amount_paid' => 'The invoice total cannot be lower than the amount already received.',
    ]);
}

$invoice->update([
    'amount_paid' => $paymentAmount,
    'due_amount' => round($invoiceTotal - $totalPayments, 2),
]);

if ($ledger) {
    $ledger->update([
        'payment_amount' => $totalPayments,
        'due_amount' => round($invoiceTotal - $totalPayments, 2),
    ]);
    $this->synchronizeCustomerInitialVoucher(
        $invoice,
        $ledger,
        $previousInitialPayment,
        $paymentAmount
    );
}
```

`synchronizeCustomerInitialVoucher` পুরোনো initial amount-এর voucher-টি নতুন amount দিয়ে update করে। Initial amount শূন্য থেকে বাড়লে voucher তৈরি হয়, শূন্যে কমলে invoice-এর initial voucher সরানো হয়; পরবর্তী ledger payment history-র voucher অপরিবর্তিত থাকে।

Permission-এর নাম `Update_today_Invoice`; এটি যোগ করা হয়েছে:

```php
// database/seeders/PermissionSeederTable.php
'Update_today_Invoice',

// database/seeders/RolePermissionSeeder.php — sales ও manager preset
'sales.create',
'Update_today_Invoice',
```

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন হয়নি। বিদ্যমান `sale_invoices`, `sale_items`, `products`, `customer_ledgers` এবং `voucher_entries` টেবিলেই update সম্পন্ন হয়।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php`

```php
Route::get('/sales/invoice/{id}/edit', [SaleController::class, 'editInvoice'])
    ->middleware('permission:Update_today_Invoice')
    ->name('sales.invoice.edit');

Route::put('/sales/invoice/{id}', [SaleController::class, 'updateInvoice'])
    ->middleware('permission:Update_today_Invoice')
    ->name('sales.invoice.update');
```

`GET` edit form দেখায় এবং `PUT` update জমা দেয়। দুটো route-তেই পৃথক permission middleware আছে; service-এ আজকের তারিখ যাচাই থাকায় route middleware পাশ করলেও পুরোনো invoice বদলানো যাবে না।

Invoice দেখা ও sales তালিকার অনুমতিতেও নতুন permission-টি যোগ হয়েছে:

```php
->middleware('permission:sales.create|sales.view|Voide_today_Invoice|Voide_anyday_Invoice|Update_today_Invoice')
->name('sales.index');

->middleware('permission:sales.report|Voide_today_Invoice|Voide_anyday_Invoice|Update_today_Invoice')
->name('sales.invoice');
```

## Model (Code and Explanation)

কোনো model পরিবর্তন হয়নি। `SaleInvoice::items()`, `SaleItem`, `Product`, `CustomerLedger` ও `VoucherEntry`-এর বিদ্যমান সম্পর্ক এবং কলাম ব্যবহার করা হয়েছে। নতুন field বা database migration-এর প্রয়োজন হয়নি।

## Blade Template (Code and Explanation)

**Invoice action:** `resources/views/backend/Sales/salesInvoice.blade.php`

```blade
@can('Update_today_Invoice')
    @if ($invoice->invoice_date?->isToday())
        <a href="{{ route('sales.invoice.edit', $invoice->id) }}" class="btn btn-outline-primary">
            <i class="bx bx-edit me-1"></i>Update Invoice
        </a>
    @endif
@endcan
```

আজকের invoice এবং উপযুক্ত permission থাকলেই `Update Invoice` action দেখা যায়।

**Create Sale-এর একই form edit mode-এ:** `resources/views/backend/Sales/salesIndex.blade.php`-এ `$editingInvoice` থাকলে পুরোনো invoice-এর product, quantity, unit price, discount, VAT, AIT, Amount Received, notes ও terms বসানো হয়। একই view-এর product search, stock validation ও add/remove item আচরণও থাকে। Customer ও আগের due edit mode-এ অপরিবর্তিত রাখা হয়।

Form-এর মূল অংশ:

```blade
<form id="saleForm"
    action="{{ $editingInvoice ? route('sales.invoice.update', $editingInvoice->id) : route('sales.store') }}"
    method="POST">
    @csrf
    @if ($editingInvoice)
        @method('PUT')
    @endif
    @foreach ($saleItems as $index => $oldItem)
        {{-- এখানে Create Sale-এর product search ও stock cell-সহ একই product row থাকে। --}}
        <input name="items[{{ $index }}][quantity]" class="quantity-input"
            value="{{ $oldItem['quantity'] ?? 0 }}">
        <input name="items[{{ $index }}][unit_price]" class="unit-price-input"
            value="{{ old("items.$index.unit_price", $oldProduct?->price ?? 0) }}">
    @endforeach
    <input type="number" name="amount_paid" id="amount_paid"
        value="{{ old('amount_paid', $receivedAmount) }}">
    <button type="submit">
        {{ $editingInvoice ? 'Update Invoice' : 'Checkout & Print Invoice' }}
    </button>
</form>
```

Edit mode-এ form customer/walk-in confirmation ধাপ বাদ দিয়ে সরাসরি update route-এ submit করে। Amount Received পরিবর্তনযোগ্য; JavaScript-এ updateTotals invoice total, received amount ও নতুন due দেখায়। পুরোনো invoice-এর বিক্রীত quantity browser-side stock validation-এ ফেরত যোগ হয়, যাতে একই পণ্য/quantity পুনরায় বেছে নিলে ভুল stock error না আসে।

Update শেষে invoice page-এ ফলাফল দেখানোর জন্য `salesInvoice.blade.php`-এ success alert যোগ হয়েছে; sales তালিকায় update permission থাকলেও recent invoices section দেখা যায়:

```blade
@if (session('success'))
    <div class="alert alert-success no-print" role="status">{{ session('success') }}</div>
@endif

@canany(['sales.view', 'Voide_today_Invoice', 'Voide_anyday_Invoice', 'Update_today_Invoice'])
    {{-- Recent Invoices --}}
@endcanany
```

## Permission সক্রিয় করা

নতুন permission ইতিমধ্যে seed করা database-এ যোগ করতে `PermissionSeederTable` চালান। `sales` ও `manager` preset-এ permission sync করার জন্য `RolePermissionSeeder`-ও চালান। `admin` preset সব permission স্বয়ংক্রিয়ভাবে পায়।

## পরীক্ষা

`tests/Feature/SalePaymentTest.php`-এ আজকের invoice update, stock, walk-in voucher এবং dashboard revenue যাচাইয়ের feature test যোগ হয়েছে। বর্তমান পরিবেশে test runner-এর SQLite PDO driver অনুপস্থিত থাকায় feature test চালানো যায়নি; সংশ্লিষ্ট PHP ফাইলগুলো lint এবং `git diff --check` সফল হয়েছে।

```php
$this->actingAs($employee)
    ->put(route('sales.invoice.update', $invoice->id), [
        'discount_type' => 'amount',
        'discount_value' => 0,
        'vat_percentage' => 0,
        'ait_percentage' => 0,
        'amount_paid' => 300,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 150],
        ],
    ])
    ->assertRedirect(route('sales.invoice', $invoice->id))
    ->assertSessionHas('success');

expect((float) $invoice->fresh()->total_amount)->toBe(450.0)
    ->and((float) $invoice->fresh()->amount_paid)->toBe(300.0)
    ->and((float) $invoice->fresh()->due_amount)->toBe(150.0)
    ->and((float) $product->fresh()->unit)->toBe(7.0)
    ->and((float) $voucher->fresh()->amount)->toBe(300.0);
```

Customer invoice-এর test-এ amount received পরিবর্তনের পর ledger-এর aggregate payment/due এবং initial receipt voucher update, কিন্তু পরবর্তী receipt voucher অপরিবর্তিত আছে—এগুলোও যাচাই করা হয়েছে।
