@extends('dashboard.dashboard')
@section('title', 'Blank Sales Invoice')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Sales
                </a>
                @can('sales.document.watermark')
                    @if ($organization?->logo)
                        <label class="mb-0 btn btn-outline-secondary">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1">
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print Blank Invoice
                </button>
            </div>

            <div id="print-document" class="border-0 shadow-sm card print-document invoice-print-area"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                <div class="p-4 card-body p-md-5">
                    @if ($organization?->logo || $organization?->name || $organization?->address || $organization?->phone || $organization?->email)
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

                    <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7">
                            <h2 class="mb-1 text-primary fw-bold">Sales Invoice</h2>
                            <div class="text-muted">Invoice: <strong>____________________</strong></div>
                            <div class="text-muted">Date: ________________________</div>
                        </div>
                        <div class="col-5 text-end">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            <div>Name: ____________________________</div>
                            <div>Phone: ____________________________</div>
                            <div>Address: __________________________</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-bordered invoice-table blank-invoice-table">
                            <colgroup>
                                <col style="width: 6%;">
                                <col style="width: 39%;">
                                <col style="width: 20%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Product</th>
                                    <th class="text-end">Unit Selling Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($row = 1; $row <= 12; $row++)
                                    <tr>
                                        <td class="text-center">{{ $row }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endfor
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Discount</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">VAT / Tax</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Total</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Amount Paid</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Due</th>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

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
                                <div class="small text-muted">Prepared by: __________________</div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 mb-0 text-center text-muted small">Thank you for your business.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .blank-invoice-table tbody tr:not(:nth-last-child(-n + 6)) {
            height: 28px;
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

            .page-wrapper,
            .page-content {
                margin: 0 !important;
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

            .print-document > .card-body {
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
@endsection
