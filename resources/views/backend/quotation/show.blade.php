@extends('dashboard.dashboard')
@section('title', 'Quotation - ' . $quotation->quotation_no)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Action Toolbar (hidden on print) -->
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('quotation.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> All Quotations
                </a>
                <a href="{{ route('quotation.create') }}" class="btn btn-outline-primary">
                    <i class="bx bx-plus me-1"></i> New Quotation
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Print Quotation
                </button>
            </div>

            <div class="border-0 shadow-sm card quotation-document">
                <div class="p-4 card-body p-md-5">

                    <!-- Top Header: Organization Info on One Side, Document Title & Customer Info on the Other Side -->
                    <div class="row align-items-start mb-4 pb-3 border-bottom">
                        <!-- Organization Information (One Side) -->
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                @if ($organization?->logo)
                                    <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                        alt="{{ $organization->name ?: 'Organization Logo' }}"
                                        style="max-width: 90px; max-height: 70px; object-fit: contain;">
                                @else
                                    <img src="{{ asset('assets/images/logo-icon.png') }}"
                                        alt="Logo"
                                        style="max-width: 60px; max-height: 60px; object-fit: contain;">
                                @endif
                                <div>
                                    <h3 class="mb-0 fw-bold text-dark">{{ $organization?->name ?: 'My-POS Enterprise' }}</h3>
                                    <span class="badge bg-light-primary text-primary">Official Quotation</span>
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                @if ($organization?->address)
                                    <div><i class="bx bx-map-pin me-1 text-primary"></i>{{ $organization->address }}</div>
                                @endif
                                @if ($organization?->phone)
                                    <div><i class="bx bx-phone me-1 text-primary"></i>Phone: {{ $organization->phone }}</div>
                                @endif
                                @if ($organization?->email)
                                    <div><i class="bx bx-envelope me-1 text-primary"></i>Email: {{ $organization->email }}</div>
                                @endif
                            </div>
                        </div>

                        <!-- Document Title & Customer Information (Other Side) -->
                        <div class="col-6 text-end">
                            <h2 class="mb-1 text-primary fw-bold text-uppercase tracking-wider">Quotation</h2>
                            <div class="text-muted small mb-3">
                                <div><strong>Ref No:</strong> {{ $quotation->quotation_no }}</div>
                                <div><strong>Date:</strong> {{ $quotation->quotation_date->format('d M Y') }}</div>
                            </div>

                            <div class="bg-light p-3 rounded text-end border">
                                <h6 class="mb-1 fw-bold text-dark">Quotation For:</h6>
                                <div class="fw-semibold text-primary fs-6">{{ $quotation->customer_name }}</div>
                                <div><i class="bx bx-phone me-1"></i>{{ $quotation->customer_phone }}</div>
                                @if ($quotation->customer_address)
                                    <div class="small text-muted"><i class="bx bx-map me-1"></i>{{ $quotation->customer_address }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Products Table (Customer Invoice Format) -->
                    <div class="table-responsive mb-4">
                        <table class="table align-middle table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th>Product Description</th>
                                    <th style="width: 150px;" class="text-end">Unit Price</th>
                                    <th style="width: 120px;" class="text-center">Quantity</th>
                                    <th style="width: 160px;" class="text-end">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quotation->items as $index => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                                            @if ($item->product_id)
                                                <small class="text-muted">Item Code: PRD-{{ str_pad($item->product_id, 5, '0', STR_PAD_LEFT) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end">৳{{ number_format((float)$item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end fw-bold">৳{{ number_format((float)$item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals Section (Customer Invoice Format) -->
                    <div class="row justify-content-end mb-4">
                        <div class="col-md-6 col-lg-5">
                            <div class="py-1 d-flex justify-content-between">
                                <span class="text-secondary">Subtotal</span>
                                <span class="fw-semibold">৳{{ number_format((float)$quotation->subtotal, 2) }}</span>
                            </div>

                            @if ((float)$quotation->discount_amount > 0)
                                <div class="py-1 d-flex justify-content-between text-danger">
                                    <span>
                                        Discount
                                        @if ($quotation->discount_type === 'percentage')
                                            ({{ rtrim(rtrim(number_format((float)$quotation->discount_value, 2), '0'), '.') }}%)
                                        @endif
                                    </span>
                                    <span>- ৳{{ number_format((float)$quotation->discount_amount, 2) }}</span>
                                </div>
                            @endif

                            @if ((float)$quotation->vat_amount > 0)
                                <div class="py-1 d-flex justify-content-between text-secondary">
                                    <span>VAT ({{ rtrim(rtrim(number_format((float)$quotation->vat_percentage, 2), '0'), '.') }}%)</span>
                                    <span>+ ৳{{ number_format((float)$quotation->vat_amount, 2) }}</span>
                                </div>
                            @endif

                            <hr class="my-2">

                            <div class="py-2 d-flex justify-content-between align-items-center bg-light-primary rounded px-3">
                                <span class="fw-bold fs-5 text-primary">Total Price</span>
                                <span class="fw-bold fs-4 text-primary">৳{{ number_format((float)$quotation->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions Section -->
                    @if ($quotation->terms_conditions)
                        <div class="mt-4 p-3 rounded bg-light border">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="bx bx-check-shield text-primary me-1"></i> Terms & Conditions
                            </h6>
                            <div class="small text-secondary" style="white-space: pre-line; line-height: 1.6;">
                                {{ $quotation->terms_conditions }}
                            </div>
                        </div>
                    @endif

                    @if ($quotation->notes)
                        <div class="mt-3 p-3 rounded bg-light border">
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="bx bx-note text-secondary me-1"></i> Special Notes
                            </h6>
                            <div class="small text-muted">{{ $quotation->notes }}</div>
                        </div>
                    @endif

                    <!-- Signatures Block -->
                    <div class="pt-5 mt-4 row">
                        <div class="col-6">
                            <div class="pt-2 border-top text-center w-75">
                                <div class="fw-semibold text-dark">Customer Acceptance</div>
                                <div class="small text-muted">Signature & Date</div>
                            </div>
                        </div>
                        <div class="col-6 text-end d-flex justify-content-end">
                            <div class="pt-2 border-top text-center w-75">
                                <div class="fw-semibold text-dark">Authorized Signature</div>
                                <div class="small text-muted">Prepared by: {{ $quotation->user->name ?? 'Sales Officer' }}</div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 mb-0 text-center text-muted small">
                        This is a computer-generated quotation document. Thank you for your business.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Specific Stylesheet -->
    <style>
        @media print {
            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer,
            .back-to-top {
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
        }
    </style>
@endsection
