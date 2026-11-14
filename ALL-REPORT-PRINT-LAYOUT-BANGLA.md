# নির্বাচিত রিপোর্টগুলোর Print Layout — বাংলা Code Guide

এই পরিবর্তনে Product Inventory, Combined Voucher, Sales, Employee Payment Voucher, Voucher Entry, Employee Payment Ledger, Purchase Return এবং Product Return report-এর print layout একরকম করা হয়েছে। সব report portrait কাগজে ছাপে, organization তথ্য দেখায়, সংরক্ষিত organization logo-কে হালকা watermark হিসেবে প্রতিটি printed page-এ বসায়, table-কে page width-এর মধ্যে রাখে এবং অতিরিক্ত blank page তৈরি করতে পারে এমন viewport-height ও forced row-break সরায়। Screen layout ও report-এর হিসাব অপরিবর্তিত।

## Controller (Code and Explanation)

**কোনো Controller পরিবর্তন করা হয়নি।** report-এর আগে থেকে তৈরি totals, summary values এবং paginator-ই Blade view-তে ব্যবহার করা হয়েছে। Print layout-এর জন্য নতুন query বা business logic যোগ করার প্রয়োজন ছিল না।

## Migration (Code and Explanation)

**কোনো Migration পরিবর্তন বা যোগ করা হয়নি।** এই কাজ শুধু report view-এর print presentation বদলায়; database schema অপরিবর্তিত।

## Route (Code and Explanation)

**কোনো Route পরিবর্তন করা হয়নি।** প্রতিটি report আগের route ও filter query ব্যবহার করে।

## Model (Code and Explanation)

**কোনো Model পরিবর্তন করা হয়নি।** Existing report data, relationship এবং paginator একই আছে।

## Blade Template (Code and Explanation)

### Organization তথ্য সরবরাহ

Purchase Return ও Product Return view-এর জন্য `AppServiceProvider`-এর view composer তালিকায় নিচের entries যোগ করা হয়েছে। এতে shared organization partial-এ `$organization` পাওয়া যায়।

```php
            'backend.PurchaseReturns.report', // purchase return report-এ প্রতিষ্ঠানের তথ্য পাঠায়।
            'backend.ProductReturns.report', // product return report-এ প্রতিষ্ঠানের তথ্য পাঠায়।
```

### Shared print CSS

**Raw code file:** `resources/views/backend/partials/reportPrintStyles.blade.php`

এই partial-টি তালিকাভুক্ত report view-গুলোতে include করা হয়েছে। CSS comment-গুলো প্রতিটি নিয়মের কাজ বাংলায় ব্যাখ্যা করে।

```blade
@if (isset($organization) && $organization && $organization->logo) {{-- logo থাকলে watermark element তৈরি করে। --}}
    <div class="print-logo-watermark" aria-hidden="true"> {{-- প্রিন্টে সজ্জামূলক প্রতিষ্ঠানের logo watermark দেখায়। --}}
        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}" alt=""> {{-- organization-এর সংরক্ষিত logo লোড করে। --}}
    </div> {{-- print watermark container শেষ করে। --}}
@endif {{-- logo না থাকলে watermark element তৈরি হয় না। --}}

<style>
    .print-logo-watermark {
        /* screen-এ watermark লুকিয়ে রাখে। */
        display: none;
        /* শুধু print media-তে watermark দেখানো হবে। */
    }

    @page {
        /* সব report-এর জন্য লম্বালম্বি কাগজ নির্বাচন করে। */
        size: portrait;
        /* মুদ্রণযোগ্য জায়গা বাড়াতে চারপাশে ছোট margin রাখে। */
        margin: 6mm;
    }

    @media print {
        /* নিচের সব নিয়ম শুধু print preview বা print-এর সময় চালু হয়। */

        .print-logo-watermark {
            /* report-এর ওপরের দিকে স্থির logo watermark container নির্বাচন করে। */
            display: flex !important;
            /* watermark-কে প্রতিটি printed page-এ দেখায়। */
            position: fixed !important;
            /* watermark-কে page-এর নির্দিষ্ট জায়গায় স্থির রাখে। */
            top: 25%;
            /* watermark area-র উপরের প্রান্ত নির্ধারণ করে। */
            right: 25%;
            /* watermark area-র ডান প্রান্ত নির্ধারণ করে। */
            bottom: 25%;
            /* watermark area-র নিচের প্রান্ত নির্ধারণ করে। */
            left: 25%;
            /* watermark area-র বাম প্রান্ত নির্ধারণ করে। */
            z-index: 9999 !important;
            /* watermark-কে table ও text-এর উপরে faint layer-এ রাখে। */
            align-items: center;
            /* logo-কে watermark area-র উল্লম্ব মাঝখানে রাখে। */
            justify-content: center;
            /* logo-কে watermark area-র আনুভূমিক মাঝখানে রাখে। */
            opacity: 0.08;
            /* logo-কে হালকা করে মূল report পড়তে সুবিধা রাখে। */
            pointer-events: none;
            /* watermark যেন mouse বা print interaction আটকাতে না পারে। */
        }

        .print-logo-watermark img {
            /* watermark container-এর ভেতরের logo নির্বাচন করে। */
            display: block;
            /* inline image-এর baseline gap সরায়। */
            max-width: 100%;
            /* logo-কে watermark area-র প্রস্থ ছাড়াতে দেয় না। */
            max-height: 100%;
            /* logo-কে watermark area-র উচ্চতা ছাড়াতে দেয় না। */
            object-fit: contain;
            /* logo-র অনুপাত অক্ষুণ্ণ রেখে পুরোটা দেখায়। */
        }

        body {
            /* পুরো browser document-এর print background নির্বাচন করে। */
            background-color: #fff !important;
            /* report page জুড়ে সাদা background রাখে। */
            height: auto !important;
            /* body-কে অপ্রয়োজনীয় অতিরিক্ত পাতার উচ্চতা নিতে দেয় না। */
            min-height: 0 !important;
            /* viewport-সমান ন্যূনতম উচ্চতা সরিয়ে blank page আটকায়। */
            overflow: visible !important;
            /* responsive table-কে body-এর overflow clipping থেকে রক্ষা করে। */
        }

        .no-print,
        /* no-print class-এ থাকা filter ও action control লুকায়। */
        .sidebar-wrapper,
        /* dashboard sidebar প্রিন্টে বাদ দেয়। */
        header,
        /* dashboard header প্রিন্টে বাদ দেয়। */
        .topbar,
        /* dashboard topbar প্রিন্টে বাদ দেয়। */
        .top-header,
        /* dashboard top header প্রিন্টে বাদ দেয়। */
        .page-footer,
        /* dashboard page footer প্রিন্টে বাদ দেয়। */
        .footer,
        /* dashboard footer প্রিন্টে বাদ দেয়। */
        .pagination {
            /* Laravel pagination control-ও selector তালিকায় যোগ করে। */
            display: none !important;
            /* তালিকাভুক্ত নেভিগেশন ও action অংশগুলো লুকায়। */
        }

        .page-wrapper,
        /* report-এর বাইরের page wrapper নির্বাচন করে। */
        .page-content,
        /* report content container-ও নির্বাচন করে। */
        .print-report {
            /* report-এর মূল area-তেও একই প্রস্থ প্রয়োগ করে। */
            width: 100% !important;
            /* print area-কে পুরো মুদ্রণযোগ্য প্রস্থ দেয়। */
            padding: 0 !important;
            /* page wrapper-এর screen padding সরিয়ে table-এর জায়গা বাড়ায়। */
            min-width: 0 !important;
            /* screen-এর minimum width print-এ বাধা হতে দেয় না। */
            height: auto !important;
            /* content-এর মাপ অনুযায়ী wrapper-কে স্বাভাবিক উচ্চতায় রাখে। */
            min-height: 0 !important;
            /* পূর্ণ viewport উচ্চতা সরিয়ে অতিরিক্ত blank page আটকায়। */
            margin: 0 !important;
            /* dashboard-এর বাহিরের margin সরায়। */
            background-color: #fff !important;
            /* পুরো report area-তে সাদা background ব্যবহার করে। */
        }

        .print-report,
        /* মূল report area-র overflow নিয়ন্ত্রণ করে। */
        .print-report .card-body,
        /* report card-এর body-র overflow নিয়ন্ত্রণ করে। */
        .print-report .table-responsive {
            /* responsive wrapper-সহ report অংশগুলো নির্বাচন করে। */
            overflow: visible !important;
            /* print-এ table content কাটা বা লুকানো বন্ধ করে। */
            max-width: 100% !important;
            /* কোনো অংশকে print page-এর প্রস্থ ছাড়াতে দেয় না। */
        }

        .print-report .card {
            /* report card-গুলোর screen shadow ও border সরায়। */
            border: 0 !important;
            /* print-এ card outline বাদ দেয়। */
            box-shadow: none !important;
            /* print-এ screen shadow বাদ দেয়। */
            background-color: #fff !important;
            /* card background-কে পূর্ণ report area-তে সাদা রাখে। */
        }

        .print-report .card-body {
            /* report card-এর body-র screen padding নির্বাচন করে। */
            padding: 0 !important;
            /* table-কে প্রিন্টে পুরো area ব্যবহার করতে দেয়। */
        }

        .print-report .card-header {
            /* table card-এর section heading নির্বাচন করে। */
            display: none !important;
            /* print-এ বাড়তি section heading লুকায়। */
        }

        .print-report .print-report-heading {
            /* voucher report-এর screen heading নির্বাচন করে। */
            display: none !important;
            /* custom print style heading দেখালেও সেটি লুকিয়ে রাখে। */
        }

        .print-report .organization-report-header {
            /* organization details-সহ print header নির্বাচন করে। */
            display: flex !important;
            /* Bootstrap-এর d-none class-কে অগ্রাহ্য করে header দেখায়। */
            align-items: flex-start !important;
            /* প্রতিষ্ঠানের তথ্য ও report title উপরে সারিবদ্ধ রাখে। */
            justify-content: space-between !important;
            /* organization এবং report title-কে দুই পাশে রাখে। */
            break-inside: avoid;
            /* header-কে দুই পাতায় ভাগ হতে বাধা দেয়। */
            page-break-inside: avoid;
            /* পুরোনো print engine-এ একই সুরক্ষা দেয়। */
        }

        .print-report .print-summary-cards {
            /* screen-এর summary card container নির্বাচন করে। */
            display: none !important;
            /* card design-এর বদলে simple text summary দেখায়। */
        }

        .print-report .print-summary-text,
        /* print-only summary text নির্বাচন করে। */
        .print-report .print-pagination-info {
            /* print-only entry count-ও একইভাবে দেখায়। */
            display: block !important;
            /* d-none class থাকা সত্ত্বেও print content দেখায়। */
            width: 100% !important;
            /* text area-কে পুরো page width দেয়। */
            text-align: center !important;
            /* summary ও entry count page-এর মাঝখানে রাখে। */
        }

        #myTable_wrapper > .dt-layout-row:first-child {
            /* DataTable-এর page size ও Search row নির্বাচন করে। */
            display: none !important;
            /* Entries per page ও Search-সহ পুরো row লুকায়। */
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
            /* DataTable-এর page size ও Search control লুকায়। */
        }

        #myTable_wrapper .dt-paging,
        /* DataTable 2-এর pagination buttons নির্বাচন করে। */
        #myTable_wrapper .dataTables_paginate {
            /* পুরোনো DataTable pagination buttons নির্বাচন করে। */
            display: none !important;
            /* pagination buttons লুকিয়ে entry info অক্ষুণ্ণ রাখে। */
        }

        #myTable_wrapper .dt-info,
        /* DataTable 2-এর Showing entries তথ্য নির্বাচন করে। */
        #myTable_wrapper .dataTables_info {
            /* পুরোনো DataTable entry info-ও নির্বাচন করে। */
            display: block !important;
            /* Showing entries তথ্য print footer-এ রাখে। */
            text-align: center !important;
            /* DataTable entry info-কে page-এর মাঝখানে রাখে। */
        }

        .print-report .table-responsive {
            /* responsive table wrapper-কে আলাদা করে নির্বাচন করে। */
            width: 100% !important;
            /* table wrapper-কে পুরো print width দেয়। */
        }

        .print-report table {
            /* report table-গুলোর প্রস্থ ও column layout নির্ধারণ করে। */
            width: 100% !important;
            /* table-কে মুদ্রণযোগ্য এলাকার পুরো প্রস্থ দেয়। */
            max-width: 100% !important;
            /* table-কে মুদ্রণযোগ্য এলাকার বাইরে যেতে দেয় না। */
            table-layout: fixed !important;
            /* সব column-কে মুদ্রণযোগ্য প্রস্থের মধ্যে রাখে। */
            border-collapse: collapse !important;
            /* table border-গুলো একত্র করে জায়গা সাশ্রয় করে। */
        }

        .print-report th,
        /* table header cell-এ প্রিন্টের style প্রয়োগ করে। */
        .print-report td {
            /* table data cell-এও একই style প্রয়োগ করে। */
            padding: 0.15rem !important;
            /* প্রতিটি cell-এর ফাঁকা জায়গা কমিয়ে table সংকুচিত করে। */
            font-size: 6.5pt !important;
            /* portrait page-এ report table-কে fit করতে compact অক্ষর ব্যবহার করে। */
            line-height: 1.15;
            /* row-এর উল্লম্ব ফাঁকা জায়গা কমায়। */
            overflow-wrap: anywhere;
            /* দীর্ঘ শব্দ ও নম্বর প্রয়োজনমতো ভেঙে দেয়। */
            white-space: normal !important;
            /* screen-এর nowrap আচরণ print-এ বন্ধ করে। */
        }

        .print-report .product-inventory-table th,
        /* বারো কলামের inventory table-এর header cell নির্বাচন করে। */
        .print-report .product-inventory-table td {
            /* inventory table-এর data cell-এও একই compact style প্রয়োগ করে। */
            padding: 0.1rem !important;
            /* portrait page-এ বেশি column ধরাতে cell gap কমায়। */
            font-size: 5.5pt !important;
            /* বারো কলামের table-কে page width-এর মধ্যে রাখে। */
        }

        .print-report .product-inventory-table th:nth-child(1),
        /* row number column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(1) {
            width: 3% !important;
            /* row number-কে অল্প জায়গা দেয়। */
        }

        .print-report .product-inventory-table th:nth-child(2),
        /* product ID column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(2) {
            width: 6% !important;
            /* product ID-এর জন্য নির্দিষ্ট জায়গা রাখে। */
        }

        .print-report .product-inventory-table th:nth-child(3),
        /* product name column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(3) {
            width: 17% !important;
            /* product name-এর জন্য তুলনামূলক বেশি জায়গা রাখে। */
        }

        .print-report .product-inventory-table th:nth-child(4),
        /* purchased quantity column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(4),
        /* purchase rate column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table th:nth-child(5),
        .print-report .product-inventory-table td:nth-child(5),
        /* sold quantity column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table th:nth-child(6),
        .print-report .product-inventory-table td:nth-child(6),
        /* sales total column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table th:nth-child(7),
        .print-report .product-inventory-table td:nth-child(7),
        /* profit column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table th:nth-child(8),
        .print-report .product-inventory-table td:nth-child(8),
        /* stock quantity column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table th:nth-child(9),
        .print-report .product-inventory-table td:nth-child(9) {
            width: 8% !important;
            /* numeric column-গুলোর জন্য সমান জায়গা রাখে। */
        }

        .print-report .product-inventory-table th:nth-child(10),
        /* expiry column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(10) {
            width: 7% !important;
            /* expiry date-এর জন্য জায়গা রাখে। */
        }

        .print-report .product-inventory-table th:nth-child(11),
        /* supplier name column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(11) {
            width: 11% !important;
            /* supplier name-এর জন্য তুলনামূলক বেশি জায়গা রাখে। */
        }

        .print-report .product-inventory-table th:nth-child(12),
        /* phone number column-এর প্রস্থ নির্ধারণ করে। */
        .print-report .product-inventory-table td:nth-child(12) {
            width: 8% !important;
            /* phone number column-কে অবশিষ্ট জায়গা দেয়। */
        }

        .print-report thead {
            /* table-এর heading row নির্বাচন করে। */
            display: table-header-group;
            /* পরের print page-গুলোতে table heading পুনরায় দেখায়। */
        }

        .print-report tr {
            /* table-এর প্রতিটি row নির্বাচন করে। */
            break-inside: auto;
            /* বড় row থাকলে print engine-কে page layout স্বাভাবিকভাবে করতে দেয়। */
            page-break-inside: auto;
            /* row বাধ্যতামূলকভাবে পরের পাতায় ঠেলে blank page তৈরি আটকায়। */
        }
    }
</style>
```

Organization logo না থাকলে watermark element তৈরি হয় না। Logo থাকলে fixed element print-এর সব page-এ একই হালকা opacity-তে থাকে; screen view-তে সেটি লুকানো।

### Organization header এবং report view-তে যুক্ত সাধারণ pattern

প্রতিটি report-এ `organizationHeader` partial print-এ প্রতিষ্ঠানের নাম, যোগাযোগ, report title ও generated time দেখায়। Return report-এ তারিখের সময়সীমাও subtitle হিসেবে পাঠানো হয়। Print stylesheet ওই header-কে দৃশ্যমান রাখে।

```blade
            {{-- print report-এ প্রতিষ্ঠানের পরিচয়, report-এর সময় ও date period দেখায়। --}}
            @include('backend.partials.organizationHeader', [
                'reportTitle' => 'Purchase Return Report', // report-এর শিরোনাম দেখায়।
                'reportSubtitle' => 'Period: ' . $fromDate . ' to ' . $toDate, // বাছাই করা সময়সীমা দেখায়।
                'reportGeneratedAt' => now()->format('d M Y, h:i A'), // report তৈরির সময় দেখায়।
            ])
```

প্রতিটি report-এর card row-তে `print-summary-cards d-print-none` class যোগ করা হয়েছে। এতে screen-এ card আগের মতো থাকে এবং print-এ card গোপন হয়। Card-এর পর report-specific `print-summary-text` block যোগ হয়েছে; প্রতিটি block-এর প্রকৃত code পরের report-ভিত্তিক অংশে দেওয়া আছে। Pagination control screen-এ থাকে, আর print-only entry count সংশ্লিষ্ট paginator থেকে দেখানো হয়।

### Sales Report

**ফাইল:** `resources/views/backend/Sales/salesReport.blade.php`

Print summary-তে Total Sale Amount, Total Profit, Total Due এবং Total Sales (Invoices) দেখানো হয়। Product-wise Sales/Profit ও Sales Details heading print-এ লুকানো হয়। Invoice pagination-এর তথ্য `$invoices` paginator থেকে আসে।

```blade
<div class="mb-4 row g-3 print-summary print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
<div class="mb-3 d-flex align-items-center justify-content-between d-print-none"> {{-- product-wise section heading প্রিন্টে লুকায়। --}}
<h5 class="mb-3 fw-bold d-print-none"><i class="bx bx-receipt text-primary me-2"></i>Sales Details</h5> {{-- sales detail heading প্রিন্টে লুকায়। --}}
<div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে sales summary card-এর বদলে সাধারণ text দেখায়। --}}
    <p class="mb-1"> {{-- মোট sales amount আলাদা লাইনে দেখায়। --}}
        <strong>Total Sale Amount:</strong> {{-- মোট বিক্রির label দেখায়। --}}
        ৳{{ number_format($totalSalesAmount, 2) }} {{-- মোট বিক্রির টাকা format করে দেখায়। --}}
    </p> {{-- মোট sales amount-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- মোট profit আলাদা লাইনে দেখায়। --}}
        <strong>Total Profit:</strong> {{-- মোট লাভের label দেখায়। --}}
        ৳{{ number_format($totalProfit ?? 0, 2) }} {{-- মোট লাভের টাকা format করে দেখায়। --}}
    </p> {{-- মোট profit-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- মোট due আলাদা লাইনে দেখায়। --}}
        <strong>Total Due:</strong> {{-- বকেয়া টাকার label দেখায়। --}}
        ৳{{ number_format($totalDue, 2) }} {{-- মোট বকেয়া format করে দেখায়। --}}
    </p> {{-- মোট due-এর লাইন শেষ করে। --}}
    <p class="mb-3"> {{-- মোট invoice সংখ্যা আলাদা লাইনে দেখায়। --}}
        <strong>Total Sales (Invoices):</strong> {{-- invoice সংখ্যার label দেখায়। --}}
        {{ number_format($totalSalesCount) }} {{-- মোট invoice সংখ্যা format করে দেখায়। --}}
    </p> {{-- মোট invoice-এর লাইন শেষ করে। --}}
</div> {{-- print summary block শেষ করে। --}}
<div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে invoice pagination-এর entry count দেখায়। --}}
    Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ number_format($invoices->total()) }} entries {{-- invoice-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
</div> {{-- invoice entry count block শেষ করে। --}}
```

### Employee Payment Voucher Report

**ফাইল:** `resources/views/backend/Accounts Entries/VoucherEntries.blade.php`

Print summary-তে selected period-এর voucher count ও amount দেখানো হয়। Daily Summary এবং Voucher Details card heading গোপন থাকে; table header rows বজায় থাকে।

```blade
<div class="row g-3 mb-4 print-summary print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
<div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে employee voucher summary text দেখায়। --}}
    <p class="mb-1"> {{-- voucher count আলাদা লাইনে দেখায়। --}}
        <strong>Vouchers created in selected period:</strong> {{-- নির্বাচিত সময়ে voucher count-এর label দেখায়। --}}
        {{ number_format($totalCount) }} {{-- voucher সংখ্যা format করে দেখায়। --}}
    </p> {{-- voucher count-এর লাইন শেষ করে। --}}
    <p class="mb-3"> {{-- মোট voucher amount আলাদা লাইনে দেখায়। --}}
        <strong>Total voucher amount:</strong> {{-- মোট voucher amount-এর label দেখায়। --}}
        ৳{{ number_format((float) $totalAmount, 2) }} {{-- মোট voucher amount format করে দেখায়। --}}
    </p> {{-- মোট voucher amount-এর লাইন শেষ করে। --}}
</div> {{-- print summary block শেষ করে। --}}
<div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে voucher pagination-এর entry count দেখায়। --}}
    Showing {{ $vouchers->firstItem() ?? 0 }} to {{ $vouchers->lastItem() ?? 0 }} of {{ number_format($vouchers->total()) }} entries {{-- voucher-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
</div> {{-- voucher entry count block শেষ করে। --}}
```

### Combined Voucher Report

**ফাইল:** `resources/views/backend/Accounts Entries/combinedVoucherReport.blade.php`

Print summary-তে cash in hand, employee vouchers/payment, debit vouchers/amount এবং credit vouchers/amount সাধারণ text-এ দেখানো হয়।

```blade
<div class="mb-4 row g-3 print-summary print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
<div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে combined voucher summary text দেখায়। --}}
    <p class="mb-1"> {{-- cash in hand আলাদা লাইনে দেখায়। --}}
        <strong>Cash in Hand:</strong> {{-- cash in hand-এর label দেখায়। --}}
        ৳{{ number_format($cashInHandTotal, 2) }} {{-- cash balance format করে দেখায়। --}}
    </p> {{-- cash in hand-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- employee voucher count আলাদা লাইনে দেখায়। --}}
        <strong>Employee Vouchers:</strong> {{-- employee voucher summary label দেখায়। --}}
        {{ number_format($employeeVoucherCount) }} vouchers, {{-- employee voucher-এর সংখ্যা দেখায়। --}}
        Payment ৳{{ number_format($employeeTotal, 2) }} {{-- employee payment-এর মোট টাকা দেখায়। --}}
    </p> {{-- employee voucher summary লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- debit voucher summary আলাদা লাইনে দেখায়। --}}
        <strong>Debit Vouchers:</strong> {{-- debit voucher summary label দেখায়। --}}
        {{ number_format($debitVoucherCount) }} vouchers, {{-- debit voucher-এর সংখ্যা দেখায়। --}}
        Amount ৳{{ number_format($debitTotal, 2) }} {{-- debit-এর মোট টাকা দেখায়। --}}
    </p> {{-- debit voucher summary লাইন শেষ করে। --}}
    <p class="mb-3"> {{-- credit voucher summary আলাদা লাইনে দেখায়। --}}
        <strong>Credit Vouchers:</strong> {{-- credit voucher summary label দেখায়। --}}
        {{ number_format($creditVoucherCount) }} vouchers, {{-- credit voucher-এর সংখ্যা দেখায়। --}}
        Amount ৳{{ number_format($creditTotal, 2) }} {{-- credit-এর মোট টাকা দেখায়। --}}
    </p> {{-- credit voucher summary লাইন শেষ করে। --}}
</div> {{-- print summary block শেষ করে। --}}
<div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে voucher pagination-এর entry count দেখায়। --}}
    Showing {{ $vouchers->firstItem() ?? 0 }} to {{ $vouchers->lastItem() ?? 0 }} of {{ number_format($vouchers->total()) }} entries {{-- voucher-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
</div> {{-- voucher entry count block শেষ করে। --}}
```

### Debit/Credit Voucher Entry Report

**ফাইল:** `resources/views/backend/Accounts Entries/voucherEntryReport.blade.php`

Print summary-তে voucher count, total debit ও total credit দেখানো হয়।

```blade
<div class="mb-4 row g-2 print-summary print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
<div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে debit/credit summary text দেখায়। --}}
    <p class="mb-1"> {{-- voucher count আলাদা লাইনে দেখায়। --}}
        <strong>Voucher Count:</strong> {{-- মোট voucher count-এর label দেখায়। --}}
        {{ number_format($totalCount) }} {{-- voucher count format করে দেখায়। --}}
    </p> {{-- voucher count-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- মোট debit আলাদা লাইনে দেখায়। --}}
        <strong>Total Debit:</strong> {{-- debit amount-এর label দেখায়। --}}
        ৳{{ number_format($debitTotal, 2) }} {{-- debit amount format করে দেখায়। --}}
    </p> {{-- মোট debit-এর লাইন শেষ করে। --}}
    <p class="mb-3"> {{-- মোট credit আলাদা লাইনে দেখায়। --}}
        <strong>Total Credit:</strong> {{-- credit amount-এর label দেখায়। --}}
        ৳{{ number_format($creditTotal, 2) }} {{-- credit amount format করে দেখায়। --}}
    </p> {{-- মোট credit-এর লাইন শেষ করে। --}}
</div> {{-- print summary block শেষ করে। --}}
<div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে voucher pagination-এর entry count দেখায়। --}}
    Showing {{ $vouchers->firstItem() ?? 0 }} to {{ $vouchers->lastItem() ?? 0 }} of {{ number_format($vouchers->total()) }} entries {{-- voucher-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
</div> {{-- voucher entry count block শেষ করে। --}}
```

### Purchase Return Report

**ফাইল:** `resources/views/backend/PurchaseReturns/report.blade.php`

Print summary-তে date period, return transaction count, returned quantity, return value এবং refund received দেখানো হয়।

মূল content-এ `print-report` class যোগ করা হয়েছে; validation message ও pagination links-এ `d-print-none` / `no-print` class যোগ করে এগুলো screen-এ রাখা হয়েছে।

```blade
<div class="page-content print-report"> {{-- shared print style-কে এই report-এ সীমাবদ্ধ রাখে। --}}
<div class="alert alert-danger d-print-none" role="alert"> {{-- validation error screen-এ রাখে, print-এ নয়। --}}
<div class="row g-3 mb-4 print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
<div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে purchase return summary text দেখায়। --}}
    <p class="mb-1"> {{-- report-এর date period আলাদা লাইনে দেখায়। --}}
        <strong>Period:</strong> {{-- নির্বাচিত date period-এর label দেখায়। --}}
        {{ $fromDate }} to {{ $toDate }} {{-- নির্বাচিত শুরু ও শেষ তারিখ দেখায়। --}}
    </p> {{-- date period-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- return transaction count আলাদা লাইনে দেখায়। --}}
        <strong>Return Transactions:</strong> {{-- transaction count-এর label দেখায়। --}}
        {{ number_format($returnCount) }} {{-- মোট return transaction দেখায়। --}}
    </p> {{-- transaction count-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- returned quantity আলাদা লাইনে দেখায়। --}}
        <strong>Returned Quantity:</strong> {{-- returned quantity-এর label দেখায়। --}}
        {{ number_format($totalQuantity, 3) }} {{-- returned quantity তিন decimal-এ দেখায়। --}}
    </p> {{-- returned quantity-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- return value আলাদা লাইনে দেখায়। --}}
        <strong>Return Value:</strong> {{-- return value-এর label দেখায়। --}}
        ৳{{ number_format($totalReturnAmount, 2) }} {{-- return value format করে দেখায়। --}}
    </p> {{-- return value-এর লাইন শেষ করে। --}}
    <p class="mb-3"> {{-- refund amount আলাদা লাইনে দেখায়। --}}
        <strong>Refund Received:</strong> {{-- পাওয়া refund-এর label দেখায়। --}}
        ৳{{ number_format($totalRefundAmount, 2) }} {{-- refund amount format করে দেখায়। --}}
    </p> {{-- refund amount-এর লাইন শেষ করে। --}}
</div> {{-- print summary block শেষ করে। --}}
<div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে return pagination-এর entry count দেখায়। --}}
    Showing {{ $rows->firstItem() ?? 0 }} to {{ $rows->lastItem() ?? 0 }} of {{ number_format($rows->total()) }} entries {{-- return-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
</div> {{-- return entry count block শেষ করে। --}}
```

### Product Return Report

**ফাইল:** `resources/views/backend/ProductReturns/report.blade.php`

Print summary-তে date period, return transaction count, returned quantity, return value এবং refund paid দেখানো হয়।

```blade
            {{-- print report-এ প্রতিষ্ঠানের পরিচয়, report-এর সময় ও date period দেখায়। --}}
            @include('backend.partials.organizationHeader', [
                'reportTitle' => 'Product Return Report', // report-এর শিরোনাম দেখায়।
                'reportSubtitle' => 'Period: ' . $fromDate . ' to ' . $toDate, // বাছাই করা সময়সীমা দেখায়।
                'reportGeneratedAt' => now()->format('d M Y, h:i A'), // report তৈরির সময় দেখায়।
            ])

<div class="row g-3 mb-4 print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
<div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে product return summary text দেখায়। --}}
    <p class="mb-1"> {{-- report-এর date period আলাদা লাইনে দেখায়। --}}
        <strong>Period:</strong> {{-- নির্বাচিত date period-এর label দেখায়। --}}
        {{ $fromDate }} to {{ $toDate }} {{-- নির্বাচিত শুরু ও শেষ তারিখ দেখায়। --}}
    </p> {{-- date period-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- return transaction count আলাদা লাইনে দেখায়। --}}
        <strong>Return Transactions:</strong> {{-- transaction count-এর label দেখায়। --}}
        {{ number_format($returnCount) }} {{-- মোট return transaction দেখায়। --}}
    </p> {{-- transaction count-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- returned quantity আলাদা লাইনে দেখায়। --}}
        <strong>Returned Quantity:</strong> {{-- returned quantity-এর label দেখায়। --}}
        {{ number_format($totalQuantity) }} {{-- returned quantity format করে দেখায়। --}}
    </p> {{-- returned quantity-এর লাইন শেষ করে। --}}
    <p class="mb-1"> {{-- return value আলাদা লাইনে দেখায়। --}}
        <strong>Return Value:</strong> {{-- return value-এর label দেখায়। --}}
        ৳{{ number_format($totalReturnAmount, 2) }} {{-- return value format করে দেখায়। --}}
    </p> {{-- return value-এর লাইন শেষ করে। --}}
    <p class="mb-3"> {{-- refund amount আলাদা লাইনে দেখায়। --}}
        <strong>Refund Paid:</strong> {{-- পরিশোধ করা refund-এর label দেখায়। --}}
        ৳{{ number_format($totalRefundAmount, 2) }} {{-- refund amount format করে দেখায়। --}}
    </p> {{-- refund amount-এর লাইন শেষ করে। --}}
</div> {{-- print summary block শেষ করে। --}}
<div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে return pagination-এর entry count দেখায়। --}}
    Showing {{ $rows->firstItem() ?? 0 }} to {{ $rows->lastItem() ?? 0 }} of {{ number_format($rows->total()) }} entries {{-- return-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
</div> {{-- return entry count block শেষ করে। --}}
```

### Employee Payment Ledger

**ফাইল:** `resources/views/backend/Accounts Entries/employeePaymentLedger.blade.php`

আগের organization header অক্ষুণ্ণ রেখে এই view-তে shared print style include করা হয়েছে। ফলে ledger-ও portrait পৃষ্ঠা, responsive table এবং blank-page সুরক্ষা পায়।

```blade
    @include('backend.partials.reportPrintStyles') {{-- employee payment ledger-এ একই portrait print style প্রয়োগ করে। --}}
```

### Shared print style include

সব সংশ্লিষ্ট report view-এর শেষে নিচের include ব্যবহার করা হয়েছে। `reportPrintStyles` partial-টি একই portrait layout প্রয়োগ করে; এটি সাধারণ screen rendering পরিবর্তন করে না।

```blade
@include('backend.partials.reportPrintStyles') {{-- সব report-এর একই portrait print layout প্রয়োগ করে। --}}
```

Product Inventory-এর ১২টি column-এর জন্য আলাদা compact font ও নির্ধারিত column width আছে। Shared style-এ `min-height: 100vh` ব্যবহার করা হয়নি এবং table row-তে forced page-break এড়ানো হয়েছে—এগুলো print-এ অপ্রয়োজনীয় blank page হওয়ার ঝুঁকি কমায়।

Print-only entry-count block প্রতিটি report-এর paginator অনুযায়ী `Showing x to y of z entries` দেখায়। Sales report-এ `$invoices`, voucher report-গুলোতে `$vouchers`, এবং return report-গুলোতে `$rows` paginator ব্যবহার করা হয়েছে।
