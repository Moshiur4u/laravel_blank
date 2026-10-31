@extends('dashboard.dashboard')

@section('title', 'Delivery Challan ' . $invoice->invoice_no)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Sales
                </a>
                @can('sales.document.watermark')
                    @if ($organization?->logo)
                        <label class="btn btn-outline-secondary mb-0">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1">
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print Delivery Challan
                </button>
            </div>

            <div id="print-document" class="border-0 shadow-sm card print-document"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
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

                    <div class="mb-4 row align-items-start">
                        <div class="col-7">
                            <h2 class="mb-1 text-primary fw-bold">Delivery Challan</h2>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-5 text-end">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            <div><i class="bx bx-user me-1" aria-hidden="true"></i>Name: {{ $invoice->customer_name ?? $invoice->customer?->name ?? 'Walk-in Customer' }}</div>
                            <div><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone: {{ $invoice->customer_phone ?? $invoice->customer?->phone ?? 'N/A' }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div><i class="bx bx-map me-1" aria-hidden="true"></i>Address: {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                    </div>

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
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-center">{{ $item->product?->stock_unit ?: 'Unit' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td colspan="2" class="text-end">Total Quantity</td>
                                    <td class="text-center">{{ $invoice->items->sum('quantity') }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($invoice->notes)
                        <div class="mt-4"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                    @endif
                    <div class="pt-4 mt-5 row">
                        <div class="col-6"><div class="pt-2 border-top">Customer Signature</div></div>
                        <div class="col-6 text-end"><div class="pt-2 border-top">Delivered by {{ $invoice->employee->name ?? 'N/A' }}</div></div>
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
@endsection
