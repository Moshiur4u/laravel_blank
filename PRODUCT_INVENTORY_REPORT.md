# Product Inventory Report — বাস্তবায়ন নোট

## Route

- `routes/web.php`-এ `GET /reports/product-inventory` route-টি
  `product.inventory.report` নামে নিবন্ধিত।
- Route-টি বিদ্যমান `auth` ও `verified` middleware group-এর মধ্যে আছে।
- Dashboard-এর **Inventory Reports → Product Inventory** menu এই route ব্যবহার করে।

## Controller ও হিসাব

- `ProductInventoryReportController::index()` পণ্যগুলো নাম অনুযায়ী সাজিয়ে আনে।
- `supplier` এবং `purchaseLedgers.supplier` eager-load করা হয়; এতে প্রতিটি পণ্যের জন্য
  আলাদা query চালিয়ে N+1 সমস্যা হয় না।
- প্রতি পণ্যের **ক্রয়কৃত পরিমাণ** হলো তার `supplierledgers.quantity`-এর যোগফল।
- **মোট ক্রয়মূল্য** হলো সেই পণ্যের `supplierledgers.total`-এর যোগফল।
- **বর্তমান মজুত** হলো `products.unit`। এটি ক্রয়ের যোগফল থেকে হিসাব করা হয় না, কারণ
  বিক্রির সময় এই field কমে।
- **মেয়াদ** হলো `products.expiry_date`। Product model-এর `isExpired()` এবং
  `isExpiringSoon()` দিয়ে মেয়াদোত্তীর্ণ, শিগগির মেয়াদ শেষ, অথবা মেয়াদ আছে status
  নির্ধারিত হয়। তারিখ না থাকলে Blade-এ “তারিখ নেই” দেখায়।
- **সরবরাহকারী ও ফোন** ledger-এর supplier relation থেকে আসে। কোনো ledger entry না থাকলে
  পণ্যের নিজস্ব `supplier` relation fallback হিসেবে ব্যবহৃত হয়। একাধিক supplier থাকলে
  প্রত্যেকের নাম ও নম্বর একই পণ্য row-তে দেখানো হয়।
- উপরের সব সারাংশ controller-এর item-wise rows যোগ করে তৈরি হয়। Ledger-এ purchase
  তথ্য না থাকলে purchase quantity ও amount শূন্য থাকে; বর্তমান stock-কে পুরোনো purchase
  হিসেবে ধরা হয় না।

## Blade

- `resources/views/backend/reports/productinventory.blade.php` চারটি summary card এবং
  সব product-এর item-wise report table দেখায়।
- টেবিলে পণ্যের নাম, ক্রয়কৃত পরিমাণ, মোট ক্রয়মূল্য, বর্তমান stock, expiry date/status,
  supplier name ও supplier phone থাকে।
- সংখ্যাগুলো `number_format` দিয়ে দেখানো হয়; Blade-এ database query বা business
  calculation করা হয় না।

## Model ও relation

- `Product::supplier()` হলো `belongsTo(Supplier::class)`; supplier ledger না থাকলে এটি
  fallback supplier দেখায়।
- নতুন `Product::purchaseLedgers()` হলো
  `hasMany(Supplierledger::class, 'product_id')`; প্রতিটি product-এর সব purchase ledger
  row দেয়।
- `Supplierledger::supplier()` হলো `belongsTo(Supplier::class)`; supplier-এর নাম ও
  ফোনের জন্য এটি eager-load করা হয়।
- `Supplierledger::product()` বিদ্যমান relation, যা ledger-এর `product_id`-কে Product-এর
  সঙ্গে যুক্ত করে।

## Migration ও data source

- এই রিপোর্টের জন্য নতুন migration দরকার নেই; schema-তে প্রয়োজনীয় field-গুলো ইতিমধ্যে আছে।
- `2026_06_30_170258_create_products_table` product name, stock-এর `unit` এবং
  `expiry_date` তৈরি করে।
- `2026_10_12_095500_add_supplier_and_purchase_price_to_products_table` product-এ
  nullable `supplier_id` ও `purchase_price` যোগ করে।
- `2026_10_06_111603_create_supplierledgers_table` purchase-এর `supplier_id`,
  `product_id`, `quantity`, `price` ও `total` রাখে।
- Product তৈরির সময় supplier ও purchase price দিলে `ProductController::store()` ledger
  row তৈরি করে; amount হিসাব করে `purchase_price × quantity`।
- `2026_07_06_172220_create_purchase_items_table`-এ বর্তমানে ID ও timestamps ছাড়া
  purchase field নেই। তাই রিপোর্ট এটি ব্যবহার করে না; বিদ্যমান কার্যকর purchase data
  source হলো `supplierledgers`।