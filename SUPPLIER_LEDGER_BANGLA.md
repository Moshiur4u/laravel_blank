# সাপ্লায়ার লেজার: ফাইল ও লজিক নির্দেশিকা

এই নথিতে supplier ledger feature-এর Blade, controller, model, migration এবং route-এর কাজ বাংলায় ব্যাখ্যা করা হয়েছে। এটি কোড পরিবর্তন করে না; কোন ফাইলে কোন কাজ হয় তা বোঝার জন্য তৈরি।

## ১. Blade view

ফাইল: `resources/views/backend/Supplier/supplierledger.blade.php`

- `@extends('dashboard.dashboard')` মূল dashboard layout ব্যবহার করে।
- `@section('title')` পেজের title-এ supplier-এর নাম দেখায়।
- Breadcrumb ও Back button supplier list-এ ফেরার navigation দেয়।
- Supplier details card-এ logo থাকলে সেটি দেখায়; না থাকলে supplier ID দিয়ে রঙ বেছে নামের প্রথম দুই অক্ষরের avatar বানায়।
- Summary card-গুলো controller থেকে পাওয়া মোট ledger amount, মোট payment ও মোট due দেখায়।
- Edit form কেবল controller থেকে `$ledger` পাঠানো হলে দেখায়। ফলে সাধারণ supplier ledger page-এ নতুন entry যোগ করার form থাকে না, কিন্তু কোনো entry-র Edit button চাপলে `Edit Ledger Entry` form দেখা যায়।
- Edit form `supplier.ledger.update` route-এ `PUT` request পাঠায়। Invoice, product, quantity ও unit price পরিবর্তন করা যায়; total হিসাবের জন্য read-only থাকে। Payment-ও read-only, কারণ payment আলাদা payment action দিয়ে যোগ করতে হয়।
- Validation error সংশ্লিষ্ট input-এর পাশে দেখানো হয়। Cancel Edit supplier-এর ledger page-এ ফিরিয়ে নেয়।
- Success message controller-এর session message দেখায়।
- Payment validation error-গুলো edit form থেকে আলাদা করে Ledger History-র উপরে দেখায়।
- Ledger History table প্রতিটি entry-র date, invoice, product, quantity, price, total, payment ও due দেখায়। Product বা invoice না থাকলে `N/A` দেখায়।
- Entry-তে payment history থাকলে `<details>` খুলে payment date ও amount দেখা যায়।
- Due শূন্যের বেশি হলে ওই entry-র জন্য payment date ও amount-সহ payment form দেখায়। Print, Edit ও Delete action-ও একই row-তে থাকে।
- নিচের JavaScript quantity × unit price থেকে total এবং total − payment থেকে due হিসাব করে। Edit form না থাকলে calculator-এর input-গুলো পাওয়া যায় না, তাই script listener যোগ না করে নিরাপদে শেষ হয়।

## ২. Controller

ফাইল: `app/Http/Controllers/SupplierledgerController.php`

### `index($supplier_id)`

নির্দিষ্ট supplier খুঁজে তার ledger entry-গুলো product ও payment history-সহ আনে। Product তালিকাও view-তে পাঠায়। সব entry থেকে total amount, payment ও due যোগ করে এবং supplier ledger Blade render করে। এই method `$ledger` পাঠায় না, তাই এখানে Edit form দেখা যায় না।

### `invoice($id)`

নির্দিষ্ট ledger entry-কে supplier, product ও payment history-সহ আনে। Invoice ID ফাঁকা থাকলে database পরিবর্তন না করে `SUP-` prefix ও padded ledger ID দিয়ে display number বানায়। এরপর printable invoice view দেখায়।

### `store(Request $request)`

নতুন ledger entry-র request validate করে, `Supplierledger` model দিয়ে record তৈরি করে এবং সংশ্লিষ্ট supplier ledger page-এ success message-সহ ফেরত পাঠায়। Store route/controller রাখা আছে, তবে supplier ledger Blade-এ নতুন entry form আর render হয় না।

### `edit($id)`

নির্দিষ্ট ledger entry খুঁজে তার supplier, product list এবং ওই supplier-এর ledger history আনে। Summary total হিসাব করে `$ledger`-সহ একই Blade-এ পাঠায়। `$ledger` থাকার কারণে শুধু এই অবস্থায় `Edit Ledger Entry` form দেখানো হয়।

### `update(Request $request, $id)`

Editable field validate করে। Database transaction ও row lock-এর মধ্যে ledger আনে। আগের payment-এর চেয়ে total কমিয়ে due ঋণাত্মক হতে পারে—এমন update validation error দিয়ে আটকে দেয়। বৈধ হলে invoice, product, quantity, price ও total আপডেট করে, payment অপরিবর্তিত রেখে due পুনরায় হিসাব করে। শেষে supplier ledger page-এ success message-সহ redirect করে।

### `storePayment(Request $request, $id)`

Payment date-কে আজ বা তার আগের তারিখ এবং amount-কে শূন্যের বেশি হতে validate করে। Transaction ও row lock-এর মধ্যে বর্তমান ledger due পরীক্ষা করে; due-এর চেয়ে বেশি payment প্রত্যাখ্যান করে। বৈধ payment-এর date ও amount আলাদা `SupplierLedgerPayment` record-এ রাখে এবং ledger-এর মোট payment ও due আপডেট করে।

### `destroy($id)`

Ledger entry খুঁজে তার supplier ID মনে রাখে, entry delete করে এবং ওই supplier-এর ledger page-এ success message-সহ ফেরত পাঠায়। Payment migration-এর foreign key cascade delete-র কারণে সংশ্লিষ্ট payment history-ও মুছে যায়।

## ৩. Model

### Supplier ledger model

ফাইল: `app/Models/Supplierledger.php`

- `$fillable`-এ supplier, invoice, product এবং পরিমাণ/অর্থের field-গুলো আছে; এগুলো mass assignment দিয়ে তৈরি বা আপডেট করা যায়।
- `supplier()` একটি ledger entry-কে তার supplier-এর সঙ্গে `belongsTo` সম্পর্কিত করে।
- `product()` `product_id` দিয়ে সংশ্লিষ্ট product-কে `belongsTo` সম্পর্কিত করে।
- `payments()` entry-র payment record-গুলোকে `HasMany` হিসেবে ফেরায়; তারিখ ও ID অনুসারে নতুন payment আগে রাখে।

### Supplier payment model

ফাইল: `app/Models/SupplierLedgerPayment.php`

- `$fillable` payment-এর ledger ID, date ও amount নির্ধারণ করে।
- `$casts` payment date-কে date object এবং amount-কে দুই decimal-এর decimal value হিসেবে ব্যবহার করে।
- `supplierledger()` payment record-কে সংশ্লিষ্ট ledger entry-র সঙ্গে `BelongsTo` সম্পর্কিত করে।

### Supplier model-এর ledger সম্পর্ক

ফাইল: `app/Models/Supplier.php`

- `$fillable` supplier-এর name, email, phone, address ও logo mass assignment-এর জন্য অনুমোদন করে।
- `supplierledgers()` supplier-এর সব ledger entry-কে `HasMany` সম্পর্ক হিসেবে দেয়।

## ৪. Migration

### Supplier ledger table

ফাইল: `database/migrations/2026_10_06_111603_create_supplierledgers_table.php`

- `up()` `supplierledgers` table তৈরি করে। এতে supplier foreign key, nullable invoice/product/quantity/price/total/payment/due এবং timestamps থাকে। Supplier মুছে গেলে তার ledger entry cascade delete হয়।
- `down()` rollback-এর সময় `supplierledgers` table সরায়।

### Supplier ledger payment table

ফাইল: `database/migrations/2026_10_12_163000_create_supplier_ledger_payments_table.php`

- `up()` payment date ও decimal amount-সহ `supplier_ledger_payments` table তৈরি করে। `supplierledger_id` ledger table-এ foreign key; ledger মুছে গেলে payment history cascade delete হয়।
- `down()` rollback-এর সময় payment history table সরায়।

## ৫. Routes

ফাইল: `routes/web.php`

Supplier ledger route-গুলো `auth` ও `verified` middleware group-এর মধ্যে আছে:

| Route name | Method ও path | কাজ |
|---|---|---|
| `supplier.ledger` | `GET /supplier/{id}/ledger` | supplier-এর ledger history দেখায় |
| `supplier.ledger.invoice` | `GET /supplier/ledger/{id}/invoice` | নির্দিষ্ট entry-র printable invoice দেখায় |
| `supplier.ledger.payments.store` | `POST /supplier/ledger/{id}/payments` | entry-তে payment যোগ করে |
| `supplier.ledger.store` | `POST /supplier/ledger/store` | request থেকে নতুন ledger entry তৈরি করে |
| `supplier.ledger.edit` | `GET /supplier/ledger/{id}/edit` | edit mode-এ ledger page দেখায় |
| `supplier.ledger.update` | `PUT /supplier/ledger/{id}/update` | ledger entry পরিবর্তন করে |
| `supplier.ledger.destroy` | `GET /supplier/ledger/{id}/destroy` | ledger entry মুছে দেয় |

`supplier.ledger.store` route backend-এ রাখা হয়েছে, তবে এই view-তে নতুন entry তৈরির form নেই। Edit, payment, invoice ও delete action-গুলো আগের মতো কাজ করে।
