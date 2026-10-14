# Product Inventory Report — বাংলা বাস্তবায়ন নথি

## পরিবর্তনের পরিধি

Product Inventory রিপোর্টে এখন পণ্যভিত্তিক বিক্রি ও লাভের হিসাব, মেয়াদোত্তীর্ণ
পণ্যের মোট সংখ্যার কার্ড, এবং export-এ একই তথ্য অন্তর্ভুক্ত আছে। বিদ্যমান purchase,
stock, expiry ও supplier বিবরণ অপরিবর্তিত রাখা হয়েছে।

## Migration ও ডেটাবেস

- এই পরিবর্তনের জন্য নতুন migration তৈরি বা চালানো হয়নি; বিদ্যমান `products` ও
  `sale_items` কলাম দিয়েই হিসাব করা সম্ভব।
- `2026_07_06_172445_create_sale_items_table`-এ বিক্রির পণ্য, পরিমাণ, unit price এবং
  line total-এর মূল schema তৈরি হয়।
- `2026_10_11_000003_upgrade_sales_tables`-এ sale item-কে invoice ও product-এর সঙ্গে
  যুক্ত করার কলাম যোগ হয়।
- `2026_10_14_120000_add_discount_type_and_sale_item_purchase_price`-এ বিক্রির
  সময়কার `purchase_price` snapshot যোগ হয়; পুরোনো বিক্রির জন্য পণ্যের purchase price
  দিয়ে backfill করা হয়।
- Inventory-র purchase ও supplier তথ্য বিদ্যমান `supplierledgers` এবং `products`
  টেবিল থেকে আসে; এ কাজের জন্য সেগুলোর schema বদলানো হয়নি।

## Model ও relation

- `Product::purchaseLedgers()` একটি `hasMany` relation; পণ্যের `id`-এর সঙ্গে
  `supplierledgers.product_id` মিলিয়ে purchase entry আনে।
- `Product::supplier()` একটি `belongsTo` relation; ledger-এ supplier না থাকলে product-এর
  supplier-কে বিকল্প হিসেবে দেখানো হয়।
- `SaleItem::product()` sale item-কে পণ্যের সঙ্গে যুক্ত করে। Sale item-এ বিক্রির সময়কার
  `product_name` এবং `purchase_price` snapshot-ও থাকে।
- `SaleItem::invoice()` একটি sale item-কে তার `SaleInvoice`-এর সঙ্গে যুক্ত করে।
- নতুন model relation বা migration যোগ করা হয়নি; বিদ্যমান model ও sale_items-এর কলাম
  ব্যবহার করা হয়েছে।

## Controller ও হিসাব

`ProductInventoryReportController::reportRows()` রিপোর্ট পেজ ও CSV export-এর জন্য
একই পণ্য-সারির তথ্য তৈরি করে।

1. পণ্যগুলো নাম অনুযায়ী লোড হয়; `supplier` এবং `purchaseLedgers.supplier` eager-load
   হওয়ায় প্রতিটি পণ্যের supplier-এর জন্য আলাদা query লাগে না।
2. বর্তমান inventory-তে থাকা product ID-গুলোর sale item একবারে aggregate করা হয়।
   কোনো বিক্রি না থাকা পণ্যের বিক্রির পরিমাণ, আয়, খরচ ও লাভ শূন্য থাকে।
3. বিক্রির cost হিসাবের সময় sale item-এর `purchase_price` snapshot আগে নেওয়া হয়;
   সেটি না থাকলে বর্তমান `products.purchase_price`, সেটিও না থাকলে শূন্য ব্যবহৃত হয়।
4. পণ্যপ্রতি গড় purchase price হলো বিক্রি হওয়া quantity-র ওজনযুক্ত গড় ক্রয়মূল্য;
   গড় selling price হলো quantity-র ওজনযুক্ত গড় line total।
5. `Total Sales` হলো `sale_items.line_total`-এর যোগফল; `Total Cost` হলো quantity
   × কার্যকর purchase price-এর যোগফল; `Profit` হলো Total Sales − Total Cost।
   এই অংশটি Sales Report-এর Product-wise Sales & Profit হিসাব অনুসরণ করে।
6. `total_expired_products` গণনায় কেবল যেসব পণ্যের expiry date আজকের আগের দিন,
   তাদের ধরা হয়। আজ বা ভবিষ্যতের expiry-কে মেয়াদোত্তীর্ণ ধরা হয় না।
7. `index()` পেজে rows ও সারাংশ পাঠায়; `export()` একই rows থেকে সংখ্যাসহ
   Excel-compatible CSV তৈরি করে।

## Route

- `routes/web.php`-এর বিদ্যমান `GET /reports/product-inventory` route
  `product.inventory.report` নামে `ProductInventoryReportController::index()` চালায়।
- বিদ্যমান `GET /reports/product-inventory/export` route
  `product.inventory.report.export` নামে `export()` চালায়।
- দুটি route-ই আগে থেকে থাকা `auth` ও `verified` middleware group-এর মধ্যে আছে।
- এই feature-এর জন্য কোনো route পরিবর্তন করা হয়নি।

## Blade ও প্রদর্শন

`resources/views/backend/reports/productinventory.blade.php`-এ:

- আগের চারটি সারাংশ কার্ডের সঙ্গে `Expired Products` কার্ড যোগ হয়েছে।
- `Stock, Expiry & Supplier Details` টেবিলেই `Total Quantity Sold`, `Total Sales`
  এবং `Profit` কলাম দেখানো হয়; আলাদা Product-wise Sales & Profit section নেই।
- টেবিলের footer-এ purchase quantity/amount, stock, sold quantity, sales ও profit-এর
  মোট দেখানো হয়।
- Purchase Price, Selling Price ও Total Cost আলাদা কলাম হিসেবে দেখানো হয় না।
- আগের expiry এবং supplier-সংক্রান্ত তথ্য ও তার আচরণ অপরিবর্তিত আছে।
- Blade শুধু controller-এর প্রস্তুত করা তথ্য format করে; এতে database query বা
  business calculation নেই।

## যাচাই

`tests/Feature/ProductInventoryReportTest.php`-এ purchase/stock/supplier-এর আগের
যাচাইয়ের সঙ্গে sale item-এর purchase-price snapshot ও fallback, গড় দাম, বিক্রির
পরিমাণ, revenue, cost, profit এবং expired-product count যাচাই করা হয়েছে।
