@extends('dashboard.dashboard')
@section('title', ($ledger->entry_type === 'payment' ? 'Customer Payment Receipt - ' : 'Customer Invoice - ') . $invoiceNumber)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- প্রিন্ট বা লেজারে ফেরার control কাগজে দেখানো হয় না। --}}
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('customer.ledger', $ledger->customer_id) }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Customer Ledger
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print
                </button>
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
                            <h2 class="mb-1 text-primary fw-bold">{{ $ledger->entry_type === 'payment' ? 'Customer Payment Receipt' : 'Customer Invoice' }}</h2>
                            <div class="text-muted">Reference: <strong>{{ $invoiceNumber }}</strong></div>
                            <div class="text-muted">Date: {{ $ledger->sale_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-5 text-end">
                            <div class="fw-semibold">{{ $ledger->customer->name }}</div>
                            @if ($ledger->customer->address)
                                <div>{{ $ledger->customer->address }}</div>
                            @endif
                            @if ($ledger->customer->email)
                                <div>Email: {{ $ledger->customer->email }}</div>
                            @endif
                            @if ($ledger->customer->phone)
                                <div>Phone: {{ $ledger->customer->phone }}</div>
                            @endif
                            @if ($ledger->customer->customer_code)
                                <div>Customer Code: {{ $ledger->customer->customer_code }}</div>
                            @endif
                        </div>
                    </div>

                    @if ($ledger->entry_type === 'sale')
                        <div class="table-responsive">
                            <table class="table align-middle table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>{{ $ledger->product_name ?? 'N/A' }}</td>
                                        <td class="text-end">৳{{ number_format((float) $ledger->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $ledger->quantity }}</td>
                                        <td class="text-end">৳{{ number_format((float) $ledger->total_amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- sale তৈরির সময়ের payment aggregate-এ আছে, পরে পাওয়া কিস্তি নিচের history-তে থাকে। --}}
                        @if ($ledger->payments->isNotEmpty())
                            <div class="mt-4">
                                <h6 class="fw-bold">Payment History</h6>
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Received By</th>
                                            <th class="text-end">Amount Paid</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ledger->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                                <td>{{ $payment->receiver->name ?? 'N/A' }}</td>
                                                <td class="text-end">৳{{ number_format((float) $payment->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="mt-4 row justify-content-end">
                            <div class="col-md-6 col-lg-5">
                                <div class="py-1 d-flex justify-content-between">
                                    <span class="fw-bold fs-5">Total</span>
                                    <span class="fw-bold fs-5">৳{{ number_format((float) $ledger->total_amount, 2) }}</span>
                                </div>
                                <div class="py-1 d-flex justify-content-between">
                                    <span>Total Received</span>
                                    <span>৳{{ number_format((float) $ledger->payment_amount, 2) }}</span>
                                </div>
                                <div class="py-1 d-flex justify-content-between text-danger fw-bold">
                                    <span>Due</span>
                                    <span>৳{{ number_format((float) $ledger->due_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- standalone customer payment entry receipt দেখায়। --}}
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th>Amount Received</th>
                                        <td class="text-end">৳{{ number_format((float) $ledger->payment_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Paid By</th>
                                        <td>{{ $ledger->paid_by ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Received By</th>
                                        <td>{{ $ledger->receiver->name ?? 'N/A' }}</td>
                                    </tr>
                                    @if ($ledger->notes)
                                        <tr>
                                            <th>Notes</th>
                                            <td>{{ $ledger->notes }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="pt-4 mt-5 row">
                        <div class="col-6"><div class="pt-2 border-top">Customer Signature</div></div>
                        <div class="col-6 text-end"><div class="pt-2 border-top">Authorized Signature</div></div>
                    </div>
                    <p class="mt-4 mb-0 text-center text-muted small">Thank you for your business.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* প্রিন্টে ড্যাশবোর্ড navigation সরিয়ে শুধু customer invoice/receipt রাখা হয়। */
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
@endsection
