# পণ্যভিত্তিক বিক্রয় রিপোর্টে VAT — বাংলা ডকুমেন্টেশন

## উদ্দেশ্য

Sales Report-এর **Product-wise Sales & Profit** টেবিলের VAT কলামে প্রতিটি পণ্যের জন্য VAT দেখানো হয়। বিদ্যমান ব্যবস্থায় VAT আলাদা `sale_items` row-তে নয়, পুরো invoice-এর `sale_invoices.vat_amount` ঘরে রাখা হয়। তাই কোনো পণ্যের নামে invoice-এর সম্পূর্ণ VAT দেখালে তা ভুল হতো। রিপোর্টে invoice-এর VAT পণ্যের বিক্রয়মূল্যের অনুপাতে ভাগ করে দেখানো হয়।

## VAT ভাগ করার নিয়ম

প্রতিটি invoice-এর জন্য:

```text
পণ্যের বরাদ্দ VAT = invoice VAT × (পণ্যের line_total ÷ invoice subtotal)
```

উদাহরণ: invoice subtotal ৳২০০ এবং VAT ৳২০। একটি পণ্যের বিক্রয়মূল্য ৳৫০ হলে তার VAT হবে ৳৫; অন্য পণ্যের বিক্রয়মূল্য ৳১৫০ হলে তার VAT হবে ৳১৫। সব পণ্যের বরাদ্দ VAT যোগ করলে invoice VAT-ই পাওয়া যায়। `subtotal` শূন্য হলে ভাগ-শূন্য এড়াতে ঐ invoice-এর item VAT শূন্য ধরা হয়।

> **দ্রষ্টব্য:** VAT মূলত invoice-level অঙ্ক। এখানে দেখানো পণ্যভিত্তিক অঙ্ক হলো পণ্যের বিক্রয়মূল্য অনুযায়ী তার আনুপাতিক অংশ; এটি নতুন VAT rate নির্ধারণ করে না বা invoice-এ সংরক্ষিত VAT পরিবর্তন করে না।

## Migration ও Database

- এই পরিবর্তনের জন্য নতুন migration তৈরি বা চালানো হয়নি।
- VAT `sale_invoices.vat_amount`-এ সংরক্ষিত থাকে; বিদ্যমান `2026_10_11_000003_upgrade_sales_tables.php` migration-এ column-টি যোগ করার ব্যবস্থা আছে।
- `sale_items.line_total` ও `sale_invoices.subtotal` ব্যবহার করে অনুপাত নির্ণয় করা হয়।
- Schema, তথ্য সংরক্ষণ এবং বিক্রির VAT হিসাব অপরিবর্তিত।

## Model ও সম্পর্ক

- `SaleInvoice` model-এ `vat_amount` decimal cast হিসেবে আছে; `items()` relation invoice-এর পণ্যসারি ফেরত দেয়।
- `SaleItem` model-এর `invoice()` relation `sale_invoice_id` দিয়ে invoice-এ যুক্ত হয়; `product()` relation পণ্যের বর্তমান তথ্য দেয়।
- রিপোর্টের aggregate query সরাসরি `sale_items`-কে `sale_invoices`-এর সঙ্গে যুক্ত করে। প্রতিটি item-এর নাম ও বিক্রির সময়কার line total snapshot হিসেবে ব্যবহৃত হয়; এ কাজে model-এর relation বা fillable field পরিবর্তন লাগেনি।

## Controller-এর কাজ

`app/Http/Controllers/SalesReportController.php`-এর `index()`:

1. বিদ্যমান তারিখ, পণ্য, কর্মী ও গ্রাহক filter প্রয়োগ করে।
2. পণ্যভিত্তিক revenue, quantity, purchase cost ও profit-এর সঙ্গে invoice VAT-এর অনুপাতভিত্তিক অংশ যোগ করে `vat_amount` হিসেবে ফেরত দেয়।
3. একই filter মানা invoice ও item-এর ওপর VAT হিসাব করে।

`export()` একই হিসাব CSV-র Product-wise Sales & Profit অংশেও চালায় এবং VAT শিরোনাম ও পণ্যভিত্তিক মান যোগ করে। ফলে ওয়েব রিপোর্ট ও CSV-তে VAT-এর হিসাব একই।

## Route

- `GET /sales/report` — `sales.report` route, `SalesReportController@index` action।
- `GET /sales/report/export` — `sales.report.export` route, `SalesReportController@export` action।
- দুই route-ই বিদ্যমান `auth` ও `verified` middleware group-এর মধ্যে আছে। Route পরিবর্তন করা হয়নি।

## Blade View

`resources/views/backend/Sales/salesReport.blade.php`-এর Product-wise Sales & Profit টেবিল controller থেকে পাওয়া `vat_amount` দেখায় এবং footer-এ পণ্যগুলোর VAT যোগ করে। ফাঁকা ফলাফলের row-ও VAT কলামসহ টেবিলের মোট কলামসংখ্যার সঙ্গে মেলে।

## যাচাই

`tests/Feature/SalesReportTest.php`-এর feature test যাচাই করে যে invoice VAT পণ্যের বিক্রয়মূল্যের অনুপাতে ভাগ হয় এবং পণ্যগুলোর বরাদ্দ VAT-এর যোগফল invoice VAT-এর সমান থাকে।
