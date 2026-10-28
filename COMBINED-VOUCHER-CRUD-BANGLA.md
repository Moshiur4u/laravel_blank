# Combined Voucher Report: Update ও Delete

## Controller (Code and explanation)----

ফাইল: `app/Http/Controllers/CombinedVoucherReportController.php`

```php
$voucher->update([
    'expense_type' => $validated['expense_type'],
    'payment_date' => $validated['payment_date'],
    'amount' => number_format($this->moneyToCents($validated['amount']) / 100, 2, '.', ''),
    'reference' => $validated['reference'] ?? null,
    'notes' => $validated['notes'] ?? null,
]);

$ledger->update([
    'payment' => number_format(($paidCents - $oldCents + $newCents) / 100, 2, '.', ''),
    'due' => number_format(($dueCents + $oldCents - $newCents) / 100, 2, '.', ''),
]);

DB::transaction(function () use ($voucher) {
    $this->deleteSupplierPayment($voucher);
    $voucher->delete();
});
```

- `update()` category অনুযায়ী validation করে এবং voucher-এর amount/date ও সংশ্লিষ্ট বিবরণ সংরক্ষণ করে।
- কর্মীর salary voucher edit করলেও বর্তমান বেতনের বেশি amount অনুমোদিত নয়; `other` expense type-এর notes আবশ্যক।
- সাধারণ debit/credit voucher supplier বা customer ledger-এর payment হলে payment history-র তারিখ ও amount এবং parent ledger-এর paid/due totals একই transaction-এ হালনাগাদ হয়।
- `destroy()` linked payment history মুছে এবং paid amount কমিয়ে due-তে ফেরত যোগ করে। Voucher ও ledger পরিবর্তন transaction-এ হওয়ায় কোনো ধাপ ব্যর্থ হলে পরিবর্তন commit হয় না।
- linked voucher-এর সংশ্লিষ্ট payment row date ও amount দিয়ে মেলানো হয়। row শনাক্ত করা না গেলে validation error দিয়ে operation বন্ধ হয়, যাতে ledger balance ভুলভাবে বদলে না যায়।
- `combinedVoucherQuery()` কর্মী voucher-এর রিপোর্ট তারিখ হিসেবে `payment_date` ব্যবহার করে, তাই date edit-এর পর report filter ও display-তেও নতুন তারিখ দেখা যায়।
- `routes/web.php`-এ update route `voucher.edit` এবং delete route `voucher.delete` permission দিয়ে সুরক্ষিত; `reports.all_access`-ও অনুমোদিত।
- `database/seeders/RolePermissionSeeder.php`-এ manager preset-কে edit ও delete permission দেওয়া হয়েছে। এই permission-গুলো `PermissionSeederTable`-এ আগে থেকেই ছিল।
- `tests/Feature/CombinedVoucherReportTest.php`-এ employee/standalone voucher CRUD এবং supplier-linked voucher balance synchronization-এর regression test যোগ করা হয়েছে।

## Migration (Code and explanation)----

এই feature-এর জন্য migration যোগ বা পরিবর্তন করা হয়নি। Voucher, supplier/customer ledger এবং payment history-র প্রয়োজনীয় table/column আগে থেকেই আছে। নতুন schema না থাকায় বিদ্যমান data migration-এর প্রয়োজন নেই।

## Model (Code and explanation)----

`EmployeePaymentVoucher`, `VoucherEntry`, `Supplierledger`, `SupplierLedgerPayment`, `CustomerLedger` এবং `CustomerLedgerPayment` model-এ কোনো পরিবর্তন করা হয়নি। Controller বিদ্যমান model, relation, morph link ও fillable field ব্যবহার করে।

## Blade (Code and explanation)----

ফাইল: `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`

```blade
<form method="POST" action="{{ route('voucher-report.combined.update', [$voucher->voucher_category, $voucher->voucher_id]) }}">
    @csrf
    @method('PUT')
    {{-- voucher type অনুযায়ী edit fields --}}
</form>

<form method="POST" action="{{ route('voucher-report.combined.destroy', [$voucher->voucher_category, $voucher->voucher_id]) }}">
    @csrf
    @method('DELETE')
    {{-- delete-এর আগে confirmation দেখানো হয় --}}
</form>
```

- Report table-এ **Actions** column যোগ করা হয়েছে; প্রতিটি row-তে **Edit** form ও **Delete** button আছে।
- `voucher.edit` permission থাকলে Edit form, আর `voucher.delete` থাকলে Delete button দেখা যায়; `reports.all_access` দুই action-ই অনুমোদন করে।
- কর্মী voucher-এ expense type, payment date, amount, reference ও notes সম্পাদনা করা যায়।
- সাধারণ debit/credit voucher-এ date, amount, head, reference ও description সম্পাদনা করা যায়।
- Supplier/customer ledger-এর সঙ্গে যুক্ত voucher-এ date ও amount সম্পাদনা করা যায়; head/reference/description read-only থাকে, কারণ এগুলো ledger payment-এর পরিচায়ক।
- Delete button নিশ্চিতকরণ বার্তা দেখায় এবং report filter (daily/monthly date) ধরে রাখে।
- সফল update/delete-এর পর report-এ success message দেখা যায়। Voucher date পরিবর্তিত হলে voucher বর্তমান filter-এর বাইরে চলে যেতে পারে; record নতুন date অনুযায়ী সংরক্ষিত থাকে।
