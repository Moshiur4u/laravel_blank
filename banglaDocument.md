# প্রজেক্ট আপগ্রেড ও ফিচার ডকুমেন্টেশন (বাংলা)

এই নথিতে আপনার নির্দেশিত তিনটি প্রধান পরিবর্তনের বিস্তারিত লজিক, ফাংশন, রিলেশন, মাইগ্রেশন, মডেল, কন্ট্রোলার, রাউট এবং ব্লেড ভিউয়ের কাজের বিবরণ তুলে ধরা হলো।

---

## সূচিপত্র
1. [কাজের উদ্দেশ্য ও সারসংক্ষেপ](#১-কাজের-উদ্দেশ্য-ও-সারসংক্ষেপ)
2. [ডাটাবেজ মাইগ্রেশন (Migration)](#২-ডাটাবেজ-মাইগ্রেশন-migration)
3. [মডেল ও রিলেশন (Model & Relations)](#৩-মডেল-ও-রিলেশন-model--relations)
4. [কন্ট্রোলার ও ব্যবসায়িক লজিক (Controllers & Logic)](#৪-কন্ট্রোলার-ও-ব্যবসায়িক-লজিক-controllers--logic)
   - [ক. SaleController](#ক-salecontroller)
   - [খ. DashboardConrtoller](#খ-dashboardconrtoller)
   - [গ. SalesReportController](#গ-salesreportcontroller)
5. [রাউট ব্যবস্থাপনা (Routes)](#৫-রাউট-ব্যবস্থাপনা-routes)
6. [ব্লেড ভিউ ও ইন্টারফেস (Blade Views & UI)](#৬-ব্লেড-ভিউ-ও-ইন্টারফেস-blade-views--ui)
   - [ক. salesIndex.blade.php](#ক-salesindexbladephp)
   - [খ. salesInvoice.blade.php](#খ-salesinvoicebladephp)
   - [গ. dashboard-body.blade.php](#গ-dashboard-bodybladephp)
   - [ঘ. salesReport.blade.php](#ঘ-salesreportbladephp)

---

## ১. কাজের উদ্দেশ্য ও সারসংক্ষেপ

১. **Sale Blade-এ ডিসকাউন্ট অপশন:**
   - ডিসকাউন্টের জন্য দুটি অপশন যুক্ত করা হয়েছে:
     - ১. **Direct Amount (৳):** সরাসরি টাকার অঙ্ক লিখে ডিসকাউন্ট দেওয়া।
     - ২. **Percentage (%):** শতকরা হার লিখে ডিসকাউন্ট দেওয়া (যা সাবটোটালের ওপর ভিত্তি করে স্বয়ংক্রিয়ভাবে টাকায় রূপান্তর হয়)।

২. **Dashboard-এ Profit Card:**
   - ড্যাশবোর্ডের মূল KPI কার্ডগুলোর সারিতে একটি নতুন **Net Profit** কার্ড যুক্ত করা হয়েছে।
   - এখানে বড় ফন্টে দেখাচ্ছে **Yearly Profit** (বাৎসরিক লাভ) এবং ছোট ফন্টে পাশাপাশি দেখাচ্ছে **Daily Profit** (দৈনিক লাভ) ও **Weekly Profit** (সাপ্তাহিক লাভ)।

৩. **Sales Report-এ Purchase Price ও Individual Product Profit:**
   - সেলস রিপোর্টে প্রতিটি বিক্রিত পণ্যের গড় ক্রয়মূল্য (**Purchase Price**) দেখানো হচ্ছে।
   - প্রতিটি পণ্যের আলাদা একক লাভ (**Individual Product-wise Profit**) ও মোট ক্রয়মূল্য প্রদর্শিত হচ্ছে।
   - ইনভয়েস তালিকায় প্রতিটি ইনভয়েসের আলাদা লাভ এবং ওপরে মূল সারাংশে **Total Profit** কার্ড যুক্ত করা হয়েছে।
   - Excel CSV এক্সপোর্টেও ক্রয়মূল্য ও লাভ অন্তর্ভুক্ত করা হয়েছে।

---

## ২. ডাটাবেজ মাইগ্রেশন (Migration)

**ফাইল পাথ:** `database/migrations/2026_10_14_120000_add_discount_type_and_sale_item_purchase_price.php`

### মাইগ্রেশনের কাজ:
1. **`sale_invoices` টেবিল:**
   - `discount_type` (`string`, দৈর্ঘ্য: ২০, ডিফল্ট: `'amount'`): ডিসকাউন্ট টাইপ নির্ধারণ করে (`'amount'` অথবা `'percentage'`)।
   - `discount_value` (`decimal:15,2`, ডিফল্ট: `0`): ব্যবহারকারীর দেওয়া প্রকৃত ইনপুট (যেমন: ১০% হলে ১০, অথবা ৫০ টাকা হলে ৫০) সংরক্ষণ করে।
   - `discount_amount` কলামটি আগেই ছিল, যাতে হিসাব করা টাকার চূড়ান্ত ডিসকাউন্ট সংরক্ষিত থাকে।
2. **`sale_items` টেবিল:**
   - `purchase_price` (`decimal:15,2`, nullable): বিক্রির সময় সংশ্লিষ্ট পণ্যের যে ক্রয়মূল্য ছিল, তা প্রতিটি বিক্রির লাইনে snapshot আকারে সংরক্ষণ করা হয়। ফলে ভবিষ্যতে যদি পণ্যের দাম পরিবর্তন বা স্টক আপডেট হয়, তাহলেও অতীতে হয়ে যাওয়া বিক্রির লাভ অক্ষুণ্ণ থাকে।
3. **পূর্বের ডাটা ব্যাকফিল (Backfill):**
   - মাইগ্রেশন চলার সময় পূর্বের যে সমস্ত `sale_items`-এ ক্রয়মূল্য ছিল না, সেগুলোতে `products` টেবিল থেকে বর্তমান ক্রয়মূল্য স্বয়ংক্রিয়ভাবে আপডেট করে দেওয়া হয়েছে:
   ```sql
   UPDATE sale_items si
   INNER JOIN products p ON si.product_id = p.id
   SET si.purchase_price = p.purchase_price
   WHERE si.purchase_price IS NULL AND p.purchase_price IS NOT NULL;
   ```

---

## ৩. মডেল ও রিলেশন (Model & Relations)

### ১. `SaleInvoice` (`app/Models/SaleInvoice.php`)
- **Fillable আপডেট:** `'discount_type'` এবং `'discount_value'` যুক্ত করা হয়েছে।
- **Casts আপডেট:** `'discount_value' => 'decimal:2'` হিসেবে কাস্ট করা হয়েছে।
- **রিলেশনসমূহ:**
  - `customer()`: `belongsTo(Customer::class)` — ইনভয়েসের ক্রেতা।
  - `employee()`: `belongsTo(User::class, 'user_id')` — বিক্রি সম্পাদনকারী কর্মী।
  - `items()`: `hasMany(SaleItem::class)` — ইনভয়েসের অধীনস্থ পণ্যসমূহ।

### ২. `SaleItem` (`app/Models/SaleItem.php`)
- **Fillable আপডেট:** `'purchase_price'` যুক্ত করা হয়েছে।
- **Casts আপডেট:** `'purchase_price' => 'decimal:2'` হিসেবে কাস্ট করা হয়েছে।
- **নতুন অ্যাক্সেসর ও মেথড:**
  - `getEffectivePurchasePriceAttribute()`: আইটেমে সংরক্ষিত snapshot ক্রয়মূল্য ফেরত দেয়; যদি কোনো কারণে খালি থাকে তবে মূল `Product` থেকে ক্রয়মূল্য সংগ্রহ করে।
  - `getProfitAttribute()`: একক আইটেমের লাইন লাভ হিসাব করে। যদি এগ্রিগেটেড কোয়েরিতে সরাসরি `'profit'` সিলেক্ট করা থাকে তবে সরাসরি তা ফেরত দেয়, অন্যথায় `line_total - (effective_purchase_price * quantity)` সূত্র প্রয়োগ করে।
- **রিলেশনসমূহ:**
  - `invoice()`: `belongsTo(SaleInvoice::class, 'sale_invoice_id')` — মূল ইনভয়েস।
  - `product()`: `belongsTo(Product::class)` — পণ্যের মূল রেকর্ড।

---

## ৪. কন্ট্রোলার ও ব্যবসায়িক লজিক (Controllers & Logic)

### ক. `SaleController` (`app/Http/Controllers/SaleController.php`)
- **ইনপুট ভ্যালিডেশন:**
  - `discount_type`: `'nullable|in:amount,percentage'`
  - `discount_value`: `'nullable|numeric|min:0'`
  - `discount_amount`: `'nullable|numeric|min:0'`
- **সার্ভার-সাইড ডিসকাউন্ট হিসাব:**
  ```php
  $discountType = $validated['discount_type'] ?? 'amount';
  $discountValue = isset($validated['discount_value']) ? round((float) $validated['discount_value'], 2) : null;

  if ($discountType === 'percentage' && $discountValue !== null) {
      // শতাংশ ডিসকাউন্টের ক্ষেত্রে সাবটোটালের ওপর শতকরা হার হিসাব হয়
      $discount = round(($subtotal * $discountValue) / 100, 2);
  } else {
      // সরাসরি টাকার ক্ষেত্রে প্রদত্ত অঙ্কই ডিসকাউন্ট হয়
      $discount = round((float) ($validated['discount_amount'] ?? $discountValue ?? 0), 2);
      $discountValue = $discount;
  }
  ```
- **ক্রয়মূল্য Snapshot সংরক্ষণ:**
  ইনভয়েস আইটেম তৈরির সময় `$product->purchase_price` রিড করে `sale_items.purchase_price`-এ সংরক্ষণ করা হয়।

### খ. `DashboardConrtoller` (`app/Http/Controllers/DashboardConrtoller.php`)
- **Yearly মেট্রিকস অন্তর্ভুক্তি:**
  - পূর্বে মেট্রিকস লুপে শুধু `['daily', 'weekly', 'monthly']` ছিল। এখন `['daily', 'weekly', 'monthly', 'yearly']` যুক্ত করা হয়েছে।
  - বাৎসরিক সীমার মধ্যে মোট বিক্রি (`revenue`), মোট ক্রয় (`purchaseTotal`), এবং কর্মচারীদের ভাউচার ও ডেবিট-ক্রেডিট ভাউচারের খরচ (`expenses`) থেকে বাৎসরিক লাভ (`$metrics['yearly']['profit']`) নিখুঁতভাবে নির্ধারিত হয়।
- **সর্বাধিক বিক্রিত পণ্যের লাভ হিসাব (`$topProducts`):**
  - `$topProducts` কোয়েরিতে বিক্রিত পরিমাণ ও মোট বিক্রয়মূল্যের পাশাপাশি প্রতিটি পণ্যের মোট লাভ (`profit`) হিসাব করা হয়েছে:
    ```php
    SUM(sale_items.line_total - (sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0))) as profit
    ```

### গ. `SalesReportController` (`app/Http/Controllers/SalesReportController.php`)
- **পণ্যভিত্তিক ক্রয়মূল্য ও লাভ হিসাব (`index` ও `export`):**
  - `sale_items`-এর সাথে `products` টেবিল লেফট জয়েন করা হয়েছে।
  - প্রতিটি পণ্যের ক্ষেত্রে:
    - **গড় বিক্রয়মূল্য:** `SUM(sale_items.line_total) / SUM(sale_items.quantity)`
    - **গড় ক্রয়মূল্য:** `SUM(sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0)) / SUM(sale_items.quantity)`
    - **মোট ক্রয়মূল্য (Cost):** `SUM(sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0))`
    - **লাভ (Profit):** `SUM(sale_items.line_total - (sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0)))`
  - সর্বমোট লাভ (`$totalProfit`) হিসাব করে ভিউতে পাঠানো হয়েছে।
  - `invoices` লিস্টে প্রতিটি ইনভয়েসের আলাদা লাভ গণনা করার জন্য `items.product` ইগার-লোড করা হয়েছে।
  - `export()` মেথডে তৈরি হওয়া CSV ফাইলেও পণ্যভিত্তিক ক্রয়মূল্য, বিক্রয়মূল্য, বিক্রিত পরিমাণ, মোট বিক্রয়, মোট ক্রয়মূল্য ও লাভ যুক্ত করা হয়েছে।

---

## ৫. রাউট ব্যবস্থাপনা (Routes)

প্রজেক্টের কোনো বিদ্যমান রাউট পরিবর্তন বা নতুন কোনো অনিয়ন্ত্রিত রাউট যোগ করা হয়নি:
- `POST /sales` (`sales.store`) -> `SaleController@store`
- `GET /sales` (`sales.index`) -> `SaleController@index`
- `GET /sales/invoice/{id}` (`sales.invoice`) -> `SaleController@invoice`
- `GET /dashboard-body` (`dashboardbody`) -> `DashboardConrtoller@dashboardbody`
- `GET /sales/report` (`sales.report`) -> `SalesReportController@index`
- `GET /sales/report/export` (`sales.report.export`) -> `SalesReportController@export`

---

## ৬. ব্লেড ভিউ ও ইন্টারফেস (Blade Views & UI)

### ক. `resources/views/backend/Sales/salesIndex.blade.php`
- **Invoice & Payment সেকশনের রেসপনসিভনেস ও পাশাপাশি ডিসকাউন্ট ফিল্ড:**
  - `col-12 col-lg-7 col-xl-8` এবং `col-12 col-lg-5 col-xl-4` গ্রিড ব্যবহারের মাধ্যমে মোবাইল, ট্যাবলেট ও ডেক্সটপ সব ডিভাইসে ইনভয়েস ও পেমেন্ট সেকশন পূর্ণ রেসপনসিভ করা হয়েছে।
  - `Discount Type` এবং `Discount Amount (৳)` উভয় ইনপুট বক্সকে `row g-2` এবং `col-6` ব্যবহারের মাধ্যমে নিখুঁতভাবে পাশাপাশি (beside each other) স্থাপন করা হয়েছে।
  - `VAT (%)` এবং `Amount Received` ফিল্ড দুটিকেও সুষমভাবে পাশাপাশি রাখা হয়েছে।
  - ড্রপডাউনে **Direct Amount (৳)** এবং **Percentage (%)** অপশন রয়েছে। ড্রপডাউন বদলালে লেবেল ও প্রতীক (`৳` বা `%`) পরিবর্তন হয় এবং জাভাস্ক্রিপ্ট দিয়ে সাবটোটালের ওপর ডিসকাউন্টের প্রকৃত টাকা রিয়েল-টাইমে হিসাব হয়।

### খ. `resources/views/backend/Sales/salesInvoice.blade.php`
- ইনভয়েসের রসিদে ডিসকাউন্টের লাইনে যদি শতকরা হারে ডিসকাউন্ট নেওয়া হয়ে থাকে, তবে তার হার পরিষ্কারভাবে দেখায় (যেমন: `Discount (10%): - ৳500.00`)।

### গ. `resources/views/dashboard/dashboard-body.blade.php`
- **কার্ডের টাকার অংক রাউন্ডিং (Rounded Amounts):**
  - ড্যাশবোর্ডের সমস্ত কার্ডে টাকার পরিমাণ পূর্ণ সংখ্যায় রাউন্ড করে (`number_format(round(...))`) প্রদর্শন করা হয়েছে (দশমিক ভগ্নাংশ মুক্ত)।
- **প্রফিট কার্ডে মাসিক তথ্য যুক্তকরণ:**
  - Net Profit কার্ডের সাবটাইটেলে দৈনিক ও সাপ্তাহিক তথ্যের সাথে **মাসিক লাভ (`Monthly ৳...`)** যুক্ত করা হয়েছে:
    `Daily ৳XX | Weekly ৳XX | Monthly ৳XX`
- **অন্যান্য কার্ডে মাসিক ও দৈনিক/সাপ্তাহিক তথ্য:**
  - বড় ফন্টে মাসিক মোট এবং ছোট ফন্টে দৈনিক ও সাপ্তাহিক মান রাউন্ডেড আকারে দেখানো হচ্ছে।
- **কার্ডের আইকন সেন্টারিং:**
  - প্রতিটি ড্যাশবোর্ড কার্ডের আইকনকে কার্ডের মাঝে (`mx-auto mb-2`) স্থাপন করা হয়েছে।
- **Top Selling Products টেবিলে Profit কলাম:**
  - ড্যাশবোর্ডের সর্বাধিক বিক্রিত পণ্য টেবিলে নতুন **Profit** কলাম যুক্ত করা হয়েছে (`৳{{ number_format(round($product->profit)) }}`), যাতে কোন পণ্য থেকে কত লাভ হয়েছে তা সরাসরি দেখা যায়।

### ঘ. `resources/views/backend/Sales/salesReport.blade.php`
- **সামারি কার্ডসমূহ:**
  - `Total Sale Amount`
  - `Total Profit` (সবুজ রঙে মোট লাভ)
  - `Total Due`
  - `Total Sales (Invoices)`
- **Product-wise Sales & Profit টেবিল:**
  - `#`, `Product Name`, `Purchase Price` (ক্রয়মূল্য), `Selling Price` (গড় বিক্রয়মূল্য), `Quantity Sold` (পরিমাণ), `Total Sales` (মোট বিক্রয়মূল্য), `Total Cost` (মোট ক্রয়মূল্য), `Profit` (লাভ)।
  - টেবিলের নিচে `<tfoot>` অংশে প্রতিটি কলামের মোট যোগফল প্রদর্শন।
- **Sales Details টেবিল:**
  - প্রতিটি ইনভয়েসের জন্য নতুন **Profit** কলাম যোগ করা হয়েছে।
- **প্রিন্ট স্টাইল:**
  - ৪টি সামারি কার্ড প্রিন্টের পাতায় ২৫% প্রস্থে সুন্দরভাবে ফিট হয়।

---
*ডকুমেন্টটি সফলভাবে তৈরি ও সংরক্ষিত হয়েছে।*

---

## ৭. ব্যবহারকারী কার্যকলাপ লগ (User Activity Log)

### উদ্দেশ্য
সিস্টেমে কোন ব্যবহারকারী কখন লগইন/লগআউট করেছেন এবং কোন রেকর্ড তৈরি, পরিবর্তন বা মুছে ফেলেছেন তা সংরক্ষণ ও খোঁজার জন্য এই মডিউল যোগ করা হয়েছে। লগ তালিকা তারিখের সীমা, ব্যবহারকারী এবং কার্যকলাপের ধরন দিয়ে ফিল্টার করা যায়।

### Migration: `database/migrations/2026_10_17_000001_create_user_activity_logs_table.php`
- `user_activity_logs` টেবিলে ব্যবহারকারীর ID, নাম ও ইমেইলের snapshot রাখা হয়। ইউজার মুছে গেলেও পুরোনো লগ অক্ষত রাখার জন্য `user_id`-এ foreign key cascade ব্যবহার করা হয়নি।
- `event`-এ `login`, `logout`, `create`, `update` বা `delete` রাখা হয়। `description`-এ কাজের সংক্ষিপ্ত বিবরণ এবং `subject_type`/`subject_id`-এ লক্ষ্য রেকর্ডের ধরন ও ID থাকে।
- `properties` JSON-এ password, token ও method spoofing field বাদ দিয়ে request-এর প্রাসঙ্গিক তথ্য রাখা হয়। Login/logout-এর জন্য guard, আর প্রতিটি রেকর্ডে IP, user-agent ও timestamp রাখা হয়।
- `event` এবং `created_at`-ভিত্তিক index দিয়ে ফিল্টার/সাম্প্রতিক লগ query সহায়তা করা হয়। `down()` rollback-এ শুধু নতুন audit table সরায়।

### Model ও Relation: `app/Models/UserActivityLog.php`
- `properties` array হিসেবে cast হয়; `user()` relation `belongsTo(User::class)`। ইউজার মুছে গেলেও snapshot নাম/ইমেইল দিয়ে ইতিহাস দেখা যায়।
- `scopeForDateRange()` শুরু ও শেষের তারিখের inclusive filter তৈরি করে।

### Logging middleware: `app/Http/Middleware/AuditUserActivity.php`
- `bootstrap/app.php`-এর web middleware group-এ যুক্ত হওয়ায় web request-এর শেষে কাজ করে। সফল POST/PUT/PATCH/DELETE এবং বিদ্যমান GET-ভিত্তিক destroy/status-update route লগ হয়।
- HTTP error ও validation error-সহ redirect লগ হয় না। Login/logout path বাদ থাকে, কারণ সেগুলো Laravel-এর Auth event থেকে একবারই লগ করা হয়।
- Route name থেকে Product, Sale, Customer/Supplier ledger, Role, User, Voucher ইত্যাদি subject চেনা হয়। Sale request-এর product ID-র পাশাপাশি product name এবং role permission ID-র পাশাপাশি permission name-ও properties-এ যোগ হয়।
- Password, token এবং remember field সংরক্ষণ করা হয় না।

### Login/logout event: `app/Providers/AppServiceProvider.php`
Laravel-এর `Login` ও `Logout` event-এ ব্যবহারকারীর ID/name/email snapshot, guard, IP এবং user-agent-সহ লগ তৈরি হয়। ফলে standard `/login`, `/logout` এবং প্রকল্পের custom logout action-ও ধরা পড়ে।

### Controller: `app/Http/Controllers/UserActivityLogController.php`
- `index()` `from`, `to`, `user_id` এবং `event` filter validate করে, `UserActivityLog` query-তে প্রয়োগ করে এবং ২৫টি করে pagination দেয়।
- User filter-এ activity history-র actor snapshot ব্যবহার হয়; তাই user delete হলেও তার ID/name দিয়ে আগের লগ filter করা যায়।

### Route ও Permission: `routes/web.php`
- `GET /user-activity-logs` (`user-activity-logs.index`) authenticated ও verified ব্যবহারকারীর জন্য এবং `reports.all_access` permission-এ সীমাবদ্ধ।
- নতুন permission বা বিদ্যমান permission পরিবর্তন করা হয়নি। Dashboard-এর Reports menu-তে একই permission-ধারী ব্যবহারকারীদের জন্য link যোগ করা হয়েছে।

### Blade: `resources/views/backend/user-activity/index.blade.php`
- তারিখ থেকে/পর্যন্ত, user এবং activity filter form; timestamp, actor, activity, description, subject ID, IP address এবং প্রয়োজনে expandable JSON details-এর তালিকা দেখায়।
- Filter query pagination link-এ বজায় থাকে; Reset link সব filter পরিষ্কার করে।
- User dropdown-এ `users` table-এর সব বর্তমান user/employee-এর নাম ও ইমেইল দেখায়, activity না থাকলেও। পাশাপাশি delete হওয়া user-এর audit snapshot-ও দেখায়। user নির্বাচন করলে ID দিয়ে history filter হয় এবং শিরোনামে নির্বাচিত নাম/ইমেইল দেখা যায়।
- `Print` এবং `Save as PDF` browser print dialog খোলে; PDF পেতে print destination থেকে `Save as PDF` বেছে নিতে হয়। Print layout-এ navigation, filter ও pagination বাদ দিয়ে selected user, date range এবং বর্তমান পাতার activity rows রাখা হয়।

### Activity-র পরিধি
এই সংস্করণে অ্যাপের web form/API-ধাঁচের route দিয়ে সম্পন্ন write action এবং login/logout রেকর্ড হয়। Background job, সরাসরি database/console পরিবর্তন এবং failed validation/write request এই HTTP audit middleware-এর আওতায় পড়ে না।
