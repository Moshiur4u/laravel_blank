@extends('dashboard.dashboard')
@section('title', 'Invoice ' . $invoice->invoice_no)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                @canany(['sales.create', 'sales.view'])
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>New Sale</a>
                @endcanany
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bx bx-printer me-1"></i>Print Invoice</button>
            </div>

            <div class="border-0 shadow-sm card">
                <div class="p-4 card-body p-md-5">
                    @if ($organization?->logo || $organization?->name)
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                            @if ($organization?->logo)
                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                    alt="{{ $organization->name ?: 'Organization logo' }}"
                                    style="max-width: 90px; max-height: 65px; object-fit: contain;">
                            @endif
                            <div>
                                <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h4>
                                @if ($organization?->address)<div class="small text-muted">{{ $organization->address }}</div>@endif
                                @if ($organization?->phone)<div class="small text-muted">Phone: {{ $organization->phone }}</div>@endif
                                @if ($organization?->email)<div class="small text-muted">Email: {{ $organization->email }}</div>@endif
                            </div>
                        </div>
                    @endif
                    <div class="mb-4 row align-items-start">
                        <div class="col-7">
                            <h2 class="mb-1 text-primary fw-bold">Sales Invoice</h2>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-5 text-end">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            @if ($invoice->customer_name)
                                <div>{{ $invoice->customer_name }}</div>
                            @endif
                            <div>Phone: {{ $invoice->customer_phone }}</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-end">Unit Selling Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
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
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 row justify-content-end">
                        <div class="col-md-6 col-lg-5">
                            <div class="py-1 d-flex justify-content-between"><span>Subtotal</span><span>৳{{ number_format($invoice->subtotal, 2) }}</span></div>
                            <div class="py-1 d-flex justify-content-between"><span>Discount{{ ($invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0) ? ' (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2), '0'), '.') . '%)' : '' }}</span><span>- ৳{{ number_format($invoice->discount_amount, 2) }}</span></div>
                            <div class="py-1 d-flex justify-content-between"><span>VAT</span><span>৳{{ number_format($invoice->vat_amount, 2) }}</span></div>
                            @if ($invoice->include_previous_due)
                                <div class="py-1 d-flex justify-content-between"><span>Previous Due</span><span>৳{{ number_format($invoice->previous_due, 2) }}</span></div>
                            @endif
                            <hr>
                            <div class="py-1 d-flex justify-content-between fw-bold fs-5"><span>Total</span><span>৳{{ number_format($invoice->total_amount, 2) }}</span></div>
                            <div class="py-1 d-flex justify-content-between"><span>Amount Paid</span><span>৳{{ number_format($invoice->amount_paid, 2) }}</span></div>
                            <div class="py-1 d-flex justify-content-between text-danger fw-bold"><span>Due</span><span>৳{{ number_format($invoice->due_amount, 2) }}</span></div>
                            <div class="py-1 d-flex justify-content-between"><span>Payment Method</span><span>{{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Unpaid' }}</span></div>
                        </div>
                    </div>

                    @if ($invoice->notes)
                        <div class="mt-4"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                    @endif
                    <div class="pt-4 mt-5 row">
                        <div class="col-6"><div class="pt-2 border-top">Customer Signature</div></div>
                        <div class="col-6 text-end"><div class="pt-2 border-top">Processed by {{ $invoice->employee->name ?? 'N/A' }}</div></div>
                    </div>
                    <p class="mt-4 mb-0 text-center text-muted small">Thank you for your business.</p>
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
        }
    </style>
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
