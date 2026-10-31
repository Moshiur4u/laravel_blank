@extends('dashboard.dashboard')
@section('title', 'Invoice ' . $invoice->invoice_no)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                @canany(['sales.create', 'sales.view'])
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>New
                        Sale</a>
                @endcanany
                @can('sales.document.watermark')
                    @if ($organization?->logo)
                        <label class="btn btn-outline-secondary mb-0">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1">
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()"><i
                        class="bx bx-printer me-1"></i>Print Invoice</button>
            </div>

            <div id="print-document" class="border-0 shadow-sm card print-document"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                <div class="p-4 card-body p-md-5">
                    @if (
                        $organization?->logo ||
                            $organization?->name ||
                            $organization?->address ||
                            $organization?->phone ||
                            $organization?->email)
                        {{-- প্রতিষ্ঠানের তথ্য টেবিলের বাইরে রাখলে ইনভয়েসের প্রিন্ট হেডার পরিষ্কার থাকে। --}}
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom invoice-organization">
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
                    <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7">
                            <h2 class="mb-1 text-primary fw-bold">Sales Invoice</h2>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-5 text-end">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            <div><i class="bx bx-user me-1" aria-hidden="true"></i>Name:
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div><i class="bx bx-map me-1" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-bordered invoice-table">
                            {{-- নির্দিষ্ট প্রস্থ পণ্যের নামকে পাশের মূল্য কলামে ঢুকে পড়া থেকে আটকায়। --}}
                            <colgroup>
                                <col style="width: 6%;">
                                <col style="width: 39%;">
                                <col style="width: 20%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <th class="text-center text-white" style="background-color: #14ADE0;">#</th>
                                    <th class="text-white" style="background-color: #14ADE0;">Product</th>
                                    <th class="text-end text-white" style="background-color: #14ADE0;">Unit Selling Price
                                    </th>
                                    <th class="text-center text-white" style="background-color: #14ADE0;">Quantity</th>
                                    <th class="text-end text-white" style="background-color: #14ADE0;">Amount</th>
                                </tr>
                                @foreach ($invoice->items as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td class="invoice-product-name">{{ $item->product_name }}</td>
                                        {{-- sale তৈরির সময় Product-এর selling price sale item-এ snapshot হয়ে invoice-এ দেখায়। --}}
                                        <td class="text-end">৳{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">৳{{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal</th>
                                    <td class="text-end">৳{{ number_format($invoice->subtotal, 2) }}</td>
                                </tr>
                                {{-- ছাড় ও করের পরিমাণ শূন্য হলে অপ্রয়োজনীয় সারি প্রিন্টে দেখানো হয় না। --}}
                                @if ((float) $invoice->discount_amount > 0)
                                    <tr>
                                        <th colspan="4" class="text-end">
                                            Discount{{ $invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0 ? ' (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2), '0'), '.') . '%)' : '' }}
                                        </th>
                                        <td class="text-end">- ৳{{ number_format($invoice->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->vat_amount > 0)
                                    <tr>
                                        <th colspan="4" class="text-end">VAT</th>
                                        <td class="text-end">৳{{ number_format($invoice->vat_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->ait_amount > 0)
                                    <tr>
                                        <th colspan="4" class="text-end">AIT</th>
                                        <td class="text-end">৳{{ number_format($invoice->ait_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($invoice->include_previous_due)
                                    <tr>
                                        <th colspan="4" class="text-end">Previous Due</th>
                                        <td class="text-end">৳{{ number_format($invoice->previous_due, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="table-light fw-bold fs-5">
                                    <th colspan="4" class="text-end">Total</th>
                                    <td class="text-end">৳{{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Amount Paid</th>
                                    <td class="text-end">৳{{ number_format($invoice->amount_paid, 2) }}</td>
                                </tr>
                                {{-- Due-কে অন্য মোটের মতো স্বাভাবিক রঙে রেখে শুধু bold করা হয়েছে। --}}
                                <tr class="fw-bold">
                                    <th colspan="4" class="text-end">Due</th>
                                    <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Payment Method</th>
                                    <td class="text-end">
                                        {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Unpaid' }}</td>
                                </tr>
                                <tr>
                                    <th colspan="2">Amount Paid in Words</th>
                                    <td colspan="3">{{ $amountInWords }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- নোট ও শর্তাবলি টেবিলের বাইরে রাখলে এগুলো পড়া সহজ হয়। --}}
                    @if ($invoice->notes)
                        <div class="mt-3"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                    @endif

                    <div class="mt-4 p-3 border rounded invoice-terms">
                        <h6 class="mb-2 fw-bold text-dark">
                            <i class="bx bx-check-shield text-primary me-1"></i>Terms &amp; Conditions
                        </h6>
                        {{-- শর্তাবলির লেখা বড় ও বামদিকে রেখে লাইনগুলোর মাঝে পড়ার উপযোগী ফাঁকা রাখা হয়। --}}
                        <div class="invoice-terms-content">{{ $termsConditions }}</div>
                    </div>

                    <div class="pt-4 mt-5 row">
                        <div class="col-6">
                            <div class="pt-2 border-top">Customer Signature</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="pt-2 border-top">Processed by {{ $invoice->employee->name ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <p class="mt-4 mb-0 text-center text-muted small">Thank you for your business.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .invoice-terms-content {
            font-size: 1rem;
            line-height: 1.8;
            text-align: left;
            white-space: pre-line;
        }

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

            /* নির্দিষ্ট টেবিল প্রস্থ প্রিন্টে পণ্যের কলামগুলোকে সঠিক জায়গায় রাখে। */
            .invoice-table {
                width: 100% !important;
                table-layout: fixed;
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
    @can('sales.document.watermark')
        @if ($organization?->logo)
            <script>
                document.getElementById('watermark-toggle')?.addEventListener('change', function() {
                    document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
                });
            </script>
        @endif
    @endcan
    @if (session('low_stock_products'))
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                window.Swal.fire({
                    title: 'Low stock',
                    text: 'The following products now have 10 or fewer units: ' +
                        @json(implode(', ', session('low_stock_products'))),
                    icon: 'warning',
                }).then(function() {
                    @if (session('auto_print'))
                        window.setTimeout(() => window.print(), 400);
                    @endif
                });
            });
        </script>
    @elseif (session('auto_print'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.setTimeout(() => window.print(), 400);
            });
        </script>
    @endif
@endsection
