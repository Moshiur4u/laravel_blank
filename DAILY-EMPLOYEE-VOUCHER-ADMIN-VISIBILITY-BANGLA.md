# User ও Employee তালিকা: Admin User লুকানো

## Controller (Code and Explanation)

Non-admin ব্যবহারকারীর user/employee তালিকা থেকে `admin` role-এর account বাদ দেওয়া হয়েছে। Admin ব্যবহারকারী সব account দেখতে পারবেন। একই নিয়ম employee report, creator filter এবং CSV export-এও রাখা হয়েছে।

### কোন controller-এর তালিকায় নিয়মটি ব্যবহার হয়েছে

```php
$Users = User::with('roles')->visibleToCurrentUser()->get(); // UserController-এর user list-এ role-অনুযায়ী দৃশ্যমান user আনে।

$employees = User::query()->visibleToCurrentUser()->orderBy('name')->get(); // CustomerController-এর create, edit ও ledger dropdown-এ দৃশ্যমান employee আনে।

$employees = User::query()->visibleToCurrentUser()->orderBy('name')->get(['id', 'name', 'salary']); // EmployeePaymentVoucherController-এর voucher form-এ দৃশ্যমান employee আনে।

$creators = User::query()->visibleToCurrentUser()->orderBy('name')->get(['id', 'name']); // Employee ও general voucher report-এর creator filter-এ দৃশ্যমান user আনে।

$employees = User::query()->visibleToCurrentUser()->orderBy('name')->get(['id', 'name']); // EmployeePaymentVoucherController-এর report filter-এ দৃশ্যমান employee আনে।

'employees' => User::query()->visibleToCurrentUser()->orderBy('name')->get(['id', 'name']), // SalesReportController-এর employee filter-এ Admin account বাদ দেয়।

$creatorsQuery = User::query()->visibleToCurrentUser(); // CombinedVoucherReportController-এর creator তালিকা সীমাবদ্ধ করে।

$users = User::query() // UserActivityLogController-এর user filter-এর query শুরু করে।
    ->visibleToCurrentUser() // non-admin viewer-এর জন্য Admin account বাদ দেয়।
    ->orderBy('name') // দৃশ্যমান user-দের নাম অনুযায়ী সাজায়।
    ->get(['id', 'name', 'email']); // তালিকার জন্য প্রয়োজনীয় column-গুলো আনে।
```

উপরের query-গুলো ব্যবহৃত controller ও method:

- `UserController::index()` — সাধারণ user list।
- `CustomerController::create()`, `edit()`, `ledger()` ও `ledgerEdit()` — customer-এর দায়িত্বপ্রাপ্ত employee dropdown।
- `EmployeePaymentVoucherController::index()` ও `report()` — voucher form, employee filter এবং creator filter।
- `SalesReportController::index()` — sales report employee filter।
- `VoucherEntryController::report()` এবং `CombinedVoucherReportController::index()` — voucher creator filter।
- `UserActivityLogController::index()` — activity log-এর user filter।

Employee voucher report এবং CSV-তে Admin role-এর কর্মীর voucher-ও non-admin viewer-এর জন্য বাদ থাকে:

```php
if (! auth()->user()->hasRole('admin')) { // বর্তমান viewer admin নয় কি না যাচাই করে।
    $reportQuery->whereDoesntHave( // employee relation-এ নির্দিষ্ট role পাওয়া voucher বাদ দেয়।
        'employee.roles', // voucher-এর employee-এর role relation পরীক্ষা করে।
        fn ($query) => $query->where('name', 'admin') // admin role যুক্ত employee শনাক্ত করে।
    ); // এমন employee-এর voucher report/export থেকে বাদ পড়ে।
} // admin viewer হলে এই role-ভিত্তিক বাধা প্রয়োগ হয় না।
```

`tests/Feature/RolePermissionAccessTest.php`-এ scope-এর admin/non-admin আচরণ পরীক্ষা করা হয়েছে। `tests/Feature/EmployeePaymentVoucherReportTest.php`-এ report-এর employee ও creator তালিকা এবং voucher visibility পরীক্ষা করা হয়েছে।

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন হয়নি। role যাচাইয়ের জন্য বিদ্যমান Spatie user-role সম্পর্ক ব্যবহার করা হয়েছে; নতুন database column বা table দরকার নেই।

## Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন হয়নি। আগের route ও permission middleware-ই ব্যবহার হয়; controller শুধু ওই route-গুলোর list query-তে দৃশ্যমানতার scope প্রয়োগ করে।

## Model (Code and Explanation)

ফাইল: `app/Models/User.php`

`visibleToCurrentUser()` একটি reusable Eloquent local scope। ফলে প্রতিটি user/employee তালিকায় আলাদা role filter না লিখে একই নিয়ম ব্যবহার করা যায়।

```php
use Illuminate\Database\Eloquent\Builder; // local scope-এর query builder type ব্যবহার করে।

public function scopeVisibleToCurrentUser(Builder $query): Builder // User query-তে দৃশ্যমানতার scope ঘোষণা করে।
{ // scope method-এর body শুরু করে।
    if (auth()->user()?->hasRole('admin')) { // বর্তমান viewer-এর admin role আছে কি না পরীক্ষা করে।
        return $query; // admin হলে সব user-সহ query অপরিবর্তিত ফেরায়।
    } // non-admin viewer হলে পরের filter চালায়।

    return $query->whereDoesntHave( // admin role যুক্ত user-দের query থেকে বাদ দেয়।
        'roles', // User model-এর role relation পরীক্ষা করে।
        fn ($roles) => $roles->where('name', 'admin') // admin role থাকা user-দের খুঁজে পায়।
    ); // admin role নেই এমন user-দের query ফেরায়।
} // scope method শেষ করে।
```

## Blade Template (Code and Explanation)

Blade template-এ role query যোগ করার প্রয়োজন নেই; controller-ই `$employees`, `$creators` বা `$Users` collection-কে scope করে পাঠায়। Existing dropdown/table loop-গুলো filtered collection-টাই render করে। উদাহরণ:

```blade
@foreach ($employees as $employee) {{-- controller থেকে পাওয়া দৃশ্যমান employee-দের ঘোরে। --}}
    <option value="{{ $employee->id }}"> {{-- employee id-কে option-এর মান হিসেবে রাখে। --}}
        {{ $employee->name }} {{-- option-এ employee-র নাম দেখায়। --}}
    </option> {{-- বর্তমান employee-র option শেষ করে। --}}
@endforeach {{-- employee তালিকার loop শেষ করে। --}}
```
