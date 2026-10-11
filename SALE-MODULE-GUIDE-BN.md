# Sales Module — বাংলা শেখার গাইড

এই ডকুমেন্টে বর্তমান Sales module-এর Blade, Controller, Model, Route এবং Migration-এ ব্যবহৃত logic ও function কীভাবে একসাথে কাজ করে তা ব্যাখ্যা করা হয়েছে।

> **এই কাজের সীমা:** এই পরিবর্তনে শুধু এই Markdown ডকুমেন্ট যোগ করা হয়েছে। অ্যাপের PHP, Blade, route বা database schema-তে কোনো পরিবর্তন করা হয়নি।

## কোন ফাইল কোন কাজ করে

| ফাইল | দায়িত্ব |
|---|---|
| `resources/views/backend/Sales/salesIndex.blade.php` | Sale form, একই input দিয়ে product খোঁজা, stock/price/total দেখানো, customer phone lookup, ledger preview, checkout confirmation |
| `app/Http/Controllers/SaleController.php` | Sale page দেখানো, phone দিয়ে customer খোঁজা, input validation, stock lock/check, invoice ও ledger database-এ লেখা |
| `resources/views/backend/Sales/salesInvoice.blade.php` | invoice দেখানো/print করা এবং print-এর সময় dashboard/notification লুকানো |
| `app/Models/SaleInvoice.php` | `sale_invoices` টেবিলের invoice ও তার সম্পর্ক |
| `app/Models/SaleItem.php` | invoice-এর প্রতিটি পণ্যের row এবং invoice/product সম্পর্ক |
| `app/Models/Customer.php` | Customer ও তার ledger-এর সম্পর্ক |
| `app/Models/CustomerLedger.php` | Customer-এর sale/payment ledger row ও কর্মী/receiver-এর সম্পর্ক |
| `routes/web.php` | Sales page, customer lookup, sale submit এবং invoice URL |
| `database/migrations/2026_10_11_000003_upgrade_sales_tables.php` | পুরোনো খালি sales table-এ প্রয়োজনীয় invoice/item/ledger columns যোগ করা |
| `database/migrations/2026_10_11_000001_create_customer_ledgers_table.php` | Customer ledger table তৈরি করা |

## সম্পূর্ণ কাজের ধাপ

1. কর্মী লগ-ইন করে **Sales → Create Sale** খুললে `sales.index` route চালু হয়।
2. Controller product catalog ও সাম্প্রতিক invoice নিয়ে sale Blade-এ পাঠায়।
3. কর্মী product name অথবা product code/SKU লিখে suggestion থেকে product নির্বাচন করেন।
4. Browser নির্বাচিত পণ্যের price/stock দিয়ে line total ও invoice preview হিসাব করে।
5. Phone number লিখলে browser customer lookup route-এ request পাঠায়।
6. আগে থেকে customer থাকলে নাম, সাম্প্রতিক ledger এবং outstanding due দেখানো হয়।
7. নতুন phone হলে checkout-এর সময় browser customer details save করবেন কি না জিজ্ঞেস করে।
8. Checkout request Controller-এ গেলে server আবার input ও stock যাচাই করে; browser-এর total-কে বিশ্বাস করে না।
9. Database transaction-এর মধ্যে invoice, sale item, stock পরিবর্তন এবং থাকলে customer ledger লেখা হয়।
10. Invoice page খোলে; নতুন sale-এর পর browser print dialog দেখায়। Print-এ dashboard notification/header লুকানো থাকে।

## ১. Route: URL-কে Controller function-এর সাথে যুক্ত করা

`routes/web.php`-এর Sales routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/customer-lookup', [SaleController::class, 'customerByPhone'])->name('sales.customer.lookup');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/invoice/{id}', [SaleController::class, 'invoice'])->name('sales.invoice');
});
```

| কোডের অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `middleware(['auth', 'verified'])` | শুধু login করা এবং verified ব্যবহারকারী এই route-গুলো ব্যবহার করতে পারেন। |
| `GET /sales` | Sale form খোলে; `SaleController::index()` চলে। |
| `sales.index` | Blade-এ URL সরাসরি না লিখে `route('sales.index')` দিয়ে URL বানানোর নাম। |
| `GET /sales/customer-lookup` | Phone পাঠিয়ে customer খোঁজে; JSON response দেয়। |
| `sales.customer.lookup` | JavaScript এই named route-এ `fetch()` request করে। |
| `POST /sales` | Sale form submit হলে invoice তৈরির কাজ চলে। |
| `sales.store` | Form-এর `action`-এ ব্যবহৃত named route। |
| `GET /sales/invoice/{id}` | নির্দিষ্ট invoice ID-এর invoice screen খোলে। |
| `{id}` | URL থেকে invoice-এর database ID নেয়। |

## ২. Sale form Blade: product নির্বাচন ও customer তথ্য

### Blade directive ও form-এর ভিত্তি

`resources/views/backend/Sales/salesIndex.blade.php`:

| Blade/HTML | বাংলা ব্যাখ্যা |
|---|---|
| `@extends('dashboard.dashboard')` | Dashboard-এর common layout ব্যবহার করে; এতে sidebar, header ও asset থাকে। |
| `@section('title', 'Create Sale')` | Browser/page title-এর জন্য section value দেয়। |
| `@section('content')` | Dashboard layout-এর content অংশ এখানে শুরু হয়। |
| `@csrf` | Laravel-এর CSRF protection token form-এর সাথে পাঠায়। |
| `<form action="{{ route('sales.store') }}" method="POST">` | Sale data POST করে `SaleController::store()`-এ পাঠায়। |
| `save_customer` hidden input | `yes` হলে নতুন customer save করতে হবে, `no` হলে শুধু phone-সহ invoice; JavaScript checkout-এর আগে value বসায়। |
| `@if ($errors->any())` | Server validation ব্যর্থ হলে form-এর ওপরে error দেখায়। |
| `@foreach ($errors->all() as $error)` | প্রতিটি validation error আলাদা bullet হিসেবে দেখায়। |

### Product name/code দিয়ে একই input-এ search

Product row-তে দৃশ্যমান search input এবং browser-এ লুকানো product ID select একসাথে কাজ করে:

```blade
<input type="search" class="form-control product-search"
    list="saleProductOptions" placeholder="Search product name or code"
    autocomplete="off" required>
<select name="items[{{ $index }}][product_id]" class="product-select d-none">
    ...
</select>
```

| লাইন/attribute | বাংলা ব্যাখ্যা |
|---|---|
| `type="search"` | Product খোঁজার জন্য দৃশ্যমান text input। |
| `class="product-search"` | JavaScript এই class দিয়ে search box শনাক্ত করে। |
| `list="saleProductOptions"` | একই নামের `<datalist>` থেকে browser suggestion দেখায়। |
| `placeholder="Search product name or code"` | Product name বা code লিখতে বলে। |
| `required` | Product না বেছে খালি input দিয়ে checkout আটকে দেয়। |
| `name="items[...][product_id]"` | Form-এ product-এর database ID পাঠায়; নাম নয়। |
| `class="product-select d-none"` | ID ও `data-*` তথ্য রাখে, কিন্তু আলাদা dropdown হিসেবে UI-তে দেখা যায় না। |

প্রতিটি product option-এ:

| Attribute/value | বাংলা ব্যাখ্যা |
|---|---|
| `value="{{ $product->id }}"` | Database-এ product-এর primary key, যা server-এ পাঠানো হয়। |
| `data-price="{{ $product->price }}"` | JavaScript-কে unit price জানায়। |
| `data-stock="{{ $product->unit }}"` | JavaScript-কে বর্তমানে থাকা stock জানায়। |
| `data-name="..."` | ছোট হাতের product name, exact search match করার জন্য। |
| `data-code="..."` | `PRD-` prefix ও zero-padded ID দিয়ে তৈরি code; যেমন `PRD-00001`। |
| `data-label="..."` | Product name এবং code মিলিয়ে তৈরি পূর্ণ suggestion label। |
| `<datalist>` | Product-এর name/SKU suggestion দেয়; suggestion নির্বাচন করলে একই input-এ label বসে। |

> **Product code সম্পর্কে:** Product schema-তে আলাদা `product_code` column নেই। বর্তমান screen-এ product ID থেকে `PRD-00001` ধরনের SKU দেখানো হয়। তাই এই search generated SKU-ও খোঁজে; এটি barcode বা আলাদা সংরক্ষিত code নয়।

### Sale form-এর product তথ্য

| UI field/class | বাংলা ব্যাখ্যা |
|---|---|
| `.stock-cell` | নির্বাচিত product-এর “বর্তমান স্টক / sale cart-এ চাওয়া quantity বাদ দিলে অবশিষ্ট” preview দেখায়। |
| `.quantity-input` | কত unit বিক্রি হবে তা নেয়; JavaScript stock অনুযায়ী `max` এবং error styling বসায়। |
| `.price-cell` | নির্বাচিত product-এর database price দেখায়। |
| `.line-total-cell` | ঐ row-এর `price × quantity` দেখায়। |
| `#subtotalText` | সব line total-এর যোগফল দেখায়। |
| `#discount_amount` | User-এর লেখা fixed discount amount নেয়; শতাংশ নয়। |
| `#vat_amount` | User-এর লেখা VAT amount নেয়; percentage calculation নয়। |
| `#amount_paid` | এই invoice-এ এখন কত টাকা পাওয়া হলো তা নেয়। |
| `#payment_method` | Cash, Bank, Nagad, bKash, Rocket বা Upay নির্বাচন করে। |
| `#include_previous_due` | Existing customer-এর আগের বকেয়া এই invoice total-এ যোগ করবে কি না নির্ধারণ করে। |
| `#invoiceTotalText` | Sale total এবং checkbox নির্বাচিত হলে আগের due মিলিয়ে browser preview দেখায়। |
| `#balanceDueText` | Preview total থেকে amount received বাদ দিয়ে এই invoice-এর due দেখায়। |

### Customer phone ও ledger UI

| UI অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `#customer_phone` | Customer খোঁজার মূল key; temporary customer-এর ক্ষেত্রে invoice-এ এই phone থাকে। |
| `#lookupCustomer` | হাতে click করে lookup চালানোর button। |
| `#customerDisplayName` | Existing customer পেলে database-এর নাম দেখায়; `readonly`, তাই এখানে বদলানো যায় না। |
| `#customerLedgerPanel` | Customer পাওয়া গেলে ledger table, due এবং checkbox দেখায়; না পেলে hidden থাকে। |
| `#customerDue` | Database ledger থেকে গণনা করা মোট outstanding due দেখায়। |
| `#customerLedgerRows` | Lookup-এর JSON ledger rows JavaScript দিয়ে এখানে বসানো হয়। |
| `#newCustomerDetails` | Phone database-এ না থাকলে optional name/email/address field দেখায়। |
| `#customer_name` | Save করার সিদ্ধান্তে নতুন customer record তৈরি করতে নাম লাগে। |
| `#save_customer` | Checkout confirmation-এর Yes/No server-এ পাঠায়। |

## ৩. Sale form JavaScript: প্রতিটি function-এর ব্যাখ্যা

### `DOMContentLoaded`

```javascript
document.addEventListener('DOMContentLoaded', function () {
    // ...
});
```

| লাইন | বাংলা ব্যাখ্যা |
|---|---|
| `document.addEventListener(...)` | Browser-কে বলে HTML তৈরি শেষ হলে function চালাতে। |
| `DOMContentLoaded` | Form-এর input/element খোঁজার আগে DOM তৈরি হওয়া নিশ্চিত করে। |
| `const form = ...` | Checkout form-টিকে JavaScript variable-এ রাখে। |
| `const itemsBody = ...` | সব product row-র parent table body ধরে রাখে। |
| `customerPhone`, `customerName` | Phone input এবং নাম দেখানো input ধরে রাখে। |
| `lookupMessage` | Lookup চলছে/ফলাফল পাওয়া গেছে—এমন status message দেখায়। |
| `ledgerPanel`, `newCustomerDetails` | Existing customer ledger অথবা নতুন customer detail panel দেখানো/লুকানোর জন্য element reference। |
| `saveCustomer` | Hidden save choice element ধরে রাখে। |
| `customerFound` | সর্বশেষ lookup-এ matching customer পাওয়া গেছে কি না রাখে। |
| `lookupTimer` | Phone টাইপের সময় debounce timer বাতিল/পরিবর্তন করতে লাগে। |
| `lastLookedUpPhone` | কোন phone সর্বশেষ সফলভাবে lookup হয়েছে তা রাখে; submit-এর আগে stale result ঠেকায়। |
| `rowIndex` গণনা | পুরোনো input row index দেখে নতুন row-কে unique `items[n]` নাম দেয়। |
| `money(amount)` | Amount-কে `৳` ও দুই decimal-সহ string বানায়। |

### `updateTotals()` — stock, price এবং line total

| Logic | বাংলা ব্যাখ্যা |
|---|---|
| `rows = ...sale-item-row` | বর্তমানে থাকা সব sale row একসাথে নেয়। |
| `requestedByProduct = new Map()` | একই product একাধিক row-তে থাকলে মোট চাওয়া quantity যোগ করে। |
| প্রথম `rows.forEach(...)` | প্রতিটি selected product-এর quantity বের করে map-এ যোগ করে। |
| `option.dataset.price` | Product option থেকে unit price পড়ে। |
| `option.dataset.stock` | Product option থেকে current stock পড়ে। |
| `totalRequested` | একই product-এর সব row মিলে মোট কত stock বিক্রি চাওয়া হচ্ছে। |
| `requestedByOtherRows` | বর্তমান row ছাড়া একই product-এর অন্য row-গুলো কত stock চায়। |
| `quantityAvailableForThisRow` | অন্য row-গুলো বাদ দিয়ে এই row-তে সর্বোচ্চ কত quantity দেওয়া যায়। |
| `stockRemaining` | সব cart row মিলিয়ে sale-এর পর আনুমানিক কত stock থাকবে। |
| `.stock-cell` update | নির্বাচিত product-এর জন্য `stockRemaining / stock` দেখায়; product না থাকলে dash। |
| `.price-cell` update | নির্বাচিত product-এর unit price দেখায়। |
| `lineTotal = price * quantity` | একটি row-এর বিক্রয়মূল্য গণনা করে। |
| `.line-total-cell` update | গণনা করা row amount দেখায়। |
| `quantityInput.max = ...` | Browser input-এ এই row-এর জন্য stock-সীমা বসায়। |
| `quantity > quantityAvailableForThisRow` | Stock ছাড়ালে field invalid দেখায় এবং warning text জমা করে। |
| Product নির্বাচন না থাকলে | Quantity max সরায় এবং invalid style পরিষ্কার করে। |
| `subtotal += lineTotal` | সব row-এর line total যোগ করে। |
| `#lowStockAlert` | শুধু cart-এর requested quantity available stock ছাড়ালে warning/error দেখায়। `3 left after sale` ধরনের informational notice দেখায় না। |
| Discount/VAT/previous due পড়া | Form থেকে user input ও checkbox-এর মান নেয়। |
| `total = Math.max(0, subtotal - discount) + vat + previousDue` | Browser preview-এর invoice total হিসাব করে। |
| `balanceDueText` | Paid amount বাদ দিয়ে balance due preview দেখায়। |

> Browser-এর হিসাব শুধুই live preview এবং UX সহায়তা। প্রকৃত stock, discount limit ও invoice হিসাব Controller server-এ পুনরায় করে।

### `syncProductSearch(row)` — search input-কে product ID-তে মেলানো

| লাইন/ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `searchInput` | Row-এর দৃশ্যমান একই product search box। |
| `select` | Row-এর hidden product select, যেখানে product ID এবং price/stock থাকে। |
| `search = ...toLowerCase()` | Input-এর অতিরিক্ত whitespace বাদ দিয়ে lowercase করে। |
| `Array.from(select.options).find(...)` | Product option-এর generated label, name, বা code-এর সাথে input exact match খোঁজে। |
| `select.value = option ? option.value : ''` | Match পেলে product ID select করে; match না পেলে ID ফাঁকা রাখে। |
| `setCustomValidity(...)` | Suggestion-এর সাথে exact match না হলে form submit-এ browser error দেখায়। |

Search input-এ suggestion নির্বাচন করলে product label পুরোটা থাকে, যেমন `Pendrive — PRD-00001`; এই পূর্ণ label-ও matching data-তে রাখা হয়েছে। Name বা SKU একা exact লিখলেও match হয়।

### `addRow()`

| লাইন/ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| প্রথম row clone | নতুন row বানাতে একই UI structure-এর copy নেয়। |
| Search input clear | নতুন row-তে আগের product text কপি হতে দেয় না। |
| নতুন `items[index][product_id]` name | Server-এ পৃথক row-এর product ID পাঠায়। |
| Hidden select clear | আগের row-এর product ID ভুল করে নতুন row-তে থাকে না। |
| stock/price/line total reset | নতুন row শুরুতে dash/zero দেখায়। |
| quantity name/value | নতুন row-র আলাদা quantity input বানায় এবং default 1 দেয়। |
| `itemsBody.appendChild(row)` | Table-এ row যোগ করে। |
| `rowIndex++` | পরবর্তী row-র জন্য আরেকটি unique index রাখে। |
| `updateTotals()` | নতুন row যোগ হওয়ার পর totals/stock আবার গণনা করে। |

### `lookupCustomer()` — phone থেকে customer ও ledger আনা

| ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| Phone trim | শুরু/শেষের ফাঁকা জায়গা বাদ দেয়। |
| Phone ফাঁকা হলে | Status message দেখিয়ে lookup ব্যর্থ হিসেবে ফেরে। |
| `new URL(route(...))` | Named route দিয়ে lookup URL বানায়। |
| `url.searchParams.set('phone', phone)` | Phone-কে query parameter হিসেবে URL-এ যোগ করে। |
| `fetch(...Accept: application/json)` | Laravel route-এ asynchronous GET request পাঠায়। |
| `response.ok` পরীক্ষা | HTTP error হলে lookup failure message দেখায়। |
| `response.json()` | Server-এর JSON response JavaScript object-এ রূপান্তর করে। |
| `customerFound = result.found` | Server customer পেয়েছে কি না state-এ রাখে। |
| Panel toggle | Customer পেলে ledger দেখায়; না পেলে নতুন customer info form দেখায়। |
| Ledger rows clear | আগের phone lookup-এর rows নতুন ফলাফলের সাথে মিশে যেতে দেয় না। |
| Name input update | Customer পাওয়া গেলে database name দেখায়। |
| Due label update | Database-calculated outstanding due এবং JavaScript value বসায়। |
| `createElement`/`textContent` | Server data HTML string হিসেবে inject না করে safe text cell বানায়। |
| Ledger empty হলে | “No previous ledger entries” row দেখায়। |
| Lookup শেষে `updateTotals()` | Previous due checkbox/total preview-কে নতুন customer অনুযায়ী আপডেট করে। |
| `catch` | Lookup request error হলে তা status message-এ স্পষ্ট দেখায়। |

### Phone input events

| Event | বাংলা ব্যাখ্যা |
|---|---|
| Find button-এর `click` | User চাইলে button চেপে সাথে সাথে lookup করে। |
| Phone-এর `change` | Phone field থেকে বের হলে lookup চালায়। |
| Phone-এর `input` | টাইপের সময় আগের found result ও ledger panel reset করে; 350ms পরে lookup চালায়, যাতে প্রতিটি keypress-এ request না যায়। |
| Phone-এর `keydown` + `Enter` | Enter চাপলে form submit না করে customer lookup চালায়। |

### Product row event handler

| Event/handler | বাংলা ব্যাখ্যা |
|---|---|
| `change` listener | Search input বদলালে product ID sync করে; তারপর totals আপডেট করে। |
| `input` listener | Quantity টাইপের সাথে সাথে totals এবং stock preview আপডেট করে। |
| Search input `input` | Suggestion থেকে নির্বাচিত/লেখা value hidden ID-এর সাথে মেলায়। |
| Remove button `click` | একাধিক row থাকলে নির্দিষ্ট row মুছে; একটিই থাকলে সেটি reset করে। |
| Discount/VAT/paid/due checkbox events | মান পরিবর্তন হলেই invoice preview নতুন করে গণনা করে। |

### Submit confirmation

| ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `form.addEventListener('submit', async ...)` | Checkout submit-এর আগে validation/confirmation চালায়। |
| `saveCustomer.dataset.confirmed` | Confirmation একবার হয়ে গেলে পুনরায় dialog দেখানো বন্ধ করে। |
| `event.preventDefault()` | Confirmation/lookup শেষ হওয়ার আগে form POST থামায়। |
| `updateTotals()` | Submit-এর ঠিক আগে visible preview/stock warning হালনাগাদ করে। |
| Incomplete product row check | Search text থাকলেও product option resolve না হলে submit থামায় ও browser message দেখায়। |
| Invalid quantity check | Stock অতিক্রম করা quantity input-এ focus করে browser validation দেখায়। |
| Last looked-up phone check | Phone বদলে গেলে submit-এর আগে নতুন lookup শেষ করে; পুরোনো customer info ভুলে ব্যবহার ঠেকায়। |
| Existing customer হলে | `save_customer=no` রেখে submit করে; পুরোনো customer আবার নতুন করে তৈরি করে না। |
| নতুন customer হলে `window.confirm(...)` | Browser Yes/Cancel dialog দেখায়—Yes মানে save, Cancel মানে customer detail save না করা। |
| Yes হলে name পরীক্ষা | Name না থাকলে alert দেখিয়ে name field-এ focus দেয়; নতুন customer save-এর জন্য নাম বাধ্যতামূলক। |
| Yes হলে hidden value `yes` | Controller নতুন temporary customer row তৈরি করতে পারে। |
| Cancel হলে hidden value `no` | শুধু phone-সহ invoice হবে; customer row/ledger তৈরি হবে না। |
| `form.requestSubmit()` | Confirmation শেষ হওয়ার পরে স্বাভাবিক HTML validationসহ form submit করে। |

## ৪. Controller: server-side logic

`app/Http/Controllers/SaleController.php`

### Imports ও class constant

| কোড | বাংলা ব্যাখ্যা |
|---|---|
| `use App\Models\...` | Controller-এ Customer, Product, SaleInvoice, SaleItem ও CustomerLedger model ব্যবহার করে। |
| `Auth` | বর্তমান login করা user-এর ID পাওয়ার জন্য। |
| `DB` | একাধিক database write-কে একটি transaction-এ রাখার জন্য। |
| `Str` | Invoice number-এর random অংশ/দীর্ঘ text limit করার জন্য। |
| `ValidationException` | Business rule না মিললে validation-style error ফেরানোর জন্য। |
| `PAYMENT_METHODS` | অনুমোদিত method: `cash`, `bank`, `nagad`, `bkash`, `rocket`, `upay`। |

### `index()`

| লাইন/ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `Product::query()->orderBy('productName')->get()` | Product-গুলো নাম অনুযায়ী সাজিয়ে form-এ পাঠায়। |
| `SaleInvoice::with('employee')` | সাম্প্রতিক invoice আনার সময় employee সম্পর্কও এক query group-এ load করে। |
| `latest('invoice_date')->limit(10)` | সর্বশেষ তারিখের 10টি invoice নেয়। |
| `view(..., compact(...))` | Products/invoices variables-কে `salesIndex` Blade-এ পাঠায়। |

### `customerByPhone(Request $request)`

| লাইন/ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `$request->validate(...)` | Phone থাকা ও সর্বোচ্চ দৈর্ঘ্য 30 character হওয়া নিশ্চিত করে। |
| `Customer::with([...ledgers...])` | Customer-এর সাথে সাম্প্রতিক ledger-ও load করে। |
| `latest('sale_date')->limit(10)` | UI preview-এর জন্য সর্বশেষ 10টি ledger row নেয়। |
| `where('phone', trim(...))->first()` | Phone number exact match করে customer খোঁজে। |
| Customer না পেলে `found: false` | Browser-কে জানায় যে এই phone-এ registered customer নেই। |
| `opening_due + sum(total_amount) - sum(payment_amount)` | পুরোনো balance এবং ledger entries মিলিয়ে outstanding due গণনা করে। |
| `max(round($due, 2), 0)` | দুই decimal-এ round করে negative due দেখানো বন্ধ করে। |
| JSON `customer` fields | ID/name/phone/email/address/due এবং ledger summary browser-এ পাঠায়। |
| `sale_date?->format(...)` | Sale date থাকলে `YYYY-MM-DD` বানায়; null হলে error ছাড়াই null দেয়। |

### `store(Request $request)` — validation

| Field/rule | বাংলা ব্যাখ্যা |
|---|---|
| `customer_phone` | Invoice-এর জন্য phone বাধ্যতামূলক; সর্বোচ্চ 30 character। |
| `save_customer` | শুধু `yes` বা `no` অনুমোদিত। |
| `customer_name` | Save choice `yes` হলে নাম বাধ্যতামূলক। |
| `customer_email` | দিলে valid email হতে হবে। |
| `include_previous_due` | Checkbox-এর optional boolean value। |
| `discount_amount`, `vat_amount` | Optional non-negative numeric fixed amounts। |
| `amount_paid` | Optional non-negative numeric payment। |
| `payment_method` | দেওয়া হলে অনুমোদিত list-এর মধ্যে হতে হবে। |
| `items` | অন্তত একটি product row লাগবে। |
| `items.*.product_id` | প্রতিটি row-র product ID integer এবং products table-এ থাকতে হবে। |
| `items.*.quantity` | প্রতিটি row-তে অন্তত 1 quantity integer হতে হবে। |

Validation শেষে Controller phone trim করে এবং amount paid-কে দুই decimal-এ round করে। টাকা পাওয়া গেলেও method নির্বাচন না থাকলে `ValidationException` দেখায়।

### `DB::transaction(...)` — atomic database কাজ

> Transaction-এর মধ্যে invoice/item/stock/ledger-এর যেকোনো write ব্যর্থ হলে আগের write-গুলোও rollback হয়; ফলে অর্ধেক sale জমা থাকে না।

| লাইন/ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `collect($validated['items'])` | Submitted product row-গুলো Laravel Collection-এ নেয়। |
| `groupBy('product_id')` | একই product একাধিক row-তে থাকলে এক group করে। |
| `sum(quantity)` | ঐ product-এর মোট requested quantity গণনা করে। |
| `whereIn('id', ...)` | প্রয়োজনীয় product rows database থেকে নেয়। |
| `orderBy('id')` | একই ক্রমে row lock নিয়ে concurrent sale deadlock ঝুঁকি কমায়। |
| `lockForUpdate()` | Checkout শেষ না হওয়া পর্যন্ত অন্য transaction যেন একই stock নিয়ে একসাথে sale করতে না পারে। |
| `keyBy('id')` | Product collection-কে ID দিয়ে দ্রুত lookup করার উপযোগী করে। |
| Stock loop | প্রতিটি product আছে কি না এবং `unit` requested quantity-র সমান/বেশি কি না যাচাই করে। |
| Stock কম হলে exception | Transaction বাতিল করে “does not have enough stock” error দেয়। |
| Customer lookup | Phone দিয়ে existing customer খোঁজে; customer row transaction চলাকালে lock করে। |
| Customer save branch | Customer না থাকলে এবং `save_customer=yes` হলে temporary customer create করে। |
| `previousDue` | Customer থাকলে opening due + ledger sale − ledger payment; না থাকলে zero। |
| `includePreviousDue` | Customer আছে এবং checkbox tick থাকলে আগের due invoice total-এ ঢোকে। |
| `Auth::id()` | এই sale submit করা login user-এর ID নেয়। |

### Server-side line total ও invoice হিসাব

| ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| Product lookup | আগে lock করা product collection থেকে ID দিয়ে product নেয়। |
| Quantity cast | Input quantity-কে integer-এ রূপান্তর করে। |
| Unit price | Product database-এর `price` থেকে নেয়; browser থেকে পাঠানো price ব্যবহার করে না। |
| Line total | `unit price × quantity`; দুই decimal-এ round করা হয়। |
| Subtotal | সব line total যোগ করে এবং round করে। |
| Discount | User input থেকে fixed amount নেয়। |
| Discount limit | Discount subtotal-এর বেশি হলে checkout বাতিল করে। |
| VAT | User input থেকে fixed VAT amount নেয়। |
| Sale amount | `subtotal - discount + VAT`। |
| Invoice total | Sale amount-এর সাথে checkbox নির্বাচিত হলে previous due যোগ করে। |
| Payment upper limit | Paid amount total-এর বেশি হলে checkout বাতিল করে। |

### Invoice create, stock কমানো, ledger লেখা

| কোড/field | বাংলা ব্যাখ্যা |
|---|---|
| `SaleInvoice::create(...)` | Invoice header `sale_invoices` table-এ সংরক্ষণ করে। |
| `invoice_no` | তারিখ/সময় ও random অংশ দিয়ে invoice number তৈরি হয়। |
| `customer_id` | Existing customer বা save করা customer-এর ID; save না করা temporary phone sale-এ null। |
| `customer_phone` | সব invoice-এই submitted phone সংরক্ষণ করে। |
| `customer_name` | Customer record থাকলে তার নাম invoice snapshot-এ রাখে; নাম save না করলে null। |
| `user_id` | Login করা employee-এর ID invoice-এ সংরক্ষণ করে। |
| `invoice_date` | Sale commit হওয়ার সময় বর্তমান date/time সংরক্ষণ করে। |
| Subtotal/discount/VAT/previous due | Invoice-এর হিসাবের আলাদা অংশ সংরক্ষণ করে। |
| `total_amount` | Invoice-এ payable মোট amount। |
| `amount_paid`, `due_amount` | কত পাওয়া হয়েছে ও কত বাকি তা রাখে। |
| `payment_method` | টাকা পাওয়া থাকলেই নির্বাচিত method সংরক্ষণ হয়; unpaid হলে null। |
| `SaleItem::create(...)` | প্রতিটি product sale-এর জন্য আলাদা `sale_items` row করে। |
| `sale_invoice_id` | Sale item-কে invoice-এর সাথে যুক্ত করে। |
| `product_id` | Original product reference রাখে। |
| `product_name` | Product name-এর snapshot রাখে, যাতে ভবিষ্যতে product মুছে গেলেও invoice পড়া যায়। |
| `unit_price`, `line_total`, `quantity` | বিক্রির সময়কার quantity ও price সংরক্ষণ করে। |
| `$product->unit - $quantity` | বিক্রি হওয়া stock কমায়; এটি transaction-এর ভেতরে হয়। |
| Customer থাকলে ledger create | Persisted customer-এর sale-টি `customer_ledgers`-এও যোগ করে। |
| `employee_id` | Sale করা login user-কে ledger-এ যুক্ত করে। |
| `received_by` | এই invoice-এ টাকা পাওয়া হলে একই login user-কে receiver হিসেবে রাখে। |
| `sale_invoice_id` | Ledger row-কে invoice-এর সাথে link করে। |
| `payment_amount`, `due_amount` | Customer ledger-এ invoice-এ পাওয়া টাকা/বাকি সংরক্ষণ করে। |
| Save না-করা customer | Customer ID নেই বলে customer ledger row তৈরি হয় না; invoice-এ phone, employee, items, payment, date তবু থাকে। |

### Transaction শেষের পর

| ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `$invoice->load(...)` | Invoice-এর items, product, customer, employee relation load করে। |
| Low-stock filter | Sale-এর পর product stock `<= 10` হলে invoice response-এ alert list তৈরি করে। |
| `redirect()->route('sales.invoice', ...)` | তৈরি invoice screen-এ redirect করে। |
| `with('low_stock_products', ...)` | Redirect-এর একবারের session data-তে low-stock notice পাঠায়। |
| `with('auto_print', true)` | নতুন invoice page-এ automatic print dialog চালু করার signal দেয়। |

### `invoice($id)`

| লাইন/ধাপ | বাংলা ব্যাখ্যা |
|---|---|
| `SaleInvoice::with([...])` | Invoice-এর items, customer ও employee relations একসাথে load করে। |
| `findOrFail($id)` | ID না থাকলে 404 দেয়; null invoice render হতে দেয় না। |
| `view(..., compact('invoice'))` | Invoice data print/view Blade-এ পাঠায়। |

## ৫. Models: database relationship ও mass assignment

### `SaleInvoice`

| Model অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `$fillable` | `create()`/`update()`-এ কোন invoice field নিরাপদে mass assign করা যাবে তার তালিকা। |
| `$casts['invoice_date'] = 'datetime'` | Database timestamp-কে Carbon date-time object করে; Blade-এ `format()` চলে। |
| Decimal casts | টাকার field-গুলো দুই decimal string format-এ পড়ে। |
| Boolean cast | `include_previous_due` database value-কে true/false বানায়। |
| `customer()` → `belongsTo(Customer::class)` | একটি invoice ঐচ্ছিকভাবে একটি Customer record-এর সাথে যুক্ত। |
| `employee()` → `belongsTo(User::class, 'user_id')` | Invoice `user_id` দিয়ে login employee/user-এর সাথে যুক্ত। |
| `items()` → `hasMany(SaleItem::class)` | একটি invoice-এর একাধিক product rows থাকে। |

### `SaleItem`

| Model অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `$fillable` | Invoice ID, product ID, product snapshot, quantity ও price/total create করতে অনুমতি দেয়। |
| Decimal casts | Unit price ও line total-কে দুই decimal currency value করে। |
| `invoice()` | `sale_invoice_id` foreign key ব্যবহার করে parent invoice খুঁজে। |
| `product()` | `product_id` দিয়ে Product model খুঁজে; product delete হয়ে গেলে relation null হতে পারে, কিন্তু `product_name` snapshot থাকে। |

### `Customer`

| Model অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `$fillable` | Customer create/update-এ অনুমোদিত name/phone/type/employee/opening due ইত্যাদি field। |
| `employee()` | `employee_id` দিয়ে দায়িত্বপ্রাপ্ত login user-এর সাথে customer-এর সম্পর্ক। |
| `ledgers()` | একটি customer-এর সব CustomerLedger row ফেরত দেয়। |

### `CustomerLedger`

| Model অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `$fillable` | Customer/invoice/employee/receiver, entry type, payment method, sale date, amount ও notes create করতে অনুমতি দেয়। |
| `$casts['sale_date']` | Sale date-কে date object করে। |
| Decimal casts | Unit price, amount, payment ও due-কে দুই decimal-এ ধরে। |
| `customer()` | Ledger row কোন customer-এর তা দেখায়। |
| `employee()` | `employee_id` দিয়ে sale করা user/employee-কে দেখায়। |
| `receiver()` | `received_by` দিয়ে payment গ্রহণকারী user-কে দেখায়। |

## ৬. Invoice Blade: দেখানো ও print

`resources/views/backend/Sales/salesInvoice.blade.php`:

| অংশ | বাংলা ব্যাখ্যা |
|---|---|
| `@extends('dashboard.dashboard')` | Dashboard layout-এর ভেতর invoice দেখায়। |
| `@section('title', ...)` | Invoice number-সহ page title সেট করে। |
| `session('low_stock_products')` | Checkout-এর পর low stock থাকলে screen-এ warning banner দেখায়। |
| `.no-print` | New Sale/Print button ও warning banner-এ দেওয়া class; print output-এ এগুলো আসে না। |
| Invoice number/date/customer phone | `SaleInvoice` snapshot থেকে invoice header-এ দেখায়। |
| `@foreach ($invoice->items ...)` | Invoice-এর প্রতিটি product-এর নাম, price, quantity ও line total ছাপে। |
| Subtotal/Discount/VAT/Previous Due | Invoice header-এর হিসাবের breakdown দেখায়। |
| Total/Amount Paid/Due/Payment Method | শেষ হিসাব ও payment status দেখায়। |
| `Processed by ...employee->name` | `user_id` relationship ব্যবহার করে sale করা কর্মীর নাম দেখায়। |
| Print button | হাতে click করলে `window.print()` চালায়। |
| `@media print` | শুধু print media-তে প্রযোজ্য CSS। |
| `header`, `.topbar`, `.sidebar-wrapper` | Dashboard header/notification ও sidebar print থেকে লুকায়। |
| `.no-print` | UI control, warning banner ইত্যাদি print থেকে বাদ দেয়। |
| `.card`/`body` print rules | Shadow/border/background সরিয়ে printer-friendly invoice দেখায়। |
| `session('auto_print')` | নতুন checkout redirect-এর session flag থাকলে auto print script চালায়। |
| `setTimeout(...window.print(), 400)` | Page load হওয়ার একটু পরে print dialog খোলে। |

## ৭. Database migration: কোন তথ্য কোথায় জমে

### `sale_invoices`

| Column | কী সংরক্ষণ করে |
|---|---|
| `invoice_no` | Unique invoice number। |
| `customer_id` | Existing/save করা customer ID; temporary unsaved sale-এ null। |
| `customer_phone`, `customer_name` | Invoice তৈরির সময়কার customer পরিচয়ের snapshot। |
| `user_id` | Sale করা login user/employee-এর ID। |
| `invoice_date` | Sale date/time। |
| `subtotal`, `discount_amount`, `vat_amount` | Sale-এর base sum, manual discount ও manual VAT। |
| `previous_due`, `include_previous_due` | আগের due invoice-এ যোগ হয়েছিল কি না ও যোগ করা amount। |
| `total_amount`, `amount_paid`, `due_amount` | Invoice total, পাওয়া টাকা ও invoice-এর বাকি। |
| `payment_method` | Cash/Bank/Nagad/bKash/Rocket/Upay। |
| `notes` | Optional note। |

### `sale_items`

| Column | কী সংরক্ষণ করে |
|---|---|
| `sale_invoice_id` | Parent invoice-এর foreign key। |
| `product_id` | Original product-এর optional foreign key। |
| `product_name` | বিক্রির সময়কার product name snapshot। |
| `quantity` | বিক্রি হওয়া unit। |
| `unit_price`, `line_total` | Sale price ও row total। |

### `customer_ledgers`

| Column | কী সংরক্ষণ করে |
|---|---|
| `customer_id` | Customer foreign key। |
| `sale_invoice_id` | কোন invoice থেকে ledger entry এসেছে। |
| `employee_id` | Sale করা employee/user। |
| `received_by` | Payment গ্রহণকারী employee/user। |
| `entry_type` | Sale/payment ধরনের ledger row; sale checkout-এ `sale`। |
| `payment_method` | ঐ sale invoice-এ payment-এর method। |
| `sale_date`, `invoice_no` | Transaction-এর date ও invoice reference। |
| `product_name`, `quantity`, `unit_price` | Ledger-এর sale summary। |
| `total_amount`, `payment_amount`, `due_amount` | Sale, received amount ও due। |

### Migration code pattern

| Migration expression | বাংলা ব্যাখ্যা |
|---|---|
| `Schema::table(...)` | আগে তৈরি table-এ নতুন column যোগ করে; table drop করে না। |
| `if (! Schema::hasColumn(...))` | Column আগে থেকেই থাকলে duplicate column error ঠেকায়। |
| `$table->foreignId(...)` | Foreign key-এর উপযোগী unsigned integer column তৈরি করে। |
| `->constrained()` | Conventional related table/primary key-তে foreign-key constraint বসায়। |
| `->nullable()` | Relation থাকা বাধ্যতামূলক নয়; যেমন invoice-এর customer optional। |
| `->nullOnDelete()` | Related user/customer মুছলে ঐ foreign-key value null করে। |
| `->cascadeOnDelete()` | Parent customer delete হলে তার ledger row delete হয়। |
| `$table->decimal(..., 15, 2)` | টাকার জন্য precision 15 এবং decimal 2 রাখে। |
| `default(0)` | নতুন row-তে input না থাকলে amount শূন্য থাকে। |
| `down()`-এ schema না মোছা | Upgrade migration rollback হলেও পুরোনো invoice/customer data না মুছতে ইচ্ছাকৃত no-op রাখা হয়েছে। |

## ৮. Due-এর হিসাব বুঝে রাখা

Customer-এর বর্তমান balance:

```text
বর্তমান due = opening_due + সব ledger total_amount - সব ledger payment_amount
```

নতুন sale-এর invoice:

```text
sale amount   = subtotal - discount + VAT
invoice total = sale amount + (checkbox tick থাকলে previous due)
invoice due   = invoice total - amount paid
```

Checkbox unticked থাকলে invoice-এ previous due যোগ হয় না। Customer ledger-এ invoice sale/payment totals জমা হয়; customer balance-এ due আগের outstanding এবং নতুন sale/receipt অনুযায়ী থেকে যায়।

## ৯. গুরুত্বপূর্ণ সীমা/বর্তমান আচরণ

- Discount ও VAT বর্তমানে fixed monetary amount হিসেবে নেওয়া হয়; percentage field নয়।
- একটি invoice-এ একটি payment method রাখা হয়; split payment methods (যেমন cash + bKash) আলাদা করে নেই।
- Browser-এর Stock Left হলো form preview; প্রকৃত stock শুধু successful checkout transaction-এর সময় কমে।
- `unit` column-এ stock রাখা হয়; SaleController সেটিই decrement করে।
- Product code `PRD-` + product ID দিয়ে তৈরি UI SKU; আলাদা product-code database column নেই।
- Customer “No save” দিলে invoice database-এ ঠিকই থাকে; customer profile/ledger record তৈরি হয় না।
- Existing customer বা “Yes save” customer হলে invoice customer table এবং customer ledger-এর সাথে linked হয়।
- Invoice/ledger কর্মীর ID হিসেবে authenticated user ID রাখে; model relation দিয়ে নাম বের করে দেখায়।
- Low-stock warning invoice screen-এ দেখা যায়; print CSS `.no-print` banner-টি কাগজে ছাপে না।

## ১০. নতুন sale route কোথায় পাওয়া যাবে

- Sale entry: `route('sales.index')` — `/sales`
- Phone lookup: `route('sales.customer.lookup')` — `/sales/customer-lookup`
- Checkout submit: `route('sales.store')` — `POST /sales`
- Invoice: `route('sales.invoice', $id)` — `/sales/invoice/{id}`

