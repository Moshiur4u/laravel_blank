# Sale Invoice-এ Unit প্রদর্শন — বাংলা নথি

## Controller (Code and Explanation)

**কোনো Controller পরিবর্তন করা হয়নি।** `SaleController`-এর `invoice()` মেথড ইতিমধ্যে `items.product` সম্পর্ক eager-load করে। তাই Blade টেমপ্লেট পণ্যের `quantity_size` এবং `stock_unit` ব্যবহার করতে পারে; Unit দেখানোর জন্য অতিরিক্ত Controller কোড প্রয়োজন নেই।

প্রাসঙ্গিক বিদ্যমান কোড:

```php
$invoice = SaleInvoice::with(['items.product', 'customer', 'employee'])->findOrFail($id);
```

## Migration (Code and Explanation)

**কোনো Migration যোগ বা পরিবর্তন করা হয়নি।** Unit-এর জন্য প্রয়োজনীয় পণ্যের তথ্য বিদ্যমান Product ডেটাতেই আছে (`quantity_size` ও `stock_unit`)। নতুন database column দরকার হয়নি।

## Route (Code and Explanation)

**কোনো Route পরিবর্তন করা হয়নি।** বিদ্যমান invoice route-ই আগের মতো `SaleController::invoice()`-এর মাধ্যমে Sale Invoice পেজ দেখায়।

## Model (Code and Explanation)

**কোনো Model পরিবর্তন করা হয়নি।** Sale Invoice-এর item থেকে Product-এর বিদ্যমান সম্পর্ক (`$item->product`) এবং `quantity_size`/`stock_unit` বৈশিষ্ট্য ব্যবহার করা হয়েছে।

## Blade Template (Code and Explanation)

### Sale Invoice-এ Unit

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

ইনভয়েসের পণ্য টেবিলের হেডার ও প্রতিটি পণ্যের সারিতে নতুন `Unit` কলাম যোগ করা হয়েছে। প্রদর্শনের fallback-টি Delivery Challan-এর মতোই: আগে `quantity_size`, না থাকলে `stock_unit`, দুটোই না থাকলে `Unit`।

```blade
<th class="text-center">Unit</th>
```

```blade
<td class="text-center">
    {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
</td>
```

Unit কলাম যুক্ত হওয়ায় টেবিলের প্রস্থগুলো ছয়টি কলামে পুনর্বণ্টন করা হয়েছে। Subtotal, Discount, VAT, AIT, Previous Due, Total, Amount Paid, Due এবং Payment Method-এর লেবেল-ঘরের `colspan` ৪ থেকে ৫ করা হয়েছে, যাতে Amount কলাম সঠিক জায়গায় থাকে। `Amount Paid in Words`-এর মানের `colspan` ৩ থেকে ৪ করা হয়েছে—এতে সব সারির মোট কলামসংখ্যা ছয় থাকে।

### Sale Items টেবিলে আলাদা Unit

**ফাইল:** `resources/views/backend/Sales/salesIndex.blade.php`

Sale Items টেবিলে Unit-এর আলাদা কলাম যোগ করা হয়েছে। পণ্য নির্বাচন তালিকা ও সেলের নাম থেকে Unit suffix বাদ দিয়ে নামের সঙ্গে কেবল Product code রাখা হয়েছে। Unit সেলটি নির্বাচন করা Product-এর তথ্য থেকে পূরণ হয়: `quantity_size`, না থাকলে `stock_unit`, অন্যথায় `Unit`। নতুন সারি বা অনির্বাচিত সারিতে `—` দেখায়।

```blade
<th style="width: 100px;">Unit</th>
<td class="unit-cell text-center">{{ $oldProduct ? ($oldProduct->quantity_size ?: ($oldProduct->stock_unit ?: 'Unit')) : '—' }}</td>
```

পণ্যের option-এ মূল্য ও Unit-এর তথ্য রাখা হয়, যাতে নির্বাচনকালে আলাদা কলাম পূরণ করা যায়। `syncProductSearch()` Product-এর `price` Unit Price-এ এবং Quantity `1`-এ বসায়। বিক্রির আগে Unit Price সম্পাদনা করা যায়।

```javascript
if (option && option.value !== previousProductId) {
    row.querySelector('.unit-price-input').value = Number(option.dataset.price || 0).toFixed(2);
    row.querySelector('.quantity-input').value = '1';
}
row.querySelector('.unit-cell').textContent = option ? (option.dataset.unit || 'Unit') : '—';
```

### Sale Invoice টেবিলে পণ্যের নাম থেকে Unit আলাদা রাখা

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

Invoice-এর Unit কলাম আগে থেকেই Product-এর Unit দেখায়। তাই Product ঘরে Unit পুনরায় না দেখিয়ে মূল সংরক্ষিত পণ্যের নাম দেখানো হয়:

```blade
<td class="invoice-product-name">{{ $item->product_name }}</td>
```

### Quotation Items টেবিলে আলাদা Unit

**ফাইল:** `resources/views/backend/quotation/create.blade.php`

Quotation তৈরির টেবিলে Unit কলাম যোগ করা হয়েছে। পণ্য-নির্বাচন তালিকায় Product-এর নামের সঙ্গে code থাকে, কিন্তু Unit নামের পাশে যোগ হয় না। Product নির্বাচন করলে `data-unit` থেকে Unit কলাম পূরণ হয়। পুরোনো ইনপুটে নির্বাচিত product থাকলে সেটির Unit server-side-এ দেখানো হয়।

```blade
<th style="width: 100px;" class="text-center">Unit</th>
<td class="item-unit text-center">{{ $oldProduct ? ($oldProduct->quantity_size ?: ($oldProduct->stock_unit ?: 'Unit')) : '—' }}</td>
```

আগের `display_name` ব্যবহার করা value/option কোড Blade comment হিসেবে রাখা হয়েছে। `display_name`-এ Unit suffix থাকায় Product নামের পাশে Unit আবার দেখাত। এখন `productName` ও Product code দেখিয়ে Unit-কে তার নিজস্ব কলামে রাখা হয়েছে।

```javascript
unit: opt.getAttribute('data-unit') || 'Unit'
```

### Quotation প্রদর্শন/প্রিন্ট টেবিলে আলাদা Unit

**ফাইল:** `resources/views/backend/quotation/show.blade.php`

Quotation document-এর Product Description-এ Unit suffix বাদ দিয়ে `$item->product_name` দেখানো হয় এবং Unit আলাদা কলামে আসে। Unit দেখাতে বিদ্যমান `$item->product` সম্পর্কের `quantity_size`/`stock_unit` ব্যবহার হয়; সম্পর্ক না থাকলে `Unit` fallback থাকে।

```blade
<div class="fw-semibold text-dark">{{ $item->product_name }}</div>
<td class="text-center">{{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}</td>
```
