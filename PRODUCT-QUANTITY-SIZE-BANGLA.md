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

`app/Http/Controllers/QuantitySizeController.php` reusable Qty/Size অপশনের তালিকা, create, update ও delete পরিচালনা করে। Create/update-এ unique নাম যাচাই হয়। Product form-এর icon থেকে তৈরি করলে JSON response-এ option ফেরত আসে এবং সেটি product form-এ বসে।

```php
$request->merge(['name' => trim((string) $request->input('name'))]);
$validated = $request->validate([
    'name' => ['required', 'string', 'max:50', 'unique:quantity_sizes,name'],
]);

$quantitySize = QuantitySize::create($validated);
```

Update validation বর্তমান option-কে unique check থেকে বাদ দেয়। Catalog option delete করলে `products.quantity_size` আলাদা text হিসেবে থাকায় আগের পণ্যের label অপরিবর্তিত থাকে।

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

Reusable catalog-এর migration: `database/migrations/2026_11_10_000002_create_quantity_sizes_table.php`

```php
Schema::create('quantity_sizes', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();
    $table->timestamps();
});
```

## Route (Code and Explanation)

`routes/web.php`-এ Qty/Size CRUD route permission middleware দিয়ে সুরক্ষিত:

```php
Route::get('/product/quantity-size/index', [QuantitySizeController::class, 'index'])
    ->middleware('permission:quantity-size.index')->name('quantity-size.index');
Route::get('/product/quantity-size/create', [QuantitySizeController::class, 'create'])
    ->middleware('permission:quantity-size.create')->name('quantity-size.create');
Route::post('/product/quantity-size/store', [QuantitySizeController::class, 'store'])
    ->middleware('permission:quantity-size.create')->name('quantity-size.store');
Route::get('/product/quantity-size/{quantitySize}/edit', [QuantitySizeController::class, 'edit'])
    ->middleware('permission:quantity-size.edit')->name('quantity-size.edit');
Route::put('/product/quantity-size/{quantitySize}/update', [QuantitySizeController::class, 'update'])
    ->middleware('permission:quantity-size.update')->name('quantity-size.update');
Route::delete('/product/quantity-size/{quantitySize}/destroy', [QuantitySizeController::class, 'destroy'])
    ->middleware('permission:quantity-size.delete')->name('quantity-size.destroy');
```

`PermissionSeederTable`-এ CRUD permission-গুলো যোগ করা হয়েছে এবং `RolePermissionSeeder`-এ manager role-এর জন্য দেওয়া হয়েছে।

## Model (Code and Explanation)

**ফাইল:** `app/Models/Product.php`

`quantity_size` mass assignment-এ অনুমোদিত এবং `display_name` accessor পণ্যের নামের সঙ্গে আকার যুক্ত করে:

```php
'quantity_size',
```

```php
class QuantitySize extends Model
{
    protected $fillable = ['name'];
}
```

Catalog model এবং product label-এর মধ্যে foreign key নেই। ফলে option edit/delete করলেও আগে save করা product text বদলায় না।

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

**পণ্য ফর্ম:** `resources/views/backend/Product/product/createProduct.blade.php` এবং `resources/views/backend/Product/product/editProduct.blade.php`-এ `Qty/Size` নির্বাচন করা যায়। Create form-এ catalog options dropdown-এ দেখায়; Edit form-এ custom text-ও লেখা যায়।

```blade
<div class="input-group">
    <button type="button" class="input-group-text bg-light text-secondary border-end-0"
        data-bs-toggle="modal" data-bs-target="#quantitySizeModal">
        <i class="bx bx-ruler"></i>
    </button>
    <input type="text" name="quantity_size" id="quantity_size"
        class="form-control border-start-0" list="quantitySizeOptions" maxlength="50"
        placeholder="e.g. RIM, pcs"
        value="{{ old('quantity_size', $product->quantity_size ?? '') }}">
</div>
<datalist id="quantitySizeOptions">
    @foreach ($quantitySizes as $quantitySize)
        <option value="{{ $quantitySize }}"></option>
    @endforeach
</datalist>
```

Clickable `bx-ruler` আইকন থেকে modal-এ reusable option তৈরি করা যায়; তৈরি option বর্তমান product field-এ বসে এবং অন্য product-এও বাছাই করা যায়। `resources/views/backend/Product/QuantitySize/`-এ Brand-এর মতো list/create/edit/delete UI রয়েছে। Delete confirmation JavaScript form-based `DELETE` request সমর্থন করে।

### Create Product-এ Qty/Size dropdown

`resources/views/backend/Product/product/createProduct.blade.php`-এ Qty/Size text
input ও datalist-কে Brand field-এর মতো `<select>` dropdown করা হয়েছে। এতে আগে তৈরি
Qty/Size-গুলো দৃশ্যমান option হিসেবে থাকে এবং submit validation error-এর পর আগের
নির্বাচন `old('quantity_size')` দিয়ে বজায় থাকে। নতুন Qty/Size modal থেকে তৈরি করলে
JavaScript সেটিকে dropdown-এ যোগ করে স্বয়ংক্রিয়ভাবে select করে। বাংলা Blade comment-এ
এই পরিবর্তনের কারণ উল্লেখ করা হয়েছে।

```blade
<select name="quantity_size" id="quantity_size" class="form-select border-start-0">
    <option value="">Select Qty/Size</option>
    @foreach ($quantitySizes as $quantitySize)
        <option value="{{ $quantitySize }}"
            {{ old('quantity_size') === $quantitySize ? 'selected' : '' }}>
            {{ $quantitySize }}
        </option>
    @endforeach
</select>
```

### নতুন Qty/Size option product-এ সংরক্ষণ

Qty/Size modal থেকে নতুন option ফেরত এলে আগে সেটিকে dropdown-এ যোগ করা হয়, এরপর
`select.value`-তে মান বসানো হয়। আগে option যোগের আগেই value বসানো হতো; `<select>`-এ
না-থাকা value browser নির্বাচন করতে পারে না, ফলে product submit-এ `quantity_size`
ফাঁকা যেত। এখন যোগ করার পরে মান select করায় form-এ value পাঠানো হয়।

```javascript
if (![...quantitySizeInput.options].some((option) => option.value === result.quantity_size.name)) {
    quantitySizeInput.add(new Option(result.quantity_size.name, result.quantity_size.name));
}
quantitySizeInput.value = result.quantity_size.name;
```

এই ক্রম ঠিক রাখার কারণ inline বাংলা code comment-এও উল্লেখ করা হয়েছে।

### Duplicate Qty/Size message modal-এ দেখানো

Qty/Size modal-এর AJAX response-এ validation status `422` হলে controller-এর
`errors`-এর message আগে দেখানো হয়, generic response `message` নয়। Controller
duplicate নাম modal-এ `This Qty/Size is existing` বার্তায় দেখানো হয়। Modal submit-এর
আগে dropdown-এর option-গুলোর সঙ্গে case-insensitive তুলনা হয়। Server-এ আগে থেকে
থাকা option ধরা পড়লেও controller modal request-এর জন্য `422` JSON validation error
ফেরত দেয়, HTML redirect নয়; তাই client-এর invalid response fallback দেখানোর বদলে
সঠিক duplicate বার্তাটি আসে। বাংলা JavaScript/PHP comment-এ এই আচরণের কারণ উল্লেখ
করা হয়েছে।

**প্রদর্শন:** পণ্যের তালিকা, category details, dashboard, sales picker/invoice/delivery challan, quotation, inventory ও sales report, supplier এবং return screens-এ `display_name` বা আইটেমের `display_product_name` ব্যবহৃত হয়েছে। উদাহরণ:

```blade
{{ $product->display_name }}
{{ $item->display_product_name }}
```

Sales report-এর aggregate query-তে `products.quantity_size`-ও অন্তর্ভুক্ত, যাতে রিপোর্ট ও CSV export-এর পণ্যের নামেও suffix থাকে। পণ্যের Qty/Size ফাঁকা হলে আগের মতো শুধু পণ্যের নামই দেখায়।
