@extends('dashboard.dashboard')

@section('content')
    {{-- The controller prepares report rows and totals; this view only formats and displays them. --}}
    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mt-2 mb-0">Product Inventory Report</h4>
                </div>
                <div class="flex-wrap gap-2 d-flex">
                    <a href="{{ route('dashboardbody') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('product.inventory.report.export', request()->only(['category_id', 'stock_status'])) }}"
                        class="btn btn-success"> {{-- বর্তমান filter-গুলো CSV export-এ পাঠায়। --}}
                        <i class="bx bx-spreadsheet me-1"></i>Export Excel
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            <div class="mb-4 border-0 shadow-sm card no-print"> {{-- category ও stock filter-কে print থেকে বাদ রাখে। --}}
                <div class="card-header"> {{-- filter panel-এর শিরোনাম দেখায়। --}}
                    <h5 class="mb-0">Inventory Filters</h5> {{-- filter panel-এর নাম দেখায়। --}}
                </div> {{-- filter panel header শেষ করে। --}}
                <div class="card-body"> {{-- filter input-গুলোর container তৈরি করে। --}}
                    @if ($errors->any())
                        {{-- filter validation error থাকলে দেখায়। --}}
                        <div class="mb-3 alert alert-danger" role="alert"> {{-- error message-গুলোকে দৃশ্যমান করে। --}}
                            @foreach ($errors->all() as $message)
                                {{-- validation message-গুলো একে একে নেয়। --}}
                                <div>{{ $message }}</div> {{-- একটি validation message দেখায়। --}}
                            @endforeach {{-- validation message loop শেষ করে। --}}
                        </div> {{-- validation alert শেষ করে। --}}
                    @endif {{-- error না থাকলে alert দেখায় না। --}}
                    <form action="{{ route('product.inventory.report') }}" method="GET" class="row g-3 align-items-end">
                        {{-- নির্বাচিত filter URL-এ পাঠায়। --}}
                        <div class="col-md-5"> {{-- category dropdown-এর responsive column তৈরি করে। --}}
                            <label for="category_id" class="form-label">Category</label> {{-- category dropdown-এর label দেখায়। --}}
                            <select name="category_id" id="category_id" class="form-select"> {{-- category filter-এর মান বেছে নেয়। --}}
                                <option value="">All Categories</option> {{-- category filter না থাকলে সব category রাখে। --}}
                                @foreach ($categories as $category)
                                    {{-- controller থেকে পাওয়া category-গুলো দেখায়। --}}
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)> {{-- বর্তমান category নির্বাচন করে। --}}
                                        {{ $category->category_name }} {{-- category-র নাম দেখায়। --}}
                                    </option> {{-- একটি category option শেষ করে। --}}
                                @endforeach {{-- category list loop শেষ করে। --}}
                            </select> {{-- category dropdown শেষ করে। --}}
                        </div> {{-- category filter column শেষ করে। --}}
                        <div class="col-md-4"> {{-- stock dropdown-এর responsive column তৈরি করে। --}}
                            <label for="stock_status" class="form-label">Stock</label> {{-- stock dropdown-এর label দেখায়। --}}
                            <select name="stock_status" id="stock_status" class="form-select"> {{-- stock status বেছে নেয়। --}}
                                <option value="" @selected(empty($filters['stock_status']))>All Stock Statuses</option>
                                {{-- সব stock status দেখায়। --}}
                                <option value="in_stock" @selected(($filters['stock_status'] ?? '') === 'in_stock')>In Stock (&gt; 0)</option>
                                {{-- positive stock থাকা product দেখায়। --}}
                                <option value="low_stock" @selected(($filters['stock_status'] ?? '') === 'low_stock')>Low Stock (1–10)</option>
                                {{-- ১ থেকে ১০ stock থাকা product দেখায়। --}}
                                <option value="out_of_stock" @selected(($filters['stock_status'] ?? '') === 'out_of_stock')>Out of Stock (0 or below)</option>
                                {{-- শূন্য বা ঋণাত্মক stock থাকা product দেখায়। --}}
                            </select> {{-- stock dropdown শেষ করে। --}}
                        </div> {{-- stock filter column শেষ করে। --}}
                        <div class="gap-2 col-md-3 d-flex"> {{-- apply ও reset button-এর responsive column তৈরি করে। --}}
                            <button type="submit" class="btn btn-primary">Apply Filters</button> {{-- নির্বাচিত filter report-এ প্রয়োগ করে। --}}
                            <a href="{{ route('product.inventory.report') }}" class="btn btn-outline-secondary">Reset</a>
                            {{-- সব filter মুছে দেয়। --}}
                        </div> {{-- filter action column শেষ করে। --}}
                    </form> {{-- GET filter form শেষ করে। --}}
                </div> {{-- filter panel body শেষ করে। --}}
            </div> {{-- filter panel শেষ করে। --}}

            <div class="border-0 shadow-sm card print-report">
                <div class="p-3 card-body p-md-4">
                    @include('backend.partials.organizationHeader', [
                        'reportTitle' => 'Product Inventory Report',
                        'reportSubtitle' => 'Purchase, stock, expiry, and supplier details by product',
                        'reportGeneratedAt' => now()->format('d M Y, h:i A'),
                    ])
                    <div class="flex-wrap gap-2 mb-4 d-flex align-items-start justify-content-between">
                        <div class="d-print-none">
                            <h2 class="mb-1 text-primary fw-bold">Product Inventory</h2>
                            <p class="mb-0 text-muted">Purchase, stock, expiry, and supplier details by product</p>
                        </div>
                        <div class="small text-muted d-print-none">
                            <span class="fw-bold text-body">Report Generated:</span>
                            {{ now()->format('d M Y, h:i A') }}
                        </div>
                    </div>

                    <div class="mb-4 row g-3 print-summary-cards d-print-none">
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Products inStore</div>
                                    <div class="text-center fs-4 fw-bold">{{ number_format($total_products) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="mb-2 text-muted">Purchase Summary</div>

                                    <div class="d-flex align-items-center justify-content-between">
                                        <!-- Right Column -->
                                        <div class="text-center flex-fill">
                                            <div class="text-muted small">Unique</div>
                                            <div class="fs-4 fw-bold">
                                                {{ number_format($unique_products_purchased) }}
                                            </div>
                                        </div>
                                        <!-- Red Divider -->
                                        <div style="width:4px;height:60px;background:red;"></div>
                                        <!-- Left Column -->
                                        <div class="text-center flex-fill">
                                            <div class="text-muted small">Qty</div>
                                            <div class="fs-4 fw-bold">
                                                {{ number_format($total_purchased_quantity) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Purchase Amount</div>
                                    <div class="text-center fs-4 fw-bold text-primary">
                                        ৳{{ number_format($total_purchase_amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Current Stock</div>
                                    <div class="text-center fs-4 fw-bold">
                                        {{ number_format($total_stock) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Expired Products</div>
                                    <div class="text-center fs-4 fw-bold text-danger">
                                        {{ number_format($total_expired_products) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 d-none d-print-block print-summary-text"> {{-- প্রিন্টে শুধু প্রয়োজনীয় summary text দেখায়। --}}
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
                            {{ number_format($total_stock) }}
                            {{-- বর্তমান stock-এর পরিমাণ দেখায়। --}}
                            {{-- বর্তমান stock-এর লাইন শেষ করে। --}}
                            {{-- expired product-এর সংখ্যা আলাদা লাইনে রাখে। --}}
                            <strong>Expired Products:</strong> {{-- মেয়াদোত্তীর্ণ পণ্যের label দেখায়। --}}
                            {{ number_format($total_expired_products) }} {{-- মেয়াদোত্তীর্ণ পণ্যের সংখ্যা দেখায়। --}}
                            {{-- expired product-এর লাইন শেষ করে। --}}
                        </p>
                    </div> {{-- প্রিন্ট summary container শেষ করে। --}}

                    <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between d-print-none">
                        {{-- Inventory Details heading প্রিন্টে লুকায়। --}}
                        <h5 class="mb-0 fw-bold">Inventory Details</h5>
                        <span class="text-muted small">{{ number_format($total_products) }} products</span>
                    </div>

                    <h6 class="mb-3 fw-bold d-print-none">Stock, Expiry &amp; Supplier Details</h6> {{-- subtitle প্রিন্টে লুকায়। --}}
                    <div class="table-responsive">
                        <table id="myTable"
                            class="table mb-0 align-middle table-bordered table-striped product-inventory-table">
                            {{-- print-এ content অনুযায়ী ১২টি column-এর মাপ নির্ধারিত হয়। --}}
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Product ID</th>
                                    <th scope="col">Product</th>
                                    {{-- প্রতিটি quantity-র সঙ্গে তার Unit একই cell-এ রাখলে অতিরিক্ত কলাম লাগে না। --}}
                                    <th scope="col" class="text-end column-width-by-header">Purchased Qty&Unit</th>
                                    {{-- column-কে header-এর প্রয়োজনীয় প্রস্থ নিতে বলে। --}}
                                    <th scope="col" class="text-end">Purchase Rate</th>
                                    <th scope="col" class="text-end column-width-by-header">Total Sold Qty&Unit</th>
                                    {{-- column-কে header-এর প্রয়োজনীয় প্রস্থ নিতে বলে। --}}
                                    <th scope="col" class="text-end">Total Sales</th>
                                    <th scope="col" class="text-end">Profit</th>
                                    <th scope="col" class="text-end column-width-by-header">Stock Qty&Unit</th>
                                    {{-- column-কে header-এর প্রয়োজনীয় প্রস্থ নিতে বলে। --}}
                                    <th scope="col">Expiry</th>
                                    <th scope="col">Supplier</th>
                                    <th scope="col">Phone Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="text-nowrap">SKU:
                                            #PRD-{{ str_pad((string) $row['id'], 5, '0', STR_PAD_LEFT) }}</td>
                                        <td class="text-wrap">{{ $row['name'] }}</td>
                                        <td class="text-end">{{ number_format($row['purchased_quantity']) }}
                                            {{ $row['unit_label'] }}</td>
                                        <td class="text-end">৳{{ number_format($row['purchase_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['quantity_sold']) }}
                                            {{ $row['unit_label'] }}</td>
                                        <td class="text-end">৳{{ number_format($row['revenue'], 2) }}</td>
                                        <td class="text-end">
                                            <span
                                                class="fw-bold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                ৳{{ number_format($row['profit'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($row['stock']) }} {{ $row['unit_label'] }}
                                        </td>
                                        <td>
                                            @if ($row['expiry_date'])
                                                <div class="text-nowrap">{{ $row['expiry_date']->format('d M Y') }}</div>
                                                @switch($row['expiry_status'])
                                                    @case('expired')
                                                        <span class="badge bg-danger">Expired</span>
                                                    @break

                                                    @case('expiring')
                                                        <span class="badge bg-warning text-dark">Expiring Soon</span>
                                                    @break

                                                    @default
                                                        <span class="badge bg-success">Valid</span>
                                                @endswitch
                                            @else
                                                <span class="text-muted">No expiry date</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse ($row['suppliers'] as $supplier)
                                                <div>{{ $supplier['name'] }}</div>
                                            @empty
                                                <span class="text-muted">N/A</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @forelse ($row['suppliers'] as $supplier)
                                                <div class="text-nowrap phone-number">{{ $supplier['phone'] ?: 'N/A' }}
                                                </div>
                                            @empty
                                                <span class="text-muted">N/A</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="py-4 text-center text-muted">No products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($rows->isNotEmpty())
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">Total:</td>
                                        <td class="text-end">{{ number_format($rows->sum('purchased_quantity'), 2) }}</td>
                                        <td class="text-end">৳{{ number_format($rows->sum('purchase_amount'), 2) }}</td>
                                        <td class="text-end">{{ number_format($rows->sum('quantity_sold'), 2) }}</td>
                                        <td class="text-end">৳{{ number_format($rows->sum('revenue'), 2) }}</td>
                                        <td class="text-end text-success">৳{{ number_format($rows->sum('profit'), 2) }}
                                        </td>
                                        <td class="text-end">{{ number_format($rows->sum('stock'), 2) }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            /* নিচের নিয়মগুলো শুধু প্রিন্ট করার সময় প্রযোজ্য। */

            body {
                /* প্রিন্টের document body নির্বাচন করে। */
                background-color: #fff !important;
                /* পুরো কাগজের background সাদা রাখে। */
            }

            .no-print,
            /* no-print চিহ্নিত নিয়ন্ত্রণ প্রিন্টে বাদ দেয়। */
            .sidebar-wrapper,
            /* পার্শ্ব নেভিগেশন প্রিন্টে বাদ দেয়। */
            header,
            /* সাধারণ header প্রিন্টে বাদ দেয়। */
            .topbar,
            /* উপরের বার প্রিন্টে বাদ দেয়। */
            .top-header,
            /* উপরের header প্রিন্টে বাদ দেয়। */
            .footer {
                /* footer-ও প্রিন্টে বাদ দেওয়ার selector তালিকা শেষ করে। */
                display: none !important;
                /* selector তালিকায় থাকা অংশগুলো লুকায়। */
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

            .print-report .print-summary-cards {
                /* স্ক্রিনের summary card-গুলো প্রিন্টে লুকায়। */
                display: none !important;
                /* প্রিন্টে card design-এর বদলে নিচের সাধারণ summary দেখায়। */
            }

            .print-report .print-summary-text {
                /* প্রিন্টের জন্য তৈরি সাধারণ summary-টি নির্বাচন করে। */
                display: block !important;
                /* d-none class থাকা সত্ত্বেও summary-টি প্রিন্টে দেখায়। */
                text-align: center !important;
                /* পাঁচটি summary line-কে page-এর মাঝখানে দেখায়। */
            }

            #myTable_wrapper>.dt-layout-row:first-child {
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

            .print-report thead {
                /* প্রতিটি নতুন মুদ্রিত পাতায় table header নিয়ন্ত্রণ করে। */
                display: table-header-group;
                /* একাধিক পাতায় কলামের শিরোনাম পুনরায় দেখায়। */
            }

            .print-report tr {
                /* টেবিলের প্রতিটি সারি নির্বাচন করে। */
                break-inside: auto;
                /* বড় সারি হলে print engine-কে স্বাভাবিক page break করতে দেয়। */
                page-break-inside: auto;
                /* সারিকে জোর করে পরের পাতায় ঠেলে blank page আটকায়। */
            }
        }
    </style>
    @include('backend.partials.reportPrintStyles') {{-- inventory report-এ shared portrait print style প্রয়োগ করে। --}}
    <style>
        {{-- shared style-এর পরে শুধু inventory table-এর column layout নির্ধারণ করে। --}} @media print {

            /* নিচের content-based layout নিয়ম শুধু প্রিন্টে প্রয়োগ করে। */
            .print-report .product-inventory-table {
                /* Inventory report-এর table-কে আলাদা করে নির্বাচন করে। */
                width: auto !important;
                /* table-কে অযথা পুরো পৃষ্ঠা জুড়ে না রেখে content অনুযায়ী রাখে। */
                max-width: 100% !important;
                /* table-কে মুদ্রণযোগ্য পৃষ্ঠার প্রস্থ ছাড়াতে দেয় না। */
                table-layout: auto !important;
                /* cell content অনুযায়ী ১২টি column-এর প্রস্থ নির্ধারণ করে। */
            }

            /* Inventory table-এর automatic layout শেষ করে। */

            .print-report .product-inventory-table th:nth-child(n),
            /* সব column-এর header cell নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(n) {
                /* সব column-এর data cell নির্বাচন করে। */
                width: auto !important;
                /* shared CSS-এর নির্দিষ্ট width বাদ দিয়ে content-এর মাপ নিতে দেয়। */
                height: auto !important;
                /* প্রতিটি cell-এর উচ্চতা content অনুযায়ী স্বাভাবিক রাখে। */
            }

            /* ১২টি column-এর স্বয়ংক্রিয় মাপ নির্ধারণ শেষ করে। */

            .print-report .product-inventory-table th,
            /* সব column heading-এ text wrap চালু করে। */
            .print-report .product-inventory-table td,
            /* সব data cell-এ text wrap চালু করে। */
            .print-report .product-inventory-table th *,
            /* heading-এর ভেতরের text element-ও wrap করে। */
            .print-report .product-inventory-table td * {
                /* cell-এর ভেতরের text element-ও wrap করে। */
                font-size: 5.5pt !important;
                /* সব column-এর লেখা Product নামের সমান font size-এ রাখে। */
                white-space: normal !important;
                /* লম্বা লেখা পরের লাইনে যাওয়ার অনুমতি দেয়। */
                overflow-wrap: anywhere !important;
                /* ফাঁকবিহীন দীর্ঘ text-ও ভেঙে column সরু রাখে। */
            }

            /* সব column-এর দীর্ঘ text wrap করার নিয়ম শেষ করে। */

            .print-report .product-inventory-table th:nth-child(4),
            /* Purchased Qty&Unit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(4),
            /* Purchased Qty&Unit data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(5),
            /* Purchase Rate header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(5),
            /* Purchase Rate data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(6),
            /* Total Sold Qty&Unit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(6),
            /* Total Sold Qty&Unit data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(7),
            /* Total Sales header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(7),
            /* Total Sales data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(8),
            /* Profit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(8),
            /* Profit data নির্বাচন করে। */
            .print-report .product-inventory-table th:nth-child(9),
            /* Stock Qty&Unit header নির্বাচন করে। */
            .print-report .product-inventory-table td:nth-child(9) {
                /* Stock Qty&Unit data নির্বাচন করে। */
                padding: 0 !important;
                /* এই numeric column-গুলো থেকে অতিরিক্ত cell ফাঁকা জায়গা সরায়। */
                white-space: nowrap !important;
                /* numeric ও quantity/unit text এক লাইনে রাখে। */
                overflow-wrap: normal !important;
                /* লম্বা numeric text ভেঙে পরের লাইনে যেতে দেয় না। */
            }

            /* ছয়টি content-sized numeric column-এর নিয়ম শেষ করে। */

            .print-report .product-inventory-table td:nth-child(8) * {
                /* Profit cell-এর ভেতরের লেখাও নির্বাচন করে। */
                white-space: nowrap !important;
                /* Profit amount-কে span-এর ভেতরেও এক লাইনে রাখে। */
                overflow-wrap: normal !important;
                /* Profit amount-এর সংখ্যাগুলোকে ভাঙতে দেয় না। */
            }

            /* Profit cell-এর nested text wrap বন্ধ করে। */

            .print-report .product-inventory-table th.column-width-by-header {
                /* তিনটি quantity/unit header নির্বাচন করে। */
                white-space: nowrap !important;
                /* header text-কে এক লাইনে রেখে column-এর প্রয়োজনীয় প্রস্থ তৈরি করে। */
                overflow-wrap: normal !important;
                /* header-এর শব্দ ভেঙে ছোট column হতে বাধা দেয়। */
            }

            /* নির্বাচিত column-এর প্রস্থ header অনুযায়ী রাখে। */

            .print-report .product-inventory-table .phone-number {
                /* Phone Number column-এর cell নির্বাচন করে। */
                white-space: nowrap !important;
                /* phone number-কে এক লাইনে রাখে। */
                overflow-wrap: normal !important;
                /* phone number-এর মাঝখানে line break হতে দেয় না। */
            }

            /* Phone Number column-এর এক-লাইন নিয়ম শেষ করে। */
        }

        /* Inventory table-এর print-only content sizing শেষ করে। */
    </style> {{-- Inventory report-এর scoped print style শেষ করে। --}}
@endsection
