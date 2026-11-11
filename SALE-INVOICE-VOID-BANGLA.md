# Sale Invoice Void — পরিবর্তনের বাংলা নথি

## Controller (Code and Explanation)

**ফাইল:** `app/Support/SaleInvoiceVoidService.php`

আগে service-এ invoice-এর তারিখ আজকের কি না যাচাই করে `403` দেওয়া হতো। এই guard-টি সরানো হয়েছে:

```diff
- if (! $invoice->invoice_date?->isToday()) {
-     abort(403, 'Only today’s sale invoices can be voided.');
- }
```

এখন `SaleController::destroy()` থেকে যেকোনো তারিখের invoice-এ একই rollback transaction চলে। বিদ্যমান logic বিক্রিত পণ্যের ফেরতযোগ্য পরিমাণ stock-এ যোগ করে, invoice-এর sale items মুছে, সংশ্লিষ্ট customer ledger ও payment history সরায় এবং invoice/ledger-এর সঙ্গে যুক্ত voucher মুছে দেয়। Voucher-এর payment amount আর ledger-এর due amount সংশ্লিষ্ট রেকর্ডসহ বাতিল হয়। Transaction থাকায় ব্যর্থ হলে আংশিক rollback commit হয় না।

**ফাইল:** `app/Http/Controllers/SaleController.php`

```php
public function destroy($id)
{
    $invoiceNo = app(SaleInvoiceVoidService::class)->void((int) $id);

    return redirect()->route('sales.index')
        ->with('success', "Invoice {$invoiceNo} was voided; its stock and payment entries were reversed.");
}
```

Controller একই service ব্যবহার করে; route বা permission পরিবর্তন ছাড়াই service-এর date restriction তুলে নেওয়ায় পুরোনো invoice-ও void করা যায়।

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

এই পরিবর্তনের জন্য **কোনো model পরিবর্তন করা হয়নি**। বিদ্যমান `SaleInvoice::items()` ও `CustomerLedger::payments()` সম্পর্ক ব্যবহার হয়; invoice-এর sale items থেকে stock ফেরে, ledger delete হলে তার payment history cascade-এ মুছে যায়, এবং সংশ্লিষ্ট `VoucherEntry`-গুলো invoice বা ledger-এর morph link দিয়ে সরানো হয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php` — invoice-এর উপরের action button-গুলোর পাশে।

```blade
@can('sales.create')
    <form action="{{ route('sales.destroy', $invoice->id) }}" method="POST"
        onsubmit="return confirm('Delete this invoice? If it is linked to a sale voucher, the invoice will be voided and its stock, ledger, payment voucher amounts, and due balance will be reversed.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="bx bx-trash me-1"></i>Void Invoice
        </button>
    </form>
@endcan
```

এখন `sales.create` permission থাকা ব্যবহারকারীরা যেকোনো তারিখের invoice-এ Void Invoice button দেখবেন। Confirmation-এ voucher amount ও due balance-সহ সংশ্লিষ্ট আর্থিক রেকর্ড rollback হওয়ার বিষয়টি জানানো হয়। Form-টি CSRF token-সহ `DELETE` request পাঠায়।

সফল void-এর পর redirect হওয়া sales page-এ ফলাফল দেখাতে `resources/views/backend/Sales/salesIndex.blade.php`-এ এই alert যোগ করা হয়েছে:

```blade
@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
```

Controller থেকে session-এ পাঠানো confirmation বার্তাটি এভাবে ব্যবহারকারীর সামনে দেখা যায়।

## স্বয়ংক্রিয় পরীক্ষা

**ফাইল:** `tests/Feature/SalePaymentTest.php`। পুরোনো দিনের invoice-এ successful void, stock ফেরত, ledger due, receipt history এবং voucher অপসারণ যাচাই করা হয়েছে।

```php
$invoice->update(['invoice_date' => today()->subDay()]);

expect((float) $ledger->fresh()->due_amount)->toBe(160.0)
    ->and((float) VoucherEntry::query()
        ->where('voucherable_type', CustomerLedger::class)
        ->where('voucherable_id', $ledger->id)
        ->sum('amount'))->toBe(40.0);

$this->actingAs($employee)
    ->delete(route('sales.destroy', $invoice->id))
    ->assertRedirect(route('sales.index'))
    ->assertSessionHas('success');

$this->assertDatabaseMissing('sale_invoices', ['id' => $invoice->id]);
$this->assertDatabaseMissing('sale_items', ['sale_invoice_id' => $invoice->id]);
$this->assertDatabaseMissing('customer_ledgers', ['id' => $ledger->id]);
$this->assertDatabaseMissing('customer_ledger_payments', ['id' => $receipt->id]);
$this->assertDatabaseMissing('voucher_entries', ['reference' => $invoice->invoice_no]);
expect((float) $product->fresh()->unit)->toBe(10.0)
    ->and((float) $customer->ledgers()->sum('due_amount'))->toBe(0.0);
```

পরীক্ষাটি প্রমাণ করে যে পুরোনো invoice void হলে তার stock ফেরে এবং invoice-এর sale item, due-সহ customer ledger, payment history ও payment voucher সরানো হয়।
