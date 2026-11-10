# স্টক থাকা পণ্য প্রদর্শন — বাংলা নথি

## Controller (code and explanation)----

- `ProductController::index()`-এ ব্যবহৃত পরিবর্তিত কোড:

  ```php
  $Products = Product::with('productCategory', 'brand')
      ->whereRaw('CAST(unit AS DOUBLE) > 0')
      ->get();
  ```

  `unit`-এর সংখ্যামান শূন্যের বেশি এমন পণ্যই তালিকা পেজে পাঠানো হয়। ফলে পণ্যের
  সারি, সারাংশ কার্ড এবং মেয়াদ সতর্কতাগুলো স্টক থাকা পণ্যের ভিত্তিতে তৈরি হয়।
- `SaleController::index()`-এ ব্যবহৃত পরিবর্তিত কোড:

  ```php
  $products = Product::query()
      ->whereRaw('CAST(unit AS DOUBLE) > 0')
      ->orderBy('productName')
      ->get();
  ```

  বিক্রয় ফর্মে ব্যবহারের জন্য কেবল স্টক থাকা পণ্য লোড হয়; product search, select
  options এবং datalist তাই শূন্য-স্টকের পণ্য দেখায় না।
- `unit` ডেটাবেসে string হিসেবে সংরক্ষিত হওয়ায় তুলনার আগে `CAST(unit AS DOUBLE)`
  দিয়ে সংখ্যায় রূপান্তর করা হয়েছে।

## Migration (code and explanation)----

- কোনো migration পরিবর্তন বা নতুন migration করা হয়নি। বিদ্যমান `products.unit`
  কলাম ব্যবহার করা হয়েছে।

## Model (code and explanation)----

- কোনো model পরিবর্তন করা হয়নি।

## Blade (code and explanation)----

- `resources/views/backend/Product/product/indexProduct.blade.php`-এর সারাংশ কার্ডে
  “Total Products” বদলে “In-stock Products” করা হয়েছে, কারণ তালিকায় এখন কেবল
  স্টক থাকা পণ্যই দেখানো হয়। পরিবর্তিত label:

  ```blade
  <p class="mb-0 text-secondary fw-semibold">In-stock Products</p>
  ```
- `resources/views/backend/Sales/salesIndex.blade.php`-এর product options Controller
  থেকে আসা স্টক-সহ পণ্যের collection থেকেই তৈরি হয়; Blade-এ আলাদা ফিল্টার যোগের
  দরকার হয়নি।
- `resources/views/backend/Product/product/createProduct.blade.php`-এর Product Name
  ইনপুটে বিদ্যমান সব পণ্যের নামের autocomplete suggestion দেখানো হয়।

## Product Index-এ Stock সংখ্যা ও Unit প্রদর্শন

**ফাইল:** `resources/views/backend/Product/product/indexProduct.blade.php`

Product তালিকার Stock / Unit badge এবং Product details modal-এ আগে stock সংখ্যার
পরে সব পণ্যের জন্য সাধারণ `Units` লেখা হতো। এখন প্রকৃত Unit দেখাতে প্রথমে
`quantity_size`, সেটি ফাঁকা হলে `stock_unit`, এবং দুটোই না থাকলে `Units` fallback
ব্যবহার করা হয়। এতে `12 RIM` বা `5 pcs`-এর মতো stock-এর সংখ্যা ও একক একসঙ্গে বোঝা যায়।
কম stock এবং শূন্য stock-এর badge-এও stock সংখ্যা ও একই Unit দৃশ্যমান থাকে।

```blade
@php
    $stockUnitLabel = $product->quantity_size ?: ($product->stock_unit ?: 'Units');
@endphp
{{ $product->unit }} {{ $stockUnitLabel }}
```

Low Stock হলে stock সংখ্যা ও Unit-এর নিচে status-টি আলাদা লাইনে 8px font-এ দেখানো হয়:

```blade
{{ $product->unit }} {{ $stockUnitLabel }}
<span class="d-block" style="font-size: 8px;">Low Stock</span>
```

এভাবে breakdown-এ পরিমাণ/একক এবং সতর্কবার্তা আলাদা করে সহজে পড়া যায়।

বাংলা Blade comment-এ এই পরিবর্তনের কারণ রাখা হয়েছে: এককসহ stock দেখালে মজুতের
পরিমাণ বোঝা সহজ হয় এবং details modal-ও তালিকার সঙ্গে একই তথ্য দেখায়। কোনো
Controller, Model বা Migration পরিবর্তন করা হয়নি; Product-এর বিদ্যমান field-ই
ব্যবহার করা হয়েছে।

## যাচাই

- `tests/Feature/ProductAvailableStockTest.php` পণ্যের তালিকা ও বিক্রয় ফর্মে
  স্টক থাকা পণ্য অন্তর্ভুক্ত এবং শূন্য-স্টকের পণ্য বাদ পড়া যাচাই করে; Product
  create পেজে stock নির্বিশেষে বিদ্যমান সব পণ্যের নামের suggestion-ও যাচাই করে।

## Product Name suggestions

### Controller (code and explanation)----

- `ProductController::create()`-এ ব্যবহৃত কোড:

  ```php
  $existingProductNames = Product::query()
      ->whereNotNull('productName')
      ->where('productName', '<>', '')
      ->distinct()
      ->orderBy('productName')
      ->pluck('productName');
  ```

  পণ্যের নামগুলো duplicate বাদ দিয়ে সাজানো অবস্থায় Blade view-তে পাঠানো হয়।
  এখানে stock filter নেই, তাই stock শূন্য হলেও পুরোনো product name suggestion-এ থাকে।

### Migration (code and explanation)----

- কোনো migration পরিবর্তন বা নতুন migration করা হয়নি।

### Model (code and explanation)----

- কোনো model পরিবর্তন করা হয়নি।

### Blade (code and explanation)----

- Product Name input-এ `list="existingProductNames"` যোগ করা হয়েছে এবং একই ID-এর
  `<datalist>`-এ Controller থেকে আসা সব নাম option হিসেবে দেখানো হয়। ব্যবহারকারী
  পুরোনো নাম নির্বাচন করতে বা নতুন নাম লিখতে পারেন; form-এর বাকি field-গুলোও
  স্বাভাবিকভাবে পূরণ করতে হয়।
