@extends('dashboard.dashboard')

@section('content')
    {{-- The controller prepares report rows and totals; this view only formats and displays them. --}}
    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mt-2 mb-0">Product Inventory Report</h4>
                </div>
                <div class="flex-wrap gap-2 d-flex">
                    <a href="{{ route('dashboardbody') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('product.inventory.report.export') }}" class="btn btn-success">
                        <i class="bx bx-spreadsheet me-1"></i>Export Excel
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            <div class="border-0 shadow-sm card print-report">
                <div class="p-3 card-body p-md-4">
                    @include('backend.partials.organizationHeader', [
                        'reportTitle' => 'Product Inventory Report',
                        'reportSubtitle' => 'Purchase, stock, expiry, and supplier details by product',
                        'reportGeneratedAt' => now()->format('d M Y, h:i A'),
                    ])
                    <div class="flex-wrap gap-2 mb-4 d-flex align-items-start justify-content-between">
                        <div class="d-print-none">
                            <h2 class="mb-1 text-primary fw-bold">Product Inventory</h2>
                            <p class="mb-0 text-muted">Purchase, stock, expiry, and supplier details by product</p>
                        </div>
                        <div class="small text-muted d-print-none">
                            <span class="fw-bold text-body">Report Generated:</span>
                            {{ now()->format('d M Y, h:i A') }}
                        </div>
                    </div>

                    <div class="mb-4 row g-3">
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Products</div>
                                    <div class="fs-4 fw-bold">{{ number_format($total_products) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Purchased Quantity</div>
                                    <div class="fs-4 fw-bold">{{ number_format($total_purchased_quantity, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Purchase Amount</div>
                                    <div class="fs-4 fw-bold text-primary">৳{{ number_format($total_purchase_amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Current Stock</div>
                                    <div class="fs-4 fw-bold">{{ number_format($total_stock, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl">
                            <div class="border-0 card bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Expired Products</div>
                                    <div class="fs-4 fw-bold text-danger">
                                        {{ number_format($total_expired_products) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Inventory Details</h5>
                        <span class="text-muted small">{{ number_format($total_products) }} products</span>
                    </div>

                    <h6 class="mb-3 fw-bold">Stock, Expiry &amp; Supplier Details</h6>
                    <div class="table-responsive">
                        <table id="myTable" class="table mb-0 align-middle table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Product ID</th>
                                    <th scope="col">Product</th>
                                    <th scope="col" class="text-end">Purchased Qty</th>
                                    <th scope="col" class="text-end">Purchase Amount</th>
                                    <th scope="col" class="text-end">Total Qty Sold</th>
                                    <th scope="col" class="text-end">Total Sales</th>
                                    <th scope="col" class="text-end">Profit</th>
                                    <th scope="col" class="text-end">Current Stock</th>
                                    <th scope="col">Expiry</th>
                                    <th scope="col">Supplier</th>
                                    <th scope="col">Phone Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="text-nowrap">SKU: #PRD-{{ str_pad((string) $row['id'], 5, '0', STR_PAD_LEFT) }}</td>
                                        <td class="fw-semibold">{{ $row['name'] }}</td>
                                        <td class="text-end">{{ number_format($row['purchased_quantity'], 2) }}</td>
                                        <td class="text-end">৳{{ number_format($row['purchase_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['quantity_sold'], 2) }}</td>
                                        <td class="text-end">৳{{ number_format($row['revenue'], 2) }}</td>
                                        <td class="text-end">
                                            <span
                                                class="fw-bold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                ৳{{ number_format($row['profit'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($row['stock'], 2) }}</td>
                                        <td>
                                            @if ($row['expiry_date'])
                                                <div class="text-nowrap">{{ $row['expiry_date']->format('d M Y') }}</div>
                                                @switch($row['expiry_status'])
                                                    @case('expired')
                                                        <span class="badge bg-danger">Expired</span>
                                                    @break

                                                    @case('expiring')
                                                        <span class="badge bg-warning text-dark">Expiring Soon</span>
                                                    @break

                                                    @default
                                                        <span class="badge bg-success">Valid</span>
                                                @endswitch
                                            @else
                                                <span class="text-muted">No expiry date</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse ($row['suppliers'] as $supplier)
                                                <div>{{ $supplier['name'] }}</div>
                                            @empty
                                                <span class="text-muted">N/A</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @forelse ($row['suppliers'] as $supplier)
                                                <div class="text-nowrap">{{ $supplier['phone'] ?: 'N/A' }}</div>
                                            @empty
                                                <span class="text-muted">N/A</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="py-4 text-center text-muted">No products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($rows->isNotEmpty())
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">Total:</td>
                                        <td class="text-end">{{ number_format($rows->sum('purchased_quantity'), 2) }}</td>
                                        <td class="text-end">৳{{ number_format($rows->sum('purchase_amount'), 2) }}</td>
                                        <td class="text-end">{{ number_format($rows->sum('quantity_sold'), 2) }}</td>
                                        <td class="text-end">৳{{ number_format($rows->sum('revenue'), 2) }}</td>
                                        <td class="text-end text-success">৳{{ number_format($rows->sum('profit'), 2) }}</td>
                                        <td class="text-end">{{ number_format($rows->sum('stock'), 2) }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
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
        }
    </style>
@endsection
