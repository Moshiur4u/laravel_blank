# Sales Report — বাংলা ডকুমেন্টেশন

## উদ্দেশ্য

Sales Report-এ তারিখের পরিসর, পণ্য, কর্মী এবং গ্রাহক অনুযায়ী বিক্রির তথ্য দেখা যায়। একই নির্বাচিত filter দিয়ে সারসংক্ষেপ, শীর্ষ পণ্য এবং invoice তালিকা তৈরি হয়। Dashboard-এর **Reports → Sale Reports → Sales Report** menu থেকে report-এ যাওয়া যায়।

## Route ও প্রবেশাধিকার

- বিক্রি তৈরি: `POST /sales`, route name `sales.store`, controller action `SaleController@store`।
- বিক্রির form: `GET /sales`, route name `sales.index`, controller action `SaleController@index`।
- Invoice দেখানো/প্রিন্ট: `GET /sales/invoice/{id}`, route name `sales.invoice`, controller action `SaleController@invoice`।
- `GET /sales/report`
- Route name: `sales.report`
- Controller action: `SalesReportController@index`
- Sales ও report route-গুলো `auth` এবং `verified` middleware-এর মধ্যে আছে।
- নতুন migration প্রয়োজন হয়নি। বর্তমান `sale_invoices`, `sale_items`, `customer_ledgers`, `products`, `users` এবং `customers` table ব্যবহার হয়েছে।

## বিক্রি ও Walk-in পেমেন্ট

`SaleController@store` সার্ভারে পণ্যের মূল্য, quantity, stock, discount, VAT এবং আগের বকেয়া যাচাই/হিসাব করে; browser-এর পাঠানো invoice total বিশ্বাস করে না।

- পরিচিত/সংরক্ষিত customer-এর ক্ষেত্রে `amount_paid` হলো ব্যবহারকারীর জমা দেওয়া টাকা; বাকি অংশ `due_amount` হয়। ফলে customer-এর partial payment আগের মতো চলে।
- Customer খুঁজে না পেয়ে checkout-এর SweetAlert-এ **No, walk-in** বাছলে invoice-এর `total_amount`-এর সমান `amount_paid` সংরক্ষণ হয় এবং due শূন্য থাকে। Browser-এ Amount Received স্বয়ংক্রিয়ভাবে পূর্ণ invoice total হয়; server-ও এই নিয়ম পুনরায় প্রয়োগ করে।
- Walk-in-এর টাকা সম্পূর্ণ গৃহীত হওয়ায় payment method আবশ্যক; না দিলে validation error আসে।
- SweetAlert-এর Yes বাছলে নাম আবশ্যক করে customer সংরক্ষণ হয়; Cancel করলে checkout বন্ধ থাকে। আগে থেকে থাকা customer lookup-এ পাওয়া গেলে customer save prompt দেখায় না।
- Sale invoice ও তার item-গুলো transaction-এ সংরক্ষিত হয় এবং product-এর stock কমে। Customer record থাকলেই customer ledger-এ sale/payment/due সংরক্ষিত হয়; Walk-in-এর customer record না থাকায় ledger row তৈরি হয় না।
- Sale invoice `total_amount`-এ পূর্বের বকেয়া যোগ থাকতে পারে; কিন্তু report-এর revenue-তে শুধু বর্তমান sale (subtotal − discount + VAT) যোগ হয়।

### SweetAlert ব্যবহার

- অজানা customer-এর save/walk-in/cancel সিদ্ধান্ত, customer name এবং payment method validation SweetAlert2-তে দেখায়।
- Laravel validation error checkout page-এ SweetAlert2 popup-এ আসে।
- Invoice তৈরির পর product stock ১০ বা কম হলে SweetAlert2 warning দেখায়।
- SweetAlert2 script `dashboard.dashboard` layout থেকে আসে; sale form ও invoice Blade এই layout extend করে।

## Filter-এর নিয়ম

- `start_date`: ঐ তারিখ থেকে invoice date ধরা হয়।
- `end_date`: ঐ তারিখ পর্যন্ত invoice date ধরা হয়; শুরুর তারিখের আগে হলে validation error দেখায়।
- `product_id`: যেসব invoice-এ নির্বাচিত পণ্য আছে সেগুলো রাখে।
- `employee_id`: `sale_invoices.user_id` দিয়ে বিক্রির সময়কার কর্মী filter করে।
- `customer_id`: `sale_invoices.customer_id` দিয়ে গ্রাহক filter করে।
- কোনো তারিখ না দিলে report বর্তমান দিনের তথ্য দিয়ে শুরু হয়। শুধু শুরুর তারিখ দিলে ঐ তারিখ থেকে বর্তমান পর্যন্ত, শুধু শেষ তারিখ দিলে শুরু থেকে ঐ তারিখ পর্যন্ত তথ্য আসে।
- filter ID অবশ্যই সংশ্লিষ্ট `products`, `users` বা `customers` table-এ থাকতে হবে।

## Controller-এর হিসাব

`app/Http/Controllers/SalesReportController.php`-এর `index` action:

1. Query string-এর filter validate করে।
2. একটি invoice query-তে তারিখ, কর্মী, গ্রাহক এবং পণ্য filter প্রয়োগ করে। পণ্য filter-এ `whereHas('items')` ব্যবহৃত হওয়ায় একই invoice একাধিকবার গণনা হয় না।
3. **Total Sale Amount** হিসাব করে `subtotal - discount_amount + vat_amount` যোগ করে। `total_amount` ব্যবহার করা হয় না, কারণ তাতে আগের বকেয়া অন্তর্ভুক্ত থাকতে পারে এবং সেটি নতুন বিক্রির revenue নয়।
4. **Total Due**-তে নির্বাচিত invoice-গুলোর `due_amount` যোগ হয়। এটি invoice-এ সংরক্ষিত বকেয়া; পূর্বের বকেয়া যোগ করে তৈরি হওয়া invoice-এর due-ও এতে থাকে।
5. **Total Sales (Invoices)** হলো filter মেলা invoice-এর সংখ্যা।
6. `sale_items`-কে `sale_invoices`-এর সঙ্গে যুক্ত করে একই date/employee/customer/product filter-এ সর্বোচ্চ revenue-এর পাঁচটি পণ্য নির্ণয় করে। Revenue হলো `sale_items.line_total`-এর যোগফল; পরিমাণ `quantity` থেকে যোগ হয়।
7. ২০টি করে invoice paginate করে এবং customer/employee relation eager-load করে।

## Model ও relation

- `SaleInvoice::customer()` — invoice-এর ঐচ্ছিক customer (`customer_id` null হলে Walk-in)।
- `SaleInvoice::employee()` — sale তৈরির কর্মী (`user_id`)।
- `SaleInvoice::items()` — invoice-এর সব `SaleItem`।
- `SaleItem::invoice()` ও `SaleItem::product()` — item-এর invoice এবং product; product name/দাম sale item-এ ঐ সময়ের snapshot।
- `Customer::ledgers()` — customer-এর sale/payment ledger; `CustomerLedger::customer()`, `employee()` ও `receiver()` যথাক্রমে customer, বিক্রির কর্মী ও payment গ্রহণকারী `User`-এর relation।
- `customer_ledgers.sale_invoice_id` database foreign key invoice-কে চিহ্নিত করে; `CustomerLedger` model-এ আলাদা invoice relation method নেই।

- `SaleItem::product()` বর্তমান `Product`-এর relation; product মুছে গেলেও invoice-এর পণ্য-নাম `sale_items.product_name`-এ snapshot থাকে।
- Product-এর top list snapshot নাম (`product_name`) দিয়ে দেখায়, তাই মুছে ফেলা product-এর ঐতিহাসিক বিক্রিও product filter না থাকলে তালিকায় আসতে পারে। Product filter কেবল বিদ্যমান `product_id` দিয়ে কাজ করে।
- Invoice-এ `customer_id` অনুপস্থিত হলে report customer dropdown দিয়ে ঐ invoice filter করতে পারে না; customer cell-এ সংরক্ষিত `customer_name` দেখায়, সেটি না থাকলে Walk-in Customer দেখায়।

## Blade View

`resources/views/backend/Sales/salesIndex.blade.php` sale item, customer lookup, invoice totals, payment input ও checkout form দেখায়। JavaScript live total হিসাব করে; SweetAlert-এ Walk-in বাছলে Amount Received-কে Invoice Total-এর সমান করে।

`resources/views/backend/Sales/salesInvoice.blade.php` invoice item, customer, employee এবং total/paid/due/payment-method breakdown দেখায়; কম stock-এর warning SweetAlert-এ দেয়।

`resources/views/backend/Sales/salesReport.blade.php`:

- তারিখ, পণ্য, কর্মী ও গ্রাহকের filter form দেখায়।
- Total Sale Amount, Total Due ও Total Sales cards দেখায়।
- Revenue অনুযায়ী শীর্ষ পাঁচটি পণ্য, বিক্রিত পরিমাণ ও revenue দেখায়।
- Invoice detail-এ তারিখ, invoice নম্বর, customer, employee, item সংখ্যা, sale amount ও due দেখায়।
- Print button browser-এর print dialog চালু করে; filter controls, navigation এবং pagination print-এ বাদ থাকে।

## Dashboard Menu

`resources/views/dashboard/dashboard.blade.php`-এর Sale Reports submenu-তে `sales.report` route-এর link যোগ হয়েছে। অন্য report menu বা dashboard metric পরিবর্তন করা হয়নি।

## Migration

এই feature-এর জন্য database schema পরিবর্তন হয়নি, তাই নতুন migration যোগ করা হয়নি। `sale_invoices`-এর `customer_id`, `user_id`, `total_amount`, `amount_paid`, `due_amount`; `sale_items`-এর invoice/product relation ও snapshot; এবং `customer_ledgers`-এর sale/payment fields বিদ্যমান schema-তেই আছে।

## Test

`tests/Feature/SalesReportTest.php` Sales Report filter ও aggregation যাচাই করে। `tests/Feature/SalePaymentTest.php` নিশ্চিত করে Walk-in sale-এ paid amount invoice total এবং due শূন্য হয়, কিন্তু পরিচিত customer-এর partial payment আগের মতো কাজ করে।
