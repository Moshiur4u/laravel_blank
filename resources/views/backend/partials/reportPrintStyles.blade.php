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
