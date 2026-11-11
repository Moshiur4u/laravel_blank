# Combined Voucher Delete Rollback (বাংলা)

## Controller (Code and Explanation)

**ফাইল:** `app/Support/SaleInvoiceVoidService.php`

```php
public function void(int $id): string
{
    return DB::transaction(function () use ($id): string {
        $invoice = SaleInvoice::query()->lockForUpdate()->findOrFail($id);
        if (! $invoice->invoice_date?->isToday()) {
            abort(403, 'Only today’s sale invoices can be voided.');
        }

        $items = $invoice->items()->lockForUpdate()->get();
        $returns = ProductReturn::query()
            ->whereIn('sale_item_id', $items->modelKeys())
            ->get(['id', 'sale_item_id', 'product_id', 'quantity']);
        $returnedByItem = $returns->groupBy('sale_item_id')
            ->map(fn ($productReturns) => $productReturns->sum('quantity'));
        $quantities = $items->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn ($productItems) => $productItems->sum(
                fn (SaleItem $item) => max((int) $item->quantity - (int) $returnedByItem->get($item->id, 0), 0)
            ));

        /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $products */
        $products = Product::query()->whereIn('id', $quantities->keys())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product) {
                $product->unit = (string) ((float) $product->unit + $quantity);
                $product->save();
            }
        }

        $ledgers = CustomerLedger::query()->where('sale_invoice_id', $invoice->id)
            ->lockForUpdate()->get();
        $ledgerIds = $ledgers->modelKeys();
        $customerName = $invoice->customer_name ?: 'Walk-in Customer';

        /** @var \Illuminate\Database\Eloquent\Collection<int, VoucherEntry> $relatedVouchers */
        $relatedVouchers = VoucherEntry::query()
            ->where(function ($query) use ($invoice, $ledgerIds, $customerName, $returns) {
                $query->where(function ($source) use ($invoice) {
                    $source->where('voucherable_type', SaleInvoice::class)
                        ->where('voucherable_id', $invoice->id);
                });
                if ($returns->isNotEmpty()) {
                    $query->orWhere(fn ($source) => $source
                        ->where('voucherable_type', ProductReturn::class)
                        ->whereIn('voucherable_id', $returns->pluck('id')));
                }
                if ($ledgerIds !== []) {
                    $query->orWhere(fn ($source) => $source
                        ->where('voucherable_type', CustomerLedger::class)
                        ->whereIn('voucherable_id', $ledgerIds));
                }
                $query->orWhere(fn ($legacy) => $legacy
                    ->whereNull('voucherable_type')->whereNull('voucherable_id')
                    ->where('voucher_type', 'credit')
                    ->where('expense_income_type', $customerName)
                    ->where('reference', $invoice->invoice_no)
                    ->where('description', 'Payment received from customer ' . $customerName));
            })->lockForUpdate()->get();
        foreach ($relatedVouchers as $voucher) {
            $voucher->delete();
        }

        foreach ($ledgers as $ledger) {
            $ledger->delete();
        }
        $invoice->items()->delete();
        $invoice->delete();

        return $invoice->invoice_no;
    });
}
```

**ফাইল:** `app/Http/Controllers/CombinedVoucherReportController.php`

```php
$saleInvoiceId = match ($voucherSource->voucherable_type) {
    SaleInvoice::class => $voucherSource->voucherable_id,
    CustomerLedger::class => CustomerLedger::query()
        ->whereKey($voucherSource->voucherable_id)
        ->value('sale_invoice_id'),
    default => null,
};

if ($saleInvoiceId) {
    $invoiceNo = app(SaleInvoiceVoidService::class)->void((int) $saleInvoiceId);
}
```

যে credit voucher সরাসরি sale invoice অথবা সেই sale-এর customer ledger-এর সঙ্গে যুক্ত, সেটি delete করলে বিদ্যমান invoice-void transaction চালু হয়। ফলে stock, invoice, sale items, সংশ্লিষ্ট ledger, payment ও voucher একই transaction-এ উল্টে যায়। সাধারণ voucher-এর বর্তমান delete ও ledger adjustment অপরিবর্তিত। পুরোনো তারিখের invoice-এ আগের void restriction-ও বজায় থাকে। `SaleController::destroy()`-ও একই service ব্যবহার করে, তাই দুই জায়গার rollback logic আলাদা হয়ে যায়নি।

**ফাইল:** `app/Http/Controllers/SaleController.php`

```php
$invoiceNo = app(SaleInvoiceVoidService::class)->void((int) $id);

return redirect()->route('sales.index')
    ->with('success', "Invoice {$invoiceNo} was voided; its stock and payment entries were reversed.");
```

আগের invoice void route-টিও একই service-এ চলে যাওয়ায় দুই ধরনের void-এ একই rollback নিয়ম বজায় থাকে।

নতুন regression test-এর মূল assertion:

```php
$this->delete(route('voucher-report.combined.destroy', [
    'category' => 'credit',
    'voucherId' => $voucher->id,
]))->assertRedirect(route('voucher-report.combined'));

$this->assertDatabaseMissing('sale_invoices', ['id' => $invoice->id]);
$this->assertDatabaseMissing('sale_items', ['sale_invoice_id' => $invoice->id]);
$this->assertDatabaseMissing('voucher_entries', ['id' => $voucher->id]);
expect((float) $product->fresh()->unit)->toBe(10.0);
```

এটি `tests/Feature/CombinedVoucherReportTest.php`-এ আছে এবং invoice, item, voucher মুছে যাওয়া ও stock ফেরত আসা যাচাই করে।

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন হয়নি। বিদ্যমান invoice, stock, ledger এবং voucher টেবিলই ব্যবহার করা হয়েছে।

## Route (Code and Explanation)

কোনো route পরিবর্তন হয়নি। বিদ্যমান `voucher-report.combined.destroy` DELETE route-ই controller-এর নতুন rollback branch-এ পৌঁছায়; permission ও route constraints আগের মতো আছে।

## Model (Code and Explanation)

কোনো model পরিবর্তন হয়নি। `SaleInvoice`, `CustomerLedger`, `Product`, `ProductReturn`, `SaleItem` ও `VoucherEntry`-এর বিদ্যমান সম্পর্ক/তথ্য ব্যবহার করে rollback হয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`

```blade
onsubmit="return confirm('Delete this voucher? If it is linked to a sale invoice, the invoice will be voided and its stock, ledger, and payment entries will be reversed.');"
```

Delete confirmation-এ এখন স্পষ্ট করে বলা হয় যে sale invoice-এর voucher হলে সম্পূর্ণ invoice void হবে এবং সংশ্লিষ্ট stock, ledger ও payment entry ফেরত যাবে।
