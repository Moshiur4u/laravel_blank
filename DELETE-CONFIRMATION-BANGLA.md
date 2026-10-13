# Delete confirmation: route, Blade, model ও migration flow

## কী পরিবর্তন হয়েছে

তালিকার Delete link-এ এখন browser-এর সাধারণ `confirm()`-এর বদলে আগে SweetAlert2-এর একটি সতর্কতা দেখায়:

- শিরোনাম: **Do you want to delete?**
- কাজ চালিয়ে যাওয়ার বোতাম: **Yes**
- বাতিলের বোতাম: **No**
- তালিকার রেকর্ডের নাম/পরিচয় বার্তায় দেখানো হয়।

**No** চাপলে কোনো route-এ যাওয়া হয় না এবং কোনো রেকর্ড মুছে না। **Yes** চাপলে link-এর আগের URL-এই যাওয়া হয়; তাই বিদ্যমান route, controller-এর response এবং success notification অপরিবর্তিত থাকে।

SweetAlert2-এর নতুন package যোগ করা হয়নি। আগে থেকেই Composer dependency-তে থাকা SweetAlert2 JS/CSS ও MIT license `public/assets/plugins/sweetalert2/`-এ রাখা হয়েছে, যাতে `.gitignore`-এ থাকা `public/vendor` path বা deployment-time publish command-এর ওপর নির্ভর না করে fresh checkout-এও confirmation চলে।

## Delete confirmation-এর code flow

1. সংশ্লিষ্ট Blade তালিকার delete link-এ `data-swal-delete` ও `data-delete-message` থাকে। Blade-এর `{{ }}` দিয়ে record-এর নাম HTML-escaped অবস্থায় attribute-এ যায়।
2. `resources/views/dashboard/dashboard.blade.php` SweetAlert2 asset ও `public/assets/js/delete-confirmation.js` load করে।
3. `delete-confirmation.js` document-level click listener দিয়ে `data-swal-delete` link শনাক্ত করে। তাই link-এর icon-এ click করলেও একই confirmation চলে।
4. listener প্রথমেই browser navigation থামায়। SweetAlert2 অনুপস্থিত থাকলে deletion চালায় না; console ও alert-এ ত্রুটি জানায়।
5. **Yes**-এর পরেই `window.location.assign()` আগের `href` খোলে। **No**-তে URL পরিবর্তন হয় না।
6. Route আগের মতো সংশ্লিষ্ট controller-এর `destroy()` method চালায়। Controller Eloquent model-এ delete করে, প্রয়োজনমতো image file সরায় এবং আগের redirect/flash message বজায় রাখে।

## কোন কোন তালিকায় confirmation যোগ হয়েছে

| রেকর্ড | Delete route | Blade তালিকা | Controller method | Model |
|---|---|---|---|---|
| Product | `product.destroy` | `resources/views/backend/Product/product/indexProduct.blade.php` | `ProductController::destroy()` | `Product` |
| Product category | `category.destroy` | `resources/views/backend/Product/Category/productCategoryindex.blade.php` | `ProductCategoryController::destroy()` | `ProductCategory` |
| Brand | `brand.destroy` | `resources/views/backend/Product/Brand/brandIndex.blade.php` | `BrandController::destroy()` | `Brand` |
| Customer | `customer.destroy` | `resources/views/backend/customer/customerIndex.blade.php` | `CustomerController::destroy()` | `Customer` |
| Supplier | `supplier.destroy` | `resources/views/backend/Supplier/supplierList.blade.php` | `SupplierController::destroy()` | `Supplier` |
| Supplier ledger entry | `supplier.ledger.destroy` | `resources/views/backend/Supplier/supplierledger.blade.php` | `SupplierledgerController::destroy()` | `Supplierledger` |
| Role | `roles.destroy` | `resources/views/backend/roles/role-index.blade.php` | `RoleController::destroy()` | Spatie `Role` |
| Employee/user | `user.destroy` | `resources/views/frontend/users/userIndex.blade.php` | `UserController::destroy()` | `User` |

## Route, controller ও database-এর বিদ্যমান কাজ

- এই delete route-গুলো `routes/web.php`-এ `GET` route হিসেবে আছে এবং `auth`, `verified` middleware group-এর অধীনে চলে। `roles.destroy`-এ অতিরিক্ত `permission:delete-role` middleware আছে।
- Confirmation শুধু UI-তে accidental click ঠেকায়; সরাসরি URL/request দিয়ে route-এ যাওয়া আটকায় না। HTTP method, authorization, route বা controller এখানে বদলানো হয়নি।
- `ProductController::destroy()` record খুঁজে product image ফাইল থাকলে `public/uploads/products/` থেকে সরিয়ে তারপর product delete করে।
- `SupplierController::destroy()` supplier logo ফাইল থাকলে `public/uploads/suppliers/` থেকে সরিয়ে supplier delete করে।
- `UserController::destroy()` employee/user-এর image থাকলে controller-এ নির্ধারিত `public/users/` path থেকে সরিয়ে `User` record delete করে।
- Category, brand, customer, supplier ledger ও role-এর delete method-গুলো তাদের নিজ নিজ Eloquent/Spatie model-এ delete চালায়। Redirect ও flash message controller অনুযায়ী আগের মতোই থাকে।

## Model relation ও migration-এর delete প্রভাব

- **Product category এবং brand:** `Product` model যথাক্রমে `productCategory()` ও `brand()` দিয়ে belongs-to relation করে। `ProductCategory::Products()` এবং `Brand::Products()` inverse has-many relation। `2026_06_30_170258_create_products_table.php`-এ category ও brand foreign key-তে `cascadeOnDelete()` আছে; তাই সংশ্লিষ্ট category/brand মুছলে database product row-গুলোও cascade-এ মুছতে পারে। এই database cascade `ProductController::destroy()` চালায় না, ফলে সংশ্লিষ্ট image file disk-এ থেকে যেতে পারে।
- **Product:** `2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php`-এ supplier nullable foreign key `nullOnDelete()`-সহ আছে। Sales migration `2026_10_11_000003_upgrade_sales_tables.php`-এ sale item-এর product relation nullable/`nullOnDelete()`; product মুছলে ঐ sales row-তে product reference null হতে পারে। Product controller image-ও সরায়।
- **Supplier ও supplier ledger:** `Supplier::supplierledgers()` has-many এবং `Supplierledger::supplier()` belongs-to। `2026_10_06_111603_create_supplierledgers_table.php`-এ `supplier_id` cascade; supplier মুছলে তার ledger entries-ও cascade-এ মুছে যায়। `Supplierledger::payments()` has-many; `2026_10_12_163000_create_supplier_ledger_payments_table.php`-এ ledger payment-এর `supplierledger_id` cascade, তাই ledger entry মুছলে payment history-ও cascade-এ মুছে যায়। `Supplierledger::product()` product-এর belongs-to relation।
- **Customer:** `Customer::ledgers()` has-many এবং `CustomerLedger::customer()` belongs-to। `2026_10_11_000001_create_customer_ledgers_table.php`-এ `customer_id` cascade; customer মুছলে customer ledger-ও মুছে যায়। Customer-এর `employee()` ও ledger-এর `employee()` / `receiver()` user relation nullable `nullOnDelete()`।
- **Employee/User ও voucher:** `EmployeePaymentVoucher::employee()` হলো `employee_id` দিয়ে `User`-এর belongs-to relation। Voucher migration `2026_10_12_181140_create_employee_payment_vouchers_table.php`-এ employee foreign key `nullOnDelete()` এবং `employee_name` আলাদাভাবে snapshot হিসেবে রাখা আছে; employee মুছলে voucher row ও নাম snapshot থেকে যায়, employee link null হয়।
- **Role ও permission:** `User` model-এ Spatie `HasRoles` trait আছে। `2026_06_17_170325_create_permission_tables.php`-এর `model_has_roles` ও `role_has_permissions` pivot foreign key role delete হলে cascade করে; user-role assignment এবং role-permission assignment পরিষ্কার হয়।
- **মূল migration:** এই confirmation পরিবর্তনের জন্য কোনো migration, model relation, database schema বা controller logic পরিবর্তন করা হয়নি।

## Employee voucher ও অন্যান্য delete সীমা

বর্তমান `EmployeePaymentVoucherController` এবং `resources/views/backend/Accounts Entries/empolyeePayment.blade.php` / `employeePaymentLedger.blade.php`-এ voucher দেখানো, তৈরি, ledger দেখা ও export করা হয়; voucher মুছতে কোনো delete button বা `destroy` route নেই। তাই নতুন voucher deletion যোগ করা হয়নি—এটি বিদ্যমান behavior বদলাত।

Profile-এর self-delete Blade form আছে, কিন্তু `routes/web.php`-এ profile route-গুলো বর্তমানে commented out; তাই সেটিও এই সক্রিয় delete-link confirmation তালিকার অংশ নয়।

## যাচাই

- আটটি সক্রিয় তালিকার delete link-এ `data-swal-delete` এবং context message আছে।
- delete confirmation script SweetAlert2 ছাড়া deletion চালায় না।
- Route name ও URL/controller mapping অপরিবর্তিত; কোনো schema পরিবর্তন বা voucher-delete behavior যোগ হয়নি।
