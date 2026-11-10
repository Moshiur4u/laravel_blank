# Employee Add/Edit Role Field

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/UserController.php`

Admin-এর Add/Edit form-এ সব role, `admin`-সহ, দেখানো হয় এবং Admin যেকোনো role
বরাদ্দ করতে পারে। Non-admin user-দের form-এ `admin` role দেখানো হয় না; অন্য সব
role দেখানো হয়। যে role-এ `employee.role.assign` permission আছে, সেটি নতুন করে
বরাদ্দ করতে current user-এরও এই permission থাকতে হবে। permission না থাকলে role
দেখা যাবে, তবে disabled থাকবে। একই নিয়ম server-side validation-এও প্রয়োগ হয়।

```php
private function assignableRoleNames(array $existingRoleNames = []): array
{
    if (auth()->user()->hasRole('admin')) {
        return Role::query()->pluck('name')->all();
    }

    $roles = Role::query()->where('name', '!=', 'admin');

    if (! auth()->user()->can('employee.role.assign')) {
        $roles->whereDoesntHave(
            'permissions',
            fn ($query) => $query->where('name', 'employee.role.assign')
        );
    }

    return array_values(array_unique(array_merge(
        $roles->pluck('name')->all(),
        array_diff($existingRoleNames, ['admin'])
    )));
}
```

Store ও Update-এ `roles` ইনপুট এই helper-এর role তালিকা দিয়ে validate হয়। Update
সফল হলে নির্বাচিত role সংরক্ষিত হয়:

```php
'roles' => ['required', 'string', Rule::in($this->assignableRoleNames())],

$user->syncRoles($request->roles);
```

Admin-এর ক্ষেত্রে helper সব role ফেরত দেয়; তাই Admin `admin`, `manager` এবং
অন্যান্য role-ও বরাদ্দ করতে পারে।

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন করা হয়নি। Role ও permission-এর বিদ্যমান
সম্পর্ক থেকেই অনুমতি যাচাই করা হয়; schema পরিবর্তনের প্রয়োজন নেই।

## Route (Code and Explanation)

কোনো route পরিবর্তন করা হয়নি। বিদ্যমান `user.create`/`user.store` route-এ
`employee.create`, এবং `user.edit`/`user.update` route-এ
`employee.edit` অথবা `employee.role.assign` middleware আগের মতোই form access
নিয়ন্ত্রণ করে।

## Model (Code and Explanation)

কোনো model যোগ বা পরিবর্তন করা হয়নি। Spatie `Role` model থেকে role ও permission
তথ্য নেওয়া হয় এবং `User::syncRoles()` দিয়ে নির্বাচিত role সংরক্ষিত হয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/frontend/users/addUser.blade.php` এবং
`resources/views/frontend/users/editUser.blade.php`

Admin-এর `$Roles` collection-এ সব role থাকে, তাই Admin `admin` এবং `manager`
দুটিই দেখতে ও নির্বাচন করতে পারে। Non-admin-এর `$Roles` collection-এ `admin`
বাদে সব role থাকে। `assignableRoleNames`-এ নেই এমন role dropdown-এ দেখা গেলেও
disabled থাকে। ফলে permission নেই এমন ব্যবহারকারী `employee.role.assign`
permission-যুক্ত role বেছে নিতে পারে না।

```blade
<option value="{{ $role->name }}"
    {{ in_array($role->name, $assignableRoleNames, true) ? '' : 'disabled' }}>
    {{ ucfirst($role->name) }}
</option>
```

## পরীক্ষা

`tests/Feature/RolePermissionAccessTest.php`-এ যাচাই করা হয়েছে যে Admin সব
role দেখতে ও `admin` role-ও বরাদ্দ করতে পারে। Non-admin user `admin` role দেখে
না, `manager` role দেখতে পায়, এবং `employee.role.assign` permission না থাকলে
সেটি বরাদ্দ করতে পারে না।
