# Product Inventory Report — বাস্তবায়ন নোট

এই নথিতে Product Inventory রিপোর্টের route, হিসাব, data source, model relation এবং Blade প্রদর্শনের বিবরণ রাখা হয়েছে। রিপোর্টে purchase, stock, expiry ও supplier তথ্য দেখা যায়। বিদ্যমান `supplierledgers` হলো purchase data source; `purchase_items` টেবিলে প্রয়োজনীয় purchase field না থাকায় রিপোর্ট সেটি ব্যবহার করে না।

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/ProductInventoryReportController.php`

```php
public function index(): View
{
    $rows = $this->reportRows();

    return view('backend.reports.productinventory', [
        'rows' => $rows,
        'total_products' => $rows->count(),
        'total_purchased_quantity' => $rows->sum('purchased_quantity'),
        'total_purchase_amount' => $rows->sum('purchase_amount'),
        'total_stock' => $rows->sum('stock'),
        'total_expired_products' => $rows
            ->where('expiry_status', 'expired')
            ->count(),
    ]);
}
```

`index()` পণ্যভিত্তিক row তৈরি করে এবং সেগুলো থেকে summary value হিসাব করে view-তে পাঠায়। `reportRows()` পণ্যগুলো নাম অনুযায়ী সাজিয়ে load করে। `supplier` ও `purchaseLedgers.supplier` eager-load করায় প্রতিটি পণ্যের জন্য supplier খুঁজতে আলাদা query চলে না (N+1 সমস্যা এড়ানো হয়)।

- **ক্রয়কৃত পরিমাণ:** প্রতিটি পণ্যের `supplierledgers.quantity`-এর যোগফল।
- **মোট ক্রয়মূল্য:** সেই পণ্যের `supplierledgers.total`-এর যোগফল।
- **বর্তমান মজুত:** `products.unit`; এটি ক্রয়ের যোগফল নয়, কারণ বিক্রির সময় এই field কমে।
- **মেয়াদ:** `products.expiry_date` এবং Product model-এর `isExpired()` / `isExpiringSoon()` দিয়ে status নির্ধারিত হয়। তারিখ না থাকলে Blade-এ “তারিখ নেই” দেখায়।
- **সরবরাহকারী ও ফোন:** ledger-এর supplier relation থেকে আসে। Ledger entry না থাকলে product-এর নিজস্ব `supplier` relation fallback হয়। একাধিক supplier থাকলে প্রত্যেকের নাম ও ফোন একই product row-তে আসে।
- সব summary item-wise rows যোগ করে তৈরি হয়। Ledger-এ purchase তথ্য না থাকলে purchase quantity ও amount শূন্য থাকে; বর্তমান stock-কে পুরোনো purchase হিসেবে ধরা হয়।

```php
'purchased_quantity' => $purchaseLedgers->sum(
    static fn (Supplierledger $ledger): float => (float) $ledger->quantity
),
'purchase_amount' => $purchaseLedgers->sum(
    static fn (Supplierledger $ledger): float => (float) $ledger->total
),
'stock' => (float) $product->unit,
'expiry_status' => $product->isExpired()
    ? 'expired'
    : ($product->isExpiringSoon() ? 'expiring' : 'valid'),
```

Product তৈরির সময় supplier ও purchase price দেওয়া থাকলে `ProductController::store()` ledger row তৈরি করে। Ledger amount `purchase_price × quantity` থেকে হিসাব হয়। Purchase source ও ledger entry-র ব্যাখ্যা নিচের Migration অংশেও দেওয়া আছে।

## Migration (Code and Explanation)

এই রিপোর্টের জন্য নতুন migration দরকার নেই; প্রয়োজনীয় schema field আগে থেকেই আছে। রিপোর্ট purchase তথ্যের জন্য `purchase_items` নয়, `supplierledgers` ব্যবহার করে।

- `2026_06_30_170258_create_products_table` product name, stock-এর `unit` এবং `expiry_date` তৈরি করে।
- `2026_10_12_095500_add_supplier_and_purchase_price_to_products_table` product-এ nullable `supplier_id` ও `purchase_price` যোগ করে।
- `2026_10_06_111603_create_supplierledgers_table` purchase-এর `supplier_id`, `product_id`, `quantity`, `price` ও `total` রাখে।
- `2026_07_06_172220_create_purchase_items_table`-এ বর্তমানে ID ও timestamps ছাড়া purchase field নেই। তাই রিপোর্ট ওই টেবিল ব্যবহার করে না।

```php
// Product Inventory Report-এর জন্য নতুন migration যোগ করা হয়নি।
```

Product তৈরির সময় supplier এবং purchase price দিলে `ProductController::store()` supplier ledger-এ purchase row তৈরি করে; `total` হলো `purchase_price × quantity`। Inventory report ওই ledger data যোগ করে product-এর purchase quantity ও amount দেখায়।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php`

```php
Route::get('/reports/product-inventory', [
    ProductInventoryReportController::class,
    'index',
])
    ->middleware('permission:product.report.inventory|reports.all_access')
    ->name('product.inventory.report');

Route::get('/reports/product-inventory/export', [
    ProductInventoryReportController::class,
    'export',
])
    ->middleware('permission:product.report.inventory|reports.all_access')
    ->name('product.inventory.report.export');
```

Report page route-টি বিদ্যমান `auth` ও `verified` middleware group-এর মধ্যে আছে। Report ও export—দুটির জন্যই `product.report.inventory` অথবা `reports.all_access` permission প্রয়োজন। Dashboard-এর **Inventory Reports → Product Inventory** menu report route ব্যবহার করে। এই রিপোর্টের জন্য route পরিবর্তন করা হয়নি।

`export()` একই প্রস্তুত করা product rows ব্যবহার করে Excel-compatible CSV পাঠায়, যাতে report page ও export-এ একই inventory হিসাব থাকে।

## Model (Code and Explanation)

### Product

**ফাইল:** `app/Models/Product.php`

```php
public function supplier()
{
    return $this->belongsTo(Supplier::class);
}

public function purchaseLedgers(): HasMany
{
    return $this->hasMany(Supplierledger::class, 'product_id');
}
```

`supplier()` product-এর নিজস্ব supplier ফেরত দেয় এবং ledger না থাকলে report-এ fallback হিসেবে কাজ করে। `purchaseLedgers()` product-এর সব purchase ledger row আনে। `supplierledgers.product_id` string হলেও এতে `products.id`-এর মান সংরক্ষিত হয়।

### Supplierledger

**ফাইল:** `app/Models/Supplierledger.php`

```php
public function supplier()
{
    return $this->belongsTo(Supplier::class);
}

public function product()
{
    return $this->belongsTo(Product::class, 'product_id');
}
```

`supplier()` ledger entry-র supplier-এর নাম ও ফোন আনে। `product()` ledger-এর product reference-কে Product model-এর সঙ্গে যুক্ত করে। Report-এর প্রয়োজনীয় relation-গুলো বিদ্যমান model-এ আছে; এই রিপোর্টের জন্য নতুন model যোগ হয়নি।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/reports/productinventory.blade.php`

```blade
<table id="myTable" class="table mb-0 align-middle table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th>Product</th>
            <th class="text-end">Purchased Qty</th>
            <th class="text-end">Purchase Amount</th>
            <th class="text-end">Current Stock</th>
            <th>Expiry</th>
            <th>Supplier</th>
            <th>Phone Number</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            {{-- controller থেকে পাওয়া প্রস্তুত তথ্য format করে দেখানো হয় --}}
        @endforeach
    </tbody>
</table>
```

Blade-এ summary card এবং product-ভিত্তিক report table দেখানো হয়। টেবিলে product-এর নাম, purchased quantity, purchase amount, sold quantity, total sales, profit, current stock, expiry date/status, supplier name ও supplier phone থাকে। সংখ্যাগুলো `number_format` দিয়ে format হয়; Blade-এ database query বা business calculation করা হয় না।

Inventory table-এর `id="myTable"` Product Index table-এর একই selector। Dashboard layout-এর বিদ্যমান DataTable initializer তাই search box, pagination ও page-size control যোগ করে।
