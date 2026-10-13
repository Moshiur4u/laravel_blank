# Employee Payment Voucher ও দৈনিক রিপোর্ট

## উদ্দেশ্য ও ব্যবহার

এই পরিবর্তনটি বিদ্যমান **Employee Payment Voucher** module-কে extend করে। নতুন cash-in/cash-out বা অন্য voucher type তৈরি করে না।

- Employee Payment পেজে আগের মতো voucher তৈরি করা যায়।
- Dashboard-এর **Reports → Daily Voucher Report** থেকে দৈনিক report খোলা যায়।
- Report-এ voucher তৈরির তারিখের শুরু/শেষ, **Employee name** এবং **Created by** user নির্বাচন করে filter করা যায়।
- শুরু ও শেষ তারিখ না দিলে আজকের report দেখা যায়। শুধু একটি তারিখ দিলে সেই দিনটিই filter হয়।
- Report-এ date, employee ও creator অনুযায়ী দৈনিক summary, মোট record/amount এবং voucher details দেখা যায়।
- Voucher তৈরির তারিখ বলতে `created_at` বোঝানো হয়েছে; অর্থপ্রদানের business date আলাদা `payment_date` কলামে আগের মতো থাকে।

## Route ও middleware

`routes/web.php`-এ route-গুলো `auth` ও `verified` middleware group-এর মধ্যে আছে:

| Method | Route name | Controller কাজ |
|---|---|---|
| GET | `employee-payment.index` | voucher form ও সাম্প্রতিক তালিকা |
| POST | `employee-payment.store` | validation-সহ voucher তৈরি |
| GET | `employee-payment.report` | date/employee/creator filter-সহ দৈনিক report |
| GET | `employee-payment.ledger` | employee-র ledger |
| GET | `employee-payment.ledger.export` | employee ledger CSV export |

Dashboard Accounts-এর Employee Payment menu voucher তৈরির পেজে যায়; Reports-এর Daily Employee Payment Report menu এই employee voucher report-এ যায়। General Debit/Credit voucher-এর নিজস্ব menu ও report আলাদা।

## Controller-এর function

`EmployeePaymentVoucherController`-এর দায়িত্ব:

- `index()` employee তালিকা, expense/payment option এবং voucher history দেখায়।
- `store(Request $request)` input validate করে; user-কে lock করে employee তথ্য নেয়; Salary voucher-এর amount কর্মীর salary ছাড়িয়েছে কি না যাচাই করে; transaction-এর মধ্যে voucher তৈরি ও unique `EPV-...` number নির্ধারণ করে। `created_by` form input নয়—বর্তমান authenticated user-এর ID থেকে নেওয়া হয়।
- `report(Request $request)` তারিখ, employee এবং creator filter validate করে; `created_at`-এর date range অনুযায়ী তালিকা, প্রতিদিন/employee/creator-প্রতি count/amount, মোট count/amount তৈরি করে। details পৃষ্ঠা ধরে paginate হয় এবং pagination-এ filter query string থাকে। ভুল filter হলে error দেখিয়ে পরিষ্কার report URL-এ ফেরে, তাই GET request-এ redirect loop হয় না।
- `ledger()` employee-র voucher-গুলো expense type অনুযায়ী summary-সহ দেখায়।
- `exportLedger()` Excel-এ খোলা যায় এমন UTF-8 CSV stream করে।
- `spreadsheetSafeValue()` CSV-তে untrusted text-কে spreadsheet formula হিসেবে চালানো থেকে আটকায়।

## Blade view ও ব্যবহারকারীর flow

- `resources/views/backend/Accounts Entries/empolyeePayment.blade.php` আগের create form/history; এখানে Daily Voucher Report-এর shortcut আছে।
- `resources/views/backend/Accounts Entries/VoucherEntries.blade.php` report-এর date, employee ও creator filter, daily summary, total এবং paginated voucher detail দেখায়।
- `resources/views/backend/Accounts Entries/employeePaymentLedger.blade.php` আগের per-employee ledger ও print/export view; এতে কোনো পরিবর্তন দরকার হয়নি।
- Date filter `GET` form হওয়ায় filter URL-এ থাকে; pagination পরের পাতায় গেলেও filter বজায় থাকে।
- Creator delete হয়ে গেলে বা পুরনো record-এ `created_by` না থাকলে report-এ `Unknown / legacy voucher` দেখায়।

## Model relation ও migration

- `EmployeePaymentVoucher::employee()` `employee_id` দিয়ে voucher-এর employee-কে যুক্ত করে। Employee মুছে গেলে তার foreign key null হতে পারে; `employee_name` snapshot থাকে।
- নতুন `EmployeePaymentVoucher::createdBy()` relation `created_by` দিয়ে voucher তৈরিকারী `User`-কে যুক্ত করে।
- Employee `User`-এর nullable `phone` field create/edit form ও employee profile-এ সংরক্ষণ/দেখানো হয়।
- `2026_10_13_000002_add_phone_to_users_table.php` users table-এ nullable phone যোগ করে; rollback করলে শুধু নতুন phone column সরায়।
- `2026_10_13_000000_add_created_by_to_employee_payment_vouchers_table.php` existing voucher মুছে/পরিবর্তন না করে nullable `created_by` foreign key যোগ করে এবং user মুছে গেলে সেটি null করে। Nullable হওয়ায় আগের voucher-গুলোর creator `NULL` থাকে; migration rollback করলে শুধু নতুন column/constraint সরবে।
- নতুন voucher insert-এ authenticated user ID `created_by` হিসেবে সংরক্ষিত হয়; report filter ঐ field-এ চলে।
- অন্য model, relation, business data, voucher type বা migration পরিবর্তন করা হয়নি।

## পরীক্ষা

`tests/Feature/EmployeePaymentVoucherReportTest.php`-এ creator/date ও employee filtering, দৈনিক summary এবং নতুন voucher-এ authenticated creator সংরক্ষণের test যোগ করা হয়েছে।
