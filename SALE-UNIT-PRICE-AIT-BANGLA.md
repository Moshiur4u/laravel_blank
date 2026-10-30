# Sales Unit Price ও AIT — বাংলা নথি

## Controller (Code and explanation)----

- `SaleController::store()` এখন প্রতিটি `items.*.unit_price` যাচাই করে এবং ওই
  হাতে-দেওয়া unit price × quantity থেকে subtotal ও sale item-এর line total হিসাব
  করে। Sale item-এ purchase price snapshot আগের মতোই থাকে; তাই লাভের হিসাব
  (`line_total - purchase cost`) manual unit price অনুযায়ী হয়।

  ```php
  'items.*.unit_price' => 'required|numeric|min:0|max:9999999999999.99',
  $unitPrice = round((float) $item['unit_price'], 2);
  $lineTotal = round($unitPrice * $quantity, 2);
  ```

- VAT ও AIT—দুটির percentage-ই ০ থেকে ১০০-এর মধ্যে যাচাই হয়। Discount বাদ দেওয়ার
  পরের taxable amount-এর ওপর আলাদাভাবে দুই tax হিসাব করা হয় এবং দুটোই invoice ও
  customer sale amount-এর সঙ্গে যোগ হয়। পুরোনো request-এ percentage না থাকলে
  আগের hidden amount-ভিত্তিক আচরণ বজায় থাকে।

  ```php
  $taxableAmount = round($subtotal - $discount, 2);
  $vat = round($taxableAmount * (float) $validated['vat_percentage'] / 100, 2);
  $ait = round($taxableAmount * (float) $validated['ait_percentage'] / 100, 2);
  $saleAmount = round($taxableAmount + $vat + $ait, 2);
  ```

- `SalesReportController`-এর invoice sales total ও CSV-তে AIT অন্তর্ভুক্ত হয়েছে।
  Product-wise report-এ invoice-এর AIT পণ্যের line total-এর অনুপাতে ভাগ করে দেখানো
  হয়; পণ্যের লাভে tax যোগ হয় না।
- `DashboardConrtoller`-এর invoice revenue aggregation-এ AIT অন্তর্ভুক্ত হয়েছে।

## Migration (Code and explanation)----

- `2026_10_30_000001_add_ait_amount_to_sale_invoices_table` `sale_invoices`-এ
  `ait_amount` decimal(15,2), default 0 যোগ করে। কলাম আগে থেকে থাকলে পুনরায় যোগ
  করে না; rollback-এ কলামটি থাকলেই drop করে।

  ```php
  $table->decimal('ait_amount', 15, 2)->default(0)->after('vat_amount');
  ```

## Model (Code and explanation)----

- `SaleInvoice`-এর fillable ও decimal cast-এ `ait_amount` যোগ হয়েছে, যাতে tax
  invoice-এর সঙ্গে সংরক্ষিত এবং দুই decimal-এ পড়া যায়।
- `SaleItem`-এর বিদ্যমান `unit_price`, `line_total`, ও profit accessor ব্যবহার করা
  হয়েছে; model schema-তে অন্য পরিবর্তন দরকার হয়নি।

  ```php
  'ait_amount',
  'ait_amount' => 'decimal:2',
  ```

## Blade (Code and explanation)----

- `salesIndex.blade.php`-এর প্রতিটি sale item-এ Quotation Items-এর মতো editable
  Unit Price input আছে। নতুন product নির্বাচন করলে input-এ product-এর default
  selling price বসে, পরে ব্যবহারকারী তা বদলাতে পারেন।

  ```blade
  <input type="number" name="items[{{ $index }}][unit_price]"
      class="form-control unit-price-input" min="0" step="0.01"
      value="{{ old("items.$index.unit_price", $oldProduct?->price ?? 0) }}" required>
  ```

- Sale form-এ `AIT (%)` input, hidden computed amount এবং live AIT/Invoice Total
  summary যোগ হয়েছে। VAT ও AIT—দুটিই discount-পরবর্তী amount-এর ওপর হিসাব হয়।
- Product search clear/invalid করে selection সরালে ওই row-এর Unit Price-ও সঙ্গে
  সঙ্গে ফাঁকা হয়। শেষ row-এর Remove button-এ product ও price—দুটিই clear হয়,
  ফলে আগের পণ্যের দাম পরের selection-এর জন্য browser-এ থেকে যায় না।
- `salesInvoice.blade.php`-এ AIT line দেখানো হয়।
- `salesReport.blade.php`-এ product-wise AIT এবং AIT-সহ invoice sales amount দেখানো হয়।

## Seeder (Code and explanation)----

- কোনো seeder পরিবর্তন করা হয়নি; tax amount sale invoice তৈরির সময় controller-এ
  হিসাব করে সংরক্ষণ হয়।

## যাচাই

- `tests/Feature/SalePaymentTest.php`-এ manual unit price, invoice subtotal/total,
  VAT/AIT এবং manual selling price অনুযায়ী sale item profit যাচাই করা হয়েছে।
