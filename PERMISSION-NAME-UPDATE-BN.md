# Permission নাম পরিবর্তন: বাংলা কারিগরি নথি

## কাজের পরিধি

এই পরিবর্তনে `PermissionSeederTable`-এর নতুন permission নামকে একমাত্র মানদণ্ড ধরা হয়েছে। সংশ্লিষ্ট route middleware, permission-নির্ভর Blade অংশ, role preset এবং কর্মীর role assignment-এর controller check একই নাম ব্যবহার করে। unrelated module-এর business logic, controller action, URL, route name, model field বা database business table পরিবর্তন করা হয়নি।

প্রতিটি পরিবর্তিত permission check এবং নতুন permission declaration-এর পাশে বাংলা comment রাখা হয়েছে। এই নথিতে কেবল permission পরিবর্তনের সঙ্গে সম্পর্কিত logic, function ও relation ব্যাখ্যা করা হয়েছে; সংশ্লিষ্ট controller বা Blade ফাইলের permission-বহির্ভূত business logic এখানে পুনরাবৃত্তি করা হয়নি।

## নতুন permission নামের ব্যবহার

| অংশ | আগের নাম | নতুন নাম |
|---|---|---|
| Product | `Products`, `create-product`, `edit-product-details`, `archive-product` | `product.index`, `product.create`, `product.edit`, `product.update`, `product.delete` |
| Brand | `Brands`, `brand_category.create/edit/update/delete` | `brand.index`, `brand.create`, `brand.edit`, `brand.update`, `brand.delete` |
| Category | `Categories`, `brand_category.create/edit/update/delete` | `category.index`, `product.category.create`, `product.category.edit`, `product.category.update`, `product.category.delete` |
| Customer | `customer.update`, `customer.ledger.payment` | `customer.edit`, `Customer.ledger.payment` |
| Supplier ledger | `supplier.ledger.create/edit/delete`, `supplier.ledger.payment`, `ledger.payment` | `Supplier.ledger.create`, `Supplier.ledger.edit`, `Supplier.ledger.delete`, `Supplier.ledger.payment` |
| Employee details | `employee.view` | `view-employee-details` |
| Employee role assignment | `assign-user-role`, `assign-role-to-user` | `employee.role.assign` |
| Role management | `role-menu`, `view-role-list`, `create-role`, `edit-role-permissions`, `delete-role` | `role.index`, `roles.create`, `role.edit`, `role.delete` |
| Voucher reports | `reports.vouchers` | `voucher.report.view`, `voucher.report.employee`, `voucher.report.miscellaneous` |
| General voucher reports | `All Vouchers` | `voucher.report.view`, `voucher.report.miscellaneous` |
| Other reports | `reports.inventory`, `view-sales-dashboard` | `product.report.inventory`, `sales.report` |

`reports.all_access` নতুন তালিকায় ছিল না, কিন্তু activity log এবং administrator-এর report access চালু রাখতে এটি রাখা হয়েছে। `Supplier.ledger.create` এবং `employee.role.assign`-ও ব্যবহৃত action-এর জন্য seeder-এ যোগ করা হয়েছে। `Customer`, `Supplier`, `Employee`-এর ledger permission-এ seeder-এ থাকা uppercase/lowercase অক্ষর হুবহু মানতে হবে; Spatie permission name-কে string key হিসেবে মেলায়।

## Request ও authorization flow

### `routes/web.php`

1. `auth` middleware ব্যবহারকারীকে login করায় এবং `verified` middleware email verification নিশ্চিত করে।
2. প্রতিটি permission-সুরক্ষিত endpoint-এ Spatie-এর `permission:<name>` middleware request চালানোর আগে role/user permission যাচাই করে।
3. report endpoint-এ নির্দিষ্ট নতুন report permission অথবা `reports.all_access`—যেখানে আগে broad report access ছিল—দুটির যেকোনো একটি গ্রহণ করা হয়।
4. এই middleware-গুলোই প্রকৃত server-side নিরাপত্তা; Blade-এ কোনো link লুকানো থাকাই authorization হিসেবে যথেষ্ট নয়।

### `RoleController`

| Function | Permission workflow-এ কাজ |
|---|---|
| `index()` | Role-গুলোর সঙ্গে তাদের `permissions` eager-load করে role তালিকার Blade view-তে পাঠায়। |
| `create()` | বর্তমান ব্যবহারকারীর `getAllPermissions()` থেকে অনুমোদিত permission-গুলো বেছে role তৈরির form-এ পাঠায়। |
| `store(Request $request)` | role নাম ও permission ID validate করে; ID-গুলো বর্তমান ব্যবহারকারীর permission set-এর মধ্যে না থাকলে request গ্রহণ করে না। নতুন `Role` তৈরি করে `syncPermissions()` দিয়ে অনুমতি সংরক্ষণ করে। |
| `show()` | বর্তমানে কোনো logic নেই; permission rename-এর জন্য পরিবর্তিত হয়নি। |
| `edit($id)` | নির্দিষ্ট role ও তার permission load করে edit form-এ পাঠায়। |
| `update(Request $request, $id)` | role-এর বর্তমান permission এবং acting user-এর permission মিলিয়ে অনুমোদিত ID validate করে; role update করে এবং নতুন permission set sync করে। |
| `destroy($id)` | নির্দিষ্ট role মুছে দেয়; route-এ এখন `role.delete` permission বাধ্যতামূলক। |

### `UserController`

- `edit($id)`-এ role selector কেবল `employee.role.assign` থাকলে role list পায়; list-টি `assignableRoleNames()` দিয়ে বর্তমান ব্যবহারকারীর permission-এর subset-এ সীমিত থাকে।
- `update(Request $request, $id)`-এ একই নতুন permission দিয়ে role assignment অনুমোদন যাচাই হয়। permission না থাকলে role field validate বা sync করা হয় না।
- employee status পরিবর্তনের `employee.inactive` check অপরিবর্তিত আছে; এটি এই rename-এর অংশ নয়।
- employee details route-এ `view-employee-details` permission middleware ব্যবহৃত হয়েছে।

## Model ও relation

### `App\Models\User`

`User` model-এ Spatie-এর `HasRoles` trait আছে। এটি user-কে role এবং সরাসরি permission দিতে/পড়তে Spatie-এর relation/API দেয়।

- User ↔ Role: polymorphic many-to-many relation, `model_has_roles` pivot table দিয়ে।
- User ↔ Permission: polymorphic many-to-many relation, `model_has_permissions` pivot table দিয়ে।
- User-এর role-গুলোর permissions: `roles.permissions` relation chain দিয়ে পাওয়া যায়।
- কোনো permission rename হলে permission ID-র pivot relation অক্ষুণ্ণ থাকলে user-এর কার্যকর access-ও অক্ষুণ্ণ থাকে।

### Spatie `Role` ও `Permission`

এই project custom `Role` বা `Permission` model ব্যবহার করে না; controller ও seeder-এ package-এর `Spatie\Permission\Models\Role` এবং `Permission` model ব্যবহার করা হয়।

- Role ↔ Permission: many-to-many relation, `role_has_permissions` pivot table দিয়ে।
- Permission ↔ User/অন্য model: `model_has_permissions` pivot table দিয়ে।
- Role-এর permission name পরিবর্তনের সময় migration role pivot ও direct model pivot—দুই জায়গাতেই assignment কপি/merge করে।

## Migration ও data preservation

### Permission table তৈরির migration

`2026_06_17_170325_create_permission_tables.php` Spatie-এর `permissions`, `roles`, `role_has_permissions`, `model_has_roles` এবং `model_has_permissions` table তৈরি করে। `permissions`-এ `name` ও `guard_name` unique; তাই পুরোনো permission row-এর সঙ্গে নতুন row মিলে গেলে সরাসরি rename না করে pivot merge করতে হয়।

### `2026_10_17_000000_rename_legacy_permissions.php`

- `up()`-এ নির্দিষ্ট legacy permission name-গুলো নতুন নামের দিকে সরানো হয়।
- নতুন permission row আগে থেকেই থাকলে `role_has_permissions` এবং `model_has_permissions` relation-গুলো `insertOrIgnore` দিয়ে merge হয়; duplicate assignment হয় না।
- `brand_category.*` permission-গুলো Brand ও Category—দুই module-এ ভাগ হওয়ায়, পুরোনো role assignment দুই নতুন permission-এই কপি করা হয়।
- migration শেষে মূল legacy row-গুলো সরানো হয় এবং Spatie permission cache পরিষ্কার করা হয়।
- `down()` reverse mapping প্রয়োগ করে; একাধিক পুরোনো alias একই নতুন permission-এ মিশে থাকলে rollback-এ সেগুলো একটি পুরোনো permission name-এ একত্র হয়।

## Seeder-এর কাজ

### `PermissionSeederTable`

নতুন permission key-গুলো `Permission::firstOrCreate()` দিয়ে তৈরি করে। `reports.all_access`, `Supplier.ledger.create` এবং `employee.role.assign` এখানে সংশ্লিষ্ট route/controller check-এর জন্য রাখা হয়েছে। Seeder permission delete করে না; পুরোনো database row migration-এর দায়িত্বে।

### `RolePermissionSeeder`

`sales`, `manager`, `projector` ও `admin` role-এর নির্ধারিত permission list synchronize করে। Manager-এর পুরোনো ক্ষমতার সীমা বজায় রাখতে role edit, supplier ledger edit/delete এবং inventory report-এর নতুন permission তাকে দেওয়া হয়নি। Admin permission seeder-এর সব permission পায়।

## Blade view-তে permission gate

Dashboard, Product, Brand, Category, Customer, Supplier ledger, Employee, Voucher report এবং Role list view-তে `@can`, `@canany` অথবা user `can()` check নতুন name-এ বদলানো হয়েছে।

- `@can('x')` একটি permission থাকলেই নির্দিষ্ট button/link দেখায়।
- `@canany([...])` তালিকার যেকোনো একটি permission থাকলে menu বা action group দেখায়।
- report menu-র child link-গুলোও নিজ নিজ report permission দিয়ে gate করা হয়েছে, যাতে এক report permission অন্য report-এর unauthorized link প্রকাশ না করে।
- Blade check কেবল UX; সংশ্লিষ্ট route middleware server-side access নিশ্চিত করে।

## পরিবর্তন চালু ও যাচাই

প্রথমে database backup নিয়ে migration চালান:

```bash
php artisan migrate
```

এরপর নতুন permission এবং standard role preset synchronize করতে:

```bash
php artisan db:seed --class=PermissionSeederTable
php artisan db:seed --class=RolePermissionSeeder
```

শেষে permission access test চালান:

```bash
php artisan test --compact tests/Feature/RolePermissionAccessTest.php
```
