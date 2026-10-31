# Sales Document Formatting পরিবর্তনের নথি

## Controller (code and explanation)----

`app/Http/Controllers/SaleController.php` এখন sale invoice তৈরি করার সময় customer-এর নাম ও address snapshot হিসেবে invoice-এ রাখে; customer save না করলেও দেওয়া নাম/address থাকে। Invoice view-তে quotation তৈরির form-এর একই default Terms & Conditions পাঠানো হয়।

```php
'customer_name' => $customer?->name ?? $validated['customer_name'] ?? null,
'customer_address' => $customer?->address ?? $validated['customer_address'] ?? null,
```

## Migration (code and explanation)----

`database/migrations/2026_10_31_000002_add_customer_address_to_sale_invoices_table.php`-এ `sale_invoices.customer_address` nullable text column যোগ হয়েছে, যাতে sale তৈরির সময়কার address invoice-এ সংরক্ষিত থাকে।

```php
$table->text('customer_address')->nullable();
```

## Model (code and explanation)----

`app/Models/SaleInvoice.php`-এর fillable attribute-এ `customer_address` যোগ হয়েছে, যেন controller invoice তৈরির সময় snapshot field-টি সংরক্ষণ করতে পারে।

```php
'customer_address',
```

## Blade (code and explanation)----

`resources/views/backend/Sales/salesInvoice.blade.php` এবং `resources/views/backend/Sales/deliveryChallan.blade.php`-এ তারিখ শুধু দিন-মাস-বছর দেখায় এবং Customer section-এ নাম, phone ও address দেখায়। পুরোনো invoice-এ snapshot address না থাকলে linked customer-এর address ব্যবহার হয়। Sale Invoice-এ Create Quotation-এর একই default Terms & Conditions দেখানো হয়।

`resources/views/backend/Sales/salesInvoice.blade.php` এবং `deliveryChallan.blade.php`-এর পণ্যের table header-এর প্রথম row-এর background `#14ADE0` এবং text `#ffff` করা হয়েছে।

## পরীক্ষা

`tests/Feature/DeliveryChallanTest.php`-এ invoice/challan customer details, date, terms ও header color-এর assertion যোগ হয়েছে। `tests/Feature/SalePaymentTest.php`-এ customer save না করেও sale invoice-এ name/address snapshot সংরক্ষিত হওয়ার পরীক্ষা যোগ হয়েছে।
