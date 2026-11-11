# ড্যাশবোর্ড মেনু ও ভাউচার ডিলিট বার্তা

## Controller (Code and Explanation)

`app/Http/Controllers/CombinedVoucherReportController.php`-এ linked invoice void করার অনুমতি আগে যাচাই করা হয়। অনুমতি না থাকলে 403 পেজ না দেখিয়ে একই report filter-এ ফিরে warning SweetAlert দেখায়; invoice বা voucher মুছে না।

```php
$voidService = app(SaleInvoiceVoidService::class);
$invoice = SaleInvoice::query()->findOrFail((int) $saleInvoiceId);
if (! $voidService->canVoid($invoice)) {
    return redirect()->route('voucher-report.combined', $request->only(['period', 'date', 'month']))
        ->with('warning', 'You do not have permission to void this sale invoice, so the voucher was not deleted.');
}

$invoiceNo = $voidService->void((int) $saleInvoiceId);
```

`app/Support/SaleInvoiceVoidService.php`-এ `canVoid()` একই আজকের invoice/যেকোনো দিনের permission rule controller ও void transaction—দুই জায়গায় ব্যবহার করে। `void()`-এর ভেতরের authorization check অক্ষুণ্ণ থাকে।

## Migration (Code and Explanation)

কোনো Migration যোগ বা পরিবর্তন করা হয়নি। এই UI ও অনুমতি-সংক্রান্ত পরিবর্তনের জন্য ডেটাবেস schema-তে পরিবর্তন দরকার নেই।

## Route (Code and Explanation)

কোনো Route পরিবর্তন করা হয়নি। `voucher-report.combined.destroy` route-এ থাকা `voucher.delete|reports.all_access` middleware server-side authorization হিসেবে বহাল আছে।

```php
->middleware('permission:voucher.delete|reports.all_access')
```

এই middleware থাকার কারণে `voucher.delete` বা `reports.all_access` না থাকলে DELETE request route-এই block হয়। রিপোর্ট UI-তে এমন ব্যবহারকারীর Delete বাটন confirmation দেখায়, কিন্তু request পাঠায় না।

## Model (Code and Explanation)

কোনো Model পরিবর্তন করা হয়নি। ভাউচার রেকর্ড ও তার সম্পর্কিত তথ্য আগের মতোই ব্যবহৃত হয়।

## Blade Template (Code and Explanation)

### `resources/views/dashboard/dashboard.blade.php`

```blade
@canany(['sales.create', 'product.return'])
    <li class="menu-label">Regural Operation</li>
    <li>
        <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class='bx bx-cart-alt'></i></div>
            <div class="menu-title">Sales</div>
        </a>
        <ul>
            @can('sales.create')
                <li>
                    <a href="{{ route('sales.index') }}"><i class='bx bx-plus-medical'></i>Create Sale</a>
                </li>
            @endcan
            @can('product.return')
                <li>
                    <a href="{{ route('product-returns.index') }}"><i class='bx bx-undo'></i>Sales Return</a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany
```

Sales মেনু থেকে “Recent Invoices” লিংক সরানো হয়েছে। কেবল Create Sale বা Sales Return অনুমতি থাকা অবস্থায় Sales মেনু দেখানো হয়, যাতে খালি মেনু না থাকে।

### `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`

```blade
@canany(['voucher.delete', 'reports.all_access'])
    <form method="POST"
        action="{{ route('voucher-report.combined.destroy', [$voucher->voucher_category, $voucher->voucher_id]) }}"
        class="mt-2" data-swal-delete
        data-delete-message="Delete this voucher? If it is linked to a sale invoice, the invoice will be voided and its stock, ledger, and payment entries will be reversed.">
        @csrf
        @method('DELETE')
        <input type="hidden" name="period" value="{{ $period }}">
        <input type="hidden" name="date" value="{{ $period === 'daily' ? $startDate : '' }}">
        <input type="hidden" name="month" value="{{ $period === 'monthly' ? $selectedMonth : '' }}">
        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
    </form>
@else
    <button type="button" class="mt-2 btn btn-sm btn-outline-danger"
        data-voucher-delete-denied
        data-delete-message="Delete this voucher? If it is linked to a sale invoice, the invoice will be voided and its stock, ledger, and payment entries will be reversed.">
        Delete
    </button>
@endcanany
```

অনুমতিপ্রাপ্ত ও অনুমতিহীন—দুই ধরনের ব্যবহারকারীর ক্ষেত্রেই একই SweetAlert-এ invoice void, stock, ledger ও payment rollback-এর বার্তা দেখানো হয়। অনুমতিপ্রাপ্ত ব্যবহারকারী Yes চাপলে DELETE form জমা হয়। Voucher delete permission না থাকা ব্যবহারকারী Yes চাপলে কোনো request বা redirect হয় না, ফলে একই report page-এ থাকেন। `voucher.delete` থাকলেও পুরোনো invoice void permission না থাকলে server একই report filter-এ ফিরিয়ে warning SweetAlert দেখায়।

```javascript
document.addEventListener('click', function(event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const deleteControl = event.target.closest('[data-voucher-delete-denied]');
    if (!deleteControl) {
        return;
    }

    window.Swal.fire({
        title: 'Do you want to delete?',
        text: deleteControl.dataset.deleteMessage,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        reverseButtons: true,
        focusCancel: true,
    });
});
```

Flash warning-টি report-এ SweetAlert হিসেবে দেখানো হয়:

```javascript
window.Swal.fire({
    title: 'Voucher not deleted',
    text: 'You do not have permission to void this sale invoice, so the voucher was not deleted.',
    icon: 'warning',
});
```

## Regression Tests (Code and Explanation)

`tests/Feature/DashboardMetricsTest.php`-এ dashboard response-এ “Recent Invoices” না থাকার পরীক্ষা যোগ করা হয়েছে।

```php
$this->actingAs($user)
    ->get(route('dashboard'))
    ->assertOk()
    ->assertDontSee('Recent Invoices');
```

`tests/Feature/CombinedVoucherReportTest.php`-এ শুধু report দেখার অনুমতি থাকা ব্যবহারকারীর Delete বোতাম দেখা, DELETE request-এ 403 পাওয়া এবং voucher রেকর্ড অক্ষত থাকার পরীক্ষা যোগ করা হয়েছে।

```php
->assertOk()
->assertSee('data-voucher-delete-denied', false)
->assertSee('data-delete-message="Delete this voucher?', false)
->assertSee('DBV-COMBINED-NO-DELETE-001');

$this->delete($deleteRoute)->assertForbidden();
$this->assertDatabaseHas('voucher_entries', ['id' => $voucher->id]);
```

এছাড়া voucher delete permission থাকা কিন্তু পুরোনো invoice void permission না থাকা ব্যবহারকারীর জন্য report-এ ফিরে warning পাওয়া এবং invoice/voucher অক্ষত থাকার test যোগ হয়েছে।
