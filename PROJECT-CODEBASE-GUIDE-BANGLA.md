# Laravel প্রকল্পের সম্পূর্ণ কোডবেস নির্দেশিকা

## নথির উদ্দেশ্য

এই নথি Laravel ব্যবসায়িক অ্যাপ্লিকেশনের route, controller, migration, model, seeder এবং Blade source-গুলো কী কাজ করে, কেন আছে এবং কোথায় ব্যবহৃত হয়—তা বাংলা ভাষায় ব্যাখ্যা করে। এটি বর্তমান source tree দেখে তৈরি; কোনো application code, database বা configuration পরিবর্তন করে না।

প্রকল্পে ইতিমধ্যে থাকা [PROJECT-MODULES-DOCUMENTATION-BANGLA.md](./PROJECT-MODULES-DOCUMENTATION-BANGLA.md)-তে module-এর ব্যবসায়িক হিসাব ও নির্বাচিত code excerpt আরও বিস্তারিত আছে। এই নথিতে তার সঙ্গে সম্পূর্ণ source-file inventory, route-flow এবং অনুল্লেখিত template-গুলো যোগ করা হয়েছে।

> প্রতিটি source file-এর নাম link করা হয়েছে—সেই file-ই সম্পূর্ণ code-এর canonical version। এখানে প্রয়োজনীয় code pattern ও ব্যাখ্যা দেওয়া হয়েছে; ২০০টির বেশি source file-এর পূর্ণ কপি Markdown-এ রাখলে একই code বারবার হয়ে maintain করা কঠিন হবে।

## প্রকল্পের স্তরগুলো কীভাবে একসঙ্গে কাজ করে

1. **Route** HTTP method ও URL-কে controller action-এর সঙ্গে যুক্ত করে; `auth`, `verified` এবং permission middleware অনুরোধ সীমাবদ্ধ করে।
2. **Controller** request validate করে, database query/transaction চালায়, model ব্যবহার করে এবং Blade view বা JSON/CSV response ফেরত দেয়।
3. **Migration** table, column, index ও foreign key তৈরি/পরিবর্তন করে।
4. **Model** table-এর fillable field, cast, relationship, scope এবং domain helper নির্ধারণ করে।
5. **Seeder** permission, role, user ও প্রাথমিক catalog data তৈরি করে।
6. **Blade** controller-এর data-কে form, list, report, invoice বা print document হিসেবে দেখায়। HTML-escaped `{{ }}`, CSRF token, validation output, `@can` directives এবং responsive/print CSS—এসব UI layer-এ ব্যবহৃত হয়।

### Source inventory

| স্তর | Source file সংখ্যা | Source root |
|---|---:|---|
| Controller | 31 | [`app/Http/Controllers/`](./app/Http/Controllers/) |
| Model | 21 | [`app/Models/`](./app/Models/) |
| Migration | 39 | [`database/migrations/`](./database/migrations/) |
| Seeder | 11 | [`database/seeders/`](./database/seeders/) |
| Route | 3 | [`routes/`](./routes/) |
| Blade | 82 | [`resources/views/`](./resources/views/) |

এই তালিকা অ্যাপের domain source-এর inventory; Laravel framework-এর `vendor/` source বা public third-party JavaScript/CSS bundle অন্তর্ভুক্ত নয়।

## ১. Routes: URL, access control ও controller flow

### [`routes/web.php`](./routes/web.php)

Back-office route-গুলোর মূল প্রবেশদ্বার। সাধারণ route-এ login এবং email verification middleware থাকে; module route-এ অতিরিক্ত `permission:` middleware দিয়ে server-side access নিয়ন্ত্রণ করা হয়। Blade-এর `@can` শুধু button/link দেখানো নিয়ন্ত্রণ করে—নিরাপত্তার বিকল্প নয়।

নিচে active route-গুলোর URI, HTTP method, route name এবং controller action দেওয়া হলো। Business route-গুলো `auth` ও `verified` group-এ; অতিরিক্ত permission middleware আলাদা কলামে আছে। `A|B` মানে middleware-এ `A` অথবা `B` permission গ্রহণযোগ্য।

| HTTP | URI | Route name | Controller@action | অতিরিক্ত permission |
|---|---|---|---|---|
| GET | `/user-activity-logs` | `user-activity-logs.index` | `UserActivityLogController@index` | `user.activity.logs` |
| GET | `/` | — | Closure | — |
| GET | `/logout` | `logout` | `UserController@logout` | — |
| GET | `/dashboard` | `dashboard` | `DashboardConrtoller@dashboard` | `Dashboard` |
| GET | `/dashboard-body` | `dashboardbody` | `DashboardConrtoller@dashboardbody` | `Dashboard` |
| GET / PUT | `/organization` | `organization.edit` / `organization.update` | `OrganizationController@edit/update` | `organization info.access` |
| GET | `/voucher-accounts` | `voucher-accounts.index` | `VoucherAccountController@index` | `voucher.account.index` |
| GET | `/voucher-accounts/create` | `voucher-accounts.create` | `VoucherAccountController@create` | `voucher.account.create` |
| POST | `/voucher-accounts` | `voucher-accounts.store` | `VoucherAccountController@store` | `voucher.account.create` |
| GET | `/voucher-accounts/{voucherAccount}/edit` | `voucher-accounts.edit` | `VoucherAccountController@edit` | `voucher.account.edit` |
| PUT | `/voucher-accounts/{voucherAccount}` | `voucher-accounts.update` | `VoucherAccountController@update` | `voucher.account.update` |
| DELETE | `/voucher-accounts/{voucherAccount}` | `voucher-accounts.destroy` | `VoucherAccountController@destroy` | `voucher.account.delete` |
| GET / POST | `/voucher-entries` | `voucher-entries.index/store` | `VoucherEntryController@index/store` | `voucher` / `voucher.entry` |
| GET | `/voucher-entries/report`, `/voucher-entries/report/export` | `voucher-entries.report`, `.report.export` | `VoucherEntryController@report/exportReport` | `voucher.report.miscellaneous\|reports.all_access` |
| GET | `/voucher-report/combined`, `/voucher-report/combined/export` | `voucher-report.combined`, `.combined.export` | `CombinedVoucherReportController@index/export` | `voucher.report.view\|reports.all_access` |
| PUT / DELETE | `/voucher-report/combined/{category}/{voucherId}` | `voucher-report.combined.update/destroy` | `CombinedVoucherReportController@update/destroy` | `voucher.edit\|reports.all_access` / `voucher.delete\|reports.all_access`; category `employee|debit|credit`, ID numeric |
| GET / POST | `/employee-payment-vouchers` | `employee-payment.index/store` | `EmployeePaymentVoucherController@index/store` | `employee_payment_voucher.entry` |
| GET | `/employee-payment-vouchers/report`, `/employee-payment-vouchers/report/export` | `employee-payment.report/export` | `EmployeePaymentVoucherController@report/exportReport` | `voucher.report.employee\|reports.all_access` |
| GET | `/employee-payment-vouchers/{employee}/ledger`, `/ledger/export` | `employee-payment.ledger/export` | `EmployeePaymentVoucherController@ledger/exportLedger` | `voucher.report.employee\|reports.all_access`; employee ID numeric |
| GET | `/user/index`, `/user/create`, `/user/show/{id}`, `/user/edit/{id}`, `/user/statusupdate/{status_id}`, `/user/destroy/{id}` | `user.index/create/show/edit/statusupdate/destroy` | `UserController@index/create/show/edit/userstatusupdate/destroy` | `employee.index`, `employee.create`, `view-employee-details\|view-employee-details-permission`, `employee.edit\|employee.role.assign` (edit/status), `employee.delete` |
| POST / PUT | `/user/store`, `/user/update/{id}` | `user.store/update` | `UserController@store/update` | `employee.create`; `employee.edit\|employee.role.assign` |
| GET | `/roles`, `/roles.create`, `/roles/{id}/edit`, `/roles.destroy/{id}` | `roles.index/create/edit/destroy` | `RoleController@index/create/edit/destroy` | `role.index`, `roles.create`, `role.edit`, `role.delete` |
| POST / PUT | `/roles.store`, `/roles/{id}/update` | `roles.store/update` | `RoleController@store/update` | `roles.create`; `role.edit` |
| GET | `/productCategory/index`, `/productCategory/create`, `/productCategory/{id}`, `/productCategory/destroy/{id}` | `category.index/create/edit/destroy` | `ProductCategoryController@index/create/edit/destroy` | `category.index`, `product.category.create/edit/delete` |
| POST / PUT | `/productCategory/store`, `/productCategory/{id}/update` | `category.store/update` | `ProductCategoryController@store/update` | `product.category.create/update` |
| GET | `/product/brand/index`, `/product/brand/create`, `/product/brand/{id}/edit`, `/product/brand/{id}/destroy` | `brand.index/create/edit/destroy` | `BrandController@index/create/edit/destroy` | `brand.index/create/edit/delete` |
| POST / PUT | `/product/brand/store`, `/product/brand/{id}/update` | `brand.store/update` | `BrandController@store/update` | `brand.create/update` |
| GET | `/supplier/index`, `/supplier/create`, `/supplier/{id}/show`, `/supplier/{id}/edit`, `/supplier/{id}/destroy` | `supplier.index/create/show/edit/destroy` | `SupplierController@index/create/show/edit/destroy` | `supplier.index`, `supplier.create`, `supplier.index`, `supplier.edit`, `supplier.delete` |
| POST / PUT | `/supplier/store`, `/supplier/{id}/update` | `supplier.store/update` | `SupplierController@store/update` | `supplier.create/edit` |
| GET | `/supplier/{id}/ledger`, `/supplier/ledger/{id}/invoice`, `/supplier/ledger/{id}/edit`, `/supplier/ledger/{id}/destroy` | `supplier.ledger/invoice/edit/destroy` | `SupplierledgerController@index/invoice/edit/destroy` | `supplier.ledger` (ledger/invoice), `Supplier.ledger.edit/delete` |
| POST / PUT | `/supplier/ledger/store`, `/supplier/ledger/{id}/update`, `/supplier/ledger/{id}/payments`, `/supplier/ledger/payments/{paymentId}` | `supplier.ledger.store/update`, `.payments.store/update` | `SupplierledgerController@store/update/storePayment/updatePayment` | `Supplier.ledger.create/edit/payment/payment.edit` |
| GET | `/product/index`, `/product/create`, `/product/edit/{id}`, `/product/destroy/{id}` | `product.index/create/edit/destroy` | `ProductController@index/create/edit/destroy` | `product.index/create/edit/delete` |
| POST / PUT | `/product/store`, `/product/update/{id}` | `product.store/update` | `ProductController@store/update` | `product.create/update` |
| GET | `/reports/product-inventory`, `/reports/product-inventory/export` | `product.inventory.report/export` | `ProductInventoryReportController@index/export` | `product.report.inventory\|reports.all_access` |
| GET | `/sales`, `/sales/customer-lookup`, `/sales/invoice/{id}`, `/sales/delivery-challan/{id}` | `sales.index/customer.lookup/invoice/delivery-challan` | `SaleController@index/customerByPhone/invoice/deliveryChallan` | `sales.create\|sales.view`, `sales.create`, `sales.report`, `sales.view\|sales.report` |
| POST | `/sales` | `sales.store` | `SaleController@store` | `sales.create` |
| GET | `/sales/report`, `/sales/report/export` | `sales.report/export` | `SalesReportController@index/export` | `reports.all_access\|sales.report`; export `sales.view` |
| GET | `/quotations`, `/quotations/create`, `/quotations/customer-lookup`, `/quotations/{id}` | `quotation.index/create/customer.lookup/show` | `QuotationController@index/create/customerLookup/show` | `sales.create\|sales.view`, then `sales.create` (create/lookup), `sales.create\|sales.view` (show) |
| POST / DELETE | `/quotations`, `/quotations/{id}` | `quotation.store/destroy` | `QuotationController@store/destroy` | `sales.create` |
| GET | `/customer/index`, `/customer/create`, `/customer/{id}/ledger`, `/customer/ledger/{id}/invoice` | `customer.index/create/ledger/ledger.invoice` | `CustomerController@index/create/ledger/ledgerInvoice` | `customer.index/create/ledger/ledger` |
| POST | `/customer/store`, `/customer/ledger/store`, `/customer/ledger/{id}/payments` | `customer.store/ledger.store/ledger.payments.store` | `CustomerController@store/ledgerStore/ledgerPaymentStore` | `customer.create`; `Customer.ledger.payment` (দুই ledger route) |
| PUT | `/customer/ledger/payments/{paymentId}` | `customer.ledger.payments.update` | `CustomerController@ledgerPaymentUpdate` | `Customer.ledger.payment.edit` |
| GET / PUT / DELETE | `/customer/ledger/{id}/edit`, `/customer/ledger/{id}/update`, `/customer/ledger/{id}` | `customer.ledger.edit/update/destroy` | `CustomerController@ledgerEdit/ledgerUpdate/ledgerDestroy` | `Customer.ledger.edit` (edit/update), `Customer.ledger.delete` |
| GET / PUT / GET | `/customer/{id}/edit`, `/customer/{id}/update`, `/customer/{id}/destroy` | `customer.edit/update/destroy` | `CustomerController@edit/update/destroy` | `customer.edit` (edit/update), `customer.delete` |

`GET /logout` ও `routes/auth.php`-এর `POST /logout` একই `logout` route name ব্যবহার করে। Route list বা URL generation debug করার সময় এই duplicate name খেয়াল করুন। `/profile` route block comment করা, তাই active route table-এ নেই।

### [`routes/auth.php`](./routes/auth.php)

Guest group-এ registration/login/forgot-password/reset; `auth` group-এ email verification, password confirmation/update এবং logout route থাকে। সংশ্লিষ্ট controller-গুলো `app/Http/Controllers/Auth/`-এ।

| HTTP | URI | Route name | Controller@action | Middleware |
|---|---|---|---|---|
| GET / POST | `/register` | `register` / — | `RegisteredUserController@create/store` | `guest` |
| GET / POST | `/login` | `login` / — | `AuthenticatedSessionController@create/store` | `guest` |
| GET / POST | `/forgot-password` | `password.request/email` | `PasswordResetLinkController@create/store` | `guest` |
| GET | `/reset-password/{token}` | `password.reset` | `NewPasswordController@create` | `guest` |
| POST | `/reset-password` | `password.store` | `NewPasswordController@store` | `guest` |
| GET | `/verify-email` | `verification.notice` | `EmailVerificationPromptController` (invokable) | `auth` |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | `VerifyEmailController` (invokable) | `auth`, `signed`, `throttle:6,1` |
| POST | `/email/verification-notification` | `verification.send` | `EmailVerificationNotificationController@store` | `auth`, `throttle:6,1` |
| GET / POST | `/confirm-password` | `password.confirm` / — | `ConfirmablePasswordController@show/store` | `auth` |
| PUT | `/password` | `password.update` | `PasswordController@update` | `auth` |
| POST | `/logout` | `logout` | `AuthenticatedSessionController@destroy` | `auth` |

### [`routes/console.php`](./routes/console.php)

`inspire` Artisan command নিবন্ধন করে; এটি web request বা business module route নয়।

## ২. Controller (কোড ও ব্যাখ্যা)----

Controller-এর মূল কাজ request validate করা, model/query ব্যবহার করে domain operation চালানো এবং view/JSON/CSV response দেওয়া। পূর্ণ method body source link-এ আছে।

উদাহরণ—sale request-এ একই product-এর cart quantity একত্র করা এবং stock যাচাইয়ের আগে সংশ্লিষ্ট product row lock করা হয়:

```php
$quantities = collect($validated['items'])
    ->groupBy('product_id')
    ->map(fn ($rows) => $rows->sum(fn ($row) => (int) $row['quantity']));

$products = Product::query()
    ->whereIn('id', $quantities->keys())
    ->orderBy('id')
    ->lockForUpdate()
    ->get()
    ->keyBy('id');
```

এই অংশটি [`SaleController.php`](./app/Http/Controllers/SaleController.php)-এর transaction flow থেকে নেওয়া। Transaction ও row lock একই পণ্যের concurrent sale-এ stock যাচাইকে নির্ভরযোগ্য করে; সব business ও authentication controller-এর কাজ নিচের source inventory-তে ব্যাখ্যা করা হয়েছে।

### Business ও report controller

| Controller source | কী করে / কোথায় ব্যবহৃত |
|---|---|
| [`BrandController.php`](./app/Http/Controllers/BrandController.php) | Brand CRUD; product brand dropdown ও catalog management-এ ব্যবহৃত। `show()` method থাকলেও route নেই। |
| [`CombinedVoucherReportController.php`](./app/Http/Controllers/CombinedVoucherReportController.php) | Employee, debit ও credit voucher-কে এক report-এ আনে; edit/delete ও CSV export; `combinedVoucherReport` view। |
| [`CustomerController.php`](./app/Http/Controllers/CustomerController.php) | Customer CRUD, phone lookup-সংশ্লিষ্ট data, ledger entry/payment/edit/delete ও printable ledger invoice/receipt। |
| [`DashboardConrtoller.php`](./app/Http/Controllers/DashboardConrtoller.php) | Dashboard KPI, date-wise aggregate, profit chart, top product ও employee তৈরি। |
| [`EmployeePaymentVoucherController.php`](./app/Http/Controllers/EmployeePaymentVoucherController.php) | Employee payment voucher create, employee ledger, daily report ও export; amount/salary validation এবং voucher history। |
| [`OrganizationController.php`](./app/Http/Controllers/OrganizationController.php) | একক organization profile edit/update; logo upload ও পুরোনো logo file-এর lifecycle পরিচালনা। |
| [`ProductCategoryController.php`](./app/Http/Controllers/ProductCategoryController.php) | Category CRUD; `show()` method থাকলেও category ID-র GET route edit action-এ যায়। |
| [`ProductController.php`](./app/Http/Controllers/ProductController.php) | Product CRUD; category/brand/supplier choices-সহ create/edit views render করে। |
| [`ProductInventoryReportController.php`](./app/Http/Controllers/ProductInventoryReportController.php) | Product stock, purchase, sold quantity, expiry ও supplier report; একই row builder export-এ ব্যবহৃত। |
| [`PurchasesContoller.php`](./app/Http/Controllers/PurchasesContoller.php) | বর্তমানে কোনো action নেই এবং purchase route-ও নেই; নামটি typo-সহ source-এ আছে। |
| [`QuotationController.php`](./app/Http/Controllers/QuotationController.php) | Quotation list/create/customer lookup/store/show/delete; item snapshot ও discount/VAT/AIT/total হিসাব। |
| [`RoleController.php`](./app/Http/Controllers/RoleController.php) | Role CRUD; user-এর effective permission-এর বাইরে permission assign ঠেকিয়ে `syncPermissions()` করে। |
| [`SaleController.php`](./app/Http/Controllers/SaleController.php) | Sale form, customer lookup, invoice store, delivery challan/invoice render এবং amount-in-words helper। |
| [`SalesReportController.php`](./app/Http/Controllers/SalesReportController.php) | Date/product/employee/customer filter-সহ sales aggregate, product summary, invoice detail ও CSV export। |
| [`SupplierController.php`](./app/Http/Controllers/SupplierController.php) | Supplier CRUD ও supplier detail view। |
| [`SupplierledgerController.php`](./app/Http/Controllers/SupplierledgerController.php) | Supplier ledger, printable invoice, entry/payment create/edit/delete; cumulative payment/due balance বজায় রাখে। |
| [`UserActivityLogController.php`](./app/Http/Controllers/UserActivityLogController.php) | Date/user/event filter-সহ paginated user activity log। |
| [`UserController.php`](./app/Http/Controllers/UserController.php) | User/employee CRUD, status এবং role assignment; assign করা যায় এমন role সীমাবদ্ধ করে। |
| [`VoucherAccountController.php`](./app/Http/Controllers/VoucherAccountController.php) | Voucher account CRUD; account list/create/edit views। |
| [`VoucherEntryController.php`](./app/Http/Controllers/VoucherEntryController.php) | Debit/credit voucher entry, account নির্বাচন, report ও CSV export। |
| [`Controller.php`](./app/Http/Controllers/Controller.php) | Laravel-এর shared base controller; নিজস্ব business action নেই। |

### Authentication controller

| Controller source | কাজ ও সংশ্লিষ্ট view/route |
|---|---|
| [`Auth/AuthenticatedSessionController.php`](./app/Http/Controllers/Auth/AuthenticatedSessionController.php) | Login form, authentication ও logout/session invalidate। |
| [`Auth/ConfirmablePasswordController.php`](./app/Http/Controllers/Auth/ConfirmablePasswordController.php) | Password confirmation form ও যাচাই। |
| [`Auth/EmailVerificationNotificationController.php`](./app/Http/Controllers/Auth/EmailVerificationNotificationController.php) | Verification email পুনরায় পাঠায়। |
| [`Auth/EmailVerificationPromptController.php`](./app/Http/Controllers/Auth/EmailVerificationPromptController.php) | Email verified না হলে verification prompt দেখায়। |
| [`Auth/NewPasswordController.php`](./app/Http/Controllers/Auth/NewPasswordController.php) | Password-reset token যাচাই করে নতুন password সংরক্ষণ। |
| [`Auth/PasswordController.php`](./app/Http/Controllers/Auth/PasswordController.php) | Login করা user-এর password update। |
| [`Auth/PasswordResetLinkController.php`](./app/Http/Controllers/Auth/PasswordResetLinkController.php) | Forgot-password form ও reset link পাঠানো। |
| [`Auth/RegisteredUserController.php`](./app/Http/Controllers/Auth/RegisteredUserController.php) | Registration form ও user creation। |
| [`Auth/VerifyEmailController.php`](./app/Http/Controllers/Auth/VerifyEmailController.php) | Signed verification request গ্রহণ করে email verify/redirect। |
| [`ProfileController.php`](./app/Http/Controllers/ProfileController.php) | Breeze profile edit/update/delete logic; সংশ্লিষ্ট web routes বর্তমানে comment করা। |

## ৩. Migration (কোড ও ব্যাখ্যা)----

Migration হলো database schema-র version history। `up()` schema যোগ/পরিবর্তন করে, `down()` rollback নির্ধারণ করে। Foreign key-তে cascade child record মুছে, `nullOnDelete()` reference সরিয়ে record রাখে। নিচের প্রতিটি migration-এর উদ্দেশ্য ও data relation দেওয়া হলো।

উদাহরণ—বিদ্যমান invoice table-এ প্রয়োজন হলে column যোগ করা:

```php
Schema::table('sale_invoices', function (Blueprint $table) {
    if (! Schema::hasColumn('sale_invoices', 'invoice_no')) {
        $table->string('invoice_no')->unique();
    }
    if (! Schema::hasColumn('sale_invoices', 'customer_id')) {
        $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    }
});
```

এটি [`2026_10_11_000003_upgrade_sales_tables.php`](./database/migrations/2026_10_11_000003_upgrade_sales_tables.php)-এর প্রকৃত pattern। `Schema::hasColumn()` আগে পরীক্ষা করে, যাতে upgrade migration অনুপস্থিত column যোগ করে; customer মুছে গেলে invoice অক্ষত রেখে foreign key null হয়। নিচের তালিকায় ৩৯টি migration-এর schema ও উদ্দেশ্য রয়েছে।

| Migration source | Schema ও পরিবর্তনের কারণ |
|---|---|
| [`0001_01_01_000000_create_users_table.php`](./database/migrations/0001_01_01_000000_create_users_table.php) | `users`, password-reset token ও session table; authentication-এর ভিত্তি। |
| [`0001_01_01_000001_create_cache_table.php`](./database/migrations/0001_01_01_000001_create_cache_table.php) | Cache value এবং lock সংরক্ষণের table। |
| [`0001_01_01_000002_create_jobs_table.php`](./database/migrations/0001_01_01_000002_create_jobs_table.php) | Queue job, batch ও failed-job persistence। |
| [`2026_06_17_170325_create_permission_tables.php`](./database/migrations/2026_06_17_170325_create_permission_tables.php) | Spatie roles/permissions ও polymorphic assignment pivot। |
| [`2026_06_20_153436_create_product_Categories_table.php`](./database/migrations/2026_06_20_153436_create_product_Categories_table.php) | Product category name ও timestamps। |
| [`2026_06_29_111603_create_brands_table.php`](./database/migrations/2026_06_29_111603_create_brands_table.php) | Brand name ও timestamps। |
| [`2026_06_30_170258_create_products_table.php`](./database/migrations/2026_06_30_170258_create_products_table.php) | Product catalog, category/brand, price, stock/unit, dates, description/image; category/brand FK। |
| [`2026_07_06_073506_create_customers_table.php`](./database/migrations/2026_07_06_073506_create_customers_table.php) | Customer profile, type, employee, shop/code, opening due, status/notes। |
| [`2026_07_06_172220_create_purchase_items_table.php`](./database/migrations/2026_07_06_172220_create_purchase_items_table.php) | প্রাথমিক purchase item table; বর্তমানে শুধু ID ও timestamps। |
| [`2026_07_06_172251_create_sale_invoices_table.php`](./database/migrations/2026_07_06_172251_create_sale_invoices_table.php) | প্রাথমিক sales invoice table; বিস্তারিত schema পরবর্তী upgrade migration-এ। |
| [`2026_07_06_172445_create_sale_items_table.php`](./database/migrations/2026_07_06_172445_create_sale_items_table.php) | প্রাথমিক sale item table; বিস্তারিত schema পরবর্তী migration-এ। |
| [`2026_10_06_111559_create_suppliers_table.php`](./database/migrations/2026_10_06_111559_create_suppliers_table.php) | Supplier name, contact, address/logo ও timestamps। |
| [`2026_10_06_111603_create_supplierledgers_table.php`](./database/migrations/2026_10_06_111603_create_supplierledgers_table.php) | Supplier purchase ledger, item/quantity/price/total/payment/due। |
| [`2026_10_11_000001_create_customer_ledgers_table.php`](./database/migrations/2026_10_11_000001_create_customer_ledgers_table.php) | Customer sale/payment ledger, employee/receiver, product, amount ও notes; customer delete cascade, user references nullable। |
| [`2026_10_11_000002_upgrade_customers_table.php`](./database/migrations/2026_10_11_000002_upgrade_customers_table.php) | Legacy customer table-এ অনুপস্থিত profile/employee/opening due field conditional ভাবে যোগ। |
| [`2026_10_11_000003_upgrade_sales_tables.php`](./database/migrations/2026_10_11_000003_upgrade_sales_tables.php) | Invoice ও item-এর পূর্ণ fields; customer/user/product deletion-এর জন্য null/cascade rules; customer ledger-এ invoice/payment link। |
| [`2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php`](./database/migrations/2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php) | Product-এ supplier relation ও purchase-price field যোগ। |
| [`2026_10_12_163000_create_supplier_ledger_payments_table.php`](./database/migrations/2026_10_12_163000_create_supplier_ledger_payments_table.php) | Supplier ledger-এর dated installment history; parent delete হলে cascade। |
| [`2026_10_12_174621_add_salary_and_nid_number_to_users_table.php`](./database/migrations/2026_10_12_174621_add_salary_and_nid_number_to_users_table.php) | User/employee-তে nullable salary ও NID field। |
| [`2026_10_12_181140_create_employee_payment_vouchers_table.php`](./database/migrations/2026_10_12_181140_create_employee_payment_vouchers_table.php) | Employee expense/payment voucher, amount/date/method/reference; employee reference nullable। |
| [`2026_10_13_000000_add_created_by_to_employee_payment_vouchers_table.php`](./database/migrations/2026_10_13_000000_add_created_by_to_employee_payment_vouchers_table.php) | Voucher কে তৈরি করেছে তার nullable user relation। |
| [`2026_10_13_000001_create_voucher_entries_table.php`](./database/migrations/2026_10_13_000001_create_voucher_entries_table.php) | Debit/credit voucher head/date/amount/creator/reference/description; report-এর date indexes। |
| [`2026_10_13_000002_add_phone_to_users_table.php`](./database/migrations/2026_10_13_000002_add_phone_to_users_table.php) | User profile-এ nullable phone। |
| [`2026_10_13_182123_create_organizations_table.php`](./database/migrations/2026_10_13_182123_create_organizations_table.php) | Organization name/address/phone/logo; invoice ও print branding-এর data source। |
| [`2026_10_14_005500_add_email_to_organizations_table.php`](./database/migrations/2026_10_14_005500_add_email_to_organizations_table.php) | Organization-এ nullable email। |
| [`2026_10_14_120000_add_discount_type_and_sale_item_purchase_price.php`](./database/migrations/2026_10_14_120000_add_discount_type_and_sale_item_purchase_price.php) | Sale discount type/value, sale-time purchase price snapshot; historical profit-এর cost source। |
| [`2026_10_17_000000_rename_legacy_permissions.php`](./database/migrations/2026_10_17_000000_rename_legacy_permissions.php) | Legacy permission names ও role/user assignment transaction-এ migrate; schema table নয়। |
| [`2026_10_17_000001_create_user_activity_logs_table.php`](./database/migrations/2026_10_17_000001_create_user_activity_logs_table.php) | User/event snapshot, subject, properties, IP/user-agent ও indexes; user FK নেই যাতে user delete হলেও history থাকে। |
| [`2026_10_17_000002_create_customer_ledger_payments_table.php`](./database/migrations/2026_10_17_000002_create_customer_ledger_payments_table.php) | Customer ledger payment history; ledger delete cascade, receiver delete হলে null। |
| [`2026_10_25_000001_make_product_fields_nullable_in_products_table.php`](./database/migrations/2026_10_25_000001_make_product_fields_nullable_in_products_table.php) | Product category/brand/price/unit nullable করে; অসম্পূর্ণ catalog record সমর্থন। |
| [`2026_10_25_000002_create_quotations_table.php`](./database/migrations/2026_10_25_000002_create_quotations_table.php) | Quotation header ও items; item delete cascade, user/product relation nullable। |
| [`2026_10_26_000001_add_created_by_to_supplier_ledger_payments_table.php`](./database/migrations/2026_10_26_000001_add_created_by_to_supplier_ledger_payments_table.php) | Supplier payment history-তে nullable creator reference। |
| [`2026_10_26_000002_expand_voucher_head_column.php`](./database/migrations/2026_10_26_000002_expand_voucher_head_column.php) | Voucher head string length বাড়িয়ে দীর্ঘ account/expense title সমর্থন। |
| [`2026_10_26_000003_add_voucherable_to_voucher_entries_table.php`](./database/migrations/2026_10_26_000003_add_voucherable_to_voucher_entries_table.php) | Voucher-কে source ledger/entity-এর সঙ্গে polymorphic relation-এ যুক্ত করে। |
| [`2026_10_30_000001_add_ait_amount_to_sale_invoices_table.php`](./database/migrations/2026_10_30_000001_add_ait_amount_to_sale_invoices_table.php) | Sales invoice-এ AIT amount field, default zero। |
| [`2026_10_31_000001_add_ait_fields_to_quotations_table.php`](./database/migrations/2026_10_31_000001_add_ait_fields_to_quotations_table.php) | Quotation-এ AIT percentage ও amount field। |
| [`2026_10_31_000001_create_voucher_accounts_table.php`](./database/migrations/2026_10_31_000001_create_voucher_accounts_table.php) | Unique voucher account এবং voucher entry-র nullable account FK; একই timestamp-prefix-এ আরেক migration আছে। |
| [`2026_10_31_000002_add_customer_address_to_sale_invoices_table.php`](./database/migrations/2026_10_31_000002_add_customer_address_to_sale_invoices_table.php) | Invoice-এ customer address snapshot রাখে। |
| [`2026_10_31_000003_add_terms_conditions_to_sale_invoices_table.php`](./database/migrations/2026_10_31_000003_add_terms_conditions_to_sale_invoices_table.php) | Invoice-এ terms and conditions সংরক্ষণ করে। |

## ৪. Model (কোড ও ব্যাখ্যা)----

`$fillable` mass-assignment-এ কোন field লেখা যাবে তা সীমিত করে; `$casts` database value-কে PHP date/boolean/decimal/array হিসেবে ব্যবহার করে। Relationship controller-এর eager-load/query এবং Blade-এর nested data access-এ ব্যবহৃত হয়।

উদাহরণ—invoice-এর amount field-কে decimal হিসেবে ব্যবহার এবং তার line item-গুলোর relationship:

```php
protected $casts = [
    'total_amount' => 'decimal:2',
    'amount_paid' => 'decimal:2',
    'due_amount' => 'decimal:2',
];

public function items()
{
    return $this->hasMany(SaleItem::class);
}
```

এই code [`SaleInvoice.php`](./app/Models/SaleInvoice.php)-এর বাস্তব model pattern। Cast অর্থের precision ধরে রাখে এবং `items()` invoice-এর line item query/eager-load করতে দেয়; ২১টি model-এর field, relation ও helper নিচের তালিকায় আছে।

| Model source | প্রধান field, relation ও helper |
|---|---|
| [`Brand.php`](./app/Models/Brand.php) | Brand name; Product-এর `hasMany` relation। |
| [`Customer.php`](./app/Models/Customer.php) | Contact/type/employee/shop/code/opening due/status/notes; assigned employee ও ledgers relation। |
| [`CustomerLedger.php`](./app/Models/CustomerLedger.php) | Sale/payment snapshot, date/amount casts; customer, employee, receiver, dated payment history relation। |
| [`CustomerLedgerPayment.php`](./app/Models/CustomerLedgerPayment.php) | Installment date/amount/receiver/notes; parent ledger ও receiver relation। |
| [`EmployeePaymentVoucher.php`](./app/Models/EmployeePaymentVoucher.php) | Voucher/employee/creator/expense/date/amount/method; employee ও creator relation, allowed type/method constants। |
| [`Organization.php`](./app/Models/Organization.php) | Organization profile ও logo-এর fillable field। |
| [`Product.php`](./app/Models/Product.php) | Catalog, category/brand/supplier/price/stock/date/image; `isExpired`, `isExpiringSoon`, shelf-life ও stock status helper। |
| [`ProductCategory.php`](./app/Models/ProductCategory.php) | Category name; Product-এর `hasMany` relation। |
| [`PurchaseInvoice.php`](./app/Models/PurchaseInvoice.php) | বর্তমানে empty Eloquent model; fillable/cast/relation নেই। |
| [`PurchaseItem.php`](./app/Models/PurchaseItem.php) | বর্তমানে empty Eloquent model; পূর্ণ purchase-item workflow অন্যত্র সম্পূর্ণ নয়। |
| [`Quotation.php`](./app/Models/Quotation.php) | Customer/date/subtotal/discount/VAT/AIT/total/terms/notes/user; item ও author relation, numeric/date casts। |
| [`QuotationItem.php`](./app/Models/QuotationItem.php) | Product snapshot/name, quantity, unit price ও line total; quotation/product relation। |
| [`SaleInvoice.php`](./app/Models/SaleInvoice.php) | Invoice/customer/date/totals/tax/due/payment/terms; customer, employee, items relation ও decimal/date/boolean casts। |
| [`SaleItem.php`](./app/Models/SaleItem.php) | Invoice/product/name/quantity/price snapshot; effective purchase price ও profit accessor। |
| [`Supplier.php`](./app/Models/Supplier.php) | Supplier contact/logo; supplier ledger relation। |
| [`Supplierledger.php`](./app/Models/Supplierledger.php) | Purchase entry/amount/payment/due; supplier/product/payment-history relation। |
| [`SupplierLedgerPayment.php`](./app/Models/SupplierLedgerPayment.php) | Supplier installment/date/amount/creator; ledger ও creator relation। |
| [`User.php`](./app/Models/User.php) | User/employee profile; hashed password, status/salary casts, hidden credential fields, `HasRoles`/`Notifiable`/factory traits। |
| [`UserActivityLog.php`](./app/Models/UserActivityLog.php) | Event/user/subject/property/IP snapshot; JSON array cast, user relation এবং date-range scope। |
| [`VoucherAccount.php`](./app/Models/VoucherAccount.php) | Account name; voucher entries-এর `hasMany` relation। |
| [`VoucherEntry.php`](./app/Models/VoucherEntry.php) | Debit/credit type, account, amount, creator ও morph fields; `createWithCode()`, `deleteForSource()` এবং source/account/creator relation। |

## ৫. Blade (কোড ও ব্যাখ্যা)----

Blade-এর controller-returned view name-এ dot notation folder path-কে নির্দেশ করে; যেমন `backend.Sales.salesInvoice` মানে `resources/views/backend/Sales/salesInvoice.blade.php`। নিচের তালিকায় প্রতিটি template এবং তার ব্যবহার আছে। `VoucherList`, `costomerIndex` ও `customerLeger` বর্তমানে খালি file; নাম থাকা সত্ত্বেও এগুলো implemented UI নয়।

উদাহরণ—sales invoice-এ logo থাকলে সেটি দেখানো এবং Blade-এর escaped output দিয়ে invoice number render করা:

```blade
@if ($organization?->logo)
    <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
        alt="{{ $organization->name ?: 'Organization logo' }}">
@endif

<div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
```

এটি [`salesInvoice.blade.php`](./resources/views/backend/Sales/salesInvoice.blade.php)-এর বাস্তব view pattern। `@if` ঐচ্ছিক logo সামলায় এবং `{{ }}` HTML-escaped output দেয়। নিচের inventory-তে সব ৮২টি Blade template-এর উদ্দেশ্য দেওয়া হয়েছে; module-ভিত্তিক নির্বাচিত পূর্ণ code excerpt ও বিস্তারিত workflow [PROJECT-MODULES-DOCUMENTATION-BANGLA.md](./PROJECT-MODULES-DOCUMENTATION-BANGLA.md)-তে আছে।

### Backend: Accounts Entries — 10টি

| Blade source | ব্যবহার |
|---|---|
| [`VoucherEntries.blade.php`](<./resources/views/backend/Accounts Entries/VoucherEntries.blade.php>) | কর্মী payment voucher-এর দৈনিক report/filter/print। |
| [`VoucherList.blade.php`](<./resources/views/backend/Accounts Entries/VoucherList.blade.php>) | খালি view; বর্তমানে কোনো UI নেই। |
| [`combinedVoucherReport.blade.php`](<./resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php>) | Employee/debit/credit combined report, filter, export, print। |
| [`employeePaymentLedger.blade.php`](<./resources/views/backend/Accounts Entries/employeePaymentLedger.blade.php>) | কর্মী payment ledger ও বিস্তারিত। |
| [`empolyeePayment.blade.php`](<./resources/views/backend/Accounts Entries/empolyeePayment.blade.php>) | কর্মীর payment voucher form। |
| [`voucherAccountCreate.blade.php`](<./resources/views/backend/Accounts Entries/voucherAccountCreate.blade.php>) | Voucher account create form। |
| [`voucherAccountEdit.blade.php`](<./resources/views/backend/Accounts Entries/voucherAccountEdit.blade.php>) | Voucher account edit form। |
| [`voucherAccountIndex.blade.php`](<./resources/views/backend/Accounts Entries/voucherAccountIndex.blade.php>) | Voucher account list ও permission-gated CRUD actions। |
| [`voucherEntryCreate.blade.php`](<./resources/views/backend/Accounts Entries/voucherEntryCreate.blade.php>) | Debit/credit voucher input ও সাম্প্রতিক entries। |
| [`voucherEntryReport.blade.php`](<./resources/views/backend/Accounts Entries/voucherEntryReport.blade.php>) | Debit/credit report, filters, export/print। |

### Backend: Product, Brand ও Category — 11টি

| Blade source | ব্যবহার |
|---|---|
| [`brandIndex.blade.php`](<./resources/views/backend/Product/Brand/brandIndex.blade.php>) | Brand list ও actions। |
| [`createBrand.blade.php`](<./resources/views/backend/Product/Brand/createBrand.blade.php>) | Brand create form। |
| [`editBrand.blade.php`](<./resources/views/backend/Product/Brand/editBrand.blade.php>) | Brand edit form। |
| [`CategoryCreate.blade.php`](<./resources/views/backend/Product/Category/CategoryCreate.blade.php>) | Category create form-এর একটি template। |
| [`CategoryDetails.blade.php`](<./resources/views/backend/Product/Category/CategoryDetails.blade.php>) | Category ও সংশ্লিষ্ট product detail। |
| [`Categoryedit.blade.php`](<./resources/views/backend/Product/Category/Categoryedit.blade.php>) | Category edit form। |
| [`ProductCategoryCreate.blade.php`](<./resources/views/backend/Product/Category/ProductCategoryCreate.blade.php>) | Category create form-এর আরেকটি template। |
| [`ProductCategoryindex.blade.php`](<./resources/views/backend/Product/Category/ProductCategoryindex.blade.php>) | Category list/action। |
| [`createProduct.blade.php`](<./resources/views/backend/Product/product/createProduct.blade.php>) | Product create form, catalog fields/image preview। |
| [`editProduct.blade.php`](<./resources/views/backend/Product/product/editProduct.blade.php>) | Product edit form ও image/date helper behavior। |
| [`indexProduct.blade.php`](<./resources/views/backend/Product/product/indexProduct.blade.php>) | Product catalog list/actions। |

### Backend: Sales — 4টি

| Blade source | ব্যবহার |
|---|---|
| [`deliveryChallan.blade.php`](./resources/views/backend/Sales/deliveryChallan.blade.php) | পণ্য/quantity/unit-সহ delivery challan; দাম বাদ, print layout। |
| [`salesIndex.blade.php`](./resources/views/backend/Sales/salesIndex.blade.php) | Sale input, customer phone lookup, cart, tax/discount preview ও submit। |
| [`salesInvoice.blade.php`](./resources/views/backend/Sales/salesInvoice.blade.php) | Sales invoice print; subtotal/discount/VAT/AIT/payment/due/terms। |
| [`salesReport.blade.php`](./resources/views/backend/Sales/salesReport.blade.php) | Sales report filter, summary, product/invoice list, export/print। |

### Backend: Supplier — 5টি

| Blade source | ব্যবহার |
|---|---|
| [`addSupplier.blade.php`](./resources/views/backend/Supplier/addSupplier.blade.php) | Supplier create form। |
| [`supplierInvoice.blade.php`](./resources/views/backend/Supplier/supplierInvoice.blade.php) | Supplier ledger invoice/print document। |
| [`supplierList.blade.php`](./resources/views/backend/Supplier/supplierList.blade.php) | Supplier directory ও summary/actions। |
| [`supplierledger.blade.php`](./resources/views/backend/Supplier/supplierledger.blade.php) | Supplier purchase ledger, payment history ও due। |
| [`updateSupplier.blade.php`](./resources/views/backend/Supplier/updateSupplier.blade.php) | Supplier edit form। |

### Backend: Customer — 7টি

| Blade source | ব্যবহার |
|---|---|
| [`addCustomer.blade.php`](./resources/views/backend/customer/addCustomer.blade.php) | Customer create form। |
| [`costomerIndex.blade.php`](./resources/views/backend/customer/costomerIndex.blade.php) | খালি template; customer list নয়। |
| [`customerIndex.blade.php`](./resources/views/backend/customer/customerIndex.blade.php) | Customer directory, aggregate ও actions। |
| [`customerLedger.blade.php`](./resources/views/backend/customer/customerLedger.blade.php) | Customer ledger entry, payment, history ও balance। |
| [`customerLedgerInvoice.blade.php`](./resources/views/backend/customer/customerLedgerInvoice.blade.php) | Customer ledger sale invoice/receipt print। |
| [`customerLeger.blade.php`](./resources/views/backend/customer/customerLeger.blade.php) | খালি template; customer ledger UI নয়। |
| [`editCutomer.blade.php`](./resources/views/backend/customer/editCutomer.blade.php) | Customer profile edit form (source filename-এর spelling-সহ)। |

### Backend: Quotation, reports, roles ও shared partial — 9টি

| Blade source | ব্যবহার |
|---|---|
| [`organizationHeader.blade.php`](<./resources/views/backend/partials/organizationHeader.blade.php>) | Report/print-এ reusable organization header। |
| [`create.blade.php`](./resources/views/backend/quotation/create.blade.php) | Quotation create form; product/tax/discount preview। |
| [`index.blade.php`](./resources/views/backend/quotation/index.blade.php) | Quotation list/actions। |
| [`show.blade.php`](./resources/views/backend/quotation/show.blade.php) | Quotation details/print ও optional watermark। |
| [`productinventory.blade.php`](./resources/views/backend/reports/productinventory.blade.php) | Inventory totals/table, export/print। |
| [`role-create.blade.php`](./resources/views/backend/roles/role-create.blade.php) | Role/permission create form। |
| [`role-edit.blade.php`](./resources/views/backend/roles/role-edit.blade.php) | Role permission edit form। |
| [`role-index.blade.php`](./resources/views/backend/roles/role-index.blade.php) | Role list/actions। |
| [`index.blade.php`](./resources/views/backend/user-activity/index.blade.php) | Activity log filter/list/print। |

### Authentication ও user frontend — 12টি

| Blade source | ব্যবহার |
|---|---|
| [`login.blade.php`](./resources/views/frontend/loginpage/login.blade.php) | Application-এর custom login page। |
| [`addOrganization.blade.php`](./resources/views/frontend/organization/addOrganization.blade.php) | Organization profile ও logo upload/edit। |
| [`addUser.blade.php`](./resources/views/frontend/users/addUser.blade.php) | User/employee create form। |
| [`editUser.blade.php`](./resources/views/frontend/users/editUser.blade.php) | User/employee edit form। |
| [`userDeteail.blade.php`](./resources/views/frontend/users/userDeteail.blade.php) | User/employee detail (source spelling অপরিবর্তিত)। |
| [`userIndex.blade.php`](./resources/views/frontend/users/userIndex.blade.php) | User/employee list ও permission-gated actions। |
| [`confirm-password.blade.php`](./resources/views/auth/confirm-password.blade.php) | Password confirmation form। |
| [`forgot-password.blade.php`](./resources/views/auth/forgot-password.blade.php) | Password reset link request form। |
| [`login.blade.php`](./resources/views/auth/login.blade.php) | Breeze authentication login view। |
| [`register.blade.php`](./resources/views/auth/register.blade.php) | Registration form। |
| [`reset-password.blade.php`](./resources/views/auth/reset-password.blade.php) | Password reset form। |
| [`verify-email.blade.php`](./resources/views/auth/verify-email.blade.php) | Email verification prompt/resend/logout। |

### Profile, layout, components ও starter views — 24টি

| Blade source | ব্যবহার |
|---|---|
| [`edit.blade.php`](./resources/views/profile/edit.blade.php) | User profile page; update info/password/delete partial অন্তর্ভুক্ত। |
| [`delete-user-form.blade.php`](./resources/views/profile/partials/delete-user-form.blade.php) | Account deletion form। |
| [`update-password-form.blade.php`](./resources/views/profile/partials/update-password-form.blade.php) | Password update form। |
| [`update-profile-information-form.blade.php`](./resources/views/profile/partials/update-profile-information-form.blade.php) | Name/email update ও verification notice। |
| [`app.blade.php`](./resources/views/layouts/app.blade.php) | Authenticated Breeze layout। |
| [`guest.blade.php`](./resources/views/layouts/guest.blade.php) | Guest/auth layout। |
| [`navigation.blade.php`](./resources/views/layouts/navigation.blade.php) | Desktop/mobile navigation, profile ও logout links। |
| [`application-logo.blade.php`](./resources/views/components/application-logo.blade.php) | Reusable logo component। |
| [`auth-session-status.blade.php`](./resources/views/components/auth-session-status.blade.php) | Session status message component। |
| [`danger-button.blade.php`](./resources/views/components/danger-button.blade.php) | Destructive action button। |
| [`dropdown-link.blade.php`](./resources/views/components/dropdown-link.blade.php) | Dropdown item link। |
| [`dropdown.blade.php`](./resources/views/components/dropdown.blade.php) | Reusable dropdown container। |
| [`input-error.blade.php`](./resources/views/components/input-error.blade.php) | Validation error component। |
| [`input-label.blade.php`](./resources/views/components/input-label.blade.php) | Form label component। |
| [`modal.blade.php`](./resources/views/components/modal.blade.php) | Reusable modal wrapper। |
| [`nav-link.blade.php`](./resources/views/components/nav-link.blade.php) | Desktop navigation link। |
| [`primary-button.blade.php`](./resources/views/components/primary-button.blade.php) | Primary button component। |
| [`responsive-nav-link.blade.php`](./resources/views/components/responsive-nav-link.blade.php) | Mobile navigation link। |
| [`secondary-button.blade.php`](./resources/views/components/secondary-button.blade.php) | Secondary button component। |
| [`text-input.blade.php`](./resources/views/components/text-input.blade.php) | Reusable text input component। |
| [`dashboard-body.blade.php`](./resources/views/dashboard/dashboard-body.blade.php) | KPI cards, chart ও top lists-এর dashboard body। |
| [`dashboard.blade.php`](./resources/views/dashboard/dashboard.blade.php) | Main dashboard layout, navigation/assets ও content wrapper। |
| [`dashboard.blade.php`](./resources/views/dashboard.blade.php) | Root-level starter dashboard view। |
| [`welcome.blade.php`](./resources/views/welcome.blade.php) | Laravel starter welcome page। |

উপরের তালিকায় **মোট 82টি Blade source** আছে; এর মধ্যে `VoucherList`, `costomerIndex` ও `customerLeger` খালি। Group count: backend 46, authentication 6, frontend 6, profile 4, layouts 3, components 13, dashboard/root 4।

## ৬. Seeder (কোড ও ব্যাখ্যা)----

Seeder development/test database-এ initial data তৈরি করে। `DatabaseSeeder`-এর বর্তমান call order গুরুত্বপূর্ণ, কারণ RoleSeeder permission seed হওয়ার পরে চলে।

```php
$this->call([
    PermissionSeederTable::class,
    RolePermissionSeeder::class,
    SalesDocumentWatermarkPermissionSeeder::class,
    VoucherAccountPermissionSeeder::class,
    UserSeederTable::class,
    ProductCategorySeeder::class,
    ProductSeeder::class,
]);
```

এই ক্রমে permission আগে তৈরি হয়, তারপর role-এ permission sync হয়; এরপর user ও product catalog seed হয়। `DatabaseSeeder`-এ factory test user-ও তৈরি হয়। `Product.php` নামে আলাদা seeder class-টি এই তালিকায় নেই।

| Seeder source | কী তৈরি করে / ব্যবহারের উদ্দেশ্য |
|---|---|
| [`DatabaseSeeder.php`](./database/seeders/DatabaseSeeder.php) | Factory test user-এর পরে permission, role preset, watermark/account permission, user, category ও product seeder চালায়। |
| [`PermissionSeederTable.php`](./database/seeders/PermissionSeederTable.php) | Module অনুযায়ী permission name `firstOrCreate()` করে; name-এর casing application check-এর সঙ্গে মিলতে হয়। |
| [`RolePermissionSeeder.php`](./database/seeders/RolePermissionSeeder.php) | `sales`, `manager`, `projector`, `admin` role create/reuse করে; `syncPermissions()` preset অনুযায়ী assignment স্থির করে। |
| [`SalesDocumentWatermarkPermissionSeeder.php`](./database/seeders/SalesDocumentWatermarkPermissionSeeder.php) | `sales.document.watermark` permission তৈরি করে এবং নির্ধারিত admin/manager role-এ যোগ করে। |
| [`VoucherAccountPermissionSeeder.php`](./database/seeders/VoucherAccountPermissionSeeder.php) | Voucher account index/create/edit/update/delete permission তৈরি ও role-এ যোগ করে। |
| [`UserSeederTable.php`](./database/seeders/UserSeederTable.php) | Admin ও super-admin ধরনের seed user ও role assignment তৈরি করে; initial account credential production-এ ব্যবহারের আগে বদলাতে হবে। |
| [`ProductCategorySeeder.php`](./database/seeders/ProductCategorySeeder.php) | সাতটি initial product category তৈরি করে। |
| [`ProductSeeder.php`](./database/seeders/ProductSeeder.php) | 661 catalog product-name literal দিয়ে product row তৈরি; অধিকাংশ optional catalog field null। |
| [`Product.php`](./database/seeders/Product.php) | ProductSeeder-এর অনুরূপ catalog data/logic আছে, কিন্তু `DatabaseSeeder` এই class-টি call করে না। |
| [`BrandSeeder.php`](./database/seeders/BrandSeeder.php) | `run()` বর্তমানে খালি; কোনো brand row তৈরি করে না। |
| [`CustomerSeeder.php`](./database/seeders/CustomerSeeder.php) | `run()` বর্তমানে খালি; কোনো customer row তৈরি করে না। |

Seed command চালানোর আগে database, environment এবং seed করা demo user পর্যালোচনা করুন; Seeder-এ catalog duplicate guard না থাকলে পুনরায় চালালে duplicate record হতে পারে।

## ৭. UI ও request code-এর পুনরাবৃত্ত pattern

### Form → Controller → Model

Create/update view named route-এ request পাঠায়, `@csrf` CSRF token যোগ করে, update form-এ HTTP method spoofing (`@method('PUT')`) ব্যবহার করতে পারে। Controller `$request->validate()`-এর পরে mass-assignment-এ অনুমোদিত field Model-এর `$fillable` অনুযায়ী সংরক্ষণ করে। Validation error `@error` দিয়ে এবং পুরোনো input `old()` দিয়ে form-এ ফেরে।

### Transaction ও balance history

Sale, customer/supplier ledger/payment ও voucher workflow-তে database transaction ব্যবহার করে related record একসঙ্গে save/update/delete করা হয়। Ledger summary-তে cumulative paid/due থাকে; আলাদা payment-history model-এ প্রতিটি dated installment রাখা হয়। Product stock ও financial amount server-side validate করাই চূড়ান্ত; JavaScript preview শুধু ব্যবহারকারীর সুবিধা।

### Report ও export

Controller filter validate করে query/aggregate চালায়; Blade total ও row format করে। Export route সাধারণত একই filter/query-এর CSV response দেয়। Customer/Supplier/employee aggregate report-এ history table এবং summary row আলাদা উদ্দেশ্যে ব্যবহৃত হয়।

### Permission ও print

- `@can`/`@canany` UI action আড়াল/দেখায়; route middleware server-side authorization করে।
- Print document-এ `.no-print` অংশ লুকানো হয়; print CSS page margin, table width, color ও break behavior সামলায়।
- Sales invoice, delivery challan ও quotation-এ organization branding ও optional logo watermark আছে; watermark permission ছাড়া toggle দেখানো হয় না।
- Browser-এর print header/footer preference browser-controlled; CSS `@page` margin শূন্য করলে অনেক browser-এ তা suppress হয়, কিন্তু user/browser print settings অনুযায়ী ফল পরিবর্তিত হতে পারে।

## ৮. কোথা থেকে কোন কাজ অনুসরণ করবেন

| কাজ বোঝার জন্য | প্রথমে দেখুন |
|---|---|
| Sale তৈরি থেকে invoice | [`SaleController.php`](./app/Http/Controllers/SaleController.php) → [`salesIndex.blade.php`](./resources/views/backend/Sales/salesIndex.blade.php) → [`salesInvoice.blade.php`](./resources/views/backend/Sales/salesInvoice.blade.php) → `SaleInvoice`/`SaleItem` models ও sales migrations |
| Quotation তৈরি/প্রিন্ট | [`QuotationController.php`](./app/Http/Controllers/QuotationController.php) → [`create.blade.php`](./resources/views/backend/quotation/create.blade.php) → [`show.blade.php`](./resources/views/backend/quotation/show.blade.php) → `Quotation`/`QuotationItem` models |
| Customer payment/receipt | [`CustomerController.php`](./app/Http/Controllers/CustomerController.php) → customer ledger/payment models → [`customerLedger.blade.php`](./resources/views/backend/customer/customerLedger.blade.php) → [`customerLedgerInvoice.blade.php`](./resources/views/backend/customer/customerLedgerInvoice.blade.php) |
| Voucher create/report | `VoucherEntryController`/`EmployeePaymentVoucherController` → `VoucherEntry`/`EmployeePaymentVoucher` → `voucherEntryCreate`/`VoucherEntries` report view → permission seeder |
| Stock report | `ProductInventoryReportController` → `Product`/`SaleItem`/`Supplierledger` → [`productinventory.blade.php`](./resources/views/backend/reports/productinventory.blade.php) |
| Login/password reset | `routes/auth.php` → `app/Http/Controllers/Auth/` controllers → `resources/views/auth/` templates |

## ৯. Scope, known gaps ও safe reading

- Inventory-তে `PurchaseInvoice`/`PurchaseItem` model-সহ purchase-named source আছে, তবে `PurchasesContoller`-এ action নেই; route map-এ purchase workflow সক্রিয় নয়।
- Category-এর দুটি create template আছে; customer list/ledger নামে দুইটি template খালি; voucher list template-টিও খালি।
- Seeder-এ repeated catalog entry তৈরির সম্ভাবনা ও initial account-এর deployment risk বিবেচনা করতে হবে।
- `PROJECT-MODULES-DOCUMENTATION-BANGLA.md`-তে Sales/Quotation/Ledger/Reports-এর formula, workflow ও নির্বাচিত বাস্তব code excerpt আছে; এই guide-এ পূর্ণ file path-এ link দিয়ে code-এর authoritative implementation চিহ্নিত করা হয়েছে।
- এই নথি কোনো migration চালায় না এবং কোনো PHP/Blade/route/model/seeder source বদলায় না।
