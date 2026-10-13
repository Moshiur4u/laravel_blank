@extends('dashboard.dashboard')

@section('title', 'Sales Report')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mb-0 mt-2">Sales Report</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dashboardbody') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Dashboard
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 no-print">
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
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Apply Filters
                            </button>
                            <a href="{{ route('sales.report') }}" class="btn btn-outline-secondary">Today</a>
                        </div>
                    </form>
                    @if ($errors->any())
                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                            @foreach ($errors->all() as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm print-report">
                <div class="card-body p-4">
                    <div class="row align-items-start mb-4">
                        <div class="col-md-8">
                            <h2 class="text-primary fw-bold mb-1">Sales Report</h2>
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

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Sale Amount</div>
                                    <div class="fs-4 fw-bold text-primary">৳{{ number_format($totalSalesAmount, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Due</div>
                                    <div class="fs-4 fw-bold text-danger">৳{{ number_format($totalDue, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Sales (Invoices)</div>
                                    <div class="fs-4 fw-bold">{{ number_format($totalSalesCount) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Top Products by Revenue</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Product</th>
                                    <th class="text-end">Quantity Sold</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topProducts as $index => $topProduct)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $topProduct->product_name }}</td>
                                        <td class="text-end">{{ number_format($topProduct->quantity_sold) }}</td>
                                        <td class="text-end">৳{{ number_format($topProduct->revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-4 text-center text-muted">No product sales found for the selected filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h5 class="fw-bold mb-3">Sales Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Employee</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Sale Amount</th>
                                    <th class="text-end">Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_date?->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <a href="{{ route('sales.invoice', $invoice->id) }}" class="no-print">
                                                {{ $invoice->invoice_no }}
                                            </a>
                                            <span class="d-none d-print-inline">{{ $invoice->invoice_no }}</span>
                                        </td>
                                        <td>{{ $invoice->customer?->name ?? $invoice->customer_name ?? 'Walk-in Customer' }}</td>
                                        <td>{{ $invoice->employee?->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($invoice->items_count) }}</td>
                                        <td class="text-end">
                                            ৳{{ number_format((float) $invoice->subtotal - (float) $invoice->discount_amount + (float) $invoice->vat_amount, 2) }}
                                        </td>
                                        <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-4 text-center text-muted">No sales found for the selected filters.</td></tr>
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
        @media print {
            .no-print,
            .sidebar-wrapper,
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
        }
    </style>
@endsection
