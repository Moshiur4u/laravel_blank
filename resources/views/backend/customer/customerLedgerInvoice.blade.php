@extends('dashboard.dashboard')
@section('title', ($ledger->entry_type === 'payment' ? 'Customer Payment Receipt - ' : 'Customer Invoice - ') . $invoiceNumber)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- প্রিন্ট বা লেজারে ফেরার control কাগজে দেখানো হয় না। --}}
            <div class="d-flex justify-content-end gap-2 mb-3 no-print">
                <a href="{{ route('customer.ledger', $ledger->customer_id) }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Customer Ledger
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-start mb-4">
                        <div class="col-7">
                            <h2 class="text-primary fw-bold mb-1">{{ $ledger->entry_type === 'payment' ? 'Customer Payment Receipt' : 'Customer Invoice' }}</h2>
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
                            <table class="table table-bordered align-middle">
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

                        <div class="row justify-content-end mt-4">
                            <div class="col-md-6 col-lg-5">
                                <div class="d-flex justify-content-between py-1">
                                    <span class="fw-bold fs-5">Total</span>
                                    <span class="fw-bold fs-5">৳{{ number_format((float) $ledger->total_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span>Total Received</span>
                                    <span>৳{{ number_format((float) $ledger->payment_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 text-danger fw-bold">
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

                    <div class="row mt-5 pt-4">
                        <div class="col-6"><div class="border-top pt-2">Customer Signature</div></div>
                        <div class="col-6 text-end"><div class="border-top pt-2">Authorized Signature</div></div>
                    </div>
                    <p class="text-center text-muted small mt-4 mb-0">Thank you for your business.</p>
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
