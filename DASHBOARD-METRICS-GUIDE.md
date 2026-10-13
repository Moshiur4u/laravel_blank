# Dashboard Metrics - শেখার নোট

ড্যাশবোর্ডে দৈনিক, সাপ্তাহিক ও মাসিক **Purchase, Revenue, Due, Profit** এই ক্রমে চারটি card এবং দুটি Top-5 table দেখানো হয়। এই পরিবর্তনে business data লেখা বা পরিবর্তন হয় না; শুধু read-only query দিয়ে হিসাব করা হয়।

## তারিখের সীমা ও KPI

- **Daily:** আজকের calendar day, server/application timezone অনুযায়ী।
- **Weekly:** চলতি সপ্তাহ সোমবার থেকে রবিবার; সপ্তাহের শুরু ধরা হয়, পরের সপ্তাহের শুরুর আগ পর্যন্ত।
- **Monthly:** চলতি calendar month; পরের মাসের প্রথম দিনের আগ পর্যন্ত।
- প্রতিটি range-এ start অন্তর্ভুক্ত এবং end বাদ, ফলে দিনের শেষ সময়ের record-ও সঠিকভাবে ধরা হয়।
- **Total Revenue:** `subtotal - discount_amount + vat_amount`; আগের customer due নতুন sale revenue হিসেবে পুনরায় গণনা হয় না।
- **Total Due:** সময়সীমার sale invoice-গুলোর `due_amount` যোগফল।
- **Purchase:** সময়সীমার মধ্যে তৈরি supplier ledger entry-র `total` যোগফল। Supplier ledger schema-তে আলাদা purchase date নেই, তাই entry-র `created_at` ব্যবহৃত হয়।
- **Employee Voucher Cost:** `employee_payment_vouchers.amount` যোগফল, `payment_date` অনুযায়ী।
- **Debit Voucher Cost:** `voucher_entries.amount`-এর debit type যোগফল, `voucher_date` অনুযায়ী।
- **Credit Voucher Income:** `voucher_entries.amount`-এর credit type যোগফল, `voucher_date` অনুযায়ী।
- **Profit:** `Revenue - Purchase - Employee Voucher Cost - Debit Voucher Cost + Credit Voucher Income`। এই একই সূত্র daily, weekly ও monthly period-এ আলাদাভাবে চলে।

Revenue query-তে `subtotal - discount_amount + vat_amount` ব্যবহৃত হয়; আগের customer due আবার revenue হিসেবে ধরা হয় না। হিসাবটি business amount/date অনুসারে; product-এর বর্তমান ক্রয়মূল্যের ওপর নির্ভর করে না।

## Top tables

1. **Top Selling Products:** `sale_items`-এর snapshot product name ও quantity দিয়ে all-time sold quantity অনুযায়ী প্রথম ৫টি। Sales amount হলো ঐ পণ্যের `line_total`-এর যোগফল।
2. **Top Sales Employees:** invoice-এ থাকা employee ID অনুযায়ী all-time sales amount-এর প্রথম ৫ জন। Sales amount থেকে `previous_due` বাদ; employee মুছে গেলে বা ID না থাকলে `Unknown` দেখায়।

## Code path

- `routes/web.php`-এর `GET /dashboard` (`dashboard`) dashboard shell দেখায়; `GET /dashboard-body` (`dashboardbody`) card ও table-এর data load করে। দুই route-ই `auth` এবং `verified` middleware-এ সুরক্ষিত; নতুন route যোগ হয়নি।
- `DashboardConrtoller::dashboard()` shell view `dashboard.dashboard` ফেরায়।
- `DashboardConrtoller::dashboardbody()` daily/week/month-এর inclusive-start, exclusive-end date range বানায়। প্রতিটি period-এ invoice থেকে revenue/due, supplier ledger থেকে purchase, employee voucher থেকে employee cost, এবং general voucher থেকে debit/credit যোগ করে; এরপর profit সূত্র প্রয়োগ করে। Top product ও employee ranking-ও তৈরি করে।
- `resources/views/dashboard/dashboard-body.blade.php` card-গুলো **Purchase → Revenue → Due → Profit** ক্রমে render করে। বড় amount monthly; নিচে daily ও weekly amount দেখায়। একই view-তে Top Selling Products ও Top Sales Employees table থাকে।
- হিসাবের জন্য ব্যবহৃত model/table: `SaleInvoice`/`sale_invoices`, `Supplierledger`/`supplierledgers`, `EmployeePaymentVoucher`/`employee_payment_vouchers`, `VoucherEntry`/`voucher_entries`।
- সংশ্লিষ্ট relation: `SaleInvoice::customer()`, `SaleInvoice::employee()`, `SaleInvoice::items()`; `EmployeePaymentVoucher::employee()` ও `EmployeePaymentVoucher::createdBy()`; `VoucherEntry::createdBy()`; `Supplierledger::supplier()` ও `Supplierledger::product()`। Dashboard-এর period aggregate সরাসরি table/date/amount column-এ চলে, তাই এই relation eager-load করা হয় না; Top Sales Employees query `sale_invoices.user_id` দিয়ে `users` join করে।
- Dashboard পরিবর্তনের জন্য route, model, relation বা migration schema-তে কোনো পরিবর্তন প্রয়োজন হয়নি। ব্যবহৃত schema আগের migration-গুলোতেই তৈরি: `2026_07_06_172251_create_sale_invoices_table.php`, `2026_07_06_172445_create_sale_items_table.php`, `2026_10_11_000003_upgrade_sales_tables.php`, `2026_10_06_111603_create_supplierledgers_table.php`, `2026_10_12_181140_create_employee_payment_vouchers_table.php`, `2026_10_13_000000_add_created_by_to_employee_payment_vouchers_table.php`, এবং `2026_10_13_000001_create_voucher_entries_table.php`। Dashboard request-এ শুধু read query চলে; নতুন migration চালাতে হয় না।

## সংশ্লিষ্ট ফাইল

- `app/Http/Controllers/DashboardConrtoller.php`
- `resources/views/dashboard/dashboard-body.blade.php`
