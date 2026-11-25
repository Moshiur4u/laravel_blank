@extends('dashboard.dashboard')
{{-- dashboard/dashboard.blade.php থেকে মূল HTML কাঠামো, Navbar ও Sidebar উত্তরাধিকার হিসেবে নেওয়া হয়েছে। --}}
@section('title', 'Invoice ' . $invoice->invoice_no)
{{-- ব্রাউজারের টাইটেলে বর্তমান ইনভয়েস নম্বর দেখানো হয়েছে। --}}

@section('content')
    {{-- এই অংশের HTML ও Blade logic শুরু হয়েছে। --}}
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                {{-- no-print ক্লাসযুক্ত বাটনগুলো প্রিন্ট করার সময় কাগজে দেখানো হবে না। --}}
                @canany(['sales.create', 'sales.view'])
                    {{-- ব্যবহারকারীর sales.create অথবা sales.view permission থাকলে New Sale লিংক দেখানো হয়। --}}
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>
                        Open Sale</a>
                @endcanany
                @if (auth()->user()->hasRole('admin') ||
                        auth()->user()->can('Update_anyday_Invoice') ||
                        (auth()->user()->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()))
                    {{-- Admin বা যথাযথ update permission থাকলে Update Invoice বাটন দেখানো হয়। আজকের ইনভয়েসের জন্য পৃথক permission আছে। --}}
                    <a href="{{ route('sales.invoice.edit', $invoice->id) }}" class="btn btn-outline-primary">
                        <i class="bx bx-edit me-1"></i>Update
                    </a>
                @endif
                @if (auth()->user()->can('Voide_anyday_Invoice') ||
                        (auth()->user()->can('Voide_today_Invoice') && $invoice->invoice_date?->isToday()))
                    {{-- Void permission ও ডেট শর্ত পূরণ হলে POST DELETE ফর্ম দেখানো হয়। confirm() ব্যবহারকারীকে আগে সতর্ক করে। --}}
                    <form action="{{ route('sales.destroy', $invoice->id) }}" method="POST"
                        onsubmit="return confirm('Delete this invoice? If it is linked to a sale voucher, the invoice will be voided and its stock, ledger, payment voucher amounts, and due balance will be reversed.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bx bx-trash me-1"></i>Void
                        </button>
                    </form>
                @endif
                @can('sales.document.watermark')
                    {{-- Watermark permission ও প্রতিষ্ঠানের logo থাকলে প্রিন্টের জন্য logo toggle তৈরি হয়। --}}
                    @if ($organization?->logo)
                        <label class="mb-0 btn btn-outline-secondary d-none">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1" checked>
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()"><i
                        class="bx bx-printer me-1"></i>Invoice</button>
                {{-- Button click করলে প্রিভিউ নয়, সরাসরি Delivery Challan-এর print dialog খুলবে। --}}
                <button type="button" class="btn btn-primary" onclick="printDeliveryChallan()">
                    <i class="bx bx-printer me-1"></i>Challan
                </button>
            </div>

            <div id="print-document"
                class="border-0 shadow-sm card print-document invoice-print-area @if (auth()->user()->can('sales.document.watermark') && $organization?->logo) watermark-enabled @endif"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                {{-- print-document হলো সম্পূর্ণ ইনভয়েসের মূল কন্টেন্ট। watermark CSS variable-এ organization logo বসেছে। --}}
                <div class="p-4 card-body p-md-5">
                    @if (
                        $organization?->logo ||
                            $organization?->name ||
                            $organization?->address ||
                            $organization?->phone ||
                            $organization?->email)
                        {{-- প্রতিষ্ঠানের তথ্য টেবিলের বাইরে রাখলে ইনভয়েসের প্রিন্ট হেডার পরিষ্কার থাকে। --}}
                        <div class="gap-3 pb-3 mb-4 d-flex align-items-center border-bottom invoice-organization">
                            @if ($organization?->logo)
                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                    alt="{{ $organization->name ?: 'Organization logo' }}"
                                    style="max-width: 90px; max-height: 65px; object-fit: contain;">
                            @endif
                            <div>
                                @if ($organization?->name)
                                    <h4 class="mb-0 fw-bold text-dark">{{ $organization->name }}</h4>
                                @endif
                                @if ($organization?->address)
                                    <div class="small text-muted">{{ $organization->address }}</div>
                                @endif
                                @if ($organization?->phone)
                                    <div class="small text-muted">Phone: {{ $organization->phone }}</div>
                                @endif
                                @if ($organization?->email)
                                    <div class="small text-muted">Email: {{ $organization->email }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- ইনভয়েসের পরিচিতি ও গ্রাহকের তথ্য আলাদা কলামে রাখলে প্রিন্টে দ্রুত পড়া যায়। --}}
                    {{-- <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7 invoice-header-details">
                            <h5 class="mb-1 text-primary fw-bold">Sales Invoice</h5>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-5 text-end invoice-customer-info">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            <div><i class="bx bx-user me-1 text-nowrap" aria-hidden="true"></i>Name:
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div><i class="bx bx-map me-1" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                    </div> --}}
                    {{-- ইনভয়েসের পরিচিতি ও গ্রাহকের তথ্য আলাদা কলামে রাখলে প্রিন্টে দ্রুত পড়া যায়। --}}
                    {{-- গ্রাহকের নাম, ফোন ও ঠিকানার fallback হিসেবে walk-in customer এবং N/A ব্যবহার করা হয়েছে। --}}
                    <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7 invoice-customer-info">
                            <h6 class="mb-1 fw-bold">Customer</h6>
                            <div class="text-nowrap"><i class="bx bx-user me-1 text-nowrap" aria-hidden="true"></i>Name:
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div class="text-nowrap"><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div><i class="bx bx-map me-1 text-wrap" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                        <div class="col-5 invoice-header-details text-end">
                            <h6 class="mb-1 text-primary fw-bold">Sales Invoice</h6>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                    </div>


                    <div class="table-responsive">
                        {{-- table-responsive ছোট স্ক্রিনে টেবিলকে অনুভূমিকভাবে স্ক্রল করতে দেয়। --}}
                        <table class="table align-middle table-bordered invoice-table">
                            {{-- নির্দিষ্ট প্রস্থ পণ্যের নামকে পাশের মূল্য কলামে ঢুকে পড়া থেকে আটকায়। --}}
                            <colgroup>
                                <col style="width: 5%;">
                                <col style="width: 40%;">
                                <col style="width: 3%">
                                <col style="width: 3%">
                                <col style="width: 5%">
                                <col style="width: 7%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Product</th>
                                    <th class="text-end fit-content-column">Price</th>
                                    <th class="text-center fit-content-column">QTY</th>
                                    <th class="text-center fit-content-column">Unit</th>
                                    <th class="text-end fit-content-column">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $key => $item)
                                    {{-- প্রতিটি ইনভয়েস আইটেম একটি করে table row তৈরি করছে। $key-কে +1 করে ধারাবাহিক ক্রম দেখানো হয়েছে। --}}
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td class="invoice-product-name text-wrap fit-content-column">
                                            {{ $item->product_name }}
                                            @if ($item->warranty_cover)
                                                {{-- warranty cover চালু থাকলে warranty year, month ও serial/MAC তথ্য আলাদা লাইনে দেখানো হয়। --}}
                                                <div class="mt-1 small text-muted text-nowrap">
                                                    Warranty:
                                                    @if ((int) $item->warranty_year > 0)
                                                        {{ $item->warranty_year }} Year(s)
                                                    @endif
                                                    @if ((int) ($item->warranty_months ?? 0) > 0)
                                                        {{ $item->warranty_months }} Month(s)
                                                    @endif
                                                    Serial/MAC: {{ $item->serial_mac }}
                                                </div>
                                            @endif
                                        </td>
                                        {{-- sale তৈরির সময় Product-এর selling price sale item-এ snapshot হয়ে invoice-এ দেখায়। --}}
                                        <td class="text-end">৳{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->remaining_quantity }}</td>
                                        <td class="text-center">
                                            {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
                                        </td>
                                        <td class="text-end">৳{{ number_format($item->remaining_line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <th colspan="5" class="text-end">Subtotal</th>
                                    <td class="text-end">৳{{ number_format($invoice->subtotal, 2) }}</td>
                                </tr>
                                {{-- ছাড় ও করের পরিমাণ শূন্য হলে অপ্রয়োজনীয় সারি প্রিন্টে দেখানো হয় না। --}}
                                @if ((float) $invoice->discount_amount > 0)
                                    {{-- ডিসকাউন্ট percentage হলে হিসাবের শতকরা মানও ডিসকাউন্ট লাইনের পাশে দেখানো হয়। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">
                                            Discount{{ $invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0 ? ' (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2), '0'), '.') . '%)' : '' }}
                                        </th>
                                        <td class="text-end">- ৳{{ number_format($invoice->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->vat_amount > 0)
                                    {{-- VAT Amount শূন্যের বেশি হলে এই row যোগ করা হয়েছে। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">VAT</th>
                                        <td class="text-end">৳{{ number_format($invoice->vat_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->ait_amount > 0)
                                    {{-- AIT Amount শূন্যের বেশি হলে এই row যোগ করা হয়েছে। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">AIT</th>
                                        <td class="text-end">৳{{ number_format($invoice->ait_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($invoice->include_previous_due)
                                    {{-- পূর্বের বকেয়া ইনভয়েসে যুক্ত করার option থাকলে previous_due row দেখানো হয়। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">Previous Due</th>
                                        <td class="text-end">৳{{ number_format($invoice->previous_due, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class=" fs-5">
                                    <th colspan="5" class="text-end">Total</th>
                                    <td class="text-end">৳{{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">Amount Paid</th>
                                    <td class="text-end">৳{{ number_format($invoice->amount_paid, 2) }}</td>
                                </tr>
                                @if ((float) $invoice->due_amount > 0)
                                    {{-- বকেয়া না থাকলে invoice-এ Due row দেখানোর প্রয়োজন নেই। --}}
                                    <tr class="fw-bold">
                                        <th colspan="5" class="text-end">Outstanding</th>
                                        <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="5" class="text-end">Payment Method</th>
                                    <td class="text-end">
                                        {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Unpaid' }}</td>
                                </tr>
                                <tr>
                                    <th colspan="2">Amount in Words</th>
                                    <td colspan="4">{{ $amountInWords }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- নোট ও শর্তাবলি টেবিলের বাইরে রাখলে এগুলো পড়া সহজ হয়। --}}
                    @if ($invoice->notes)
                        {{-- বিক্রয়কালে সংরক্ষিত ব্যবহারকারী নোট থাকলে তা Invoices-এর শেষে দেখানো হয়। --}}
                        <div class="mt-3"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                    @endif

                    <div class="p-3 mt-4 border rounded invoice-terms">
                        <h6 class="mb-2 fw-bold text-dark">
                            <i class="bx bx-check-shield text-primary me-1"></i>Terms &amp; Conditions
                        </h6>
                        <!--শর্তাবলির লেখা বড় ও বামদিকে রেখে লাইনগুলোর মাঝে পড়ার উপযোগী ফাঁকা রাখা হয়।-->
                        <div class="invoice-terms-content">{{ $termsConditions }}</div>
                    </div>

                    <!-- স্বাক্ষরের অংশ -->
                    <!-- গ্রাহক ও প্রতিষ্ঠান উভয়ের স্বাক্ষরের জন্য fixed signature area তৈরি হয়েছে। -->
                    <div class="pt-5 mt-4 row">
                        <div class="col-6">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Customer Signature</div>
                                <div class="small text-muted">Signature & Date</div>
                            </div>
                        </div>
                        <div class="col-6 text-end d-flex justify-content-end">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Authorized Signature</div>
                                <div class="small text-muted">Prepared by: {{ $quotation->user->name ?? 'Sales Officer' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 mb-0 text-center text-muted small">Thank you for your business.</p>
                </div>
            </div>

            {{-- একই পেজে Challan print area রাখা হয়েছে, যেন আলাদা window ছাড়াই print করা যায়। --}}
            <div id="delivery-challan-print-wrapper">
                <div id="delivery-challan-print-document"
                    class="border-0 shadow-sm card print-document invoice-print-area @if ($organization?->logo) watermark-enabled @endif"
                    @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                    <div class="p-4 card-body p-md-5">
                        @if ($organization?->logo || $organization?->name)
                            {{-- প্রতিষ্ঠানের পরিচিতি থাকলে Challan-এর উপরে logo ও contact info দেখানো হয়। --}}
                            <div class="gap-3 pb-3 mb-4 d-flex align-items-center border-bottom invoice-organization">
                                @if ($organization?->logo)
                                    <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                        alt="{{ $organization->name ?: 'Organization logo' }}"
                                        style="max-width: 90px; max-height: 65px; object-fit: contain;">
                                @endif
                                <div>
                                    <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h4>
                                    @if ($organization?->address)
                                        <div class="small text-muted">{{ $organization->address }}</div>
                                    @endif
                                    @if ($organization?->phone)
                                        <div class="small text-muted">Phone: {{ $organization->phone }}</div>
                                    @endif
                                    @if ($organization?->email)
                                        <div class="small text-muted">Email: {{ $organization->email }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Customer-এর তথ্য এবং Challan-এর invoice number/date একই header-এ দেখানো হয়। --}}
                        <div class="mb-4 row align-items-start invoice-header">
                            <div class="col-7 invoice-customer-info">
                                <h6 class="mb-1 fw-bold">Customer</h6>
                                <div class="text-nowrap"><i class="bx bx-user me-1" aria-hidden="true"></i>Name:
                                    {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}
                                </div>
                                <div class="text-nowrap"><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                    {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                                @if ($invoice->customer_address ?? $invoice->customer?->address)
                                    <div>
                                        <i class="bx bx-map me-1 text-wrap" aria-hidden="true"></i>Address:
                                        {{ $invoice->customer_address ?? $invoice->customer?->address }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-5 invoice-header-details text-end">
                                <h6 class="mb-1 text-primary fw-bold">Delivery Challan</h6>
                                <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                                <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                            </div>
                        </div>

                        {{-- Challan-এ মূল্য বাদ দিয়ে শুধু পণ্য, পরিমাণ ও unit দেখানো হয়। --}}
                        <div class="table-responsive">
                            <table class="table align-middle table-bordered">
                                <thead>
                                    <tr>
                                        <th style="background-color: #14ADE0; color: #ffff;">#</th>
                                        <th style="background-color: #14ADE0; color: #ffff;">Product</th>
                                        <th class="text-center" style="background-color: #14ADE0; color: #ffff;">QTY</th>
                                        <th class="text-center" style="background-color: #14ADE0; color: #ffff;">UNIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $key => $item)
                                        {{-- প্রতিটি sale item-এর product, অবশিষ্ট quantity এবং unit দেখানো হয়। --}}
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->display_product_name }}</td>
                                            <td class="text-center">{{ $item->remaining_quantity }}</td>
                                            <td class="text-center">
                                                {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-end">Total Quantity</td>
                                        <td class="text-center">{{ $invoice->items->sum('remaining_quantity') }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if ($invoice->notes)
                            {{-- Invoice-এ note থাকলে Challan-এও দেখানো হয়। --}}
                            <div class="mt-4"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                        @endif

                        {{-- পণ্য বুঝে নেওয়া ও অনুমোদনের জন্য দুইটি স্বাক্ষর স্থান রাখা হয়েছে। --}}
                        <div class="pt-5 mt-4 row">
                            <div class="col-6">
                                <div class="pt-2 text-center border-top w-75">
                                    <div class="fw-semibold text-dark">Customer Signature</div>
                                    <div class="small text-muted">Signature &amp; Date</div>
                                </div>
                            </div>
                            <div class="col-6 text-end d-flex justify-content-end">
                                <div class="pt-2 text-center border-top w-75">
                                    <div class="fw-semibold text-dark">Authorized Signature</div>
                                    <div class="small text-muted">Signature &amp; Date</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Blade-এর ভিতরের CSS মূলত print layout ও invoice terms formatting নিয়ন্ত্রণ করে। */
        .invoice-terms-content {
            font-size: 1rem;
            line-height: 1.8;
            text-align: left;
            white-space: pre-line;
        }

        /* Screen-এ embedded Challan গোপন থাকে; Print button চাপলে শুধু print layout-এ দেখানো হয়। */
        #delivery-challan-print-wrapper {
            display: none;
        }

        @media print {
            /* @media print ব্লক সক্রিয় হলে screen-এর sidebar, header ও action button কাগজে দেখাবে না। */

            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer {
                display: none !important;
            }

            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 0 !important;
            }

            body {
                background: #fff !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            /* নির্দিষ্ট টেবিল প্রস্থ প্রিন্টে পণ্যের কলামগুলোকে সঠিক জায়গায় রাখে। */
            .invoice-table {
                /* width: 100% !important; */
                table-layout: auto;
            }

            .invoice-table .fit-content-column {
                width: 1%;
                white-space: nowrap;
            }

            .invoice-table tr {
                break-inside: avoid;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-document {
                position: relative;
                isolation: isolate;
            }

            .print-document.watermark-enabled::before {
                /* pseudo-element-টি মাঝখানে হালকা logo watermark হিসেবে পেইজে বসাবে। */
                content: '';
                position: fixed;
                inset: 0;
                z-index: 0;
                background-image: var(--invoice-watermark);
                background-position: center;
                background-repeat: no-repeat;
                background-size: min(65vw, 600px);
                opacity: .09;
                pointer-events: none;
            }

            .print-document>.card-body {
                position: relative;
                z-index: 1;
            }

            /* Challan button থেকে print হলে Invoice আড়াল করে Challan area-টি print করা হয়। */
            body.print-delivery-challan #print-document {
                display: none !important;
            }

            body.print-delivery-challan #delivery-challan-print-wrapper {
                display: block !important;
            }
        }
    </style>
    @can('sales.document.watermark')
        @if ($organization?->logo)
            <script>
                // checkbox পরিবর্তন হলে print-document-এর উপর .watermark-enabled class যোগ বা বাদ হয়।
                document.getElementById('watermark-toggle')?.addEventListener('change', function() {
                    document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
                });
            </script>
        @endif
    @endcan
    <script>
        // বর্তমান পেজে Challan mode চালু করে একই browser print dialog ব্যবহার করা হয়।
        function printDeliveryChallan() {
            document.body.classList.add('print-delivery-challan');
            window.print();
        }

        // Print dialog বন্ধ হলে পরেরবার Invoice স্বাভাবিকভাবে print করার জন্য Challan mode সরানো হয়।
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('print-delivery-challan');
        });
    </script>
    @if (session('low_stock_products'))
        <script>
            // low_stock_products session থাকলে DOM ready হওয়ার পর warning popup দেখাবে।
            window.addEventListener('DOMContentLoaded', function() {
                window.Swal.fire({
                    title: 'Low stock',
                    text: 'The following products now have 10 or fewer units: ' +
                        @json(implode(', ', session('low_stock_products'))),
                    icon: 'warning',
                }).then(function() {
                    @if (session('auto_print'))
                        // warning popup বন্ধ হওয়ার 400ms পরে স্বয়ংক্রিয় print dialog খুলবে।
                        window.setTimeout(() => window.print(), 400);
                    @endif
                });
            });
        </script>
    @elseif (session('auto_print'))
        <script>
            // low_stock warning না থাকলেও auto_print session থাকলে 400ms পর print dialog খুলবে।
            document.addEventListener('DOMContentLoaded', function() {
                window.setTimeout(() => window.print(), 400);
            });
        </script>
    @endif
@endsection
