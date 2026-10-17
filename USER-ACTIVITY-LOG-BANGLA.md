# User Activity Log: বাংলা কারিগরি নথি

## উদ্দেশ্য ও অনুমতি

User Activity Log-এ সফল login/logout, এবং সফল data create/update/delete request-এর actor, সময়, event, subject, request detail, IP address ও user agent রেকর্ড হয়। Activity history user মুছে ফেললেও টিকে থাকে।

`PermissionSeederTable`-এ `user-activity-log.view` dedicated permission তৈরি হয়। Activity Log route-এ `auth`, `verified` এবং `permission:user-activity-log.view|reports.all_access` middleware আছে। অর্থাৎ dedicated permission অথবা আগের broad report permission থাকলে page দেখা যায়। Dashboard sidebar link-ও `@canany` দিয়ে একই access rule মেনে চলে। Admin preset-এর সব permission থাকায় সে Activity Log দেখতে পারে; permission-টি role editor দিয়ে অন্য অনুমোদিত role-এও দেওয়া যায়।

## Request থেকে log তৈরির ধারা

1. `bootstrap/app.php`-এর web middleware stack-এ `AuditUserActivity` append করা আছে। ফলে web request controller/response-এর মধ্য দিয়ে গেলে middleware তা পরীক্ষা করে।
2. `AuditUserActivity::handle()` পরের middleware/controller চালিয়ে response পাওয়ার পর request audit হওয়ার যোগ্য কি না যাচাই করে।
3. সফল authenticated write হলে actor, route, event, subject, sanitized request properties, IP ও user-agent দিয়ে `UserActivityLog` row তৈরি হয়।
4. login/logout-এর জন্য আলাদা event listener `AppServiceProvider::boot()`-এ log তৈরি করে। Middleware login/logout path-কে write audit থেকে বাদ দেয়, তাই event duplicate হয় না।
5. Activity Log page GET route-এ middleware audit record তৈরি করে না; GET request write method নয়।

## Controller: `UserActivityLogController`

### `index(Request $request)`

1. `User` table থেকে filter dropdown-এ দেখানোর user ID, নাম ও ইমেইল name অনুযায়ী সাজিয়ে নেয়।
2. মুছে ফেলা user-দের পুরোনো log থেকে প্রতিটি user-এর সর্বশেষ log row বেছে নেয়; সংশ্লিষ্ট user ID বর্তমান `users` table-এ নেই এমন row-কে dropdown-এ যোগ করে। এতে deleted actor-দের history-ও filter করা যায়।
3. `from`, `to`, `user_id`, `event` query parameter validate করে। End date `from`-এর আগের হতে পারে না; user ID dropdown-এর অনুমোদিত ID-র মধ্যে হতে হয়; event নির্দিষ্ট তালিকার মধ্যে হতে হয়।
4. `UserActivityLog` query-তে `forDateRange()` scope, optional user filter ও event filter প্রয়োগ করে।
5. সর্বশেষ log আগে দেখিয়ে প্রতি page-এ ২৫টি row paginate করে এবং filter query string বজায় রাখে।
6. নির্বাচিত user খুঁজে `backend.user-activity.index` Blade view-তে `logs`, `users`, `filters`, `selectedUser` পাঠায়।

Controller-এর আলাদা CRUD function নেই; এই page কেবল audit history পড়া/ফিল্টার করার কাজ করে।

## Middleware: `AuditUserActivity`

### `handle(Request $request, Closure $next): Response`

- `$next($request)` আগে চালিয়ে controller-এর চূড়ান্ত response পায়।
- `shouldAudit()` false হলে কোনো row তৈরি না করে মূল response ফেরত দেয়।
- request actor না পাওয়া গেলে row তৈরি না করে response ফেরত দেয়।
- route name থেকে event ও subject type নির্ধারণ করে; request input থেকে token, password, file input এবং সংবেদনশীল field বাদ দেয়।
- Sales item list থাকলে product ID থেকে product name resolve করে properties-এ সংরক্ষণ করে।
- role permission ID list থাকলে permission name resolve করে properties-এ যোগ করে।
- actor snapshot, description, subject ID, safe properties, IP ও user-agent-সহ `UserActivityLog::create()` চালায়।
- response অপরিবর্তিত ফেরত দেয়।

### `shouldAudit(Request $request, Response $response): bool`

- `login` ও `logout` path middleware audit থেকে বাদ; এগুলো event listener log করে।
- HTTP status 400 বা তার বেশি হলে failed request log হয় না।
- validation error-সহ redirect response-ও session-এর নতুন flash error দেখে বাদ দেওয়া হয়।
- POST/PUT/PATCH/DELETE এবং `.destroy` বা `.statusupdate` route সফল হলে audit হয়।

### `eventFor(Request $request, string $routeName): string`

- `.destroy` route-কে `delete` এবং `.statusupdate` route-কে `update` হিসেবে চিহ্নিত করে।
- বাকি POST → `create`, PUT/PATCH → `update`, DELETE → `delete`; অন্য audited write-কে `activity` বলে।

### `subjectTypeFor(string $routeName): string`

Route name-এর prefix অনুযায়ী Supplier ledger/payment, Customer ledger, Employee payment, Voucher entry, Sale, Product, Product category, Brand, Supplier, Customer, User, Role বা Organization type বেছে নেয়। কোনো match না হলে `System record` ব্যবহার করে।

### `subjectIdFor(Request $request): ?string`

Route parameter থেকে ক্রমানুসারে `id`, `status_id`, `supplier_id`, `employee` খোঁজে। Scalar value পেলে string-এ রূপান্তর করে; না পেলে `null` ফেরায়।

### `descriptionFor(string $event, string $subjectType, Request $request): string`

Event অনুযায়ী Created/Updated/Deleted/Changed verb বেছে নেয়। Product name, সাধারণ name অথবা invoice ID থাকলে subject description-এর শেষে যোগ করে।

## Login ও logout event: `AppServiceProvider`

`boot()`-এর `Login` event listener সফল login-এর actor ID/name/email, `login` event, “Signed in” description, guard, IP ও user-agent সংরক্ষণ করে।

`Logout` event listener event-এ user না থাকলে কিছু করে না; user থাকলে একই actor snapshot ও guard/network তথ্যসহ `logout` row সংরক্ষণ করে।

`View::composer('dashboard.dashboard', ...)` Activity Log permission পরিচালনা করে না। এটি dashboard-এর organization এবং product notification data সরবরাহ করে; Activity Log navigation-এর authorization Blade-এর `@canany` gate-এ, আর route access middleware-এ।

## Model: `App\Models\UserActivityLog`

- `user_id`, actor name/email, event, description, subject type/ID, properties, IP এবং user-agent mass assignment-এর জন্য `$fillable`-এ আছে।
- `properties` JSON data-কে PHP array হিসেবে পড়া/লেখার জন্য `casts()`-এ `array` cast করা।
- `user(): BelongsTo` হলো `UserActivityLog` থেকে `User`-এর optional belongs-to relation। User delete হলেও audit row টিকে থাকার জন্য foreign key cascade নেই; deleted user-এর ক্ষেত্রে relation `null` হতে পারে এবং actor name/email snapshot log-এ থাকে।
- `scopeForDateRange(Builder $query, ?string $from, ?string $to)` দেওয়া তারিখের শুরু/শেষে `created_at` filter প্রয়োগ করে। কোনো তারিখ null হলে সেই পাশের filter বাদ থাকে।

## Database migration: `2026_10_17_000001_create_user_activity_logs_table.php`

`up()` `user_activity_logs` table তৈরি করে:

| Column | ভূমিকা |
|---|---|
| `id` | log row-এর primary key |
| `user_id` | actor-এর nullable indexed ID; FK নেই যাতে user deletion-এ log না মুছে যায় |
| `user_name`, `user_email` | সময়ের actor পরিচয়ের snapshot |
| `event` | login/logout/create/update/delete/activity; indexed |
| `description` | মানুষের পড়ার মতো action summary |
| `subject_type`, `subject_id` | পরিবর্তিত record/module-এর পরিচয় |
| `properties` | নিরাপদ request details-এর JSON |
| `ip_address`, `user_agent` | request source metadata |
| timestamps | log creation/update সময় |

`created_at` ও `event`-এর composite index date/event filtering-এ সাহায্য করে। `down()` table drop করে।

## Blade: `backend/user-activity/index.blade.php`

- Page title, selected user, filter করা তারিখ এবং report generation time দেখায়।
- GET filter form-এ From date, To date, User এবং Activity বেছে নেওয়া যায়; Reset link query filter সরিয়ে দেয়।
- Paginated log row-তে timestamp, actor snapshot, capitalized event, description, subject ID/type এবং IP দেখায়।
- `properties` থাকলে `<details>` খুলে JSON pretty-print করা যায়।
- Print ও Save as PDF button browser print dialog খোলে; print CSS navigation/filter/pagination লুকায় এবং report table-কে landscape print-এর জন্য সাজায়।
- কোনো log না থাকলে empty-state message দেখায়; pagination link `withQueryString()`-এর কারণে filter বজায় রাখে।
- Blade-এ permission gate রাখা নেই; route middleware authorization নিশ্চিত করে।

## Relation মানচিত্র

```text
User 1 ─── 0..* UserActivityLog
```

`user_activity_logs.user_id` একটি user-এর ID নির্দেশ করে, কিন্তু database foreign key নেই। তাই user মুছে গেলেও log row এবং actor snapshot অক্ষুণ্ণ থাকে। `UserActivityLog::user()` relation বর্তমানে থাকা user-কে resolve করতে পারে।

## নতুন permission চালু করা

Database-এ permission row ও standard role grants হালনাগাদ করতে:

```bash
php artisan db:seed --class=PermissionSeederTable
php artisan db:seed --class=RolePermissionSeeder
```

এরপর Role & Permission Management page থেকে `user-activity-log.view` অনুমোদিত custom role-এ বরাদ্দ করা যায়। আগের `reports.all_access` holders-এর access বজায় থাকে।

## সম্পর্কিত পরীক্ষা

- `tests/Feature/UserActivityLogTest.php`: সফল login/logout, safe request logging, subject ID এবং validation-failure logging বাদ পড়া যাচাই করে।
- `tests/Feature/RolePermissionAccessTest.php`: dedicated Activity Log permission দিয়ে route access এবং role assignment workflow যাচাই করে।
