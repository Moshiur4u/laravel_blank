# Combined Voucher Report - Bangla Documentation

## উদ্দেশ্য

একটি report-এ Employee Payment Voucher এবং General Debit/Credit Voucher একসঙ্গে দেখা যায়। Report period হিসেবে Daily অথবা Monthly বেছে নেওয়া যায়। Report-এর print header Sales Invoice-এর মতো শিরোনাম/period বামে এবং generated date ডানে দেখায়। Print করার সময় dashboard navigation, filter form ও action button বাদ পড়ে; summary ও voucher table print হয়।

## Route ও প্রবেশাধিকার

`routes/web.php`-এ route-টি `auth` এবং `verified` middleware group-এর মধ্যে:

| Method | Route | কাজ |
|---|---|---|
| GET | `voucher-report.combined` (`/voucher-report/combined`) | Combined voucher report দেখানো ও Daily/Monthly filter প্রয়োগ |

Dashboard-এর Reports menu-তে **Combined Voucher Report** link এই route-এ যায়। Report-এর জন্য আলাদা API, write route, model relation বা migration যোগ করা হয়নি।

## Filter ও তারিখের নিয়ম

- Period নির্বাচন `period=daily` অথবা `period=monthly`।
- Daily-তে `date` input-এর একটি নির্দিষ্ট দিন report হয়; filter না দিলে আজকের তারিখ ব্যবহৃত হয়।
- Monthly-তে `month` input (`YYYY-MM`) থেকে মাসের প্রথম ও শেষ দিন নির্ধারিত হয়।
- Employee Payment Voucher-এর report date হলো `created_at`-এর দিন—আগের Employee Voucher Report-এর তারিখ-ব্যবহারের সঙ্গে সামঞ্জস্য রাখতে।
- Debit/Credit Voucher-এর report date হলো business date `voucher_date`—আগের Debit/Credit report-এর নিয়ম অনুযায়ী।
- ভুল period/date/month validation হলে error message-সহ একই report route-এ ফেরত যায়।

## Controller logic

`app/Http/Controllers/CombinedVoucherReportController.php`:

- `index(Request $request)` filter validate করে, Daily অথবা Monthly date range নির্ধারণ করে।
- Employee voucher table থেকে voucher number, expense head, employee snapshot, creator name, amount, reference/notes এবং `created_at`-এর date নেয়।
- General voucher table থেকে voucher code, Debit/Credit type, user-entered expense/income head, creator snapshot, amount, reference/description এবং `voucher_date` নেয়।
- দুই query একই column shape-এ `UNION ALL` করে, ফলে কোনো ধরনের voucher বাদ না দিয়ে এক report list তৈরি হয়।
- Combined query থেকে Debit voucher count-এ Employee ও General Debit entry একসঙ্গে, Credit voucher count-এ General Credit entry গণনা করে; পাশাপাশি Employee, Debit ও Credit amount total বের করে।
- Voucher detail date/code অনুযায়ী সাজিয়ে ৩০টি করে paginate হয়; URL query parameter pagination-এ বজায় থাকে।
- Controller কোনো voucher insert/update/delete করে না।

## Blade ও print header

`resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`:

- Daily/Monthly filter form এবং summary cards দেখায়। Voucher Count card-এ Debit count (Employee + General Debit) ও Credit count আলাদা অংশে থাকে।
- Combined detail table-এ date, code, voucher type, voucher head, employee, creator, reference/description ও amount দেখায়।
- Employee head-কে `EmployeePaymentVoucher::EXPENSE_TYPES` থেকে label-এ রূপান্তর করে; Debit/Credit type-কে `VoucherEntry::TYPES` থেকে label-এ দেখায়।
- Sales Invoice-এর print-header layout অনুসরণ করে report title/period বামে, report-generated timestamp ডানে দেখায়।
- Print media style-এ navigation/filter/button/pagination লুকায়; report summary ও data table মুদ্রণের জন্য রাখে।

## Model, relation ও migration

- `EmployeePaymentVoucher` model-এর `employee()` relation কর্মী `User`-এর সঙ্গে এবং `createdBy()` relation voucher তৈরিকারী `User`-এর সঙ্গে যুক্ত। Employee name database snapshot থেকে দেখানো হয়।
- `VoucherEntry` model-এর `createdBy()` relation voucher তৈরিকারী `User`-এর সঙ্গে যুক্ত; report-এ `created_by_name` snapshot ব্যবহৃত হয়।
- Combined report SQL query table-এর columns-এ সরাসরি চলে; relation eager-load করে না।
- `employee_payment_vouchers` table পূর্বের `2026_10_12_181140_create_employee_payment_vouchers_table.php` এবং creator column `2026_10_13_000000_add_created_by_to_employee_payment_vouchers_table.php`-এ তৈরি।
- `voucher_entries` table পূর্বের `2026_10_13_000001_create_voucher_entries_table.php`-এ তৈরি।
- Combined report-এর জন্য নতুন migration বা schema পরিবর্তন নেই।

## পরিবর্তিত project surface

- Controller: `app/Http/Controllers/CombinedVoucherReportController.php`
- Route registration: `routes/web.php`
- Report view: `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`
- Dashboard Reports menu: `resources/views/dashboard/dashboard.blade.php`
- Feature tests: `tests/Feature/CombinedVoucherReportTest.php`
