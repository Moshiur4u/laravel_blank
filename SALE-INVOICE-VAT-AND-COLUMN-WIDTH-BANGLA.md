# Sale Invoice VAT ও কলামের প্রস্থ — বাংলা নথি

এই নথিতে Sale Invoice-এর নতুন ইনভয়েসে VAT-এর ডিফল্ট `0%` করা এবং ইনভয়েস টেবিলের `Unit Selling Price`, `QTY`, `Unit` ও `Amount` কলামকে কনটেন্ট অনুযায়ী প্রস্থ দেওয়ার পরিবর্তন ব্যাখ্যা করা হয়েছে।

## Controller (Code and Explanation)

**কোনো Controller কোড পরিবর্তন করা হয়নি।** নতুন ইনভয়েসে VAT-এর প্রাথমিক মান ফর্মের Blade template থেকে আসে। বিদ্যমান Controller validation ও হিসাবের ধারা অপরিবর্তিত আছে।

## Migration (Code and Explanation)

**কোনো Migration যোগ বা পরিবর্তন করা হয়নি।** VAT-এর হার সংরক্ষণের জন্য নতুন database field প্রয়োজন হয়নি।

## Route (Code and Explanation)

**কোনো Route যোগ বা পরিবর্তন করা হয়নি।** বিদ্যমান Sale routes-ই ইনভয়েস তৈরি ও দেখানোর কাজ করছে।

## Model (Code and Explanation)

**কোনো Model যোগ বা পরিবর্তন করা হয়নি।** বিদ্যমান Sale Invoice ও item-এর ডেটাই আগের মতো ব্যবহৃত হচ্ছে।

## Blade Template (Code and Explanation)

### নতুন Sale-এর VAT ডিফল্ট `0%`

**ফাইল:** `resources/views/backend/Sales/salesIndex.blade.php`

```blade
<input type="number" name="vat_percentage" id="vat_percentage"
    class="form-control" min="0" max="100" step="0.01"
    value="{{ old('vat_percentage', $editingInvoice ? $vatPercentage : 0) }}">
```

লাইন ধরে ব্যাখ্যা:

1. `<input type="number"...>` VAT-এর হার সংখ্যায় নেওয়ার ইনপুট তৈরি করে এবং form submission-এর জন্য `vat_percentage` নামে পাঠায়।
2. `class`, `min`, `max` ও `step` ইনপুটের Bootstrap style এবং গ্রহণযোগ্য সংখ্যার সীমা নির্ধারণ করে।
3. `old(...)` validation ব্যর্থ হলে আগের ইনপুট রাখে; invoice edit করলে সংরক্ষিত VAT হার দেখায়; নতুন invoice হলে `0` দেখায়।

VAT summary-তেও প্রথমবারের প্রদর্শিত হার `0%` করা হয়েছে:

```blade
<div class="mb-2 d-flex justify-content-between"><span>VAT (<span
        id="vatPercentageText">0%</span>)</span><strong id="vatText">৳0.00</strong>
</div>
```

লাইন ধরে ব্যাখ্যা:

1. প্রথম লাইন VAT-এর হার ও টাকার পরিমাণকে পাশাপাশি দেখানোর summary row শুরু করে।
2. `vatPercentageText`-এ শুরুতে `0%` দেখা যায়; JavaScript-এ হার পরিবর্তন হলে এই অংশটি আপডেট হয়, আর `vatText` VAT-এর টাকার অঙ্ক দেখায়।
3. `</div>` summary row বন্ধ করে।

### ইনভয়েসে কনটেন্ট-ফিট কলাম

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

টেবিলের `#` এবং Product কলামের নির্ধারিত প্রস্থ রাখা হয়েছে; বাকি চারটি কলামের নির্দিষ্ট শতাংশ-প্রস্থ তুলে দেওয়া হয়েছে:

```blade
<colgroup>
    <col style="width: 5%;">
    <col style="width: 32%;">
    <col>
    <col>
    <col>
    <col>
</colgroup>
```

লাইন ধরে ব্যাখ্যা:

1. `<colgroup>` টেবিলের ছয়টি কলামের প্রস্থ নির্ধারণের অংশ শুরু করে।
2. `#` কলামকে টেবিলের `5%` প্রস্থ দেয়।
3. Product কলামের জন্য `32%` জায়গা রাখে।
4. `Unit Selling Price` কলামের জন্য নির্দিষ্ট শতাংশ বেঁধে না দিয়ে কনটেন্ট অনুযায়ী জায়গা নিতে দেয়।
5. `QTY` কলামের জন্য নির্দিষ্ট শতাংশ বেঁধে না দিয়ে কনটেন্ট অনুযায়ী জায়গা নিতে দেয়।
6. `Unit` কলামের জন্য নির্দিষ্ট শতাংশ বেঁধে না দিয়ে কনটেন্ট অনুযায়ী জায়গা নিতে দেয়।
7. `Amount` কলামের জন্য নির্দিষ্ট শতাংশ বেঁধে না দিয়ে কনটেন্ট অনুযায়ী জায়গা নিতে দেয়।
8. `</colgroup>` কলাম প্রস্থের বর্ণনা শেষ করে।

Header ও প্রতিটি পণ্যের row-তে চারটি কলামে `fit-content-column` class যোগ করা হয়েছে:

```blade
<th class="text-end fit-content-column">Unit Selling Price</th>
<th class="text-center fit-content-column">QTY</th>
<th class="text-center fit-content-column">Unit</th>
<th class="text-end fit-content-column">Amount</th>
<td class="text-end fit-content-column">৳{{ number_format($item->unit_price, 2) }}</td>
<td class="text-center fit-content-column">{{ $item->remaining_quantity }}</td>
<td class="text-center fit-content-column">
    {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
</td>
<td class="text-end fit-content-column">৳{{ number_format($item->remaining_line_total, 2) }}</td>
```

লাইন ধরে ব্যাখ্যা:

1. `Unit Selling Price` header-কে ডানদিকে align করে এবং content-fit class দেয়।
2. `QTY` header-কে মাঝখানে align করে এবং content-fit class দেয়।
3. `Unit` header-কে মাঝখানে align করে এবং content-fit class দেয়।
4. `Amount` header-কে ডানদিকে align করে এবং content-fit class দেয়।
5. item-এর unit price দুই decimal-এ ফরম্যাট করে ডানদিকে দেখায় এবং তার কলামকে content-fit করে।
6. item-এর বাকি quantity মাঝখানে দেখায় এবং তার কলামকে content-fit করে।
7. Unit-এর cell শুরু করে এবং content-fit class যোগ করে।
8. Product-এর `quantity_size`, না থাকলে `stock_unit`, কোনোটিই না থাকলে `Unit` দেখায়।
9. Unit-এর cell বন্ধ করে।
10. item-এর line total দুই decimal-এ ফরম্যাট করে ডানদিকে দেখায় এবং তার কলামকে content-fit করে।

CSS-এ `table-layout: auto` ব্রাউজারকে কনটেন্টের ভিত্তিতে কলাম সাজাতে দেয়। `width: 1%` ও `white-space: nowrap` চারটি নির্দিষ্ট কলামকে প্রয়োজনের অতিরিক্ত চওড়া না হতে সাহায্য করে:

```css
.invoice-table {
    table-layout: auto;
}

.invoice-table .fit-content-column {
    width: 1%;
    white-space: nowrap;
}
```

লাইন ধরে ব্যাখ্যা:

1. `.invoice-table` শুধু Sale Invoice-এর টেবিলের layout style শুরু করে।
2. `table-layout: auto` কলামের প্রস্থ কনটেন্ট অনুযায়ী হিসাব করতে বলে।
3. `}` ওই style block বন্ধ করে।
4. ফাঁকা লাইনটি CSS নিয়মগুলো আলাদা করে।
5. `.fit-content-column` class-যুক্ত header ও cell-এর style শুরু করে।
6. `width: 1%` কলামকে তার কনটেন্ট যতটুকু জায়গা চায় ততটুকুতে সংকুচিত হতে সহায়তা করে।
7. `white-space: nowrap` শিরোনাম বা মানকে একাধিক লাইনে ভাঙা থেকে বিরত রাখে।
8. `}` ওই style block বন্ধ করে।

প্রিন্টেও একই auto layout রাখা হয়েছে, যাতে কাগজে কলামগুলো content-fit থাকে:

```css
.invoice-table {
    width: 100% !important;
    table-layout: auto;
}
```

লাইন ধরে ব্যাখ্যা:

1. প্রিন্টের সময় invoice table-এর style শুরু করে।
2. টেবিলকে মুদ্রণযোগ্য জায়গার পুরো প্রস্থে রাখে।
3. প্রিন্টেও কলামগুলোকে কনটেন্টের ভিত্তিতে সাজায়।
4. style block বন্ধ করে।
