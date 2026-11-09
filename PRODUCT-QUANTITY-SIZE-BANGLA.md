# পণ্যের Qty/Size ফিল্ড — বাস্তবায়ন ও ব্যাখ্যা

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/ProductController.php`

পণ্য তৈরি ও আপডেট—দুই ক্ষেত্রেই `quantity_size` ঐচ্ছিক স্ট্রিং হিসেবে যাচাই করা হয় এবং `Product` মডেলে সংরক্ষণ করা হয়:

```php
'quantity_size' => 'nullable|string|max:50',
```

```php
'quantity_size' => $request->quantity_size,
```

এতে `RIM`, `pcs` বা অনুরূপ একক/প্যাকের বর্ণনা রাখা যায়। এটি পণ্যের সংখ্যাগত মজুত `unit` থেকে আলাদা; তাই স্টক কমানো-বাড়ানো বা মূল্য গণনায় এই নতুন ফিল্ড ব্যবহার করা হয় না।

বিক্রয় ও quotation আইটেমে পণ্যের নামের snapshot আগের মতোই সংরক্ষিত হয়। প্রদর্শনের সময় সংশ্লিষ্ট পণ্যের Qty/Size যোগ হয়। ফলে পুরোনো invoice/quotation-ও বর্তমান পণ্যের আকারসহ নাম দেখাতে পারে।

## Migration (Code and Explanation)

**ফাইল:** `database/migrations/2026_11_10_000001_add_quantity_size_to_products_table.php`

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('quantity_size', 50)->nullable()->after('unit');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn('quantity_size');
    });
}
```

ফিল্ডটি nullable, তাই আগের পণ্যগুলোর জন্য কোনো মান বাধ্যতামূলক নয়। `down()`-এ কলামটি বাদ দেওয়ার ব্যবস্থা থাকায় migration rollback করা যায়।

## Route (Code and Explanation)

Qty/Size-এর জন্য আলাদা route যোগ করা হয়নি। পণ্য তৈরি ও সম্পাদনার বিদ্যমান route-ই ব্যবহার হয়:

```php
Route::Post('/product/store', [ProductController::class, 'store'])
    ->middleware('permission:product.create')
    ->name('product.store');
Route::put('/product/update/{id}', [ProductController::class, 'update'])
    ->middleware('permission:product.update')
    ->name('product.update');
```

অতএব route বা permission কাঠামোর পরিবর্তন ছাড়াই বিদ্যমান form submit থেকে নতুন মান সংরক্ষিত হয়।

## Model (Code and Explanation)

**ফাইল:** `app/Models/Product.php`

`quantity_size` mass assignment-এ অনুমোদিত এবং `display_name` accessor পণ্যের নামের সঙ্গে আকার যুক্ত করে:

```php
'quantity_size',
```

```php
public function getDisplayNameAttribute(): string
{
    $name = trim((string) $this->productName);
    $quantitySize = trim((string) $this->quantity_size);
    $suffix = ' - '.$quantitySize;

    if ($quantitySize === '' || str_ends_with(mb_strtolower($name), mb_strtolower($suffix))) {
        return $name;
    }

    return $name.$suffix;
}
```

**Sale/Quotation:** `app/Models/SaleItem.php` এবং `app/Models/QuotationItem.php`-এর `display_product_name` accessor-ও একই suffix যোগ করে, তবে product relation না থাকলে snapshot নাম অক্ষুণ্ণ রাখে এবং suffix আগে থেকেই থাকলে পুনরাবৃত্তি করে না।

## Blade Template (Code and Explanation)

**পণ্য ফর্ম:** `resources/views/backend/Product/product/createProduct.blade.php` এবং `resources/views/backend/Product/product/editProduct.blade.php`-এ ঐচ্ছিক `Qty/Size` ইনপুট রয়েছে। সম্পাদনার ফর্মে বর্তমান মান এবং validation ব্যর্থ হলে আগের ইনপুট দেখানো হয়।

```blade
<div class="input-group">
    <span class="input-group-text bg-light text-secondary border-end-0">
        <i class="bx bx-ruler"></i>
    </span>
    <input type="text" name="quantity_size" id="quantity_size"
        class="form-control border-start-0" maxlength="50"
        placeholder="e.g. RIM, pcs"
        value="{{ old('quantity_size', $product->quantity_size ?? '') }}">
</div>
```

Qty/Size বোঝাতে ইনপুটের আগে `bx-ruler` আইকন ব্যবহার করা হয়েছে।

**প্রদর্শন:** পণ্যের তালিকা, category details, dashboard, sales picker/invoice/delivery challan, quotation, inventory ও sales report, supplier এবং return screens-এ `display_name` বা আইটেমের `display_product_name` ব্যবহৃত হয়েছে। উদাহরণ:

```blade
{{ $product->display_name }}
{{ $item->display_product_name }}
```

Sales report-এর aggregate query-তে `products.quantity_size`-ও অন্তর্ভুক্ত, যাতে রিপোর্ট ও CSV export-এর পণ্যের নামেও suffix থাকে। পণ্যের Qty/Size ফাঁকা হলে আগের মতো শুধু পণ্যের নামই দেখায়।
