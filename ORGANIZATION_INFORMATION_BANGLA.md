# Organization Information — বাংলা নির্দেশিকা

## উদ্দেশ্য

প্রতিষ্ঠানের logo, নাম, ফোন, email এবং ঠিকানা এক জায়গায় সংরক্ষণ করে sales invoice, report এবং voucher report-এ একই পরিচিতি দেখানো হয়। Organization menu থেকে তথ্য সম্পাদনা করা যায়।

## ফাইল ও দায়িত্ব

### Database ও Model

- `database/migrations/2026_10_13_182123_create_organizations_table.php`
  - `up()` `organizations` table তৈরি করে; প্রতিষ্ঠানের নাম আবশ্যক এবং ঠিকানা, ফোন, logo ঐচ্ছিক।
  - `down()` rollback-এর সময় শুধু `organizations` table সরায়।
- `database/migrations/2026_10_14_005500_add_email_to_organizations_table.php`
  - `up()` বিদ্যমান organization table-এ nullable email column যোগ করে।
  - `down()` rollback হলে email column সরায়।
- `app/Models/Organization.php`
  - Eloquent model এবং `name`, `address`, `phone`, `email`, `logo` mass assignment-এর জন্য অনুমোদন করে।
  - এই feature একটি একক organization profile ব্যবহার করে, যা `id = 1` দিয়ে পড়া/সংরক্ষণ করা হয়।

### Controller

- `app/Http/Controllers/OrganizationController.php`
  - `edit()` আগে থেকে থাকা profile (ID 1) অথবা নতুন খালি model দিয়ে settings form দেখায়।
  - `update()` input যাচাই করে; email বৈধ email format হতে হবে এবং logo সর্বোচ্চ 2 MB, JPEG/PNG/WebP হতে হবে।
  - নতুন logo `public/uploads/organizations`-এ সংরক্ষণ করে, profile ID 1-এ তথ্য লেখে এবং সফল save-এর পর পুরোনো logo সরায়।
  - validation ব্যর্থ হলে Laravel স্বাভাবিকভাবে error-সহ form-এ ফেরত পাঠায়।
- `app/Http/Controllers/EmployeePaymentVoucherController.php`
  - `exportLedger()` employee ledger-এর CSV-তে organization name, phone, email এবং address যোগ করে। CSV ফরম্যাটে image embed হয় না; logo কেবল HTML/print report-এ দেখা যায়।

### Routes ও Settings Blade

- `routes/web.php`
  - `GET /organization` (`organization.edit`) settings form দেখায়।
  - `PUT /organization` (`organization.update`) তথ্য update করে।
  - দুই route-ই `auth` এবং `verified` middleware-এর আওতায়।
- `resources/views/dashboard/dashboard.blade.php`
  - Organization Information menu-টি settings route-এ নিয়ে যায়।
- `resources/views/frontend/organization/addOrganization.blade.php`
  - logo, নাম, phone, email, address ক্রমে তথ্য সম্পাদনার responsive form।
  - validation error, save success message এবং বর্তমানে সংরক্ষিত logo দেখায়।

### Report Header ও Integration

- `app/Providers/AppServiceProvider.php`
  - নির্বাচিত invoice/report Blade view-তে organization ID 1-এর model সরবরাহ করে।
- `resources/views/backend/partials/organizationHeader.blade.php`
  - report print header-এ বামে logo, নাম, phone, email, address এবং ডানে report title ও ঐচ্ছিক subtitle/generated time দেখায়; invoice/ledger-এর সাধারণ include আগের মতো organization details দেখায়।
  - কোনো organization তথ্য না থাকলে খালি header দেখায় না।
- Header যুক্ত view:
  - `resources/views/backend/Sales/salesInvoice.blade.php`
  - `resources/views/backend/Sales/salesReport.blade.php`
  - `resources/views/backend/reports/productinventory.blade.php`
  - `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`
  - `resources/views/backend/Accounts Entries/voucherEntryReport.blade.php`
  - `resources/views/backend/Accounts Entries/VoucherEntries.blade.php`
  - `resources/views/backend/Accounts Entries/employeePaymentLedger.blade.php`
  - `resources/views/backend/customer/customerLedger.blade.php`
  - `resources/views/backend/Supplier/supplierledger.blade.php`

## চালু করার নিয়ম

নতুন table তৈরি করতে deployment-এ Laravel migration চালাতে হবে:

```sh
php artisan migrate
```

Logo `public/uploads/organizations`-এর অধীনে থাকবে; তাই app-এর public directory write permission থাকা প্রয়োজন।
