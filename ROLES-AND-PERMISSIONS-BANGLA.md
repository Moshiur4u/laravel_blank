# Roles and Permissions: Manager ও Admin সেটআপ

## বাস্তবায়নের সারাংশ

এই প্রকল্পের Spatie Permission-ভিত্তিক RBAC ব্যবস্থায় বিদ্যমান permission-গুলো ব্যবহার করে `sales`, `manager`, `projector` ও `admin` role preset যোগ করা হয়েছে। যেসব কাজের জন্য আগে আলাদা permission ছিল না—যেমন supplier তৈরি/সম্পাদনা/মুছে ফেলা এবং supplier ledger সম্পাদনা/মুছে ফেলা—সেসবের জন্য granular permission যোগ করা হয়েছে।

Role preset-গুলো `RolePermissionSeeder`-এ রাখা হয়েছে। `PermissionSeederTable` আগে permission তৈরি করে, তারপর `RolePermissionSeeder` প্রতিটি preset-এর permission একসঙ্গে synchronize করে। তাই seeder পুনরায় চালালেও preset role-এ নির্ধারিত permission-গুলো একই থাকে।

## Manager role

`manager` role-এ নিচের অনুমতিগুলো দেওয়া হয়েছে:

- Dashboard দেখা এবং Sales তৈরি করা।
- Brand ও Product Category দেখা, তৈরি, সম্পাদনা, update এবং delete করা।
- সাধারণ voucher ও employee payment voucher তৈরি/দেখা; পাশাপাশি voucher-সংক্রান্ত report দেখা।
- Supplier দেখা, তৈরি ও সম্পাদনা করা; supplier মুছে ফেলা যাবে না।
- Supplier ledger দেখা ও payment জমা দেওয়া; ledger entry সম্পাদনা বা মুছে ফেলা যাবে না।
- Employee দেখা, তৈরি ও সম্পাদনা করা; employee মুছে ফেলা বা status পরিবর্তন করা যাবে না।
- Role ও তার permission-গুলো দেখা এবং নতুন role তৈরি করা; বিদ্যমান role edit/delete করা যাবে না।
- Organization Information-এ প্রবেশাধিকার দেওয়া হয়নি।

Manager নতুন role তৈরি করতে পারবে, তবে নিজের কাছে থাকা permission-এর বাইরের permission নতুন role-এ দিতে পারবে না। Employee তৈরি করার সময়ও কেবল এমন role বেছে নেওয়া যাবে যার permission Manager-এর বর্তমান permission-এর subset।

## Admin role

`admin` role-এ permission seeder-এ থাকা সব permission দেওয়া হয়। ফলে Admin সব module, action, report এবং Organization Information-এ প্রবেশ করতে পারে। `UserSeederTable`-এর seeded admin account-গুলো এই role-এ যুক্ত থাকে।

## Authorization ও UI

- Route-এ Spatie permission middleware ব্যবহার করা হয়েছে; URL সরাসরি খুললেও অনুমতি না থাকলে অনুরোধ `403` পাবে।
- Sidebar, create/edit/delete action, ledger payment action এবং role-management control-এ Blade `@can` / `@canany` ব্যবহার করা হয়েছে। অনুমতি না থাকলে সংশ্লিষ্ট link বা button দেখানো হয় না।
- Role তৈরি/সম্পাদনার request-এ server-side permission validation আছে, তাই UI এড়িয়ে request পাঠিয়েও অনুমতিহীন permission বরাদ্দ করা যাবে না।
- Permission-গুলো additive: প্রয়োজন অনুযায়ী Admin role editor দিয়ে custom role তৈরি ও user-কে assign করতে পারে।

## চালু করা

নতুন বা বিদ্যমান database-এ permission ও role preset তৈরি/হালনাগাদ করতে:

```bash
php artisan db:seed --class=PermissionSeederTable
php artisan db:seed --class=RolePermissionSeeder
```

নতুন account-এ role বরাদ্দ করতে Admin হিসেবে Employee module ব্যবহার করুন। Admin-এর জন্য role assignment-এর permission আছে; Manager কেবল নিরাপদ subset-এর role বরাদ্দ করতে পারে এবং বিদ্যমান employee-র role পরিবর্তন করতে পারে না।

পরিবর্তন যাচাইয়ের জন্য:

```bash
php artisan test --compact tests/Feature/RolePermissionAccessTest.php
```
