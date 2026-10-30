@extends('dashboard.dashboard')

@section('title', 'Sales Report')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mt-2 mb-0">Sales Report</h4>
                </div>
                <div class="gap-2 d-flex">
                    <a href="{{ route('dashboardbody') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('sales.report.export', request()->except('page')) }}" class="btn btn-success">
                        <i class="bx bx-spreadsheet me-1"></i>Export Excel
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            <div class="mb-4 border-0 shadow-sm card no-print">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-filter-alt me-1"></i>Filter Sales</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('sales.report') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-6 col-xl-3">
                            <label for="start_date" class="form-label">From date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ $startDate }}">
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <label for="end_date" class="form-label">To date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-6 col-xl-2">
                            <label for="product_id" class="form-label">Product</label>
                            <select name="product_id" id="product_id" class="form-select">
                                <option value="">All products</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                        {{ (string) ($filters['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                        {{ $product->productName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-xl-2">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">All employees</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                        {{ (string) ($filters['employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-xl-2">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">All customers</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        {{ (string) ($filters['customer_id'] ?? '') === (string) $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="gap-2 col-12 d-flex">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Apply Filters
                            </button>
                            <a href="{{ route('sales.report') }}" class="btn btn-outline-secondary">Today</a>
                        </div>
                    </form>
                    @if ($errors->any())
                        <div class="mt-3 mb-0 alert alert-danger" role="alert">
                            @foreach ($errors->all() as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-0 shadow-sm card print-report">
                <div class="p-4 card-body">
                    @include('backend.partials.organizationHeader', [
                        'reportTitle' => 'Sales Report',
                        'reportSubtitle' =>
                            'Period: ' .
                            ($startDate
                                ? \Illuminate\Support\Carbon::parse($startDate)->format('d M Y')
                                : 'Beginning') .
                            ' – ' .
                            ($endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') : 'Present'),
                        'reportGeneratedAt' => $reportGeneratedAt->format('d M Y, h:i A'),
                    ])
                    <div class="mb-4 row align-items-start d-print-none">
                        <div class="col-md-8">
                            <h2 class="mb-1 text-primary fw-bold">Sales Report</h2>
                            <div class="text-muted">
                                Period:
                                <strong>
                                    {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') : 'Beginning' }}
                                    –
                                    {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') : 'Present' }}
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="fw-bold">Report Generated</div>
                            <div>{{ $reportGeneratedAt->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>

                    <div class="mb-4 row g-3 print-summary">
                        <div class="col-6 col-md-3 summary-card">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Sale Amount</div>
                                    <div class="fs-4 fw-bold text-primary">৳{{ number_format($totalSalesAmount, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 summary-card">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Profit</div>
                                    <div class="fs-4 fw-bold text-success">৳{{ number_format($totalProfit ?? 0, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 summary-card">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Due</div>
                                    <div class="fs-4 fw-bold text-danger">৳{{ number_format($totalDue, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 summary-card">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Sales (Invoices)</div>
                                    <div class="fs-4 fw-bold">{{ number_format($totalSalesCount) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold"><i class="bx bx-package text-primary me-2"></i>Product-wise Sales &
                            Profit</h5>
                        <small class="text-muted">Purchase price and individual product profit</small>
                    </div>
                    <div class="mb-4 table-responsive">
                        <table class="table align-middle table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Product</th>
                                    <th class="text-end">Purchase Price</th>
                                    <th class="text-end">Selling Price</th>
                                    <th class="text-end">Quantity Sold</th>
                                    <th class="text-end">Total Sales</th>
                                    <th class="text-end">Purchase Cost </th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">VAT</th>
                                    <th class="text-end">AIT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topProducts as $index => $topProduct)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="fw-semibold">{{ $topProduct->product_name }}</td>
                                        <td class="text-end">
                                            ৳{{ number_format((float) ($topProduct->purchase_price ?? 0), 2) }}</td>
                                        <td class="text-end">
                                            ৳{{ number_format((float) ($topProduct->avg_selling_price ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((int) $topProduct->quantity_sold) }}</td>
                                        <td class="text-end">৳{{ number_format((float) $topProduct->revenue, 2) }}</td>
                                        <td class="text-end">
                                            ৳{{ number_format((float) ($topProduct->total_cost ?? 0), 2) }}</td>
                                        <td class="text-end">
                                            <span
                                                class="fw-bold {{ (float) ($topProduct->profit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                ৳{{ number_format((float) ($topProduct->profit ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="fw-bold {{ (float) ($topProduct->vat_amount ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                ৳{{ number_format((float) ($topProduct->vat_amount ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            ৳{{ number_format((float) ($topProduct->ait_amount ?? 0), 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="py-4 text-center text-muted">No product sales found for
                                            the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($topProducts->isNotEmpty())
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="4" class="text-end">Total:</td>
                                        <td class="text-end">{{ number_format($topProducts->sum('quantity_sold')) }}</td>
                                        <td class="text-end">৳{{ number_format($topProducts->sum('revenue'), 2) }}</td>
                                        <td class="text-end">৳{{ number_format($topProducts->sum('total_cost'), 2) }}</td>
                                        <td class="text-end text-success">
                                            ৳{{ number_format($topProducts->sum('profit'), 2) }}</td>
                                        <td class="text-end text-success">
                                            ৳{{ number_format($topProducts->sum('vat_amount'), 2) }}</td>
                                        <td class="text-end text-success">
                                            ৳{{ number_format($topProducts->sum('ait_amount'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    <h5 class="mb-3 fw-bold"><i class="bx bx-receipt text-primary me-2"></i>Sales Details</h5>
                    <div class="table-responsive">
                        <table class="table align-middle table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Employee</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Sale Amount</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    @php
                                        $invoiceProfit =
                                            $invoice->items->sum(fn($item) => (float) $item->profit) -
                                            (float) $invoice->discount_amount;
                                    @endphp
                                    <tr>
                                        <td>{{ $invoice->invoice_date?->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <a href="{{ route('sales.invoice', $invoice->id) }}" class="no-print">
                                                {{ $invoice->invoice_no }}
                                            </a>
                                            <span class="d-none d-print-inline">{{ $invoice->invoice_no }}</span>
                                        </td>
                                        <td>{{ $invoice->customer?->name ?? ($invoice->customer_name ?? 'Walk-in Customer') }}
                                        </td>
                                        <td>{{ $invoice->employee?->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($invoice->items_count) }}</td>
                                        <td class="text-end">
                                            ৳{{ number_format((float) $invoice->subtotal - (float) $invoice->discount_amount + (float) $invoice->vat_amount + (float) $invoice->ait_amount, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="fw-semibold {{ $invoiceProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                                ৳{{ number_format($invoiceProfit, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-4 text-center text-muted">No sales found for the
                                            selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="no-print">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }

        @media print {

            .no-print,
            .sidebar-wrapper,
            header,
            .topbar,
            .top-header,
            .footer {
                display: none !important;
            }

            .page-wrapper,
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-report {
                border: 0 !important;
                box-shadow: none !important;
            }

            .print-report,
            .print-report .card-body,
            .print-report .table-responsive {
                overflow: visible !important;
            }

            .print-report table {
                width: 100% !important;
                table-layout: fixed;
            }

            .print-report th,
            .print-report td {
                padding: 0.3rem !important;
                font-size: 9pt;
                overflow-wrap: anywhere;
                white-space: normal !important;
            }

            .print-report thead {
                display: table-header-group;
            }

            .print-report tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-summary {
                display: flex !important;
                flex-wrap: nowrap !important;
                margin-bottom: 1rem !important;
                break-inside: avoid;
            }

            .print-summary .summary-card {
                display: block !important;
                flex: 0 0 25% !important;
                max-width: 25% !important;
                visibility: visible !important;
            }

            .print-summary .card {
                display: block !important;
                min-height: 0 !important;
                border: 1px solid #dee2e6 !important;
                background-color: #f8f9fa !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .print-summary .card-body {
                display: block !important;
                padding: 0.75rem !important;
            }
        }
    </style>
@endsection
