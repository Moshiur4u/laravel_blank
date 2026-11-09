# Sale ও Quotation-এর Quantity — কোড ও ব্যাখ্যা

Sale ও Quotation তৈরির ফর্মে quantity পূর্ণসংখ্যা হিসেবে নেওয়া হয়। Create Quotation-এর **Quotation Items** অংশে quantity `1` দেখানোর কারণ ছিল Blade-এর প্রাথমিক item, পুরোনো মান না থাকলে ব্যবহৃত fallback, এবং JavaScript দিয়ে যোগ করা নতুন item—এই সব জায়গায় `1` নির্দিষ্ট করে দেওয়া ছিল। এগুলো `0` করা হয়েছে, তাই শুরুতে, validation error-এর পরের form-এ এবং নতুন item যোগ করলেও quantity `0` দেখাবে। তবে `min="1"` এবং server-side validation (`min:1`) অপরিবর্তিত: quotation জমা দিতে বৈধ ধনাত্মক quantity দিতে হবে। `0` হলো শুধু প্রাথমিক display value, গ্রহণযোগ্য জমার মান নয়।

Quotation form-এর validation error এখন দৃশ্যমান হয় এবং আগের item-গুলোর মান ফেরত আসে। Sale ফর্মেও প্রাথমিক, নতুন ও reset হওয়া quantity `0` দেখায়; সেখানে পূর্ণসংখ্যার ধাপটি স্পষ্ট করা হয়েছে।

## Controller (Code and Explanation)

কোনো Controller পরিবর্তন করা হয়নি। Sale ও Quotation Controller-এ জমা দেওয়া quantity দিয়েই সার্ভারে line total হিসাব ও সংরক্ষণ করা হয়।

## Migration (Code and Explanation)

কোনো Migration পরিবর্তন করা হয়নি। বিদ্যমান sale ও quotation item টেবিলে quantity কলাম রয়েছে।

## Route (Code and Explanation)

কোনো Route পরিবর্তন করা হয়নি। বিদ্যমান `sales.store` এবং `quotation.store` route-ই ব্যবহার হয়।

## Model (Code and Explanation)

কোনো Model পরিবর্তন করা হয়নি। বিদ্যমান item model-গুলো quantity-কে পূর্ণসংখ্যা হিসেবে রাখে।

## Blade Template (Code and Explanation)

### Quotation

**ফাইল:** `resources/views/backend/quotation/create.blade.php`

আগের submit থেকে পাওয়া item row-গুলো পুনরায় তৈরি হয়; আর আগের মান না থাকলে quantity `0` দিয়ে শুরু হয়। নতুন item row-ও JavaScript-এ `0` দিয়ে তৈরি হয়। `min="1"` থাকার কারণে `0` রেখে জমা দিলে browser এবং server validation তা গ্রহণ করবে না—পণ্য যোগ করার পর সঠিক quantity দিতে হবে:

```blade
@foreach (old('items', [['product_name' => '', 'product_id' => '', 'unit_price' => '0.00', 'quantity' => 0]]) as $index => $oldItem)
    <input name="items[{{ $index }}][product_name]"
        value="{{ $oldItem['product_name'] ?? '' }}">
    <input name="items[{{ $index }}][product_id]"
        value="{{ $oldItem['product_id'] ?? '' }}">
    <input name="items[{{ $index }}][unit_price]"
        value="{{ $oldItem['unit_price'] ?? '0.00' }}">
    <input type="number" min="1" step="1"
        name="items[{{ $index }}][quantity]"
        value="{{ $oldItem['quantity'] ?? 0 }}" required>
@endforeach
```

নতুন item যোগ করার সময় JavaScript quantity input-এ `value="0"` সেট করে। Item index বিদ্যমান row-গুলোর পর থেকে গণনা করা হয়, যাতে আগের row-র input name-এর সঙ্গে সংঘর্ষ না হয়। Validation error-গুলোও form-এর ওপরে দেখানো হয়, যাতে save ব্যর্থ হওয়ার কারণ বোঝা যায়।

### Sale

**ফাইল:** `resources/views/backend/Sales/salesIndex.blade.php`

Sale quantity input-এ integer step স্পষ্টভাবে দেওয়া হয়েছে এবং প্রাথমিক/fallback মান `0`। নতুন row যোগ বা শেষ row reset করলেও JavaScript `0` বসায়। বিদ্যমান `old('items', ...)` binding validation ব্যর্থ হলে আগের quantity-গুলো ধরে রাখে:

```blade
<input type="number" name="items[{{ $index }}][quantity]"
    class="form-control quantity-input" min="1" step="1"
    value="{{ $oldItem['quantity'] ?? 0 }}" required>
```
