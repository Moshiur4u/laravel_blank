# Sale Invoice Void — পরিবর্তনের বাংলা নথি

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/SaleController.php`  
**স্থান:** `invoice()` মেথডের পর `destroy($id)` মেথড।

```php
public function destroy($id)
{
    $invoiceNo = DB::transaction(function () use ($id) {
        $invoice = SaleInvoice::query()->lockForUpdate()->findOrFail($id);

        if (! $invoice->invoice_date?->isToday()) {
            abort(403, 'Only today’s sale invoices can be voided.');
        }

        $items = $invoice->items()->lockForUpdate()->get();
        $quantities = $items
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn ($productItems) => $productItems->sum('quantity'));
        $products = Product::query()
            ->whereIn('id', $quantities->keys())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product) {
                $product->unit = (string) ((float) $product->unit + $quantity);
                $product->save();
            }
        }

        $ledgers = CustomerLedger::query()
            ->where('sale_invoice_id', $invoice->id)
            ->lockForUpdate()
            ->get();
        $ledgerIds = $ledgers->modelKeys();
        $customerName = $invoice->customer_name ?: 'Walk-in Customer';

        VoucherEntry::query()
            ->where(function ($query) use ($invoice, $ledgerIds, $customerName) {
                $query->where(function ($source) use ($invoice) {
                    $source->where('voucherable_type', SaleInvoice::class)
                        ->where('voucherable_id', $invoice->id);
                });

                if ($ledgerIds !== []) {
                    $query->orWhere(function ($source) use ($ledgerIds) {
                        $source->where('voucherable_type', CustomerLedger::class)
                            ->whereIn('voucherable_id', $ledgerIds);
                    });
                }

                $query->orWhere(function ($legacy) use ($invoice, $customerName) {
                    $legacy->whereNull('voucherable_type')
                        ->whereNull('voucherable_id')
                        ->where('voucher_type', 'credit')
                        ->where('expense_income_type', $customerName)
                        ->where('reference', $invoice->invoice_no)
                        ->where('description', 'Payment received from customer '.$customerName);
                });
            })
            ->lockForUpdate()
            ->delete();

        foreach ($ledgers as $ledger) {
            $ledger->delete();
        }

        $invoice->items()->delete();
        $invoice->delete();

        return $invoice->invoice_no;
    });

    return redirect()->route('sales.index')
        ->with('success', "Invoice {$invoiceNo} was voided; its stock and payment entries were reversed.");
}
```

এই মেথড transaction ব্যবহার করে—কোনো ধাপে ব্যর্থ হলে stock বা আর্থিক রেকর্ডের আংশিক পরিবর্তন commit হবে না। ইনভয়েসটি আজকের না হলে server-side থেকে `403` দেওয়া হয়। বিক্রীত পণ্যের পরিমাণ stock-এ ফেরত যোগ হয়; ইনভয়েসের sale items, customer ledger, ledger-এর payment history এবং ওই invoice/ledger-এর সঙ্গে যুক্ত credit voucher মুছে যায়। পুরোনো morph-link-বিহীন voucher-ও invoice নম্বর ও payment description দিয়ে খোঁজা হয়।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য **কোনো migration যোগ বা পরিবর্তন করা হয়নি**। Void অপারেশনটি ইনভয়েস ও সংশ্লিষ্ট রেকর্ড মুছে দিয়ে বিদ্যমান stock ও voucher data ফিরিয়ে দেয়; নতুন status column প্রয়োজন হয় না।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php` — sales route group-এর মধ্যে।

```php
Route::delete('/sales/invoice/{id}', [SaleController::class, 'destroy'])
    ->middleware('permission:sales.create')
    ->name('sales.destroy');
```

`DELETE` method ব্যবহার করায় এটি সাধারণ invoice দেখার route থেকে পৃথক। `sales.create` permission ছাড়া অনুরোধ অনুমোদিত হয় না।

## Model (Code and Explanation)

এই পরিবর্তনের জন্য **কোনো model পরিবর্তন করা হয়নি**। বিদ্যমান `SaleInvoice::items()` সম্পর্ক invoice-এর product lines খুঁজতে ব্যবহৃত হয়েছে; `SaleItem`-এর `product_id`, `quantity` এবং `sale_invoice_id` দিয়েই stock ফেরানো ও item অপসারণ করা হয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php` — invoice-এর উপরের action button-গুলোর পাশে।

```blade
@can('sales.create')
    @if ($invoice->invoice_date?->isToday())
        <form action="{{ route('sales.destroy', $invoice->id) }}" method="POST"
            onsubmit="return confirm('Void this invoice? Its stock and related payment entries will be reversed.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="bx bx-trash me-1"></i>Void Invoice
            </button>
        </form>
    @endif
@endcan
```

আজকের invoice এবং `sales.create` permission থাকা ব্যবহারকারীরাই button দেখবেন। Confirmation dialog-এর পরে form-টি CSRF token-সহ `DELETE` request পাঠায়; শুধু UI-র শর্তের ওপর নির্ভর না করে controller-ও তারিখ যাচাই করে।

সফল void-এর পর redirect হওয়া sales page-এ ফলাফল দেখাতে `resources/views/backend/Sales/salesIndex.blade.php`-এ এই alert যোগ করা হয়েছে:

```blade
@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
```

Controller থেকে session-এ পাঠানো confirmation বার্তাটি এভাবে ব্যবহারকারীর সামনে দেখা যায়।

## স্বয়ংক্রিয় পরীক্ষা

**ফাইল:** `tests/Feature/SalePaymentTest.php`। নতুন test-গুলোর জন্য ফাইলের শুরুতে `CustomerLedger` ও `CustomerLedgerPayment` model import করা হয়েছে।

```php
test('today invoice void restores stock and removes its ledger and linked vouchers', function () {
    $employee = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Void Invoice Customer',
        'phone' => '01700000004',
        'customer_type' => 'temporary',
        'status' => 1,
    ]);
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Void Test Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Void Test Brand']);
    $product = Product::create([
        'productName' => 'Void Test Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => $customer->phone,
            'save_customer' => 'no',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 30,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $ledger = CustomerLedger::query()->where('sale_invoice_id', $invoice->id)->firstOrFail();
    $receipt = CustomerLedgerPayment::create([
        'customer_ledger_id' => $ledger->id,
        'payment_date' => today(),
        'amount' => 10,
        'received_by' => $employee->id,
    ]);
    VoucherEntry::createWithCode([
        'voucher_type' => 'credit',
        'expense_income_type' => $customer->name,
        'voucher_date' => today(),
        'amount' => '10.00',
        'reference' => $invoice->invoice_no,
        'description' => 'Payment received from customer '.$customer->name,
        'created_by' => $employee->id,
        'created_by_name' => $employee->name,
        'voucherable_type' => CustomerLedger::class,
        'voucherable_id' => $ledger->id,
    ]);

    $this->actingAs($employee)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('sale_invoices', ['id' => $invoice->id]);
    $this->assertDatabaseMissing('sale_items', ['sale_invoice_id' => $invoice->id]);
    $this->assertDatabaseMissing('customer_ledgers', ['id' => $ledger->id]);
    $this->assertDatabaseMissing('customer_ledger_payments', ['id' => $receipt->id]);
    $this->assertDatabaseMissing('voucher_entries', ['reference' => $invoice->invoice_no]);
    expect((float) $product->fresh()->unit)->toBe(10.0);
});

test('an invoice from an earlier date cannot be voided', function () {
    $employee = User::factory()->create();
    $categoryId = DB::table('product_categories')->insertGetId(['category_name' => 'Old Invoice Category']);
    $brandId = DB::table('brands')->insertGetId(['name' => 'Old Invoice Brand']);
    $product = Product::create([
        'productName' => 'Old Invoice Product',
        'product_categorie_id' => $categoryId,
        'brand_id' => $brandId,
        'price' => 100,
        'unit' => '10',
        'stock_unit' => '10',
    ]);

    $this->actingAs($employee)
        ->post(route('sales.store'), [
            'customer_phone' => '01700000005',
            'save_customer' => 'no',
            'customer_name' => 'Old Invoice Customer',
            'discount_amount' => 0,
            'vat_amount' => 0,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])
        ->assertRedirect();

    $invoice = SaleInvoice::query()->latest('id')->firstOrFail();
    $invoice->update(['invoice_date' => today()->subDay()]);

    $this->actingAs($employee)
        ->delete(route('sales.destroy', $invoice->id))
        ->assertForbidden();

    $this->assertDatabaseHas('sale_invoices', ['id' => $invoice->id]);
    expect((float) $product->fresh()->unit)->toBe(8.0);
});
```

প্রথম test নিশ্চিত করে stock আগের পরিমাণে ফেরে এবং invoice-এর item, customer ledger, receipt history ও voucher অপসারিত হয়। দ্বিতীয় test নিশ্চিত করে আগের দিনের invoice `403` পায় এবং stock/invoice অপরিবর্তিত থাকে।
