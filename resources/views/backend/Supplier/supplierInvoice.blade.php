@extends('dashboard.dashboard')
@section('title', 'Supplier Invoice - ' . $invoiceNumber)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- প্রিন্ট এবং লেজারে ফেরার নিয়ন্ত্রণগুলো কাগজের ইনভয়েসে দেখানো হবে না। --}}
            <div class="d-flex justify-content-end gap-2 mb-3 no-print">
                <a href="{{ route('supplier.ledger', $ledger->supplier_id) }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Supplier Ledger
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print Invoice
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-start mb-4">
                        <div class="col-7">
                            <h2 class="text-primary fw-bold mb-1">Supplier Invoice</h2>
                            <div class="text-muted">Invoice: <strong>{{ $invoiceNumber }}</strong></div>
                            {{-- লেজারের তৈরির তারিখই ইনভয়েসের তারিখ হিসেবে দেখানো হচ্ছে। --}}
                            <div class="text-muted">Date: {{ $ledger->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-5 text-end">
                            {{-- invoice-এ supplier-এর সংরক্ষিত নাম, যোগাযোগ ও ঠিকানা দেখানো হয়। --}}
                            @if ($ledger->supplier->logo)
                                <img src="{{ asset('uploads/suppliers/' . $ledger->supplier->logo) }}"
                                    alt="{{ $ledger->supplier->name }} logo"
                                    style="max-width: 120px; max-height: 90px; object-fit: contain;">
                            @endif
                            <div class="fw-semibold">{{ $ledger->supplier->name }}</div>
                            @if ($ledger->supplier->address)
                                <div>{{ $ledger->supplier->address }}</div>
                            @endif
                            @if ($ledger->supplier->email)
                                <div>Email: {{ $ledger->supplier->email }}</div>
                            @endif
                            @if ($ledger->supplier->phone)
                                <div>Phone: {{ $ledger->supplier->phone }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-end">Unit Purchase Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- এই ইনভয়েসটি নির্বাচিত ledger row-র product ও amount-ই দেখায়। --}}
                                <tr>
                                    <td>1</td>
                                    <td>{{ $ledger->product->productName ?? 'N/A' }}</td>
                                    <td class="text-end">৳{{ number_format($ledger->price ?? 0, 2) }}</td>
                                    <td class="text-center">{{ $ledger->quantity ?? 0 }}</td>
                                    <td class="text-end">৳{{ number_format($ledger->total ?? 0, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- প্রতিটি তারিখভিত্তিক payment invoice-এ দেখিয়ে জমার পরিষ্কার হিসাব রাখা হয়। --}}
                    @if ($ledger->payments->isNotEmpty())
                        <div class="mt-4">
                            <h6 class="fw-bold">Payment History</h6>
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payment Date</th>
                                        <th class="text-end">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ledger->payments as $paymentRecord)
                                        <tr>
                                            <td>{{ $paymentRecord->payment_date->format('d M Y') }}</td>
                                            <td class="text-end">৳{{ number_format((float) $paymentRecord->amount, 2) }}</td>
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
                                <span class="fw-bold fs-5">৳{{ number_format($ledger->total ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Amount Paid</span>
                                <span>৳{{ number_format($ledger->payment ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-danger fw-bold">
                                <span>Due</span>
                                <span>৳{{ number_format($ledger->due ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-5 pt-4">
                        <div class="col-6"><div class="border-top pt-2">Supplier Signature</div></div>
                        <div class="col-6 text-end"><div class="border-top pt-2">Authorized Signature</div></div>
                    </div>
                    <p class="text-center text-muted small mt-4 mb-0">Thank you for your business.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* প্রিন্টে ড্যাশবোর্ডের নেভিগেশন ও নিয়ন্ত্রণ লুকিয়ে শুধু ইনভয়েস রাখা হয়। */
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
