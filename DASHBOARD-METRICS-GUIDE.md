# Dashboard Metrics - শেখার নোট

ড্যাশবোর্ডে দৈনিক, সাপ্তাহিক ও মাসিক **Purchase, Revenue, Due, Total Expense** এই ক্রমে চারটি card এবং Revenue column/Profit line-সহ একটি Profit Trend chart দেখানো হয়। Chart Daily, Weekly, Monthly ও Yearly দিয়ে filter হয়; দুটি Top-5 table আগের মতো থাকে। এই পরিবর্তনে business data লেখা বা পরিবর্তন হয় না; শুধু read-only query দিয়ে হিসাব করা হয়।

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
- **Total Expense:** `Employee Voucher Cost + Debit Voucher Cost - Credit Voucher Income`।
- **Profit:** `Revenue - Purchase - Total Expense`। এই একই সূত্র daily, weekly ও monthly period-এ আলাদাভাবে চলে।
- **Profit Trend chart:** Revenue-কে column এবং হিসাব করা Profit-কে line হিসেবে দেখায়। Daily-তে আজকের total-এ একটি point; Weekly-তে চলতি সপ্তাহের প্রতিদিন; Monthly-তে চলতি মাসের প্রতিদিন; Yearly-তে চলতি বছরের প্রতি মাসের total থাকে। তারিখ-ভিত্তিক group না থাকলেও শূন্য amount-এর bucket chart-এ থাকে।

Revenue query-তে `subtotal - discount_amount + vat_amount` ব্যবহৃত হয়; আগের customer due আবার revenue হিসেবে ধরা হয় না। হিসাবটি business amount/date অনুসারে; product-এর বর্তমান ক্রয়মূল্যের ওপর নির্ভর করে না।

## Top tables

1. **Top Selling Products:** `sale_items`-এর snapshot product name ও quantity দিয়ে all-time sold quantity অনুযায়ী প্রথম ৫টি। Sales amount হলো ঐ পণ্যের `line_total`-এর যোগফল।
2. **Top Sales Employees:** invoice-এ থাকা employee ID অনুযায়ী all-time sales amount-এর প্রথম ৫ জন। Sales amount থেকে `previous_due` বাদ; employee মুছে গেলে বা ID না থাকলে `Unknown` দেখায়।

## Code path

- `routes/web.php`-এর `GET /dashboard` (`dashboard`) dashboard shell দেখায়; `GET /dashboard-body` (`dashboardbody`) card ও table-এর data load করে। দুই route-ই `auth` এবং `verified` middleware-এ সুরক্ষিত; নতুন route যোগ হয়নি।
- `DashboardConrtoller::dashboard()` shell view `dashboard.dashboard` ফেরায়।
- `DashboardConrtoller::dashboardbody()` daily/week/month-এর inclusive-start, exclusive-end date range বানায়। প্রতিটি card period-এ invoice থেকে revenue/due, supplier ledger থেকে purchase, employee voucher থেকে employee cost, এবং general voucher থেকে debit/credit যোগ করে; এরপর expense ও profit সূত্র প্রয়োগ করে। Chart-এর জন্য চলতি বছরের প্রতিদিনের aggregate নিয়ে চার filter-এর bucket বানায়। Top product ও employee ranking-ও তৈরি করে।
- `DashboardConrtoller::dailyTotals()` date range-এ একটি source-এর amount date ধরে aggregate করে chart data তৈরির map দেয়।
- `resources/views/dashboard/dashboard-body.blade.php` card-গুলো **Purchase → Revenue → Due → Total Expense** ক্রমে render করে। বড় amount monthly; নিচে daily ও weekly amount দেখায়। Chart period selector-এ chart-টি update হয়; Revenue column ও Profit line dataset দেখায়। একই view-তে Top Selling Products ও Top Sales Employees table থাকে।
- হিসাবের জন্য ব্যবহৃত model/table: `SaleInvoice`/`sale_invoices`, `Supplierledger`/`supplierledgers`, `EmployeePaymentVoucher`/`employee_payment_vouchers`, `VoucherEntry`/`voucher_entries`।
- সংশ্লিষ্ট relation: `SaleInvoice::customer()`, `SaleInvoice::employee()`, `SaleInvoice::items()`; `EmployeePaymentVoucher::employee()` ও `EmployeePaymentVoucher::createdBy()`; `VoucherEntry::createdBy()`; `Supplierledger::supplier()` ও `Supplierledger::product()`। Dashboard-এর period aggregate সরাসরি table/date/amount column-এ চলে, তাই এই relation eager-load করা হয় না; Top Sales Employees query `sale_invoices.user_id` দিয়ে `users` join করে।
- `routes/web.php`-এ আগের `dashboardbody` route-ই chart ও metrics load করে; নতুন route নেই। Chart data একই controller থেকে Blade-এ যায়; আলাদা API/controller route নেই।
- Dashboard পরিবর্তনের জন্য model, relation বা migration schema-তে কোনো পরিবর্তন প্রয়োজন হয়নি। ব্যবহৃত schema আগের migration-গুলোতেই তৈরি। Dashboard request-এ শুধু read query চলে; নতুন migration চালাতে হয় না।

## সংশ্লিষ্ট ফাইল

- `app/Http/Controllers/DashboardConrtoller.php`
- `resources/views/dashboard/dashboard-body.blade.php`
