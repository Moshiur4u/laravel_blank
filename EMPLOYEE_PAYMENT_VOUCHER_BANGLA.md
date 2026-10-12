# Employee Payment Voucher — বাংলা নির্দেশিকা

এই feature দিয়ে কর্মীর salary, salary advance, bonus, bonus advance, conveyance, sales incentive, lunch bill, mobile bill এবং অন্যান্য কর্মী-খরচের payment voucher তৈরি ও দেখা যায়। প্রতিটি কর্মীর সব ধরনের voucher আলাদা ledger-এ দেখা, প্রিন্ট এবং Excel-এ খোলার উপযোগী CSV হিসেবে export করা যায়।

## ব্যবহার করার নিয়ম

1. Accounts মেনু থেকে **Employee Payment** খুলুন।
2. কর্মী, খরচের ধরন, payment date, amount এবং payment method নির্বাচন/লিখুন।
3. প্রয়োজন হলে reference দিন। **Other Employee Expense** বাছলে notes-এ খরচের বিবরণ লিখুন।
4. **Save Voucher** চাপলে voucher সংরক্ষিত হবে এবং history-তে দেখা যাবে।
5. Voucher history-তে কর্মীর **View Ledger** button চাপলে তার সব expense category-র payment history ও category totals দেখুন। Ledger page থেকে print বা **Export Excel** করা যায়।

Voucher number স্বয়ংক্রিয়ভাবে `EPV-YYYYMMDD-000001` ধরনের হয়। Payment date আজ বা অতীতের হতে হবে; amount শূন্যের বেশি এবং সর্বোচ্চ দুই decimal-এর। `Salary` expense হলে amount নির্বাচিত কর্মীর বর্তমান salary-এর বেশি হতে পারে না; কর্মীর salary সেট করা না থাকলে Salary voucher জমা দেওয়া যাবে না। Voucher record মুছে ফেলা বা edit করার route দেওয়া হয়নি, যাতে তৈরি হওয়া expense history অজান্তে বদলে না যায়।

## Blade view

ফাইল: `resources/views/backend/Accounts Entries/empolyeePayment.blade.php`

- Dashboard layout ব্যবহার করে employee voucher form ও voucher history একই পেজে দেখায়।
- Form-এর employee, expense type, payment date, amount ও payment method আবশ্যক; reference ঐচ্ছিক।
- Expense type ও payment method-এর option controller থেকে model-এর constant map দিয়ে আসে, যাতে form ও validation-এ একই অনুমোদিত মান থাকে।
- Error হলে সংশ্লিষ্ট field-এর পাশে Laravel validation message দেখায় এবং `old()` দিয়ে আগের input ধরে রাখে।
- `Salary` expense বাছলে নির্বাচিত কর্মীর salary-কে amount input-এর সর্বোচ্চ সীমা হিসেবে দেখায়; employee/category বদলালে limit আপডেট হয়।
- Employee select করার পর **View Employee Ledger** link দেখায়; voucher history না থাকলেও নির্বাচিত কর্মীর ledger খোলা যায়।
- JavaScript `Other Employee Expense` নির্বাচন করলে notes field-কে browser-এ required করে; controller-এও `required_if` validation আছে।
- Summary card সব voucher-এর মোট amount এবং মোট voucher record দেখায়।
- History table voucher number, payment date, employee-র snapshot নাম, category, method, reference, amount ও notes দেখায়। Table pagination Bootstrap 5 format ব্যবহার করে।
- History-র **View Ledger** button নির্দিষ্ট কর্মীর সব voucher ledger page-এ নিয়ে যায়।

### কর্মীর payment ledger

ফাইল: `resources/views/backend/Accounts Entries/employeePaymentLedger.blade.php`

- নির্বাচিত কর্মীর নাম, email, current salary এবং সব expense category-র lifetime total দেখায়।
- Payment history voucher number, date, category, method, reference, amount ও notes-সহ পূর্ণ table-এ দেখায়। Footer-এ সব voucher-এর total থাকে; তাই Print Ledger-এ শুধু বর্তমান page নয়, সব history অন্তর্ভুক্ত হয়।
- **Print Ledger** browser print dialog খোলে; print CSS dashboard navigation ও buttons লুকিয়ে সম্পূর্ণ ledger-টি কাগজে পরিষ্কারভাবে রাখে।
- **Export Excel** route-এ সব matching voucher (শুধু বর্তমান page নয়) download করে Excel-compatible UTF-8 CSV হিসেবে।

## Controller

ফাইল: `app/Http/Controllers/EmployeePaymentVoucherController.php`

### `index()`

- Employee dropdown-এর জন্য user-দের নাম অনুসারে আনে।
- Model থেকে খরচের category এবং payment method-এর অনুমোদিত option নেয়।
- Voucher-গুলো employee relation-সহ payment date ও ID অনুসারে নতুন আগে paginate করে।
- সব voucher-এর amount aggregate করে এবং data-গুলো Blade view-তে পাঠায়।

### `ledger(int $employeeId)`

- Employee ID দিয়ে user খুঁজে না পেলে 404 দেয়।
- ওই কর্মীর সব voucher payment date ও ID অনুসারে সাজিয়ে এবং সব record-এর মোট amount/category summary গণনা করে।
- কর্মী, voucher, category ও payment method data `employeePaymentLedger` Blade-এ পাঠায়।

### `exportLedger(int $employeeId)`

- Employee-কে খুঁজে ওই কর্মীর সব voucher-কে date order-এ stream করে; বড় ledger-ও ছোট batch-এ export হয়।
- UTF-8 BOM-সহ `.csv` download দেয়, যা Excel-এ খোলা যায়; সব history এবং all-time total এতে থাকে, অতিরিক্ত Excel library লাগে না।
- Name, reference ও notes-এর মতো untrusted text spreadsheet formula হিসেবে execute হওয়া ঠেকাতে neutralize করে।

### `store(Request $request)`

- Employee ID-কে বিদ্যমান `users` record, expense type ও payment method-কে model-এর অনুমোদিত option, date-কে আজ বা তার আগের দিন এবং amount-কে positive দুই-decimal money হিসেবে validate করে।
- Transaction-এর মধ্যে employee row lock করে `Salary` expense-এর amount-কে কর্মীর বর্তমান salary-এর সঙ্গে cents হিসেবে তুলনা করে; salary অনির্ধারিত বা amount বেশি হলে `amount` validation error ফেরায়। Browser-এর limit ছাড়াও এই server-side পরীক্ষা বাধ্যতামূলক।
- `Other Employee Expense`-এর ক্ষেত্রে notes আবশ্যক করে; reference ও notes-এর দৈর্ঘ্যও সীমাবদ্ধ করে।
- Database transaction-এর মধ্যে নির্বাচিত employee lock করে, voucher-এ employee ID এবং তার নামের snapshot রাখে।
- Record insert হওয়ার পর database ID ব্যবহার করে unique voucher number তৈরি করে; একই transaction-এ পরিবর্তনটি commit হয়।
- সফল হলে voucher number-সহ success message দিয়ে voucher page-এ ফেরত যায়।

## Model

ফাইল: `app/Models/EmployeePaymentVoucher.php`

- `EXPENSE_TYPES` অনুমোদিত expense key এবং ব্যবহারকারীর জন্য label নির্ধারণ করে।
- `PAYMENT_METHODS` বিদ্যমান sales payment choices—Cash, Bank, Nagad, bKash, Rocket ও Upay—পুনর্ব্যবহার করে।
- `$fillable` voucher তৈরির সময় অনুমোদিত database field নির্ধারণ করে।
- `casts()` payment date-কে date object এবং amount-কে দুই decimal-এর decimal value হিসেবে ফেরায়।
- `employee()` ভাউচারকে তার কর্মী user-এর সঙ্গে `BelongsTo` relation করে। Employee user delete হলেও foreign key null হয়, কিন্তু `employee_name` snapshot history-তে নামটি রাখে।

## Migration

ফাইল: `database/migrations/2026_10_12_181140_create_employee_payment_vouchers_table.php`

- `up()` voucher table তৈরি করে: unique voucher number, ঐচ্ছিক employee foreign key, employee name snapshot, expense type, indexed payment date, decimal amount, method, optional reference/notes এবং timestamps।
- Employee মুছে গেলে `employee_id` null হয়; voucher record মুছে যায় না।
- Voucher number nullable রাখা হয়েছে, কারণ record তৈরির ID পাওয়ার পর একই transaction-এ পূরণ করা হয়। পূর্ণ voucher number-এর ওপর unique index duplicate প্রতিরোধ করে।
- `down()` rollback-এর সময় শুধু voucher table সরায়।

## Routes

ফাইল: `routes/web.php`

দুটি route-ই `auth` এবং `verified` middleware-এর অধীনে:

| Route name | HTTP method ও path | Controller action |
|---|---|---|
| `employee-payment.index` | `GET /employee-payment-vouchers` | Form, summary ও voucher history দেখায় |
| `employee-payment.store` | `POST /employee-payment-vouchers` | Validation করে নতুন voucher সংরক্ষণ করে |
| `employee-payment.ledger` | `GET /employee-payment-vouchers/{employee}/ledger` | একজন কর্মীর paginated সব payment history দেখায় |
| `employee-payment.ledger.export` | `GET /employee-payment-vouchers/{employee}/ledger/export` | ওই কর্মীর সব voucher Excel-compatible CSV হিসেবে download করে |

## Navigation

ফাইল: `resources/views/dashboard/dashboard.blade.php`

Accounts মেনুর **Employee Payment** link `employee-payment.index` route-এ যায়। Voucher Entries link এই feature-এ পরিবর্তন করা হয়নি।

## Database migration

Project root থেকে নতুন voucher table তৈরি করতে চালান:

```bash
php artisan migrate
```
