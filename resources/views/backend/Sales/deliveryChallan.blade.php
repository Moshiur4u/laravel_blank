@extends('dashboard.dashboard')
{{-- Print popup-এ শুধু Challan দেখা যাবে; সাধারণ Challan page-এ Dashboard layout থাকবে। --}}

@section('title', 'Delivery Challan ' . $invoice->invoice_no)
{{-- Popup window-এর browser title-এ Challan এবং Invoice number দেখানো হবে। --}}

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Sales Open
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Challan
                </button>
            </div>
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
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div class="text-nowrap"><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div>
                                    <i class="bx bx-map me-1 text-wrap" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
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

    <style>
        @if (request()->boolean('print'))
            /* Print popup-এ preview না দেখিয়ে কেবল print dialog-ই সামনে আনা হয়। */
            @media screen {
                body {
                    visibility: hidden;
                }
            }

            /* Print media-তে Dashboard header, sidebar ও action area বাদ দিয়ে Challan-টি print হয়। */
            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer {
                display: none !important;
            }

            .page-wrapper,
            .page-content,
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .print-document {
                border: 0 !important;
                box-shadow: none !important;
            }
        @endif

        @media print {

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

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-document {
                position: relative;
                isolation: isolate;
            }

            .print-document.watermark-enabled::before {
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
        }
    </style>
    @if (request()->boolean('print'))
        <script>
            // ?print=1 থাকলে popup window load হওয়ার পর স্বয়ংক্রিয় print dialog খোলে।
            window.addEventListener('load', function() {
                window.setTimeout(function() {
                    // এটি popup window-এর নিজস্ব print() কল করে; মূল Sales Invoice পেজ প্রিন্ট করবে না।
                    window.print();
                }, 250);
            });
        </script>
    @endif
@endsection
