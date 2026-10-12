# কর্মীর বেতন ও NID/Smart Card ফিল্ড

এই নথিতে কর্মীর `salary` এবং `nid_number` ফিল্ডের Blade form, controller validation ও save/update logic, User model, migration এবং সংশ্লিষ্ট route-গুলোর কাজ বাংলায় বর্ণনা করা হয়েছে।

## ফিল্ড ও ডেটাবেস

- `salary`: ঐচ্ছিক কর্মী বেতন; `users` টেবিলে `DECIMAL(12, 2)` হিসেবে থাকে। ঋণাত্মক বা কলামের সর্বোচ্চ সীমা `9999999999.99`-এর বেশি মান গ্রহণ করা হয় না।
- `nid_number`: ঐচ্ছিক NID/Smart Card নম্বর; দিলে ঠিক ১০ অথবা ১৭টি ASCII digit হতে হবে। এটি text input, তাই শুরুতে থাকা শূন্য অক্ষত থাকে। ডেটাবেস কলাম সর্বোচ্চ ৩০ অক্ষর রাখে; ফর্মে ১৭টির বেশি টাইপ করা যায় না।
- পুরোনো কর্মীদের জন্য দুটি কলামই nullable, তাই migration চালালে বিদ্যমান record-এ মান না থাকলেও সমস্যা হয় না।
- NID/Smart Card নম্বরের unique constraint যোগ করা হয়নি।

## Blade

### কর্মী যোগ করার ফর্ম

ফাইল: `resources/views/frontend/users/addUser.blade.php`

- `POST` form-টি `user.store` route-এ কর্মীর তথ্য পাঠায় এবং ছবির জন্য `multipart/form-data` ব্যবহার করে।
- Employee Salary field-এ `number`, `min="0"` এবং `step="0.01"` ব্যবহার করা হয়েছে। এটি ঐচ্ছিক; validation error হলে সংশ্লিষ্ট বার্তা দেখায় এবং `old('salary')` দিয়ে আগের ইনপুট ধরে রাখে।
- NID / Smart Card No. field-টি `text` হিসেবে নেয়, `pattern` দিয়ে ১০ বা ১৭ digit-এর client-side check করে এবং validation error বা redirect-এর পর `old('nid_number')` দেখায়।
- উভয় field-এর input name controller validation ও database column-এর নামের সঙ্গে মেলে।
- বাকি নাম, role, email, password, remark ও photo form আগের মতোই কাজ করে।

### কর্মী সম্পাদনার ফর্ম

ফাইল: `resources/views/frontend/users/editUser.blade.php`

- Salary ও NID/Smart Card নম্বর edit করা যায়।
- `old(field, $Users->field)` validation error-এর পর আগের ইনপুট দেখায়; নতুন ইনপুট না থাকলে database-এ থাকা বর্তমান মান দেখায়।
- field খালি করে save করলে মানটি পরিষ্কার হয়। নতুন কর্মীর form-এর মতোই salary non-negative decimal এবং NID সর্বোচ্চ ৩০ অক্ষরের text।

## Controller

ফাইল: `app/Http/Controllers/UserController.php`

### `index()`

সব user এবং তাদের role লোড করে `userIndex` view-তে পাঠায়।

### `create()`

নতুন কর্মীর form-এর জন্য role-গুলোর তালিকা এনে `addUser` view-তে পাঠায়।

### `store(Request $request)`

- জমা দেওয়া কর্মীর তথ্য validate করে।
- `salary`-কে ঐচ্ছিক non-negative numeric মান এবং `nid_number`-কে ঐচ্ছিক ঠিক ১০ অথবা ১৭টি digit হিসেবে যাচাই করে।
- বৈধ salary ও NID/Smart Card নম্বর `User::create()`-এর মাধ্যমে কর্মীর record-এ রাখে।
- আগের মতো photo upload করে, password hash করে এবং নির্বাচিত role assign করে।
- সফল হলে employee list-এ success message-সহ ফেরত যায়।

### `show(string $id)`

নির্দিষ্ট user, তার role এবং permission লোড করে employee details view-তে পাঠায়। এই পরিবর্তনে details view-তে নতুন পরিচয়-তথ্য প্রকাশ করা হয়নি।

### `edit(string $id)`

নির্দিষ্ট user, role-গুলোর তালিকা এবং তার নির্বাচিত role নিয়ে `editUser` form দেখায়। Salary ও NID মান Blade-এ `$Users` থেকে আসে।

### `update(Request $request, string $id)`

- `salary` ও `nid_number`-এর validation নিয়ম create action-এর সঙ্গে একই রাখে। NID/Smart Card নম্বর দিলে ঠিক ১০ অথবা ১৭টি digit না হলে update প্রত্যাখ্যান করে।
- বৈধ মান `$updateData`-তে যোগ করে, যাতে কর্মীর বাকি তথ্যের সঙ্গে সংরক্ষিত হয়।
- field খালি থাকলে Laravel-এর empty-string-to-null middleware মানটিকে `null` করে; ফলে পুরোনো salary/NID পরিষ্কার করা যায়।
- আগের মতো image/password/status update এবং role sync করে।

### `userstatusupdate(string $status_id)`

user-এর বর্তমান status উল্টে active/inactive করে। Salary/NID পরিবর্তন করে না।

### `destroy(string $id)`

নির্বাচিত user record মুছে দেয়। Salary ও NID একই `users` record-এর অংশ হওয়ায় user-এর সঙ্গে স্বাভাবিকভাবেই মুছে যায়।

### `logout(Request $request)`

Web guard-এ logout করে, session invalidate ও CSRF token regenerate করে login page-এ ফেরত যায়।

## Model

ফাইল: `app/Models/User.php`

- `#[Fillable(...)]` attribute এবং `$fillable` property—দুই জায়গাতেই `salary` ও `nid_number` যোগ করা হয়েছে, যাতে Laravel mass assignment-এ এই field-গুলো গ্রহণ করে।
- `casts()`-এ `salary`-কে `decimal:2` হিসেবে cast করা হয়েছে; ফলে বেতন দুই দশমিক ঘরসহ ধারাবাহিকভাবে সংরক্ষিত/পড়া হয়।
- `nid_number` string হিসেবেই রাখা হয়েছে; এতে পরিচয় নম্বরের leading zero নষ্ট হয় না।
- Password hashing, hidden fields, status cast এবং role-related behavior অপরিবর্তিত।

## Migration

ফাইল: `database/migrations/2026_10_12_174621_add_salary_and_nid_number_to_users_table.php`

- `up()` বিদ্যমান `users` টেবিলে nullable `salary` decimal এবং nullable `nid_number` string কলাম যোগ করে; পুরোনো schema বা data মুছে ফেলে না।
- `down()` rollback হলে শুধু এই দুইটি নতুন কলাম সরায়।
- এটি `0001_01_01_000000_create_users_table.php`-কে পরিবর্তন না করে নতুন migration হিসেবে তৈরি, তাই আগে থেকে থাকা deployment-এও schema upgrade করা যায়।

## Routes

ফাইল: `routes/web.php`

নতুন route প্রয়োজন হয়নি; কর্মী create/edit/update-এর route আগেই আছে এবং সবগুলো `auth` ও `verified` middleware-এর অধীনে:

| Route name | Method ও path | Controller action |
|---|---|---|
| `user.create` | `GET /user/create` | `UserController::create` |
| `user.store` | `POST /user/store` | `UserController::store` |
| `user.edit` | `GET /user/edit/{id}` | `UserController::edit` |
| `user.update` | `PUT /user/update/{id}` | `UserController::update` |

## Migration চালানো

প্রকল্পের root folder থেকে নিচের command চালালে শুধু pending migration-গুলো প্রয়োগ হবে:

```bash
php artisan migrate
```
