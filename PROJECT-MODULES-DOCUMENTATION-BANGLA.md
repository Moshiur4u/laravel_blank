# প্রকল্পের সকল মডিউল: Controller, Migration, Model, Blade ও Seeder নথি

এই নথিতে প্রকল্পের অনুরোধকৃত সব মডিউলের **Controller (কোড, লজিক, ফাংশন ও ব্যাখ্যা), Migration (কোডের কাঠামো, কলাম ও সম্পর্ক), Blade (ফর্ম, রিপোর্ট ও UI), Model (fillable, cast, relationship ও helper), Seeder (প্রাথমিক ডেটা ও run order)** একত্রে লেখা হয়েছে। প্রতিটি মডিউলে Controller, Migration এবং Blade আলাদা শিরোনামে আছে; Models ও Seeders-এর ফাইল ও কাজের বিবরণ পরে পৃথক অংশে দেওয়া হয়েছে। Code block-গুলো বাস্তব source থেকে নির্বাচিত প্রাসঙ্গিক অংশ—পূর্ণ source file-এর প্রতিলিপি নয়।

## বিষয়সূচি

1. [Dashboard](#১-dashboard)
2. [Sales ও Invoice](#২-sales-ও-invoice)
3. [Quotations](#৩-quotations)
4. [Product ও Category](#৪-product-ও-category)
5. [Customer ও Customer Ledger](#৫-customer-ও-customer-ledger)
6. [Brand](#৬-brand)
7. [Supplier List ও Ledger Details](#৭-supplier-list-ও-ledger-details)
8. [Accounts: Employee Payment ও Debit/Credit Voucher](#৮-accounts-employee-payment-ও-debitcredit-voucher)
9. [Employee ও User Management](#৯-employee-ও-user-management)
10. [Roles ও Permissions](#১০-roles-ও-permissions)
11. [Organization Information](#১১-organization-information)
12. [Combined Voucher Report](#১২-combined-voucher-report)
13. [Daily Employee Voucher Report](#১৩-daily-employee-voucher-report)
14. [Daily Debit/Credit Voucher Report](#১৪-daily-debitcredit-voucher-report)
15. [Sales Report](#১৫-sales-report)
16. [Product Inventory Report](#১৬-product-inventory-report)
17. [User Activity Log](#১৭-user-activity-log)
18. [সিডার ও সাধারণ প্রবেশাধিকার](#১৮-সিডার-ও-সাধারণ-প্রবেশাধিকার)
19. [Models ও সম্পর্ক](#১৯-models-ও-সম্পর্ক)
20. [Seeder-এর ডেটা ও কার্যপ্রবাহ](#২০-seeder-এর-ডেটা-ও-কার্যপ্রবাহ)
21. [অতিরিক্ত Controller code excerpt](#২১-অতিরিক্ত-controller-code-excerpt)
22. [মডিউলভিত্তিক Model ও Seeder সূচি](#২২-মডিউলভিত্তিক-model-ও-seeder-সূচি)

---

## ১. Dashboard

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/DashboardConrtoller.php`

- `dashboard()` সরাসরি `dashboardbody()`-এর ফল ফেরত দেয়।
- `dashboardbody()` আজ, চলতি সপ্তাহ, মাস ও বছরের শুরু/শেষের সীমানা তৈরি করে; তারপর প্রতিটি সময়সীমার KPI, চার্ট-ডেটা এবং top selling তালিকা প্রস্তুত করে।
- `dailyTotals()` একটি নির্দিষ্ট table/date column/amount expression-এর date-wise যোগফল দেয়। Voucher-এর ক্ষেত্রে ঐচ্ছিক voucher type filter প্রয়োগ করে।
- Revenue হিসাব হয় `subtotal - discount_amount + vat_amount` থেকে; invoice-এর `previous_due`-কে নতুন বিক্রয় আয় ধরা হয় না।
- ব্যয় = employee payment voucher + Debit voucher − Credit voucher। লাভ = revenue − purchase − expense। Purchase সময় নির্ধারিত হয় supplier ledger row-এর `created_at` দিয়ে।
- চার্টের daily/weekly/monthly/yearly bucket-এ Revenue এবং Profit তৈরি হয়। বছরের bucket মাসভিত্তিক; অন্যগুলো দিনভিত্তিক।
- Top five product-এ মোট বিক্রিত quantity, revenue ও লাভ দেখানো হয়। Sale item-এর purchase-price snapshot থাকলে সেটি ব্যবহার হয়; না থাকলে বর্তমান Product-এর purchase price বিকল্প।
- Top five employee-কে sales amount অনুযায়ী সাজানো হয়; invoice-এর `total_amount - previous_due` বিক্রয় হিসেবে গণনা হয়।

```php
$revenue = (clone $invoiceTotals)
    ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount), 0) as total')
    ->value('total');

$expenses = (float) $employeeVoucherTotal
    + (float) $debitVoucherTotal
    - (float) $creditVoucherTotal;

$profit = round((float) $revenue - (float) $purchaseTotal - $expenses, 2);
```

### Migration (Code, logic, functions, explanation)

Dashboard-এর জন্য আলাদা table নেই। এটি `sale_invoices`, `sale_items`, `products`, `supplierledgers`, `employee_payment_vouchers`, `voucher_entries` এবং `users` থেকে aggregate তৈরি করে। এই টেবিলগুলোর schema নিজ নিজ Sales, Product, Supplier ও Accounts মডিউলের migration-এ বর্ণিত।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/dashboard/dashboard-body.blade.php`, `resources/views/dashboard/dashboard.blade.php`

- Dashboard body-তে configuration array ও `@foreach` দিয়ে Purchase, Revenue, Due, Expense এবং Net Profit card render হয়।
- Profit card-এ yearly figure বড় করে এবং daily/weekly figure ছোট করে দেখানো হয়; অন্যান্য KPI-তে monthly, weekly ও daily মান আসে।
- Chart.js-এ `@json($profitChart)` পাঠিয়ে revenue bar ও profit line দেখানো হয়। Selector দিয়ে daily/weekly/monthly/yearly data বদলানো যায়।
- Top Selling Products এবং Top Sales Employees-এর টেবিল empty state-সহ render হয়।
- Dashboard layout-এ container/table/image preview-এর DOM element আছে কি না যাচাই করে script চালানো হয়। `public/assets/js/app.js`-এ notification scroll element অনুপস্থিত হলেও sidebar/menu initialization থেমে না যাওয়ার guard আছে।
- `AppServiceProvider` dashboard layout-এ organization, এক মাসের মধ্যে/তার আগের expiry notification এবং ১০ বা কম stock-এর low-stock notification দেয়।

---

## ২. Sales ও Invoice

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/SaleController.php`

- `index()` product-গুলো নাম অনুযায়ী আনে এবং সর্বশেষ ১০টি invoice কর্মীর সম্পর্কসহ sales form-এ পাঠায়।
- `customerByPhone()` phone validate করে customer খোঁজে। না পেলে `found: false`; পেলে profile, opening due-সহ balance এবং সর্বশেষ ledger entries JSON হিসেবে দেয়।
- `store()` form input validate করে এবং database transaction-এর ভেতরে sale সম্পন্ন করে।
- একই cart-এ একই product একাধিকবার থাকলে quantity যোগ করে Product row-গুলো ID অনুযায়ী `lockForUpdate()`-এ lock করে। পর্যাপ্ত stock না থাকলে validation error হয়।
- Phone-এ customer পাওয়া গেলে সেই customer ব্যবহার হয়; না পাওয়া গেলে এবং `save_customer=yes` হলে temporary customer তৈরি হয়। Walk-in sale-এ customer ledger তৈরি হয় না।
- Unit price Product-এর `price` থেকে server-side নেওয়া হয়; browser থেকে পাঠানো total-কে চূড়ান্ত মান হিসেবে ধরা হয় না। Discount, VAT, previous due, invoice total, payment এবং due server-side-এ হিসাব হয়। Discount subtotal ছাড়াতে পারে না; payment invoice total ছাড়াতে পারে না।
- নতুন invoice number `INV-তারিখ-এলোমেলো suffix` আকারে তৈরি হয়। Sale item-এ product name ও ক্রয়মূল্য snapshot সংরক্ষণ হয়; Product stock কমে।
- Customer-যুক্ত sale-এ CustomerLedger row একই transaction-এ তৈরি হয়। সফল save হলে invoice page-এ redirect এবং auto-print/low-stock flash data পাঠানো হয়।
- `invoice()` invoice, item/product, customer ও employee load করে printable invoice view দেয়।

```php
$unitPrice = round((float) $product->price, 2);
$lineTotal = round($unitPrice * $quantity, 2);

$saleAmount = round($subtotal - $discount + $vat, 2);
$invoiceTotal = round($saleAmount + ($includePreviousDue ? $previousDue : 0), 2);
$due = round($invoiceTotal - $paymentAmount, 2);
```

### Migration (Code, logic, functions, explanation)

- `2026_07_06_172251_create_sale_invoices_table.php` ও `2026_07_06_172445_create_sale_items_table.php` মূলত id/timestamps দিয়ে টেবিল তৈরি করে।
- `2026_10_11_000003_upgrade_sales_tables.php` পুরোনো টেবিল না ভেঙে invoice number, customer/user reference, date, subtotal, discount amount, VAT, previous due, total/paid/due, payment method ও notes যোগ করে। Sale item-এ invoice/product foreign key, নাম snapshot, quantity, unit price ও line total যোগ করে; customer ledger-এ invoice linkage/payment method যোগ করে।
- `2026_10_14_120000_add_discount_type_and_sale_item_purchase_price.php` invoice-এ `discount_type`/`discount_value`, sale item-এ `purchase_price` snapshot যোগ করে। পুরোনো item-এর purchase price Product table থেকে backfill করার চেষ্টা করে।
- Customer মুছে গেলে invoice-এর customer reference null হয়; invoice মুছলে তার items cascade delete হয়; product মুছে গেলে item-এর product reference null হলেও পণ্যের নাম snapshot থাকে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Sales/salesIndex.blade.php`, `salesInvoice.blade.php`

- Sales form-এ searchable product picker, SKU, stock, quantity, item price/line total, add/remove item control আছে।
- Customer phone lookup-এ JSON response দিয়ে গ্রাহকের তথ্য ও আগের due পূরণ হয়। Customer save করা হবে কি না তা hidden `save_customer` field নিয়ন্ত্রণ করে।
- Discount, VAT, payment method, paid amount এবং previous due যোগ করার form control-গুলো client-side preview-কে সমর্থন করলেও চূড়ান্ত হিসাব server-side হয়।
- Invoice view-তে organization header, invoice/customer/employee তথ্য, item table, subtotal, discount, VAT, previous due, paid ও due দেখানো হয় এবং print/auto-print flow সমর্থিত।

---

## ৩. Quotations

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/QuotationController.php`

- `index()` quotation-গুলো user ও items-সহ date/ID অনুযায়ী newest-first দেয়।
- `create()` alphabetically sorted Product, Organization, চলতি মাসের `QT-YYYYMM-0001` ধারার পরবর্তী preview number এবং default terms পাঠায়।
- `store()` customer, date, item, discount, VAT, terms ও notes validate করে। Product ID দিলে সেটি products table-এ থাকতে হবে; custom product line-এ ID ঐচ্ছিক।
- প্রতিটি item-এর line total ও subtotal সার্ভার-side গণনা হয়। Flat discount subtotal ছাড়ায় না; percentage discount subtotal-এর উপর হিসাব হয়; VAT discounted taxable subtotal-এর উপর।
- Header এবং items একটি transaction-এ তৈরি হয়। কোটেশন তারিখের মাসের sequence দেখে unique quotation number তৈরি হয় এবং current user ID যুক্ত হয়।
- `show()` item ও user load করে organization-সহ quotation নথি দেখায়।
- `destroy()` quotation মুছে index-এ ফেরায়; item cascade delete schema-তে নির্ধারিত।

```text
Line total = Quantity × Unit price
Discount   = Percentage হলে Subtotal × হার ÷ 100; Flat হলে min(Flat amount, Subtotal)
VAT        = (Subtotal − Discount) × VAT rate ÷ 100
Grand total= (Subtotal − Discount) + VAT
```

### Migration (Code, logic, functions, explanation)

**ফাইল:** `2026_10_25_000002_create_quotations_table.php`

`quotations` table-এ unique number, তারিখ, customer, subtotal/discount/VAT/total, terms, notes ও nullable user foreign key থাকে। `quotation_items`-এ quotation foreign key cascade delete, nullable Product foreign key null-on-delete, product name snapshot, unit price, quantity ও line total থাকে। `down()` আগে items, পরে quotations table drop করে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/quotation/index.blade.php`, `create.blade.php`, `show.blade.php`

- Index-এ quotation directory ও open/delete actions।
- Create form-এ customer details, organization branding, product lines, discounts/VAT, terms এবং notes।
- Show-তে issuer ও customer দুই পক্ষের পরিচয়, reference/date, itemized table, monetary summary এবং print action।
- Print layout-এ action toolbar বাদ পড়ে; view-এর client-side হিসাব preview মাত্র, save-এর সময় controller পুনরায় হিসাব করে।

---

## ৪. Product ও Category

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/ProductController.php`, `ProductCategoryController.php`

**ProductController**

- `index()` Product-এর category ও brand eager-load করে তালিকা দেয়।
- `create()` category, brand এবং নাম অনুযায়ী supplier list form-এ দেয়।
- `store()` optional image validate/upload করে Product তৈরি করে। Supplier এবং purchase price দুটোই থাকলে transaction-এ supplier ledger row তৈরি করে; `total = purchase_price × unit`, initial payment zero এবং due total-এর সমান।
- `edit()` নির্বাচিত Product এবং category/brand/supplier options দেয়।
- `update()` Product ও Supplier Ledger transaction-এ সমন্বয় করে। নতুন supplier ও purchase price থাকলে ledger total পুনর্গণনা করে; existing payment অক্ষত রেখে `due = max(0, new total − paid)`। Ledger না থাকলে নতুন ledger row তৈরি হয়। Supplier বা purchase price বাদ গেলে বিদ্যমান ledger-কে এই method পরিবর্তন করে না।
- ছবির ক্ষেত্রে নতুন upload থাকলেই পুরোনো ছবি মুছে নতুন ছবি রাখা হয়। `destroy()` Product এবং থাকলে তার image file মুছে দেয়।

```php
$newTotal = round((float) $newPurchasePrice * $newUnit, 2);
$paidAmount = (float) $existingLedger->payment;
$newDue = max(0.0, $newTotal - $paidAmount);
```

**ProductCategoryController**

- `index()` category list, `create()` create form ও list, `store()` validated category name save করে।
- `edit()` নির্বাচিত category form-এ পাঠায়; `update()` name validate করে পরিবর্তন করে; `destroy()` row delete করে।

### Migration (Code, logic, functions, explanation)

- `2026_06_20_153436_create_product_Categories_table.php`: category entity/table।
- `2026_06_30_170258_create_products_table.php`: নাম, category/brand references, sale price, unit/stock unit, purchase/manufacture/expiry date, description ও image।
- `2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php`: nullable supplier foreign key এবং nullable purchase price যোগ করে; supplier delete হলে reference null।
- `2026_10_25_000001_make_product_fields_nullable_in_products_table.php`: category ID, brand ID, price ও unit nullable করে; `down()`-এ non-nullable ফিরিয়ে দেয়।

### Blade (Code, logic, functions, explanation)

**Product views:** `resources/views/backend/Product/product/indexProduct.blade.php`, `createProduct.blade.php`, `editProduct.blade.php`
**Category views:** `resources/views/backend/Product/Category/productCategoryindex.blade.php`, `ProductCategoryCreate.blade.php`, `Categoryedit.blade.php`

- Product list-এ catalog values, related category/brand, image এবং CRUD actions।
- Create/edit form-এ category, brand, supplier, sale/purchase price, stock quantity, date, description ও image input।
- Edit form `multipart/form-data` এবং `PUT` method ব্যবহার করে; validation errors সংশ্লিষ্ট field-এর পাশে দেখায়।
- Category views-এ category list/create/edit form এবং route অনুযায়ী CRUD action।

---

## ৫. Customer ও Customer Ledger

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/CustomerController.php`

- `index()` customers, employee ও ledgers load করে customer count, sales received এবং opening due-সহ aggregate balance তৈরি করে।
- `create()` employee dropdown-সহ customer form দেখায়।
- `store()` customer type temporary/permanent, contact, optional employee, customer code ও opening due validate করে। Permanent customer-এর employee প্রয়োজন। Code না দিলে `CUST-000001` ধাঁচে ID থেকে code বানায়।
- `ledger($id)` customer, employee, ledger, receiver ও payment history load করে; total sales/payment/due summary-সহ ledger view দেয়।
- `ledgerStore()` sale বা standalone payment entry validate করে, Customer row lock করে transaction-এর ভেতরে record লেখে। Sale-এ quantity × price; payment standalone হলে opening due-সহ outstanding হিসাব করে। Overpayment বা zero standalone receipt প্রত্যাখ্যাত হয়।
- `ledgerEdit()` edit form-এর সঙ্গে সংশ্লিষ্ট customer-এর পূর্ণ ledger ও payment history দেখায়।
- `ledgerUpdate()` sale entry-তে name/quantity/price edit করে; আগের received amount বজায় রেখে total কমিয়ে paid amount-এর নিচে যাওয়া ঠেকায়। Payment entry-তে amount edit করে outstanding due-এর বেশি হতে দেয় না। Customer ও entry row lock করে।
- `ledgerPaymentStore()` নির্দিষ্ট sale ledger-এ তারিখসহ কিস্তি যোগ করে, payment history লেখে এবং ledger-এর paid/due summary update করে।
- `ledgerDestroy()` ledger row সরায়; `ledgerInvoice()` printable sale invoice/payment receipt view দেখায়।
- `moneyToCents()` decimal amount-কে integer পয়সায় রূপান্তর করে balance comparison-এ floating-point drift কমায়।
- `edit()`, `update()`, `destroy()` customer profile update/delete চালায়।

Customer-এর সামগ্রিক due:

```text
Customer Due = Opening Due + Σ Ledger Total − Σ Ledger Payment
```

### Migration (Code, logic, functions, explanation)

- `2026_07_06_073506_create_customers_table.php` customer identity, contact, type, assigned employee, shop, unique optional customer code, opening due, status ও notes তৈরি করে।
- `2026_10_11_000002_upgrade_customers_table.php` বিদ্যমান customer table-এ অনুপস্থিত field-গুলো conditionalভাবে যোগ করে। `down()` ইচ্ছাকৃতভাবে column সরায় না, যাতে পুরোনো customer data নষ্ট না হয়।
- `2026_10_11_000001_create_customer_ledgers_table.php` customer FK, assigned employee/receiver, entry type/date, invoice/product snapshot, quantity/price, total, payment type/amount, due ও notes সংরক্ষণ করে।
- `2026_10_17_000002_create_customer_ledger_payments_table.php` ledger payment-এর তারিখ, amount, receiver ও note history রাখে। Ledger delete হলে payment history cascade delete হয়।
- Sales schema upgrade customer ledger-এ nullable sale invoice foreign key ও payment method যোগ করে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `customerIndex.blade.php`, `addCustomer.blade.php`, `editCutomer.blade.php`, `customerLedger.blade.php`, `customerLedgerInvoice.blade.php`

- Customer directory-তে total customer/sales/due card, profile/type/assigned employee এবং ledger/action links থাকে।
- Customer form temporary/permanent type, contact, address, shop, code, employee, opening due ও notes নেয়।
- Ledger page organization header, customer profile এবং total sale/payment/due card দেখায়।
- একই ledger Blade-তে sale/payment entry form, edit mode, payment history, ledger table, invoice/receipt link ও delete action render হয়।
- Invoice Blade customer ledger sale অথবা receipt-এর print view হিসেবে চলে; organization logo/contact details print header-এ দেখায়।

---

## ৬. Brand

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/BrandController.php`

- `index()` brand-গুলো newest-first আনে এবং brand directory view-তে পাঠায়।
- `create()` create form দেয়।
- `store()` required এবং unique brand name validate করে, brand তৈরি করে index-এ ফেরায়।
- `edit()` নির্দিষ্ট brand lookup করে edit view দেয়।
- `update()` name validate করে এবং brand update করে।
- `destroy()` brand মুছে success alert-সহ list-এ ফেরায়।

### Migration (Code, logic, functions, explanation)

**ফাইল:** `2026_06_29_111603_create_brands_table.php`

Brands table-এ ID, name এবং created/updated timestamp আছে। Product migration-এ brand foreign key নির্ধারিত। Product schema-তে brand delete cascade রয়েছে; তাই এই সম্পর্কের data impact বুঝে Brand delete operation ব্যবহার করতে হবে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Product/Brand/brandIndex.blade.php`, `createBrand.blade.php`, `editBrand.blade.php`

- Index-এ brand count, product-যুক্ত brand count, linked products-এর count এবং brand list।
- Create/edit form brand name submit করে।
- `@can('brand.create')`, `@can('brand.edit')` ইত্যাদি নির্দেশনা অনুযায়ী action link লুকায়/দেখায়; প্রকৃত নিরাপত্তা route middleware-ও প্রয়োগ করে।

---

## ৭. Supplier List ও Ledger Details

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/SupplierController.php`, `SupplierledgerController.php`

**SupplierController**

- `index()` suppliers-সহ ledger rows আনে।
- `create()`/`edit()` supplier form দেখায়।
- `store()` name/email/phone/address validate করে; optional image `uploads/suppliers`-এ রেখে Supplier save করে।
- `show()` supplier ledger details route-এ redirect করে।
- `update()` supplier fields পরিবর্তন করে; নতুন logo দিলে পুরোনো logo file থাকলে মুছে দেয়।
- `destroy()` logo file থাকলে মুছে Supplier record delete করে।

**SupplierledgerController**

- `index($supplier_id)` নির্দিষ্ট supplier, product/payment relations এবং সব ledger row load করে total purchase/payment/due summary দেয়।
- `invoice($id)` নির্দিষ্ট ledger row-এর printable supplier invoice দেখায়। Invoice ID অনুপস্থিত হলে `SUP-000001` ধাঁচের display number বানায়; database-এ লেখা হয় না।
- `store()` ledger-এর supplier, product, quantity, price, total, payment ও due validate করে entry তৈরি করে।
- `edit()` selected ledger এবং ওই supplier-এর summary/ledger list পুনরায় load করে edit mode দেখায়।
- `update()` row lock ও transaction ব্যবহার করে ledger fields update করে; নতুন total আগের payment-এর নিচে হলে reject করে এবং due পুনর্গণনা করে। Payment column এই update-এ অপরিবর্তিত।
- `storePayment()` past-or-today payment date ও positive amount validate করে; due ছাড়িয়ে payment ঠেকিয়ে payment history তৈরি করে এবং paid/due total update করে।
- `destroy()` ledger row মুছে সংশ্লিষ্ট supplier ledger page-এ ফেরায়।

```text
New Due = New Ledger Total − Existing Paid Amount
Payment-এর পরে Paid = Existing Paid + New Payment
Payment-এর পরে Due  = Existing Due − New Payment
```

### Migration (Code, logic, functions, explanation)

- `2026_10_06_111559_create_suppliers_table.php`: supplier name, email, phone, address, optional logo।
- `2026_10_06_111603_create_supplierledgers_table.php`: supplier FK, invoice/product references, quantity, price, total, payment ও due। Supplier delete হলে তার ledger cascade delete।
- `2026_10_12_163000_create_supplier_ledger_payments_table.php`: ledger FK, payment date ও amount; ledger delete হলে payment history cascade delete।
- Product supplier migration-এ supplier reference null-on-delete নির্ধারিত।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Supplier/supplierList.blade.php`, `addSupplier.blade.php`, `updateSupplier.blade.php`, `supplierledger.blade.php`, `supplierInvoice.blade.php`

- Supplier directory-তে supplier count, total purchase, paid ও due card; supplier profile ও ledger access।
- Create/update form-এ contact/address ও optional logo।
- Ledger view-তে supplier totals, purchase rows, products, quantities, edit/delete actions এবং date/amount-সহ payment entry/history।
- Invoice view supplier/product/payment details print করে; fallback invoice reference-ও দেখাতে পারে।

---

## ৮. Accounts: Employee Payment ও Debit/Credit Voucher

### Controller (Code, logic, functions, explanation)

**Employee voucher:** `app/Http/Controllers/EmployeePaymentVoucherController.php`
**General voucher:** `app/Http/Controllers/VoucherEntryController.php`

**EmployeePaymentVoucherController**

- `index()` employee options, allowed expense types/payment methods, সাম্প্রতিক ২০ voucher ও all-time amount দেয়।
- `store()` employee/type/date/amount/payment method validate করে; `other` expense হলে notes বাধ্যতামূলক। Employee row lock করে salary voucher employee salary ছাড়াচ্ছে কি না পরীক্ষা করে। Voucher তৈরি করে ID-ভিত্তিক `EPV-YYYYMMDD-000001` number বসায়।
- `ledger($employeeId)` কর্মীর সব voucher, total ও expense type অনুযায়ী aggregate দেখায়।
- `exportLedger()` employee ledger ও organization তথ্য UTF-8 BOM-সহ CSV করে; spreadsheet cell formula injection এড়াতে `spreadsheetSafeValue()` formula-সদৃশ prefix-যুক্ত value neutralize করে।
- `report()`/`exportReport()` date range, creator ও employee filter-সহ দৈনিক summary/detail list ও CSV বানায়। বর্তমান implementation-এ এই report-এর date filter voucher-এর `created_at` অনুযায়ী, `payment_date` নয়।

**VoucherEntryController**

- `index()` সাম্প্রতিক general voucher এবং আজকের count/amount দেখায়।
- `store()` debit/credit, category, date, positive amount, reference/description validate করে। Transaction-এ voucher তৈরি করে পরে ID-ভিত্তিক `DBV-YYYYMMDD-000001` বা `CRV-...` code বসায়; creator ID/name snapshot হয়।
- `report()`/`exportReport()` voucher date range এবং creator filter করে daily/type/creator summary, Debit/Credit totals, paginated report ও CSV দেয়। Start/end উল্টো হলে validation error।

### Migration (Code, logic, functions, explanation)

- `2026_10_12_181140_create_employee_payment_vouchers_table.php`: voucher number, nullable employee FK, employee name snapshot, expense category, payment date, amount/method, reference, notes। Employee delete হলেও snapshot রাখা হয়।
- `2026_10_13_000001_create_voucher_entries_table.php`: unique voucher code, indexed debit/credit type/date, expense-income type, amount, reference, description এবং nullable creator FK/name snapshot।
- User salary field `2026_10_12_174621_add_salary_and_nid_number_to_users_table.php`-এ যোগ হয়।
- Employee voucher-এর creator linkage `2026_10_13_000000_add_created_by_to_employee_payment_vouchers.php` migration-এ যোগ করা হয়েছে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Accounts Entries/empolyeePayment.blade.php`, `employeePaymentLedger.blade.php`, `voucherEntryCreate.blade.php`

- Employee voucher form employee, expense type, payment date, amount, payment channel, reference ও notes নেয়।
- Employee ledger-এ category summary ও voucher rows; `other` category-এর note-ও দেখায়।
- Debit/Credit form-এ voucher type, category, date, amount, reference ও description; page-এ আজকের voucher summary ও সাম্প্রতিক list।
- Voucher type/expense type/payment method-এর allowed choices controller/model constants থেকে form-এ পাঠানো হয়।

---

## ৯. Employee ও User Management

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/UserController.php`

- `index()` user-দের roles-সহ list করে।
- `create()` কেবল বর্তমান user-এর permissions subset—এমন assignable role-গুলো form-এ দেয়।
- `store()` name/email/role/password confirmation, optional image, salary, NID ও phone validate করে। Password hash করে; role assign করে; image `uploads/users`-এ রাখে।
- `show()` user, roles ও role permissions load করে detail page দেয়।
- `edit()` user-সহ role choices দেয়; `employee.role.assign` না থাকলে role assignment options শূন্য।
- `update()` email uniqueness-এ বর্তমান ID বাদ দেয়, optional password hash করে, user fields update করে। Role field পাঠানোর অধিকার না থাকলে 403 দেয়; অনুমতি থাকলে assignable role-এর মধ্যেই validate করে এবং roles sync করে। Status field বদলাতে `employee.inactive` permission লাগে।
- `userstatusupdate()` active/inactive status toggle করে।
- `destroy()` user ও image delete করে।
- `logout()` session invalidate করে এবং CSRF token regenerate করে।
- `assignableRoleNames()` current user-এর permission set-এর মধ্যে সম্পূর্ণ আবদ্ধ role-গুলোর নাম ফেরায়; role দিয়ে নিজের চেয়ে বেশি ক্ষমতা assign করা ঠেকায়।

```php
return Role::with('permissions')
    ->get()
    ->filter(fn (Role $role) => $role->permissions->every(
        fn ($permission) => in_array($permission->name, $permissions, true)
    ))
    ->pluck('name')
    ->all();
```

### Migration (Code, logic, functions, explanation)

- Laravel `0001_01_01_000000_create_users_table.php` authentication user-এর মূল field রাখে।
- `2026_10_12_174621_add_salary_and_nid_number_to_users_table.php` nullable salary ও NID/Smart Card number যোগ করে।
- `2026_10_13_000002_add_phone_to_users_table.php` nullable phone যোগ করে।
- Roles table, permission table এবং pivots-এর কাঠামো নিচের Roles & Permissions অংশে আছে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/frontend/users/userIndex.blade.php`, `addUser.blade.php`, `editUser.blade.php`, `userDeteail.blade.php`

- User list-এ identity, status ও role এবং permission-ভিত্তিক actions।
- Create/edit form-এ user profile, image, contact, salary, NID, password ও role।
- Detail view-তে user-এর assigned roles ও permissions।
- Password edit-এ ফাঁকা রাখলে পুরোনো password বজায় থাকে; status/role controls permission অনুযায়ী দেখানো হয়।

---

## ১০. Roles ও Permissions

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/RoleController.php`

- `index()` roles-এর সঙ্গে permissions eager-load করে role list দেয়।
- `create()` permission option শুধু current user-এর effective permissions থেকে দেয়।
- `store()` unique role name এবং অন্তত একটি permission validate করে। Permission ID distinct হতে হবে এবং current user-এর permission ID-র subset হতে হবে; তারপর role create ও permission sync।
- `edit()` selected role এবং permission list/selected IDs form-এ দেয়।
- `update()` current role permissions-সহ load করে; current user permissions union existing role permissions-এর মধ্যে submitted IDs validate করে। Role name update করে permissions sync করে।
- `destroy()` role delete করে।

### Migration (Code, logic, functions, explanation)

**ফাইল:** `2026_06_17_170325_create_permission_tables.php`, `2026_10_17_000000_rename_legacy_permissions.php`

Spatie Permission migration `permissions`, `roles`, `model_has_permissions`, `model_has_roles` এবং `role_has_permissions` table তৈরি করে। Unique role/permission name guard অনুযায়ী; pivot table-এ model morph key/type এবং foreign key থাকে; সংশ্লিষ্ট role/permission delete হলে pivot cascade হয়। Migration config থেকে table নাম ও key নাম নেয় এবং Permission cache clear করে।

Legacy permission name update migration পুরোনো permission নামগুলো বর্তমান module permission নামের সঙ্গে সামঞ্জস্য করে। Seeder-এ granular permission ও standard role presets তৈরি হয়।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/roles/role-index.blade.php`, `role-create.blade.php`, `role-edit.blade.php`

- Index-এ role count, permission assignment count, role name, assigned permission ও actions।
- Create/edit form role name ও permission checkbox list দেয়; select-all এবং permission search control আছে।
- View-level `@can` directives action link নিয়ন্ত্রণ করে; routes-এ middleware authorization enforcement করে।

---

## ১১. Organization Information

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/OrganizationController.php`

- `edit()` ID 1 organization profile খোঁজে; না থাকলে নতুন model form-এ দেয়।
- `update()` required name, optional address/phone/email এবং JPG/PNG/JPEG/WebP logo 2 MB পর্যন্ত validate করে।
- `firstOrNew(['id' => 1])` দিয়ে একক organization record বজায় রাখে।
- নতুন logo `uploads/organizations`-এ hash name-এ সংরক্ষণ করে, profile save করে, সফলভাবে নতুন file reference save হওয়ার পর পুরোনো logo file থাকলে মুছে দেয়।

### Migration (Code, logic, functions, explanation)

- `2026_10_13_182123_create_organizations_table.php`: name, address, phone, logo এবং timestamps।
- `2026_10_14_005500_add_email_to_organizations_table.php`: nullable email।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/frontend/organization/addOrganization.blade.php`

Organization name, phone, email, address ও optional logo upload form। Existing logo preview এবং validation message দেখায়। Form multipart PUT request পাঠায়।

`AppServiceProvider` View composer organization data login page, invoice, sales/report, ledger ও quotation views-এ সরবরাহ করে। `backend/partials/organizationHeader.blade.php` print/report header-এ নাম, যোগাযোগ ও logo পুনর্ব্যবহার করে।

---

## ১২. Combined Voucher Report

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/CombinedVoucherReportController.php`

- `index()` period `daily` বা `monthly` validate করে; daily date/month অনুযায়ী start/end নির্ধারণ করে।
- `combinedVoucherQuery()` employee payment voucher এবং general debit/credit voucher-কে একই column schema-তে `UNION ALL` করে।
- Employee voucher report date `created_at`; general voucher report date `voucher_date`। এই আলাদা date semantics report-এ বজায় থাকে।
- Summary-তে debit count = employee + debit voucher, credit count = credit voucher; employee/debit/credit amount আলাদাভাবে aggregate হয়।
- Voucher listing date ও code অনুযায়ী newest-first, ৩০ rows/page।
- `export()` একই filter ও totals ব্যবহার করে সব matching rows cursor করে CSV stream করে; category অনুযায়ী debit/credit side, display labels, employee/creator ও reference/description লেখে।

```php
$employeeVouchers->unionAll($generalVouchers);
```

### Migration (Code, logic, functions, explanation)

এই report নতুন কোনো table তৈরি করে না। `employee_payment_vouchers` ও `voucher_entries`-এর records একত্র করে। তাদের schema Accounts অংশে বর্ণিত।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`

Daily/monthly period selector, date/month input, apply/reset, export এবং print control। Page-এ organization print header, debit/credit count, employee total, debit/credit amount এবং একীভূত voucher table। Filter/action controls print-এ লুকানো হয়।

---

## ১৩. Daily Employee Voucher Report

### Controller (Code, logic, functions, explanation)

**ফাইল:** `EmployeePaymentVoucherController::report()`, `exportReport()`

- Start/end date, voucher creator এবং employee ID validate করে; end date start date-এর আগে হলে validation error।
- Filter না দিলে আজকের date-কে start/end ধরে।
- Record filter `employee_payment_vouchers.created_at`-এর উপর ভিত্তি করে।
- Daily summary date, employee, creator, count ও total amount অনুযায়ী group করে; detail list ৩০ rows paginate হয়।
- Export একই filter ব্যবহার করে summary ও সব details CSV-তে দেয়। Creator না থাকলে legacy voucher হিসেবে label দেয়।

### Migration (Code, logic, functions, explanation)

Report-এ `employee_payment_vouchers` migration-এর `created_at`, `employee_id`, `employee_name`, `created_by`, `expense_type`, `payment_date`, `amount` field ব্যবহার হয়। আলাদা report table নেই। `created_by` linkage migration-এ যোগ করা।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Accounts Entries/VoucherEntries.blade.php`

Filter-এ create date range, employee ও creator নির্বাচন। Voucher count/amount summary, grouped daily report ও paginated voucher details। Export ও browser print action আছে; print report header-এ organization details ব্যবহার হয়।

---

## ১৪. Daily Debit/Credit Voucher Report

### Controller (Code, logic, functions, explanation)

**ফাইল:** `VoucherEntryController::report()`, `exportReport()`

- Voucher date range ও creator ID validate করে; উল্টো date range প্রত্যাখ্যান করে।
- ডিফল্ট period আজ; summary `voucher_date`, `voucher_type`, `created_by` ও creator-name snapshot দিয়ে group হয়।
- Count, debit total, credit total, daily summary ও ৩০-row pagination দেয়।
- Export-এ একই query দিয়ে summary এবং CSV detail stream হয়।

### Migration (Code, logic, functions, explanation)

`voucher_entries`-এর `voucher_date`, `voucher_type`, `amount`, `created_by`, `created_by_name`, `reference`, `description` field report-এর উৎস। Date/type index ও `(voucher_date, created_by)` composite index report/filter query-কে সহায়তা করে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Accounts Entries/voucherEntryReport.blade.php`

Start/end voucher date ও creator filter, voucher count, total Debit/Credit card, daily grouped summary এবং voucher detail table। Export, print, organization header এবং print-এ filter/button লুকানোর layout।

---

## ১৫. Sales Report

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/SalesReportController.php`

- `index()` date range, product, employee ও customer filter validate করে। Date range উল্টো হলে error দেয়।
- শুধু start/end না দিলে default আজ; কেবল এক সীমা দিলে অন্য পাশে date constraint বসে না—report ঐ দিকে open-ended থাকে।
- Invoice query-তে product filter থাকলে `whereHas('items')` দিয়ে সংশ্লিষ্ট product বিক্রি হওয়া invoice বেছে নেয়।
- Total revenue = subtotal − discount + VAT; previous due নতুন sale revenue হিসেবে ধরা হয় না। Due ও invoice count-ও summary হয়।
- Product-wise aggregation quantity, revenue, weighted average purchase/selling price, total cost, profit ও invoice VAT-এর line-total অনুপাতে ভাগ করা অংশ হিসাব করে।
- Invoice detail customer/employee/items/product eager-load এবং ২০ rows paginate।
- `export()` একই filters/aggregate ব্যবহার করে CSV-তে summary, product-wise figures ও invoice details দেয়।

```php
$totalSalesAmount = (float) ((clone $invoiceQuery)
    ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount), 0) as total')
    ->value('total') ?? 0);
```

### Migration (Code, logic, functions, explanation)

Report `sale_invoices`, `sale_items` ও `products`-এর ওপর চলে। `sale_items.purchase_price` historical snapshot-কে অগ্রাধিকার দেয়; null হলে বর্তমান Product purchase price fallback। Sale invoice/item schema এবং discount snapshot migration Sales অংশে বর্ণিত।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/Sales/salesReport.blade.php`

Filter form-এ date, Product, Employee ও Customer। Summary-তে total sale, profit, due, cost/count; product-wise table এবং invoice-wise বিস্তারিত table। Organization header, report-generation time, CSV export, print action এবং print stylesheet আছে।

---

## ১৬. Product Inventory Report

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/ProductInventoryReportController.php`

- `reportRows()` সব Product-এর supplier ও purchase-ledger relations eager-load করে।
- Sale item-কে Product অনুযায়ী aggregate করে quantity sold, revenue, average price, total cost ও profit বের করে; sale-time purchase-price snapshot আগে ব্যবহার হয়।
- Purchased quantity ও purchase amount supplier ledger rows থেকে; current stock Product-এর `unit` থেকে।
- Supplier হিসেবে ledger-এ থাকা unique suppliers দেখায়; ledger না থাকলে Product-এর direct supplier fallback।
- Expiry status Product model-এর `isExpired()`/`isExpiringSoon()` method দিয়ে নির্ধারণ হয়।
- `index()` totals-সহ report view render করে; `export()` একই rows CSV-তে stream করে।

```text
Profit = Sales Revenue − Σ(Quantity × Purchase Price Snapshot)
Current Stock = products.unit
Purchased Qty/Amount = Σ supplierledger.quantity / Σ supplierledger.total
```

### Migration (Code, logic, functions, explanation)

Inventory report `products`, `sale_items`, `supplierledgers`, `suppliers` table-এর উপর নির্ভর করে। Product purchase price/supplier columns এবং supplier ledger schema Product ও Supplier অংশে বর্ণিত। আলাদা inventory report table নেই।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/reports/productinventory.blade.php`

Total products, purchased quantity/amount, stock এবং expired product summary card। Details table-এ purchase, sold quantity, sale, profit, stock, expiry status, supplier ও phone। CSV export, browser print এবং reusable organization header।

---

## ১৭. User Activity Log

### Controller (Code, logic, functions, explanation)

**ফাইল:** `app/Http/Controllers/UserActivityLogController.php`

- `index()` বর্তমান user list-এর পাশাপাশি delete হওয়া user-দের সর্বশেষ log snapshot থেকে filter option তৈরি করে।
- Query date range (`from`/`to`), user ID এবং event (`create`, `update`, `delete`, `login`, `logout`) validate করে।
- `UserActivityLog::scopeForDateRange()` date সীমা প্রয়োগ করে; query newest-first ২৫ row paginate করে এবং query string pagination-এ রাখে।
- Selected user তথ্য view-তে দেয়; deleted user-এর activity-ও snapshot থেকে চেনা যায়।

`AppServiceProvider` Laravel Login/Logout event-এ user ID, নাম, email, event, guard, IP ও user-agent snapshot লিখে। Logout event-এ user object অনুপস্থিত হলে log লেখা বাদ যায়। অন্য create/update/delete event-গুলো model observer/controller event integration থাকলেই তৈরি হবে; এই controller নিজে কোনো log তৈরি করে না।

### Migration (Code, logic, functions, explanation)

**ফাইল:** `2026_10_17_000001_create_user_activity_logs_table.php`

Table-এ nullable indexed `user_id` রাখা হয় কিন্তু foreign key নেই, যাতে user delete হলেও historical log থাকে। User name/email snapshot, indexed event, description, subject type/ID, JSON properties, IP, user-agent ও timestamps থাকে। Created date/event composite index আছে।

### Blade (Code, logic, functions, explanation)

**ফাইল:** `resources/views/backend/user-activity/index.blade.php`

Date/user/event filter, selected user/period header, timestamp/user/activity/description/IP table, pagination। Print/PDF browser dialog-ভিত্তিক; print CSS navigation/filter/pagination লুকিয়ে landscape report তৈরি করে; properties/details expandable output-এ থাকে।

---

## ১৮. Seeder ও সাধারণ প্রবেশাধিকার

### Seeders (Code, logic, functions, explanation)

`database/seeders/DatabaseSeeder.php` permission, role-permission, user, product category ও product seeders ধারাবাহিকভাবে চালায়। `PermissionSeederTable` granular permission-গুলো populate করে; `RolePermissionSeeder` সেই permission-গুলোর পর standard roles sync করে। `ProductSeeder` catalog name দিয়ে Product rows তৈরি করে; null category/brand/price/unit value সংরক্ষণে nullable product migration প্রাসঙ্গিক। `BrandSeeder` ও `CustomerSeeder` আলাদাভাবে চালানোর উপযোগী domain seeder।

### Routes ও middleware

**ফাইল:** `routes/web.php`

- অধিকাংশ back-office module route `auth` ও `verified` middleware group-এ।
- Route-level permission middleware-ই data-changing operation-এ authorization enforce করে; Blade-এর `@can` শুধু UI action visibility নির্ধারণ করে।
- প্রধান route families:
  - Dashboard: `dashboard`, `dashboardbody`
  - Sales: `sales.*`, invoice ও customer phone lookup
  - Quotations: `quotation.index/create/store/show/destroy`
  - Product/category/brand: `product.*`, `category.*`, `brand.*`
  - Customer ledger: `customer.*`, payment/history, invoice
  - Supplier/ledger: `supplier.*`, ledger, payment, invoice
  - Accounts: `voucher-entries.*`, `employee-payment.*`, `voucher-report.combined*`
  - Organization: `organization.edit/update`
  - Reports: `sales.report*`, `product.inventory.report*`
  - Activity: `user-activity-logs.index`

## হিসাব ও ডেটা ব্যবহারের গুরুত্বপূর্ণ নিয়ম

1. Dashboard, Sales Report ও Inventory Report-এ sale item-এর ক্রয়মূল্য snapshot ব্যবহার করা হয়, যাতে বর্তমান Product price বদলালেও পুরোনো sale profit বদলে না যায়।
2. Sales revenue-এ আগের customer due অন্তর্ভুক্ত হয় না; চাইলে invoice-এ previous due আলাদা component হিসেবে যোগ হয়।
3. Customer ও Supplier ledger payment-এর history আলাদা payment table-এ থাকে; summary row-তে cumulative paid/due balance-ও রাখা হয়।
4. Employee voucher daily report ও combined report employee voucher-এর `created_at`-কে report date হিসেবে ব্যবহার করে; general voucher report `voucher_date` ব্যবহার করে।
5. Organization profile shared print header, login, invoice ও report branding-এর একক উৎস।
6. এই নথি code behavior বর্ণনা করে; এটি কোনো migration চালায় না, কোনো application source file পরিবর্তন করে না এবং deploy/configuration নির্দেশ নয়।

---

## ১৯. Models ও সম্পর্ক

এই অংশে domain model-গুলোর mass-assignment field, cast, relationship এবং computed helper সংক্ষেপে দেখানো হয়েছে। Dashboard ও Inventory Report আলাদা model তৈরি করে না; বিদ্যমান Eloquent model ও query builder ব্যবহার করে।

### Product, Brand, Category ও Supplier

**ফাইল:** `app/Models/Product.php`, `Brand.php`, `ProductCategory.php`, `Supplier.php`

- `Product::$fillable` name, category/brand/supplier IDs, purchase/sale prices, stock, dates, description এবং image field অনুমোদন করে।
- Product purchase/manufacture/expiry date-কে date cast করে।
- `productCategory()`, `brand()`, `supplier()` belongs-to relationship; `purchaseLedgers()` supplier purchase ledger-এ has-many।
- `shelfDurationDays()` manufacturing date ও expiry date থাকলে দিনের ব্যবধান দেয়।
- `isExpired()`, `isExpiringSoon()`, `stockQuantity()`, `isOutOfStock()`, `isLowStock()` inventory condition নির্ণয় করে। Low stock ১–১০; zero stock-কে out-of-stock হিসেবে আলাদা ধরা হয়।
- Brand-এর `Products()` এবং ProductCategory-এর `Products()` has-many Product; Supplier-এর `supplierledgers()` has-many।

```php
public function isLowStock(): bool
{
    $qty = $this->stockQuantity();

    return $qty > 0 && $qty <= 10;
}
```

### Sales ও Quotation

**ফাইল:** `app/Models/SaleInvoice.php`, `SaleItem.php`, `Quotation.php`, `QuotationItem.php`

- `SaleInvoice` invoice header-এর fillable field ও decimal/date/boolean cast রাখে; `customer()`, `employee()`, `items()` সম্পর্ক দেয়।
- `SaleItem` sale invoice-এ belongs-to, Product-এ nullable belongs-to; product name ও purchase price snapshot ধরে রাখে।
- `getEffectivePurchasePriceAttribute()` historical purchase price থাকলে সেটি, না হলে সম্পর্কিত Product-এর বর্তমান purchase price দেয়।
- `getProfitAttribute()` SQL aggregation-এ আগে থেকে `profit` থাকলে সেটি ফেরত দেয়; অন্যথায় line total থেকে quantity × effective purchase price বিয়োগ করে।
- `Quotation` item-গুলোর has-many এবং author user-এর belongs-to; date ও monetary field cast করা।
- `QuotationItem` quotation এবং optional Product-এর belongs-to; price/line total float এবং quantity integer cast।

```php
public function getProfitAttribute(): float
{
    if (array_key_exists('profit', $this->attributes) && $this->attributes['profit'] !== null) {
        return (float) $this->attributes['profit'];
    }

    $lineTotal = (float) ($this->line_total ?? 0);
    $cost = $this->effective_purchase_price * (float) ($this->quantity ?? 0);

    return round($lineTotal - $cost, 2);
}
```

### Customer Ledger ও Payment History

**ফাইল:** `app/Models/Customer.php`, `CustomerLedger.php`, `CustomerLedgerPayment.php`

- Customer-এর `employee()` belongs-to assigned user; `ledgers()` has-many ledger entries।
- CustomerLedger-এর `customer()`, `employee()`, `receiver()` আলাদা সম্পর্ক; `payments()` ledger-এর পরবর্তী payment history date/ID descending ক্রমে আনে।
- CustomerLedgerPayment `ledger()` এবং `receiver()` belongs-to দেয়; date cast এবং amount decimal:2।
- Opening due customer row-তে, sale/payment running totals ledger row-তে, installment details payment-history table-এ থাকে।

### Supplier Ledger ও Payment History

**ফাইল:** `app/Models/Supplierledger.php`, `SupplierLedgerPayment.php`

- Supplierledger supplier ও Product-এ belongs-to; `payments()` child payment history newest-first আনে।
- SupplierLedgerPayment-এর `supplierledger()` belongs-to; payment date date cast এবং amount decimal:2।
- Parent ledger-এর `payment`/`due` cumulative balance; child row-গুলো প্রতিটি dated payment-এর history।

### Accounts ও Access Models

**ফাইল:** `app/Models/EmployeePaymentVoucher.php`, `VoucherEntry.php`, `User.php`

- EmployeePaymentVoucher `EXPENSE_TYPES` ও `PAYMENT_METHODS` constant-এ allowed values/labels রাখে; employee ও creator-কে আলাদা belongs-to সম্পর্ক দেয়। `employee_name` snapshot employee deletion-এর পরও থাকে।
- VoucherEntry `TYPES`-এ Debit/Credit labels; creator relationship `createdBy()` এবং `created_by_name` snapshot।
- User `HasRoles` trait দিয়ে Spatie role/permission support পায়; password hashed, status boolean, salary decimal:2 cast হয়। Fillable profile field-এ salary, NID, phone-ও আছে।

### Organization ও Activity Model

**ফাইল:** `app/Models/Organization.php`, `UserActivityLog.php`

- Organization-এর fillable profile field name/address/phone/email/logo।
- UserActivityLog JSON properties-কে array cast করে, optional user belongs-to দেয় এবং `scopeForDateRange()` created_at-এর inclusive date bound প্রয়োগ করে।
- Activity log-এর `user_id` foreign key নয়; তাই User delete হলেও event history ও name/email snapshot থাকে।

---

## ২০. Seeder-এর ডেটা ও কার্যপ্রবাহ

### `DatabaseSeeder`

**ফাইল:** `database/seeders/DatabaseSeeder.php`

```php
$this->call([
    PermissionSeederTable::class,
    RolePermissionSeeder::class,
    UserSeederTable::class,
    ProductCategorySeeder::class,
    ProductSeeder::class,
]);
```

`run()`-এ `User::factory()` দিয়ে একটি test user তৈরি করার code-টি `call()`-এর আগেই চলে। `call()`-এর ভেতরের ক্রমে permission তৈরি হয়, তারপর role preset-এ permission assign হয়; তারপরে user seed এবং catalog seed চলে।

### Permission ও Role preset

**ফাইল:** `database/seeders/PermissionSeederTable.php`, `RolePermissionSeeder.php`

- `PermissionSeederTable` module অনুযায়ী Dashboard, sales, customer, supplier, product, brand, category, employee, voucher, reports, role এবং organization permissions-এর নামের তালিকা `Permission::firstOrCreate()` দিয়ে তৈরি করে।
- `RolePermissionSeeder` `sales`, `manager`, `projector`, `admin` preset তৈরি/পুনর্ব্যবহার করে।
- `sales` role-এ dashboard, sale create ও customer access-এর সীমিত অনুমতি; `projector`-এ dashboard ও report access; `admin`-এ সব permission।
- `manager` preset-এ catalog, voucher/report, supplier, employee ও role-related কিছু অনুমতি একত্রিত।
- প্রতিটি run-এ role-এর permission `syncPermissions()` দিয়ে preset অনুযায়ী synchronize হয়—ম্যানুয়ালি যুক্ত অতিরিক্ত permission preset-এর বাইরে থাকলে বাদ যেতে পারে।

```php
$role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
$permissions = Permission::query()->whereIn('name', $permissionNames)->get();
$role->syncPermissions($permissions);
```

### User ও Product Category Seeder

**ফাইল:** `database/seeders/UserSeederTable.php`, `ProductCategorySeeder.php`

- `UserSeederTable` `Supper Admin` ও `Admin` seed users তৈরি করে; code-এ initial credential `password` string hash করে সংরক্ষণ করা হয়। Seeder admin role-কে সব permission-এ sync করে এবং দুজন user-কে সেই role দেয়।
- `ProductCategorySeeder` Hardware, Pre-Built Systems, Peripherals, Networking, Printers/Toner, Software, Power/Accessories category names দিয়ে category row তৈরি করে।

### Product, Brand ও Customer Seeder

**ফাইল:** `ProductSeeder.php`, `BrandSeeder.php`, `CustomerSeeder.php`

- `ProductSeeder` catalog product name-গুলোর array iterate করে প্রতিটি নামের Product row তৈরি করে; category, brand, supplier, purchase/sale price, unit, dates, description ও image null।
- `BrandSeeder::run()` এবং `CustomerSeeder::run()` বর্তমানে ফাঁকা method; তারা run করলেও কোনো brand/customer row তৈরি করে না।
- ProductSeeder-এর বর্তমান আচরণ duplicate name check করে না; পুনরায় চালালে একই catalog name-এর নতুন row তৈরি হতে পারে।

---

## ২১. অতিরিক্ত Controller code excerpt

এই উদাহরণগুলো উপরের module explanation-এর সঙ্গে source code-কে মিলিয়ে দেখায়। Excerpt সংক্ষিপ্ত করা হয়েছে; প্রতিটি source file-এ validation, error handling ও অতিরিক্ত field mapping-সহ পূর্ণ implementation রয়েছে।

### Customer ledger payment — `CustomerController`

```php
if ($amountCents > $dueCents) {
    throw ValidationException::withMessages([
        'amount' => 'Payment cannot be greater than the current sale due.',
    ]);
}

CustomerLedgerPayment::create([
    'customer_ledger_id' => $ledger->id,
    'payment_date' => $validated['payment_date'],
    'amount' => $amountCents / 100,
]);

$ledger->update([
    'payment_amount' => ($this->moneyToCents($ledger->payment_amount) + $amountCents) / 100,
    'due_amount' => ($dueCents - $amountCents) / 100,
]);
```

পেমেন্টের amount integer পয়সায় তুলনা হয়। Outstanding due ছাড়ালে error; সফল হলে payment history এবং parent ledger-এর cumulative amount একই transaction-এ হালনাগাদ।

### Employee voucher salary guard — `EmployeePaymentVoucherController`

```php
if ($validated['expense_type'] === 'salary') {
    $salaryInCents = (int) round((float) ($employee->salary ?? 0) * 100);

    if ($amountInCents > $salaryInCents) {
        throw ValidationException::withMessages([
            'amount' => 'Salary voucher cannot exceed the employee salary.',
        ]);
    }
}
```

Employee record row-lock করে Salary expense current salary-র বেশি হলে transaction বন্ধ করে। Successful create-এর পরে generated voucher number employee voucher row-তে save হয়।

### Debit/Credit voucher code — `VoucherEntryController`

```php
$voucher = VoucherEntry::create([
    ...$validated,
    'voucher_code' => null,
    'created_by' => $creator->id,
    'created_by_name' => $creator->name,
]);

$prefix = $validated['voucher_type'] === 'debit' ? 'DBV' : 'CRV';
$voucher->update([
    'voucher_code' => $prefix.'-'.Carbon::parse($validated['voucher_date'])->format('Ymd')
        .'-'.str_pad((string) $voucher->id, 6, '0', STR_PAD_LEFT),
]);
```

প্রথমে primary key পাওয়ার জন্য record তৈরি হয়; তারপর voucher type/date/ID মিলিয়ে unique code বসে—যেমন `DBV-20261025-000123`।

### Role-এর permission সীমা — `RoleController`

```php
$permissionIds = $request->user()->getAllPermissions()->modelKeys();
$validated = $request->validate([
    'name' => 'required|unique:roles,name',
    'permission' => ['required', 'array', 'min:1'],
    'permission.*' => ['required', 'integer', 'distinct', Rule::in($permissionIds)],
]);
$role->syncPermissions(array_map('intval', $validated['permission']));
```

Role creator কেবল নিজের effective permission-এর subset নতুন role-এ দিতে পারে। ফলে role creation দিয়ে নিজের অনুমতির বাইরে permission grant করা হয় না।

### Organization save — `OrganizationController`

```php
$organization = Organization::query()->firstOrNew(['id' => 1]);
$oldLogo = $organization->logo;
unset($validated['logo']);

if ($request->hasFile('logo')) {
    $logo = $request->file('logo');
    $directory = public_path('uploads/organizations');
    File::ensureDirectoryExists($directory);
    $logoName = $logo->hashName();
    $logo->move($directory, $logoName);
    $validated['logo'] = $logoName;
}

$organization->fill($validated);
$organization->save();

if (isset($validated['logo']) && $oldLogo
    && File::exists(public_path('uploads/organizations/' . $oldLogo))) {
    File::delete(public_path('uploads/organizations/' . $oldLogo));
}
```

Profile সব সময় ID 1-এ রাখা হয়। নতুন logo upload থাকলে controller আগে নতুন file সংরক্ষণ করে, record save-এর পরে পুরোনো logo file থাকলে মুছে দেয়; logo না পাঠালে বর্তমান logo অক্ষত থাকে।

### Sales Report product aggregation

```php
->selectRaw('SUM(sale_items.line_total) as revenue')
->selectRaw('SUM(sale_items.quantity * COALESCE(
    sale_items.purchase_price, products.purchase_price, 0
)) as total_cost')
->selectRaw('SUM(sale_items.line_total - (
    sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0)
)) as profit')
```

Historical cost snapshot-কে priority দিয়ে Product current purchase price fallback করে; তাই পণ্যদরের পরবর্তী পরিবর্তনে historical profit-কে অযথা বদলানো হয় না।

### User Activity filter

```php
$logs = UserActivityLog::query()
    ->forDateRange($filters['from'] ?? null, $filters['to'] ?? null)
    ->when($filters['user_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
    ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
    ->latest()
    ->paginate(25)
    ->withQueryString();
```

Date range, user ও event filter এক query-তে প্রয়োগ হয়; pagination-এ filter query string অক্ষুণ্ণ থাকে।

### Blade form ও output-এর ধরন

```blade
<form action="{{ route('quotation.store') }}" method="POST">
    @csrf
    <input name="customer_name" value="{{ old('customer_name') }}" required>
    @error('customer_name')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</form>
```

একই Laravel Blade pattern Sales, Customer, Product, Organization ও Accounts-এর create/update form-এ দেখা যায়: named route, CSRF token, validation feedback, `old()` input restoration এবং escaped `{{ }}` output। Print/report view-তে filter ও toolbar সাধারণত `no-print` class-এ থাকে; report data controller থেকে আসে, Blade formatting ও presentation করে।

### Migration table shape-এর উদাহরণ

```php
Schema::create('customer_ledger_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_ledger_id')->constrained('customer_ledgers')->cascadeOnDelete();
    $table->date('payment_date');
    $table->decimal('amount', 15, 2);
    $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

এখানে installment history parent sale ledger-এ cascade-এ যুক্ত; receiver account মুছে গেলেও payment row থাকে এবং receiver reference null হয়।

---

## ২২. মডিউলভিত্তিক Model ও Seeder সূচি

নিচের সূচিতে প্রতিটি মডিউলের Model ও Seeder সংযোগ এক জায়গায় দেওয়া হলো। বিস্তারিত field, relation, seed order এবং behavioral note যথাক্রমে [Models ও সম্পর্ক](#১৯-models-ও-সম্পর্ক) এবং [Seeder-এর ডেটা ও কার্যপ্রবাহ](#২০-seeder-এর-ডেটা-ও-কার্যপ্রবাহ) অংশে আছে। যেসব মডিউলের নিজস্ব Seeder নেই, সেখানে স্পষ্টভাবে তা উল্লেখ করা হয়েছে—কোনো seed behavior অনুমান করা হয়নি।

| মডিউল | প্রধান Model / ডেটা উৎস | Seeder / seed আচরণ |
|---|---|---|
| Dashboard | `SaleInvoice`, `SaleItem`, `Product`, `Supplierledger`, `EmployeePaymentVoucher`, `VoucherEntry`, `User` | Dashboard-এর নিজস্ব Seeder নেই; underlying module-এর record aggregate করে। |
| Sales | `SaleInvoice`, `SaleItem`, `Product`, `Customer`, `CustomerLedger` | Sale-নির্দিষ্ট Seeder নেই; demo/catalog data অন্য Seeder দ্বারা আসে। |
| Quotations | `Quotation`, `QuotationItem`, `Product`, `User` | Quotation-নির্দিষ্ট Seeder নেই। |
| Product | `Product`, `ProductCategory`, `Brand`, `Supplier`, `Supplierledger` | `ProductSeeder`, `ProductCategorySeeder`; ProductSeeder catalog row তৈরি করে, category seeder category তৈরি করে। |
| Customer | `Customer`, `CustomerLedger`, `CustomerLedgerPayment`, `User` | `CustomerSeeder`-এর `run()` বর্তমানে ফাঁকা। |
| Brand | `Brand`, `Product` | `BrandSeeder`-এর `run()` বর্তমানে ফাঁকা। |
| Supplier List & Ledger | `Supplier`, `Supplierledger`, `SupplierLedgerPayment`, `Product` | Supplier/ledger-নির্দিষ্ট Seeder নেই। |
| Accounts | `EmployeePaymentVoucher`, `VoucherEntry`, `User` | Voucher-নির্দিষ্ট Seeder নেই। |
| Employee | `User`, `EmployeePaymentVoucher`, `CustomerLedger` | `UserSeederTable` Admin ও Supper Admin account seed করে; employee voucher seed করে না। |
| Roles & Permissions | Spatie `Role`, `Permission`, `User` | `PermissionSeederTable`, `RolePermissionSeeder`, `UserSeederTable`—ক্রম ও synchronization-এর বিবরণ Seeder অংশে। |
| Organization Information | `Organization` | Organization-নির্দিষ্ট Seeder নেই। |
| Combined Voucher Report | `EmployeePaymentVoucher`, `VoucherEntry` | আলাদা report Seeder নেই; Accounts record-এর উপর চলে। |
| Daily Employee Voucher Report | `EmployeePaymentVoucher`, `User` | Employee voucher report-এর আলাদা Seeder নেই। |
| Daily Debit/Credit Voucher Report | `VoucherEntry`, `User` | General voucher report-এর আলাদা Seeder নেই। |
| Sales Report | `SaleInvoice`, `SaleItem`, `Product`, `Customer`, `User` | Report Seeder নেই; sales/catalog seed data-র উপর নির্ভর করে। |
| Product Inventory Report | `Product`, `SaleItem`, `Supplierledger`, `Supplier` | Report Seeder নেই; Product catalog-এর জন্য `ProductSeeder` ব্যবহৃত হয়। |
| User Activity Log | `UserActivityLog`, `User` | Activity-log Seeder নেই; login/logout event runtime-এ record হয়। |

## চূড়ান্ত scope নিশ্চিতকরণ

- এই পুনর্গঠিত নথিতে ১৭টি অনুরোধকৃত business/report module এবং shared Seeder/route behavior অন্তর্ভুক্ত।
- Module section-গুলোতে Controller, Migration এবং Blade আলাদাভাবে লেখা; model ও seeder mapping এই সূচিতে যুক্ত এবং সংশ্লিষ্ট বিস্তারিত অংশে বর্ণিত।
- Code sample হলো প্রকল্পের বর্তমান implementation-এর প্রাসঙ্গিক excerpt। যে মডিউলের জন্য আলাদা migration, model, Blade বা seeder নেই, তা dependency/shared implementation হিসেবে চিহ্নিত; কাল্পনিক component যোগ করা হয়নি।
- পরিবর্তন কেবল এই Markdown নথিতেই প্রযোজ্য। Laravel/PHP, Blade, route, migration, model, seeder, configuration, database বা asset file পরিবর্তন করা হয়নি।
