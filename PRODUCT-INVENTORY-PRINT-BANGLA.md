# Product Inventory Report-এর প্রিন্ট Summary

এই নথিতে print summary, organization header, organization logo watermark, DataTable control, portrait page, responsive table এবং blank page এড়ানোর Blade code ও ব্যাখ্যা আছে। Screen-এর card ও report heading আগের মতোই থাকে।

## Controller (Code and Explanation)

`ProductInventoryReportController` category ও stock status যাচাই করে। একই filter report এবং CSV export—দুই জায়গাতেই ব্যবহৃত হয়; summary total-ও filtered row থেকে গণনা হয়।

```php
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'], // থাকা category ID-ই গ্রহণ করে।
            'stock_status' => ['nullable', 'in:in_stock,low_stock,out_of_stock'], // নির্দিষ্ট stock status-ই গ্রহণ করে।
        ]);
    }

    private function filteredRows(array $filters): Collection
    {
        return $this->reportRows()
            ->filter(static function (array $row) use ($filters): bool {
                if (
                    isset($filters['category_id'])
                    && (int) $row['category_id'] !== (int) $filters['category_id']
                ) {
                    return false; // অন্য category-র product বাদ দেয়।
                }

                $stock = $row['stock']; // বর্তমান stock quantity নেয়।

                return match ($filters['stock_status'] ?? null) {
                    'in_stock' => $stock > 0, // একের বেশি stock থাকা product রাখে।
                    'low_stock' => $stock > 0 && $stock <= 10, // ১ থেকে ১০ stock থাকা product রাখে।
                    'out_of_stock' => $stock <= 0, // শূন্য বা ঋণাত্মক stock থাকা product রাখে।
                    default => true, // filter না থাকলে সব product রাখে।
                };
            })
            ->values(); // filter-এর পর table row numbering ধারাবাহিক রাখে।
    }
```

`index()` এই validated filter দিয়ে `rows`, category option ও নির্বাচিত filter Blade-এ পাঠায়। `export()` একই `filteredRows()` ব্যবহার করে, তাই Export Excel link-এ filter-গুলো query string হিসেবে যোগ করা হয়েছে।

## Migration (Code and Explanation)

**পরিবর্তন নেই।** এই পরিবর্তনে database schema বদলায় না; নতুন migration তৈরি করা হয়নি।

## Route (Code and Explanation)

**নতুন Route যোগ হয়নি।** আগের `product.inventory.report` GET route-এই `category_id` ও `stock_status` query parameter পাঠানো হয়; export route-এও একই filter parameter যায়।

## Model (Code and Explanation)

**পরিবর্তন নেই।** কোনো Model বা relation পরিবর্তন করা হয়নি।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/reports/productinventory.blade.php`

### Category ও Stock filter

Category dropdown-এ সব বিদ্যমান category থাকে। Stock dropdown-এর `In Stock` মানে stock শূন্যের বেশি; `Low Stock` মানে ১–১০; `Out of Stock` মানে শূন্য বা তার নিচে। Reset link filter সরিয়ে আগের পূর্ণ report দেখায়।

```blade
            <div class="mb-4 border-0 shadow-sm card no-print"> {{-- category ও stock filter-কে print থেকে বাদ রাখে। --}}
                <div class="card-header"> {{-- filter panel-এর শিরোনাম দেখায়। --}}
                    <h5 class="mb-0">Inventory Filters</h5> {{-- filter panel-এর নাম দেখায়। --}}
                </div> {{-- filter panel header শেষ করে। --}}
                <div class="card-body"> {{-- filter input-গুলোর container তৈরি করে। --}}
                    @if ($errors->any()) {{-- filter validation error থাকলে দেখায়। --}}
                        <div class="mb-3 alert alert-danger" role="alert"> {{-- error message-গুলোকে দৃশ্যমান করে। --}}
                            @foreach ($errors->all() as $message) {{-- validation message-গুলো একে একে নেয়। --}}
                                <div>{{ $message }}</div> {{-- একটি validation message দেখায়। --}}
                            @endforeach {{-- validation message loop শেষ করে। --}}
                        </div> {{-- validation alert শেষ করে। --}}
                    @endif {{-- error না থাকলে alert দেখায় না। --}}
                    <form action="{{ route('product.inventory.report') }}" method="GET" class="row g-3 align-items-end"> {{-- নির্বাচিত filter URL-এ পাঠায়। --}}
                        <div class="col-md-5"> {{-- category dropdown-এর responsive column তৈরি করে। --}}
                            <label for="category_id" class="form-label">Category</label> {{-- category dropdown-এর label দেখায়। --}}
                            <select name="category_id" id="category_id" class="form-select"> {{-- category filter-এর মান বেছে নেয়। --}}
                                <option value="">All Categories</option> {{-- category filter না থাকলে সব category রাখে। --}}
                                @foreach ($categories as $category) {{-- controller থেকে পাওয়া category-গুলো দেখায়। --}}
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)> {{-- বর্তমান category নির্বাচন করে। --}}
                                        {{ $category->category_name }} {{-- category-র নাম দেখায়। --}}
                                    </option> {{-- একটি category option শেষ করে। --}}
                                @endforeach {{-- category list loop শেষ করে। --}}
                            </select> {{-- category dropdown শেষ করে। --}}
                        </div> {{-- category filter column শেষ করে। --}}
                        <div class="col-md-4"> {{-- stock dropdown-এর responsive column তৈরি করে। --}}
                            <label for="stock_status" class="form-label">Stock</label> {{-- stock dropdown-এর label দেখায়। --}}
                            <select name="stock_status" id="stock_status" class="form-select"> {{-- stock status বেছে নেয়। --}}
                                <option value="" @selected(empty($filters['stock_status']))>All Stock Statuses</option> {{-- সব stock status দেখায়। --}}
                                <option value="in_stock" @selected(($filters['stock_status'] ?? '') === 'in_stock')>In Stock (&gt; 0)</option> {{-- positive stock থাকা product দেখায়। --}}
                                <option value="low_stock" @selected(($filters['stock_status'] ?? '') === 'low_stock')>Low Stock (1–10)</option> {{-- ১ থেকে ১০ stock থাকা product দেখায়। --}}
                                <option value="out_of_stock" @selected(($filters['stock_status'] ?? '') === 'out_of_stock')>Out of Stock (0 or below)</option> {{-- শূন্য বা ঋণাত্মক stock থাকা product দেখায়। --}}
                            </select> {{-- stock dropdown শেষ করে। --}}
                        </div> {{-- stock filter column শেষ করে। --}}
                        <div class="col-md-3 d-flex gap-2"> {{-- apply ও reset button-এর responsive column তৈরি করে। --}}
                            <button type="submit" class="btn btn-primary">Apply Filters</button> {{-- নির্বাচিত filter report-এ প্রয়োগ করে। --}}
                            <a href="{{ route('product.inventory.report') }}" class="btn btn-outline-secondary">Reset</a> {{-- সব filter মুছে দেয়। --}}
                        </div> {{-- filter action column শেষ করে। --}}
                    </form> {{-- GET filter form শেষ করে। --}}
                </div> {{-- filter panel body শেষ করে। --}}
            </div> {{-- filter panel শেষ করে। --}}
```

Export button selection ধরে রাখে:

```blade
<a href="{{ route('product.inventory.report.export', request()->only(['category_id', 'stock_status'])) }}" class="btn btn-success"> {{-- বর্তমান filter-গুলো CSV export-এ পাঠায়। --}}
```

### Summary card-গুলো screen-এ রাখা

```blade
                    <div class="mb-4 row g-3 print-summary-cards d-print-none">
```

`d-print-none` এবং print CSS-এর `print-summary-cards` class card-গুলো শুধু প্রিন্টে লুকায়; screen-এ আগের card layout দেখা যায়।

```css
            .print-report .print-summary-cards {
                /* স্ক্রিনের summary card-গুলো প্রিন্টে লুকায়। */
                display: none !important;
                /* প্রিন্টে card design-এর বদলে নিচের সাধারণ summary দেখায়। */
            }
```

### প্রিন্টে শুধু প্রয়োজনীয় summary text

এই block-এ Product Inventory শিরোনাম, Report Generated, card design বা বাড়তি বর্ণনা রাখা হয়নি—শুধু চাওয়া পাঁচটি metric আলাদা লাইনে আছে। Print CSS এগুলোকে organization header-এর নিচে page-এর মাঝখানে দেখায়।

```blade
                    <div class="d-none d-print-block mb-3 print-summary-text"> {{-- প্রিন্টে শুধু প্রয়োজনীয় summary text দেখায়। --}}
                        <p class="mb-1"> {{-- মোট পণ্যের সংখ্যা আলাদা লাইনে রাখে। --}}
                            <strong>Total Products in Store:</strong> {{-- মোট পণ্যের সংখ্যার label দেখায়। --}}
                            {{ number_format($total_products) }} {{-- মোট পণ্যের সংখ্যা format করে দেখায়। --}}
                        </p> {{-- মোট পণ্যের সংখ্যার লাইন শেষ করে। --}}
                        <p class="mb-1"> {{-- মোট ক্রয়মূল্য আলাদা লাইনে রাখে। --}}
                            <strong>Total Purchase Amount:</strong> {{-- মোট ক্রয়মূল্যের label দেখায়। --}}
                            ৳{{ number_format($total_purchase_amount, 2) }} {{-- টাকার চিহ্নসহ মোট ক্রয়মূল্য দেখায়। --}}
                        </p> {{-- মোট ক্রয়মূল্যের লাইন শেষ করে। --}}
                        <p class="mb-1"> {{-- Purchase Summary-র তথ্য আলাদা লাইনে রাখে। --}}
                            <strong>Purchase Summary:</strong> {{-- Purchase Summary-র label দেখায়। --}}
                            Unique {{ number_format($unique_products_purchased) }}, {{-- স্বতন্ত্র পণ্যের সংখ্যা দেখায়। --}}
                            Qty {{ number_format($total_purchased_quantity) }} {{-- ক্রয়কৃত মোট quantity দেখায়। --}}
                        </p> {{-- Purchase Summary-র লাইন শেষ করে। --}}
                        <p class="mb-1"> {{-- বর্তমান stock আলাদা লাইনে রাখে। --}}
                            <strong>Current Stock:</strong> {{-- বর্তমান stock-এর label দেখায়। --}}
                            {{ number_format($total_stock) }} {{-- বর্তমান stock-এর পরিমাণ দেখায়। --}}
                        </p> {{-- বর্তমান stock-এর লাইন শেষ করে। --}}
                        <p class="mb-3"> {{-- expired product-এর সংখ্যা আলাদা লাইনে রাখে। --}}
                            <strong>Expired Products:</strong> {{-- মেয়াদোত্তীর্ণ পণ্যের label দেখায়। --}}
                            {{ number_format($total_expired_products) }} {{-- মেয়াদোত্তীর্ণ পণ্যের সংখ্যা দেখায়। --}}
                        </p> {{-- expired product-এর লাইন শেষ করে। --}}
                    </div> {{-- প্রিন্ট summary container শেষ করে। --}}
```

`d-none d-print-block` block-টিকে screen-এ লুকিয়ে শুধু প্রিন্টে দেখায়। প্রতিটি metric আগের Controller value থেকেই আসে।

### প্রিন্টে বাদ দেওয়া report heading

```blade
                    <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between d-print-none"> {{-- Inventory Details heading প্রিন্টে লুকায়। --}}
                        <h5 class="mb-0 fw-bold">Inventory Details</h5>
                        <span class="text-muted small">{{ number_format($total_products) }} products</span>
                    </div>

                    <h6 class="mb-3 fw-bold d-print-none">Stock, Expiry &amp; Supplier Details</h6> {{-- subtitle প্রিন্টে লুকায়। --}}
```

`d-print-none` থাকার কারণে “Inventory Details”, product count এবং “Stock, Expiry & Supplier Details” প্রিন্টে আসে না; screen-এ আসে।

### Portrait page, summary, full-page background ও responsive table

```css
            body {
                /* প্রিন্টের document body নির্বাচন করে। */
                background-color: #fff !important;
                /* পুরো কাগজের background সাদা রাখে। */
            }

            .page-wrapper,
            /* বাইরের report wrapper নির্বাচন করে। */
            .page-content {
                /* ভেতরের report content-ও একই নিয়মে রাখে। */
                margin: 0 !important;
                /* dashboard-এর চারপাশের margin সরায়। */
                padding: 0 !important;
                /* dashboard-এর ভেতরের padding সরায়। */
                width: 100% !important;
                /* প্রিন্টে পুরো মুদ্রণযোগ্য প্রস্থ ব্যবহার করে। */
                min-width: 0 !important;
                /* স্ক্রিনের ন্যূনতম প্রস্থের বাধা সরায়। */
                height: auto !important;
                /* report content অনুযায়ী page wrapper-এর উচ্চতা রাখে। */
                min-height: 0 !important;
                /* পূর্ণ viewport উচ্চতা সরিয়ে অতিরিক্ত blank page আটকায়। */
                background-color: #fff !important;
                /* সাদা background পুরো page wrapper-এ রাখে। */
            }

            .print-report {
                /* শুধু Product Inventory report কার্ড নির্বাচন করে। */
                border: 0 !important;
                /* প্রিন্টে কার্ডের বাইরের border সরায়। */
                box-shadow: none !important;
                /* প্রিন্টে স্ক্রিনের shadow সরায়। */
                width: 100% !important;
                /* report কার্ডকে মুদ্রণযোগ্য এলাকার পূর্ণ প্রস্থ দেয়। */
                height: auto !important;
                /* card-এর উচ্চতা content অনুযায়ী স্বাভাবিক রাখে। */
                min-height: 0 !important;
                /* অপ্রয়োজনীয় পূর্ণ-পৃষ্ঠার উচ্চতা সরায়। */
                background-color: #fff !important;
                /* report-এর সাদা background পুরো প্রস্থে দেখায়। */
            }

            .print-report .card-body {
                /* report card-এর ভেতরের অংশ নির্বাচন করে। */
                background-color: #fff !important;
                /* content background-কে পুরো report জুড়ে সাদা রাখে। */
            }

            .print-report,
            /* report কার্ডের overflow স্বাভাবিক করে। */
            .print-report .card-body,
            /* কার্ডের ভেতরের অংশের overflow স্বাভাবিক করে। */
            .print-report .table-responsive {
                /* responsive table wrapper-এ কাটছাঁট বন্ধ করে। */
                overflow: visible !important;
                /* প্রিন্টে table content লুকানো বা কাটা আটকায়। */
                width: 100% !important;
                /* responsive wrapper-কে পুরো প্রিন্ট প্রস্থ দেয়। */
                max-width: 100% !important;
                /* wrapper-কে পৃষ্ঠার প্রস্থ ছাড়াতে দেয় না। */
            }

            .print-report table {
                /* report-এর টেবিলের প্রিন্ট বিন্যাস ঠিক করে। */
                width: 100% !important;
                /* টেবিলকে মুদ্রণযোগ্য এলাকার পূর্ণ প্রস্থ দেয়। */
                max-width: 100% !important;
                /* টেবিলকে মুদ্রণযোগ্য এলাকার বাইরে যেতে বাধা দেয়। */
                table-layout: fixed !important;
                /* বারোটি column-কে portrait page-এর প্রস্থের মধ্যে রাখে। */
            }

            .print-report th,
            /* টেবিলের শিরোনাম ঘরে প্রিন্টের নিয়ম প্রয়োগ করে। */
            .print-report td {
                /* টেবিলের তথ্য ঘরেও একই নিয়ম প্রয়োগ করে। */
                padding: 0.1rem !important;
                /* বেশি কলাম এক পাতায় ধরাতে ঘরের ফাঁকা জায়গা কমায়। */
                font-size: 5.5pt !important;
                /* বারোটি কলাম পড়ার উপযোগী ছোট আকারে মুদ্রণ করে। */
                line-height: 1.15;
                /* সারির উল্লম্ব জায়গা কমিয়ে তথ্য গুছিয়ে রাখে। */
                overflow-wrap: anywhere;
                /* দীর্ঘ নাম ও নম্বর প্রয়োজনমতো ভেঙে দেয়। */
                white-space: normal !important;
                /* স্ক্রিনের nowrap আচরণ প্রিন্টে বন্ধ করে। */
            }

            .print-report .print-summary-text {
                /* প্রিন্টের জন্য তৈরি সাধারণ summary-টি নির্বাচন করে। */
                display: block !important;
                /* d-none class থাকা সত্ত্বেও summary-টি প্রিন্টে দেখায়। */
                text-align: center !important;
                /* পাঁচটি summary line-কে page-এর মাঝখানে দেখায়। */
            }

            .print-report tr {
                /* টেবিলের প্রতিটি সারি নির্বাচন করে। */
                break-inside: auto;
                /* বড় সারি হলে স্বাভাবিক page break করতে দেয়। */
                page-break-inside: auto;
                /* সারিকে পরের পাতায় ঠেলে blank page তৈরি আটকায়। */
            }
```

### Organization header এবং shared print style

এই report আগেই organization তথ্যসহ title ও generated time partial-এ পাঠায়। Shared print CSS header-টি নিশ্চিতভাবে দেখায় এবং portrait print নিয়ম প্রয়োগ করে।

```blade
                    @include('backend.partials.organizationHeader', [
                        'reportTitle' => 'Product Inventory Report',
                        'reportSubtitle' => 'Purchase, stock, expiry, and supplier details by product',
                        'reportGeneratedAt' => now()->format('d M Y, h:i A'),
                    ])

    @include('backend.partials.reportPrintStyles') {{-- inventory report-এ shared portrait print style প্রয়োগ করে। --}}
```

Shared partial-এ organization-এর logo থাকলে নিচের markup watermark image তৈরি করে। Print CSS-এ image-টি হালকা opacity-তে সব পাতার মাঝখানে দেখায়; screen-এ লুকানো থাকে।

```blade
@if (isset($organization) && $organization && $organization->logo) {{-- logo থাকলে watermark element তৈরি করে। --}}
    <div class="print-logo-watermark" aria-hidden="true"> {{-- প্রিন্টে সজ্জামূলক প্রতিষ্ঠানের logo watermark দেখায়। --}}
        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}" alt=""> {{-- organization-এর সংরক্ষিত logo লোড করে। --}}
    </div> {{-- print watermark container শেষ করে। --}}
@endif {{-- logo না থাকলে watermark element তৈরি হয় না। --}}
```

এর সম্পূর্ণ shared CSS ও প্রতিটি property-র বাংলা ব্যাখ্যা [সব রিপোর্টের বাংলা guide](./ALL-REPORT-PRINT-LAYOUT-BANGLA.md)-এ আছে।

প্রিন্টে body, report wrapper ও report card সাদা background নিয়ে পুরো মুদ্রণযোগ্য প্রস্থ ব্যবহার করে। Shared print style-এ fixed table layout থাকলেও Inventory report-এর scoped override content অনুযায়ী column মাপ নির্ধারণ করে। ছোট padding, font এবং wrapping ১২টি column-কে মুদ্রণযোগ্য প্রস্থে রাখতে সাহায্য করে। Summary-র পাঁচটি লাইন কেন্দ্রে align হয়।

Inventory table-এ `product-inventory-table` class দিয়ে shared print CSS-কে শুধু এই ১২-column table-এর জন্য style প্রয়োগ করতে বলা হয়েছে।

```blade
<table id="myTable" class="table mb-0 align-middle table-bordered table-striped product-inventory-table"> {{-- print-এ content অনুযায়ী ১২টি column-এর মাপ নির্ধারিত হয়। --}}
```

### প্রিন্টে DataTable-এর Search/Pagination লুকিয়ে entry info রাখা

```css
            #myTable_wrapper > .dt-layout-row:first-child {
                /* page size ও Search থাকা DataTable-এর উপরের row নির্বাচন করে। */
                display: none !important;
                /* উপরের controls-সহ পুরো row প্রিন্টে লুকায়। */
            }

            #myTable_wrapper .dt-length,
            /* DataTable 2-এর Entries per page control নির্বাচন করে। */
            #myTable_wrapper .dt-search,
            /* DataTable 2-এর Search box নির্বাচন করে। */
            #myTable_wrapper .dataTables_length,
            /* পুরোনো DataTable Entries per page control নির্বাচন করে। */
            #myTable_wrapper .dataTables_filter {
                /* পুরোনো DataTable Search box-ও নির্বাচন করে। */
                display: none !important;
                /* Entries per page ও Search-কে প্রিন্টে নিশ্চিতভাবে লুকায়। */
            }

            #myTable_wrapper .dt-paging,
            /* DataTable 2-এর pagination controls নির্বাচন করে। */
            #myTable_wrapper .dataTables_paginate {
                /* পুরোনো DataTable pagination class-ও নির্বাচন করে। */
                display: none !important;
                /* পেজ বাটনগুলো প্রিন্টে লুকায়, কিন্তু entry-info অক্ষুণ্ণ রাখে। */
            }

            #myTable_wrapper .dt-info,
            /* DataTable 2-এর "Showing ..." তথ্য নির্বাচন করে। */
            #myTable_wrapper .dataTables_info {
                /* পুরোনো DataTable info class-ও নির্বাচন করে। */
                display: block !important;
                /* "Showing 1 to 10 of 662 entries" তথ্যটি প্রিন্টে দেখায়। */
            }
```

প্রিন্টে DataTable-এর উপরের page-size/Search row এবং pagination buttons লুকানো হয়। Info অংশটি দৃশ্যমান থাকে, তাই “Showing 1 to 10 of 662 entries” লেখা footer-এ দেখা যায়। DataTable-এর বর্তমান ১০-row page-ই প্রিন্ট হয়; entry info সেই page ও মোট entry সংখ্যা জানায়।

### Content অনুযায়ী column মাপ, text wrapping ও phone number

Controller, Migration, Route ও Model-এ কোনো পরিবর্তন নেই। Blade-এর ১২টি column-ই প্রিন্ট হয়। Shared partial-এর fixed width-গুলোকে override করতে inventory table-এর style shared partial include-এর পরে রাখা হয়েছে।

Product name-এর cell `text-wrap` class ব্যবহার করে:

```blade
<td class="text-wrap">{{ $row['name'] }}</td> {{-- Product নামের দীর্ঘ লেখা cell-এর ভেতরে wrap করে। --}}
```

Phone number-কে এক লাইনে রাখার জন্য cell-এ আলাদা class দেওয়া হয়েছে:

```blade
<div class="text-nowrap phone-number">{{ $supplier['phone'] ?: 'N/A' }}</div> {{-- phone number-কে আলাদা selector দিয়ে এক লাইনে রাখে। --}}
```

নিচের style-টি `reportPrintStyles` include-এর পরে রাখা হয়। এতে table content অনুযায়ী মাপ নেয় এবং shared partial-এর নির্দিষ্ট width সরে যায়। সব column-এর font size Product নামের মতো `5.5pt` থাকে। Purchase Rate, Total Sales, Profit এবং তিনটি Qty&Unit column-এ padding সরিয়ে text এক লাইনে রাখা হয়, যাতে width লেখা অনুযায়ী হয়। Phone number-ও এক লাইনে থাকে; বাকি cell-গুলোর text দীর্ঘ হলে wrap হয়।

```blade
    <style> {{-- shared style-এর পরে শুধু inventory table-এর column layout নির্ধারণ করে। --}}
        @media print { /* নিচের content-based layout নিয়ম শুধু প্রিন্টে প্রয়োগ করে। */
            .print-report .product-inventory-table { /* Inventory report-এর table-কে আলাদা করে নির্বাচন করে। */
                width: auto !important; /* table-কে অযথা পুরো পৃষ্ঠা জুড়ে না রেখে content অনুযায়ী রাখে। */
                max-width: 100% !important; /* table-কে মুদ্রণযোগ্য পৃষ্ঠার প্রস্থ ছাড়াতে দেয় না। */
                table-layout: auto !important; /* cell content অনুযায়ী ১২টি column-এর প্রস্থ নির্ধারণ করে। */
            } /* Inventory table-এর automatic layout শেষ করে। */

            .print-report .product-inventory-table th:nth-child(n), /* সব column-এর header cell নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(n) { /* সব column-এর data cell নির্বাচন করে। */
                width: auto !important; /* shared CSS-এর নির্দিষ্ট width বাদ দিয়ে content-এর মাপ নিতে দেয়। */
                height: auto !important; /* প্রতিটি cell-এর উচ্চতা content অনুযায়ী স্বাভাবিক রাখে। */
            } /* ১২টি column-এর স্বয়ংক্রিয় মাপ নির্ধারণ শেষ করে। */

            .print-report .product-inventory-table th, /* সব column heading-এ text wrap চালু করে। */
            .print-report .product-inventory-table td, /* সব data cell-এ text wrap চালু করে। */
            .print-report .product-inventory-table th *, /* heading-এর ভেতরের text element-ও wrap করে। */
            .print-report .product-inventory-table td * { /* cell-এর ভেতরের text element-ও wrap করে। */
                font-size: 5.5pt !important; /* সব column-এর লেখা Product নামের সমান font size-এ রাখে। */
                white-space: normal !important; /* লম্বা লেখা পরের লাইনে যাওয়ার অনুমতি দেয়। */
                overflow-wrap: anywhere !important; /* ফাঁকবিহীন দীর্ঘ text-ও ভেঙে column সরু রাখে। */
            } /* সব column-এর দীর্ঘ text wrap করার নিয়ম শেষ করে। */

            .print-report .product-inventory-table th:nth-child(4), /* Purchased Qty&Unit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(4), /* Purchased Qty&Unit data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(5), /* Purchase Rate header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(5), /* Purchase Rate data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(6), /* Total Sold Qty&Unit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(6), /* Total Sold Qty&Unit data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(7), /* Total Sales header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(7), /* Total Sales data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(8), /* Profit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(8), /* Profit data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(9), /* Stock Qty&Unit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(9) { /* Stock Qty&Unit data নির্বাচন করে। */
                padding: 0 !important; /* এই numeric column-গুলো থেকে অতিরিক্ত cell ফাঁকা জায়গা সরায়। */
                white-space: nowrap !important; /* numeric ও quantity/unit text এক লাইনে রাখে। */
                overflow-wrap: normal !important; /* লম্বা numeric text ভেঙে পরের লাইনে যেতে দেয় না। */
            } /* ছয়টি content-sized numeric column-এর নিয়ম শেষ করে। */

            .print-report .product-inventory-table td:nth-child(8) * { /* Profit cell-এর ভেতরের লেখাও নির্বাচন করে। */
                white-space: nowrap !important; /* Profit amount-কে span-এর ভেতরেও এক লাইনে রাখে। */
                overflow-wrap: normal !important; /* Profit amount-এর সংখ্যাগুলোকে ভাঙতে দেয় না। */
            } /* Profit cell-এর nested text wrap বন্ধ করে। */

            .print-report .product-inventory-table th.column-width-by-header { /* তিনটি quantity/unit header নির্বাচন করে। */
                white-space: nowrap !important; /* header text-কে এক লাইনে রেখে column-এর প্রয়োজনীয় প্রস্থ তৈরি করে। */
                overflow-wrap: normal !important; /* header-এর শব্দ ভেঙে ছোট column হতে বাধা দেয়। */
            } /* নির্বাচিত column-এর প্রস্থ header অনুযায়ী রাখে। */

            .print-report .product-inventory-table .phone-number { /* Phone Number column-এর cell নির্বাচন করে। */
                white-space: nowrap !important; /* phone number-কে এক লাইনে রাখে। */
                overflow-wrap: normal !important; /* phone number-এর মাঝখানে line break হতে দেয় না। */
            } /* Phone Number column-এর এক-লাইন নিয়ম শেষ করে। */
        } /* Inventory table-এর print-only content sizing শেষ করে। */
    </style> {{-- Inventory report-এর scoped print style শেষ করে। --}}
```

`Purchased Qty&Unit`, `Total Sold Qty&Unit` এবং `Stock Qty&Unit`—এই তিনটি header-এ class যোগ করে header-এর পুরো লেখা এক লাইনে রাখা হয়। এতে auto table layout প্রতিটি column-এর জন্য অন্তত header-এ যতটুকু প্রস্থ দরকার ততটুকু রাখে; data দীর্ঘ হলে তা cell-এর ভেতরে wrap হতে পারে।

```blade
<th scope="col" class="text-end column-width-by-header">Purchased Qty&Unit</th> {{-- column-কে header-এর প্রয়োজনীয় প্রস্থ নিতে বলে। --}}
<th scope="col" class="text-end column-width-by-header">Total Sold Qty&Unit</th> {{-- column-কে header-এর প্রয়োজনীয় প্রস্থ নিতে বলে। --}}
<th scope="col" class="text-end column-width-by-header">Stock Qty&Unit</th> {{-- column-কে header-এর প্রয়োজনীয় প্রস্থ নিতে বলে। --}}
```
