@extends('dashboard.dashboard')
@section('title', 'Invoice ' . $invoice->invoice_no)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                @canany(['sales.create', 'sales.view'])
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>New Sale</a>
                @endcanany
                @can('sales.document.watermark')
                    @if ($organization?->logo)
                        <label class="btn btn-outline-secondary mb-0">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1">
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bx bx-printer me-1"></i>Print Invoice</button>
            </div>

            <div id="print-document" class="border-0 shadow-sm card print-document"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                <div class="p-4 card-body p-md-5">
                    <div class="table-responsive">
                        <table class="table align-middle table-bordered invoice-table">
                            <tbody>
                                @if ($organization?->logo || $organization?->name || $organization?->address || $organization?->phone || $organization?->email)
                                    <tr>
                                        @if ($organization?->logo)
                                            <td class="text-center" style="width: 110px;">
                                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                                    alt="{{ $organization->name ?: 'Organization logo' }}"
                                                    style="max-width: 90px; max-height: 65px; object-fit: contain;">
                                            </td>
                                        @endif
                                        <td colspan="{{ $organization?->logo ? 4 : 5 }}">
                                            @if ($organization?->name)<strong class="fs-5">{{ $organization->name }}</strong><br>@endif
                                            @if ($organization?->address){{ $organization->address }}<br>@endif
                                            @if ($organization?->phone)Phone: {{ $organization->phone }}<br>@endif
                                            @if ($organization?->email)Email: {{ $organization->email }}@endif
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="2" class="fs-4">Sales Invoice</th>
                                    <th colspan="3" class="text-end">Invoice: {{ $invoice->invoice_no }}<br>Date: {{ $invoice->invoice_date->format('d M Y') }}</th>
                                </tr>
                                <tr class="table-light">
                                    <th colspan="5">Customer Details</th>
                                </tr>
                                <tr>
                                    <th colspan="1"><i class="bx bx-user me-1" aria-hidden="true"></i>Name</th>
                                    <td colspan="4">{{ $invoice->customer_name ?? $invoice->customer?->name ?? 'Walk-in Customer' }}</td>
                                </tr>
                                <tr>
                                    <th><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone</th>
                                    <td colspan="4">{{ $invoice->customer_phone ?? $invoice->customer?->phone ?? 'N/A' }}</td>
                                </tr>
                                @if ($invoice->customer_address ?? $invoice->customer?->address)
                                    <tr>
                                        <th><i class="bx bx-map me-1" aria-hidden="true"></i>Address</th>
                                        <td colspan="4">{{ $invoice->customer_address ?? $invoice->customer?->address }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th style="background-color: #14ADE0; color: #ffff;">#</th>
                                    <th style="background-color: #14ADE0; color: #ffff;">Product</th>
                                    <th class="text-end" style="background-color: #14ADE0; color: #ffff;">Unit Selling Price</th>
                                    <th class="text-center" style="background-color: #14ADE0; color: #ffff;">Quantity</th>
                                    <th class="text-end" style="background-color: #14ADE0; color: #ffff;">Amount</th>
                                </tr>
                                @foreach ($invoice->items as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item->product_name }}</td>
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
                                <tr>
                                    <th colspan="4" class="text-end">Discount{{ ($invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0) ? ' (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2), '0'), '.') . '%)' : '' }}</th>
                                    <td class="text-end">- ৳{{ number_format($invoice->discount_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">VAT</th>
                                    <td class="text-end">৳{{ number_format($invoice->vat_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">AIT</th>
                                    <td class="text-end">৳{{ number_format($invoice->ait_amount, 2) }}</td>
                                </tr>
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
                                <tr class="text-danger fw-bold">
                                    <th colspan="4" class="text-end">Due</th>
                                    <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Payment Method</th>
                                    <td class="text-end">{{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Unpaid' }}</td>
                                </tr>
                                <tr>
                                    <th colspan="2">Amount Paid in Words</th>
                                    <td colspan="3">{{ $amountInWords }}</td>
                                </tr>
                                @if ($invoice->notes)
                                    <tr>
                                        <th>Notes</th>
                                        <td colspan="4">{{ $invoice->notes }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="5" class="table-light"><i class="bx bx-check-shield text-primary me-1"></i>Terms & Conditions</th>
                                </tr>
                                <tr>
                                    <td colspan="5" class="small" style="white-space: pre-line; line-height: 1.6;">{{ $termsConditions }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="pt-5">Customer Signature</td>
                                    <td colspan="3" class="pt-5 text-end">Processed by {{ $invoice->employee->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-center text-muted small">Thank you for your business.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer { display: none !important; }
            .page-wrapper { margin: 0 !important; padding: 0 !important; }
            .page-content { padding: 0 !important; }
            .card { box-shadow: none !important; border: 0 !important; }
            body { background: #fff !important; }
            .table-responsive { overflow: visible !important; }
            .invoice-table { width: 100% !important; }
            .invoice-table tr { break-inside: avoid; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .print-document { position: relative; isolation: isolate; }
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
            .print-document > .card-body { position: relative; z-index: 1; }
        }
    </style>
    @can('sales.document.watermark')
        @if ($organization?->logo)
            <script>
                document.getElementById('watermark-toggle')?.addEventListener('change', function () {
                    document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
                });
            </script>
        @endif
    @endcan
    @if (session('low_stock_products'))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                window.Swal.fire({
                    title: 'Low stock',
                    text: 'The following products now have 10 or fewer units: ' +
                        @json(implode(', ', session('low_stock_products'))),
                    icon: 'warning',
                }).then(function () {
                    @if (session('auto_print'))
                        window.setTimeout(() => window.print(), 400);
                    @endif
                });
            });
        </script>
    @elseif (session('auto_print'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.setTimeout(() => window.print(), 400);
            });
        </script>
    @endif
@endsection
