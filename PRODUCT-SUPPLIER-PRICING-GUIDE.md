# Product Supplier ও Pricing - শেখার নোট

এই পরিবর্তনে Product Create form থেকে supplier নির্বাচন এবং purchase order price সংরক্ষণ করা যায়। আগের `price` field-টি Product Selling Price হিসেবে রাখা হয়েছে; অন্য product field বা বিদ্যমান row-এর মান পরিবর্তন করা হয়নি।

## কাজের ধাপ

1. **Supplier list আনা:** `ProductController::create()` supplier-গুলো নাম অনুযায়ী সাজিয়ে form-এ পাঠায়, যাতে dropdown-এ সহজে খুঁজে পাওয়া যায়।
2. **Supplier নির্বাচন:** `createProduct.blade.php`-এর dropdown-এ supplier ID পাঠানো হয়। Field ঐচ্ছিক, তাই supplier ছাড়া product তৈরির আগের আচরণ বজায় থাকে।
3. **Purchase order price:** form-এর `purchase_price` field numeric এবং zero-or-greater মান নেয়। এটি selling price field-টির সঙ্গে মেশানো হয় না।
4. **Selling price:** form-এ আগের `price` input এবং database column অক্ষত আছে; শুধু label এখন `Product Selling Price`। বিদ্যমান listing ও বিক্রির logic আগের `price`-ই ব্যবহার করতে পারে।
5. **Validation ও save:** supplier ID `suppliers` table-এ আছে কি না এবং purchase price non-negative numeric কি না controller যাচাই করে। দুটো মান একসঙ্গে দিতে হয়, অথবা দুটোই বাদ দেওয়া যায়।
6. **Model relationship:** `Product::supplier()` product থেকে তার নির্বাচিত supplier-কে Eloquent relationship দিয়ে পাওয়া সম্ভব করে।
7. **Database migration:** nullable `supplier_id` ও `purchase_price` column যোগ করা হয়। ফলে পুরোনো product-এর জন্য কোনো মান অনুমান বা rewrite হয় না। কোনো supplier delete হলে foreign key product row রেখে supplier reference null করে।
8. **Migration rollback:** `down()` শুধু এই migration-এর foreign key ও নতুন column সরায়।

## Product তৈরি হলে Supplier Ledger

1. **একসঙ্গে তথ্য দেওয়া:** supplier ও purchase price দুটোই ফাঁকা থাকলে আগের মতো supplier ছাড়া product তৈরি হয়। supplier নির্বাচন করলে purchase price-ও দিতে হয়; price দিলে supplier-ও দিতে হয়।
2. **একটি transaction:** product save এবং তার supplier ledger entry database transaction-এর মধ্যে হয়। যেকোনো একটি database save ব্যর্থ হলে দুটোই rollback হয়, তাই অসম্পূর্ণ product-ledger জুটি থাকে না।
3. **Ledger সংযোগ:** নতুন product save হওয়ার পর তার ID, supplier ID, quantity, purchase price ledger-এ রাখা হয়। Ledger-এর `price` হলো প্রতি-unit purchase price; `invoice_id` আগের schema-তে optional, তাই অজানা supplier invoice number বানিয়ে দেওয়া হয় না।
4. **মোট ক্রয় ও বকেয়া:** `total = purchase_price × product quantity`। নতুন entry-তে আগের কোনো payment নেই বলে `payment = 0.00` এবং `due = total`। হিসাবটি দুই decimal স্থানে round করে ledger-এর বিদ্যমান string field-এ decimal text হিসেবে লেখা হয়।
5. **বিক্রয়মূল্য অপরিবর্তিত:** `price`-এ product selling price আগের মতোই থাকে; supplier ledger-এর unit purchase price কেবল `purchase_price` থেকে নেওয়া হয়।
6. **Sales invoice price:** sale তৈরি হওয়ার সময় `SaleController` product-এর `price` (Product Create-এর selling price) নেয় এবং sale item-এর `unit_price`-এ সেই সময়কার দাম সংরক্ষণ করে। Sales invoice ওই snapshot-টিকেই `Unit Selling Price` নামে দেখায়, ফলে পরে product-এর selling price বদলালেও পুরোনো invoice-এর হিসাব বদলায় না।

## পরিবর্তিত ফাইল

- `resources/views/backend/Product/product/createProduct.blade.php`
- `app/Http/Controllers/ProductController.php`
- `app/Models/Product.php`
- `database/migrations/2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php`
- `PRODUCT-SUPPLIER-PRICING-GUIDE.md`

নতুন field database-এ কার্যকর করতে deployment-এর সময় স্বাভাবিক Laravel migration চালাতে হবে: `php artisan migrate`। এই workspace-এর database-এ schema migration আগেই প্রয়োগ করা হয়েছে।
