# Product Supplier ও Pricing — শেখার নোট

Product Create form-এ supplier নির্বাচন ও purchase price সংরক্ষণের নিয়ম এবং supplier-সহ product তৈরি হলে ledger-এ ক্রয়-এন্ট্রি তৈরির প্রবাহ এখানে নথিভুক্ত করা হয়েছে। আগের `price` field-টি Product Selling Price হিসেবে রাখা হয়েছে; বিদ্যমান product field বা row-এর মান পরিবর্তন করা হয়নি। Product-এর supplier ও purchase price ঐচ্ছিক, তাই supplier ছাড়া product তৈরির পুরোনো ব্যবহার বজায় থাকে।

## Controller (Code and Explanation)

### Supplier dropdown-এর জন্য তথ্য

**ফাইল:** `app/Http/Controllers/ProductController.php`

```php
public function create()
{
    $ProductCategories = ProductCategory::all();
    $brands = Brand::all();
    $existingProductNames = Product::query()
        ->whereNotNull('productName')
        ->where('productName', '<>', '')
        ->distinct()
        ->orderBy('productName')
        ->pluck('productName');
    $suppliers = Supplier::where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('backend.Product.product.createProduct', compact(
        'brands',
        'ProductCategories',
        'suppliers',
        'existingProductNames'
    ));
}
```

শুধু active supplier-গুলো নাম অনুযায়ী সাজিয়ে Product Create view-তে পাঠানো হয়, যাতে dropdown-এ সেগুলো নির্বাচন করা যায়।

### Product তৈরি ও supplier ledger

**ফাইল:** `app/Http/Controllers/ProductController.php`

```php
$request->validate([
    'supplier_id' => [
        'nullable',
        Rule::exists('suppliers', 'id')->where('is_active', true),
    ],
    'purchase_price' => 'nullable',
    'price' => 'nullable',
    'unit' => 'nullable',
]);

DB::transaction(function () use ($request, $product_image_name) {
    $product = Product::create([
        'productName' => $request->productName,
        'product_categorie_id' => $request->product_categorie_id,
        'brand_id' => $request->brand_id,
        'supplier_id' => $request->supplier_id,
        'purchase_price' => $request->purchase_price,
        'price' => $request->price,
        'unit' => $request->unit,
        'purchase_date' => $request->purchase_date,
        'mfg_date' => $request->menufecher_date,
        'expiry_date' => $request->expire_date,
        'description' => $request->description,
        'img_url' => $product_image_name,
    ]);

    if ($product->supplier_id !== null && $product->purchase_price !== null) {
        $ledgerTotal = round(
            (float) $product->purchase_price * (float) $product->unit,
            2
        );

        Supplierledger::create([
            'supplier_id' => $product->supplier_id,
            'product_id' => (string) $product->id,
            'quantity' => (string) $product->unit,
            'price' => (string) $product->purchase_price,
            'total' => number_format($ledgerTotal, 2, '.', ''),
            'payment' => '0.00',
            'due' => number_format($ledgerTotal, 2, '.', ''),
        ]);
    }
});
```

Supplier নির্বাচন করলে controller যাচাই করে ID-টি active supplier-এর কি না। Product save এবং supplier ledger entry একই database transaction-এ হয়, ফলে একটি save ব্যর্থ হলে দুটোই rollback হয়। Supplier ও purchase price দুটোই থাকলেই ledger entry তৈরি হয়। Ledger-এর মোট ক্রয়মূল্য `purchase_price × unit`; প্রাথমিক payment `0.00`, তাই due পুরো total-এর সমান। Ledger-এর `invoice_id` ঐচ্ছিক হওয়ায় অজানা supplier invoice number বানিয়ে দেওয়া হয় না।

Controller-এর store validation-এ `supplier_id`-এর active supplier পরীক্ষা আছে; `purchase_price`-এর rule nullable। Form-এ numeric input ও `min="0"` দেওয়া হয়েছে। Supplier বা purchase price না থাকলে product তৈরি হয়, তবে supplier ledger row তৈরি হয় না।

### Sales invoice-এ বিক্রয়মূল্যের snapshot

**ফাইল:** `app/Http/Controllers/SaleController.php`

```php
$unitPrice = round((float) $item['unit_price'], 2);
$lineTotal = round($unitPrice * $quantity, 2);

SaleItem::create([
    'product_id' => $product->id,
    'product_name' => $product->productName,
    'quantity' => $quantity,
    'purchase_price' => $product->purchase_price !== null
        ? (float) $product->purchase_price
        : null,
    'unit_price' => $lineItem['unitPrice'],
    'line_total' => $lineItem['lineTotal'],
]);
```

Sale-এ form থেকে পাওয়া unit selling price line total গণনা করে sale item-এ সংরক্ষিত হয়। Product-এর purchase price আলাদা snapshot হিসেবে রাখা হয়। ফলে Product-এর selling বা purchase price পরে বদলালেও পুরোনো sale item-এর সংরক্ষিত মূল্য বদলায় না।

## Migration (Code and Explanation)

**ফাইল:** `database/migrations/2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php`

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->foreignId('supplier_id')
            ->nullable()
            ->after('brand_id')
            ->constrained('suppliers')
            ->nullOnDelete();
        $table->decimal('purchase_price', 10, 2)
            ->nullable()
            ->after('supplier_id');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['supplier_id']);
        $table->dropColumn(['supplier_id', 'purchase_price']);
    });
}
```

`supplier_id` এবং `purchase_price` nullable, তাই পুরোনো product row-তে supplier বা purchase price না থাকলেও migration data-কে পরিবর্তন বা অনুমান করে না। Supplier মুছে গেলে foreign key product row রেখে `supplier_id` null করে। Rollback-এ migration শুধু তার নিজের foreign key ও column সরায়।

নতুন field কার্যকর করতে deployment-এ স্বাভাবিক Laravel migration চালাতে হয়: `php artisan migrate`। এই workspace-এর database-এ schema migration আগেই প্রয়োগ করা হয়েছে।

## Route (Code and Explanation)

Product Create-এর supplier ও pricing পরিবর্তনের জন্য কোনো নতুন route যোগ করা হয়নি। বিদ্যমান Product Create route-ই controller-এর `create()` এবং `store()` action ব্যবহার করে। Supplier-এর দ্রুত তৈরি modal-ও বিদ্যমান supplier create route ব্যবহার করে।

```php
// এই পরিবর্তনের জন্য নতুন route যোগ করা হয়নি।
```

অতএব route configuration বদলানো ছাড়াই নতুন form field এবং controller save flow কাজ করে।

## Model (Code and Explanation)

**ফাইল:** `app/Models/Product.php`

```php
protected $fillable = [
    'productName',
    'product_categorie_id',
    'brand_id',
    'supplier_id',
    'purchase_price',
    'price',
    'unit',
    'stock_unit',
    'purchase_date',
    'mfg_date',
    'expiry_date',
    'description',
    'img_url',
];

public function supplier()
{
    return $this->belongsTo(Supplier::class);
}
```

`supplier_id` ও `purchase_price` mass assignment-এ অনুমোদিত, তাই controller product তৈরির সময় সেগুলো সংরক্ষণ করতে পারে। `supplier()` relationship দিয়ে product-এর নির্বাচিত supplier পাওয়া যায়। পুরোনো `price` field-ই selling price; এটি purchase price-এর বিকল্প নয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/Product/product/createProduct.blade.php`

```blade
<select name="supplier_id" id="supplier_id">
    <option value="">Select Supplier</option>
    @foreach ($suppliers as $supplier)
        <option value="{{ $supplier->id }}"
            {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
            {{ $supplier->name }}
        </option>
    @endforeach
</select>

<label for="purchase_price">Purchase Order Price (৳)</label>
<input type="number" step="0.01" min="0" name="purchase_price"
    id="purchase_price" placeholder="0.00"
    value="{{ old('purchase_price') }}">

<label for="price">Product Selling Price (৳)</label>
<input type="number" step="0.01" name="price" id="price"
    placeholder="0.00" value="{{ old('price') }}" required>
```

Supplier dropdown ঐচ্ছিক এবং validation error-এর পর আগের নির্বাচন `old('supplier_id')` দিয়ে ধরে রাখে। Purchase Order Price আলাদা `purchase_price` field-এ নেওয়া হয়, decimal ধাপ ও শূন্যের minimum-সহ। বিদ্যমান `price` input অপরিবর্তিত database field ব্যবহার করে এবং label-এ স্পষ্ট করা হয়েছে যে এটি Product Selling Price।

Product Create form-এ supplier ও purchase price একসঙ্গে না দিলেও product তৈরি করা যায়। দুটোই থাকলে controller supplier ledger entry তৈরি করে; বিক্রয়ের সময় Product Selling Price দিয়েই invoice line-এর unit selling price নির্ধারিত হয়।

## পরিবর্তিত ফাইল

- `resources/views/backend/Product/product/createProduct.blade.php`
- `app/Http/Controllers/ProductController.php`
- `app/Models/Product.php`
- `database/migrations/2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php`
- `PRODUCT-SUPPLIER-PRICING-GUIDE.md`
