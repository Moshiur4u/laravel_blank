# Product Inventory Report-এর প্রিন্ট Summary

এই নথিতে print summary, organization header, organization logo watermark, DataTable control, portrait page, responsive table এবং blank page এড়ানোর Blade code ও ব্যাখ্যা আছে। Screen-এর card ও report heading আগের মতোই থাকে।

## Controller (Code and Explanation)

**পরিবর্তন নেই।** বিদ্যমান Controller থেকে আসা `$total_products`, `$total_purchase_amount`, `$unique_products_purchased`, `$total_purchased_quantity`, `$total_stock` ও `$total_expired_products` value-গুলোই format করে দেখানো হয়। নতুন হিসাব বা Controller code যোগ করা হয়নি।

## Migration (Code and Explanation)

**পরিবর্তন নেই।** এই পরিবর্তনে database schema বদলায় না; নতুন migration তৈরি করা হয়নি।

## Route (Code and Explanation)

**পরিবর্তন নেই।** Report route এবং `window.print()` button আগের মতোই কাজ করে।

## Model (Code and Explanation)

**পরিবর্তন নেই।** কোনো Model বা relation পরিবর্তন করা হয়নি।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/reports/productinventory.blade.php`

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

প্রিন্টে body, report wrapper ও report card সাদা background নিয়ে পুরো মুদ্রণযোগ্য প্রস্থ ব্যবহার করে। টেবিল fixed layout-এ থাকে; ছোট padding, font, নির্দিষ্ট column width ও word wrapping-এর কারণে ১২টি column portrait পৃষ্ঠার মধ্যে ধরে। Summary-র পাঁচটি লাইন কেন্দ্রে align হয়।

Inventory table-এ `product-inventory-table` class দিয়ে shared print CSS-কে শুধু এই ১২-column table-এর জন্য আরও compact width প্রয়োগ করতে বলা হয়েছে।

```blade
<table id="myTable" class="table mb-0 align-middle table-bordered table-striped product-inventory-table"> {{-- portrait page-এ ১২টি column fit করার print style পায়। --}}
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
