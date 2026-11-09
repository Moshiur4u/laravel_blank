# Product Inventory Report — বাংলা বাস্তবায়ন নথি

Product Inventory রিপোর্টে পণ্যভিত্তিক বিক্রি ও লাভের হিসাব, মেয়াদোত্তীর্ণ
পণ্যের মোট সংখ্যার কার্ড, এবং export-এ একই তথ্য অন্তর্ভুক্ত আছে। বিদ্যমান purchase,
stock, expiry ও supplier বিবরণ অপরিবর্তিত রাখা হয়েছে।

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/ProductInventoryReportController.php`

### Report page ও সারাংশ

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

`index()` একই `reportRows()` থেকে পণ্যের row তৈরি করে এবং page-এর summary value পাঠায়। `total_expired_products`-এ শুধু `expired` status-সহ product গণনা করা হয়।

### বিক্রির পরিমাণ, আয় ও লাভ

```php
$netQuantity = 'CASE WHEN sale_items.quantity > COALESCE(report_item_returns.returned_quantity, 0)
    THEN sale_items.quantity - COALESCE(report_item_returns.returned_quantity, 0)
    ELSE 0 END';
$netRevenue = "CASE WHEN sale_items.quantity > 0
    THEN sale_items.line_total * ({$netQuantity}) / sale_items.quantity
    ELSE 0 END";
$unitCost = 'COALESCE(sale_items.purchase_price, products.purchase_price, 0)';

$salesByProduct = SaleItem::query()
    ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
    ->leftJoinSub($returnTotalsByItem, 'report_item_returns',
        'report_item_returns.sale_item_id', '=', 'sale_items.id')
    ->whereIn('sale_items.product_id', $products->pluck('id'))
    ->select('sale_items.product_id')
    ->selectRaw("SUM({$netQuantity}) as quantity_sold")
    ->selectRaw("SUM({$netRevenue}) as revenue")
    ->selectRaw("SUM(({$netQuantity}) * {$unitCost}) as total_cost")
    ->selectRaw("SUM(({$netRevenue}) - (({$netQuantity}) * {$unitCost})) as profit")
    ->groupBy('sale_items.product_id')
    ->get()
    ->keyBy('product_id');
```

`reportRows()`-এ products নাম অনুযায়ী load হয় এবং `supplier`, `purchaseLedgers.supplier` eager-load করা হয়, যাতে supplier দেখাতে প্রতিটি row-তে আলাদা query না লাগে। Product return-এর পরিমাণ বাদ দিয়ে net quantity ও proportional net revenue গণনা করা হয়। Cost-এর জন্য বিক্রির সময়কার `sale_items.purchase_price` snapshot আগে নেওয়া হয়; snapshot না থাকলে বর্তমান `products.purchase_price`, সেটিও না থাকলে শূন্য ব্যবহৃত হয়। Profit হলো net revenue থেকে net cost বাদ।

প্রতি product-এর average purchase price হলো বিক্রিত net quantity-র ওজনযুক্ত গড় cost; average selling price হলো net revenue-র ওজনযুক্ত গড় unit price। কোনো বিক্রি না থাকলে বিক্রি, আয়, cost ও profit শূন্য। `Total Sales` হলো net `sale_items.line_total`-এর return-adjusted যোগফল; `Total Cost` হলো net quantity × কার্যকর purchase price-এর যোগফল।

Product-এর supplier list purchase ledger থেকে তৈরি হয়; ledger supplier না থাকলে product-এর নিজস্ব supplier বিকল্প হিসেবে দেখানো হয়। Purchased quantity ও purchase amount ledger থেকে আসে; stock `products.unit` থেকে আসে। Expiry status `Product::isExpired()` এবং `Product::isExpiringSoon()` দিয়ে নির্ধারিত হয়। Expiry date আজকের আগের দিন হলে expired; আজ বা ভবিষ্যতের expiry expired নয়।

### CSV export

```php
public function export(): StreamedResponse
{
    $rows = $this->reportRows();

    return ExcelCsvExport::download(
        'product-inventory-report-'.now()->format('Ymd-His').'.csv',
        function (callable $writeRow) use ($rows): void {
            // summary ও column header লেখা হয়।
            foreach ($rows as $row) {
                // report page-এ ব্যবহৃত একই প্রস্তুত করা row export হয়।
            }
        }
    );
}
```

`export()` একই `reportRows()` ব্যবহার করে, তাই report page ও CSV-তে একই পণ্যের হিসাব থাকে। Export-এ summary-সহ purchase, stock, expiry, supplier এবং বিক্রি, revenue, cost ও profit column লেখা হয়।

## Migration (Code and Explanation)

এই report পরিবর্তনের জন্য নতুন migration তৈরি বা চালানো হয়নি; বিদ্যমান `products`, `supplierledgers` ও `sale_items` field ব্যবহার করা হয়।

- `database/migrations/2026_07_06_172445_create_sale_items_table.php` sale item schema-র প্রাথমিক migration।
- `database/migrations/2026_10_11_000003_upgrade_sales_tables.php` sale item-কে invoice ও product-এর সঙ্গে যুক্ত করার field যোগ করে।
- `database/migrations/2026_10_14_120000_add_discount_type_and_sale_item_purchase_price.php` sale item-এ বিক্রির সময়কার `purchase_price` snapshot যোগ করে। পুরোনো sale-এর জন্য product-এর বর্তমান purchase price দিয়ে backfill করার চেষ্টা করে।
- Inventory-র purchase ও supplier তথ্য বিদ্যমান `supplierledgers` এবং `products` table থেকে আসে; এই report-এর জন্য ওই schema বদলানো হয়নি।

```php
// Product Inventory Report-এর জন্য নতুন migration বা database column যোগ করা হয়নি।
```

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

দুটি GET route-ই বিদ্যমান `auth` ও `verified` middleware group-এর মধ্যে আছে এবং report permission অথবা broad report access-সহ ব্যবহারকারীদের জন্য। Route পরিবর্তন করা হয়নি; page route `index()` এবং CSV route `export()` চালায়।

## Model (Code and Explanation)

### Product-এর supplier ও purchase ledger

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

`purchaseLedgers()` product ID-র সঙ্গে `supplierledgers.product_id` মেলায়—ledger column string হলেও তাতে product ID রাখা হয়। `supplier()` product-এ থাকা supplier reference আনে। Report এই relation দুটো ব্যবহার করে purchase totals ও supplier details তৈরি করে।

### Sale item-এর product, return ও purchase-price snapshot

**ফাইল:** `app/Models/SaleItem.php`

```php
public function invoice()
{
    return $this->belongsTo(SaleInvoice::class, 'sale_invoice_id');
}

public function product()
{
    return $this->belongsTo(Product::class);
}

public function returns(): HasMany
{
    return $this->hasMany(ProductReturn::class);
}

public function getEffectivePurchasePriceAttribute(): float
{
    if ($this->purchase_price !== null) {
        return (float) $this->purchase_price;
    }

    return (float) ($this->product?->purchase_price ?? 0);
}
```

`SaleItem`-এ বিক্রির সময়কার `product_name` এবং `purchase_price` snapshot থাকে। Effective purchase price accessor snapshot থাকলে সেটি, না থাকলে product-এর বর্তমান purchase price ফেরত দেয়। Report aggregate query-তে একই fallback নিয়ম ব্যবহার হয়, আর `returns()` relation দিয়ে ফেরত পণ্যের quantity হিসাব করা যায়।

নতুন Model বা relation যোগ করা হয়নি; report-এর জন্য বিদ্যমান Model ও `sale_items` column ব্যবহার করা হয়েছে।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/reports/productinventory.blade.php`

```blade
<div class="text-muted">Expired Products</div>
<div class="fs-4 fw-bold text-danger">
    {{ number_format($total_expired_products) }}
</div>

<table id="myTable" class="table mb-0 align-middle table-bordered table-striped">
    {{-- product, purchase, sale, profit, stock, expiry ও supplier column --}}
</table>
```

View-তে আগের summary card-গুলোর সঙ্গে `Expired Products` card দেখানো হয়। `Stock, Expiry & Supplier Details` table-তেই `Total Qty Sold`, `Total Sales` এবং `Profit` column যোগ আছে; আলাদা Product-wise Sales & Profit section নেই। Footer-এ purchased quantity, purchase amount, sold quantity, sales, profit ও stock-এর মোট দেখানো হয়।

Purchase Price, Selling Price ও Total Cost আলাদা table column হিসেবে দেখানো হয় না। আগের expiry ও supplier detail এবং তার আচরণ বজায় আছে। Blade শুধু controller-এর প্রস্তুত করা তথ্য format ও display করে; database query বা business calculation view-তে নেই.

Table-এ `id="myTable"` selector Product Index table-এর selector-টির সঙ্গে এক। Dashboard layout-এর বিদ্যমান DataTable initializer তাই search box, pagination ও page-size control যোগ করে। Print style landscape page-এ report রাখে এবং dashboard navigation ও action button লুকায়।

## যাচাই

`tests/Feature/ProductInventoryReportTest.php`-এ route registration এবং report row-এর purchase/stock/supplier তথ্যের পাশাপাশি returned quantity বাদ দিয়ে বিক্রির পরিমাণ, purchase-price snapshot ও fallback, average price, revenue, cost, profit এবং expired-product count যাচাই করা হয়।
