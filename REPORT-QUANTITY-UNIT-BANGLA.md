# রিপোর্টে Quantity ও Unit একই কলামে — বাংলা নথি

## Controller (Code and Explanation)

### Product Inventory Report

**ফাইল:** `app/Http/Controllers/ProductInventoryReportController.php`

Inventory row-তে Product-এর মূল নাম এবং Unit-এর label আলাদা data হিসেবে প্রস্তুত
করা হয়। Blade ও CSV-তে প্রতিটি পরিমাণের সঙ্গে একই ঘরে Unit যোগ হয়। Unit-এর জন্য
আগে `quantity_size`, এরপর `stock_unit`, এবং দুটোই না থাকলে `Unit` fallback:

```php
'name' => $product->productName,
'unit_label' => $product->quantity_size ?: ($product->stock_unit ?: 'Unit'),
```

আগে `display_name` পণ্যের নামের সঙ্গে size জুড়ে দিত; তাই মূল Product নাম ব্যবহার
করে Quantity cell-এ Unit-সহ মান দেখানো হয়। Inventory CSV-তেও `Unit` আলাদা field
বাদ দিয়ে `Purchased Quantity / Unit`, `Quantity Sold / Unit`, `Current Stock / Unit`
cell-এ Unit-সহ মান লেখা হয়।

### Sales Report

**ফাইল:** `app/Http/Controllers/SalesReportController.php`

Product-wise Sales query `quantity_size` ও `stock_unit` দুটোই select/group করে:

```php
->select([
    'sale_items.product_id',
    'sale_items.product_name',
    'products.quantity_size',
    'products.stock_unit',
])
```

`quantity_size` ফাঁকা হলে `stock_unit` দিয়ে Unit দেখানো যায়। Sales Report table ও CSV
export-এ Unit-কে আলাদা কলাম না রেখে `Quantity Sold / Unit` cell-এ দেখানো হয়। Sale
Return report-এর Product relation-ও একই কারণে `productName`, `quantity_size`,
`stock_unit` সহ eager-load করা হয়।

### Sale Return ও Purchase Return

**ফাইল:** `app/Http/Controllers/ProductReturnController.php`

```php
->with(['returnedBy', 'invoice', 'product:id,productName,quantity_size,stock_unit'])
```

**ফাইল:** `app/Http/Controllers/PurchaseReturnController.php`

```php
->with(['returnedBy', 'product:id,productName,quantity_size,stock_unit'])
```

Report table-এর `Quantity / Unit` cell-এ Unit দেখানোর জন্য এই eager loading প্রয়োজন;
এতে প্রতি return row-তে আলাদা database query না করে Product-এর Unit পাওয়া যায়।

## Migration (Code and Explanation)

কোনো migration করা হয়নি। Product-এর বিদ্যমান `quantity_size` ও `stock_unit` field-ই
ব্যবহার করা হয়েছে।

## Route (Code and Explanation)

কোনো route পরিবর্তন করা হয়নি। আগের inventory, sales এবং return report route-এই
তথ্যগুলো দেখা যায়।

## Model (Code and Explanation)

কোনো model পরিবর্তন করা হয়নি। বিদ্যমান Product relation এবং Unit field ব্যবহার করা
হয়েছে। Product record না থাকলে return report-এ `Unit` fallback দেখায়।

## Blade Template (Code and Explanation)

নিচের report table-গুলোতে আলাদা `Unit` column না রেখে পরিমাণ ও Unit একই cell-এ
দেখানো হয়েছে:

- `resources/views/backend/reports/productinventory.blade.php` — Product, Purchased
  Qty, Total Qty Sold এবং Current Stock-এর প্রতিটি quantity cell-এ Unit।
- `resources/views/backend/Sales/salesReport.blade.php` — Product-wise Quantity Sold
  cell-এই Unit।
- `resources/views/backend/ProductReturns/report.blade.php` — Sale return quantity-র
  সঙ্গে Unit।
- `resources/views/backend/PurchaseReturns/report.blade.php` — Purchase return
  quantity-র সঙ্গে Unit।

```blade
<th class="text-end">Quantity / Unit</th>
<td class="text-end">
    {{ number_format($quantity, 2) }} {{ $product->quantity_size ?: ($product->stock_unit ?: 'Unit') }}
</td>
```

একটি ঘরে `5 pcs` বা `2 RIM` দেখালে আলাদা Unit column ছাড়াই পরিমাণের মাপ বোঝা যায়।
Footer-এ বিভিন্ন পণ্যের Unit আলাদা হতে পারে; তাই সামগ্রিক Quantity total-কে কোনো
একক দিয়ে দেখানো হয়নি।
