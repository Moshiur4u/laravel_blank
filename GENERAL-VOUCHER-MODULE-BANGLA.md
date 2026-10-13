# General Debit/Credit Voucher Module

## Module-এর পরিধি

এটি Employee Payment Voucher থেকে আলাদা সাধারণ Debit/Credit voucher module। Employee Payment-এর form, ledger, report, route, model ও data-তে এই module কোনো পরিবর্তন করে না।

Voucher form-এ ব্যবহারকারী Debit অথবা Credit নির্বাচন করেন, Expense/Income Type নিজে লিখেন এবং তারিখ, পরিমাণ, reference ও description দিতে পারেন। Login করা user-এর ID ও সেই সময়ের নাম server-side-এ সংরক্ষিত হয়।

## Voucher code

Voucher database-এ insert করার পর primary key পাওয়া যায়। Transaction-এর মধ্যে সেই ID দিয়ে unique code তৈরি হয়:

- Debit: `DBV-YYYYMMDD-000001`
- Credit: `CRV-YYYYMMDD-000002`

তারিখ code-এ voucher-এর নির্বাচিত তারিখ থেকে আসে; ক্রমিক অংশ database ID থেকে আসে। Code server-এ generate হয়; form থেকে গ্রহণ করা হয় না।

## Route ও middleware

`routes/web.php`-এ সব general voucher route `auth` ও `verified` middleware-এর মধ্যে:

| Method | Route name | কাজ |
|---|---|---|
| GET | `voucher-entries.index` | Create form ও সাম্প্রতিক voucher |
| POST | `voucher-entries.store` | Voucher validate ও save |
| GET | `voucher-entries.report` | Daily report এবং filter |

Dashboard-এর Accounts menu-তে **Voucher Entries** এবং Reports menu-তে **Daily Debit/Credit Voucher Report** এই route-গুলো খোলে। Employee Payment-এর menu/report আলাদাই থাকে এবং তার report label **Daily Employee Payment Report**।

## Controller function

`VoucherEntryController`:

- `index()` voucher type option, recent voucher page এবং আজকের voucher count/amount view-তে পাঠায়।
- `store()` `voucher_type`-ийг Debit/Credit-এ সীমাবদ্ধ করে; expense/income category-কে user-input string হিসেবে validate করে; date, decimal amount, reference ও description validate করে। Authenticated user-এর ID ও নাম নেয়, database transaction-এ record insert করে এবং primary key দিয়ে code বসায়।
- `report()` voucher date range ও creator ID validate করে। Date না দিলে আজকের তারিখ ধরে; শুধু একটি date দিলে ওই একদিন report করে। Invalid filter হলে error flash করে পরিষ্কার report route-এ ফেরায়, যাতে GET redirect loop না হয়। Daily summary date, Debit/Credit type ও creator snapshot ধরে group করে; আলাদা debit/credit total, record count এবং filter ধরে paginated detail পাঠায়।

## Blade view

- `resources/views/backend/Accounts Entries/voucherEntryCreate.blade.php`: Debit/Credit select, free-text Expense/Income Type, voucher date, amount, optional reference/description, validation feedback এবং recent voucher table।
- `resources/views/backend/Accounts Entries/voucherEntryReport.blade.php`: date range ও creator dropdown, daily summary, total এবং paginated voucher details।
- `resources/views/backend/Accounts Entries/empolyeePayment.blade.php`: পুরনো Employee Payment module; এই পরিবর্তনে এর form ও behavior পরিবর্তিত হয়নি।
- `resources/views/dashboard/dashboard.blade.php`: Accounts ও Reports section-এ general voucher-এর link, Employee Payment-এর আলাদা link অক্ষত।

## Model, relation ও migration

- `App\Models\VoucherEntry` হলো `voucher_entries` table-এর Eloquent model। `TYPES` constant-এ debit/credit value ও label; `voucher_date` date এবং `amount` দুই দশমিকের cast; fillable-এ form data-র পাশাপাশি server-generated voucher code ও creator snapshot রয়েছে।
- `VoucherEntry::createdBy()` nullable `created_by` foreign key দিয়ে `User`-এর belongs-to relation। `created_by_name` snapshot account-এর নাম পরে পরিবর্তিত/মুছে গেলেও voucher-এ তৈরির সময়কার নাম রেখে দেয়।
- `2026_10_13_000001_create_voucher_entries_table.php` শুধু নতুন `voucher_entries` table তৈরি করে। Voucher code unique; voucher type ও তারিখ indexed; report দ্রুত করার জন্য voucher date ও creator ID-এর compound index আছে। User মুছে গেলে `created_by` null হবে, কিন্তু snapshot name থাকবে।
- Existing Employee Payment table বা তার schema-তে কোনো পরিবর্তন করা হয়নি।

## যাচাইয়ের test

`tests/Feature/VoucherEntryTest.php` voucher save-এর automatic code, debit/credit, free-text category, creator ID/name, date ও creator filtering এবং daily summary যাচাই করে।
