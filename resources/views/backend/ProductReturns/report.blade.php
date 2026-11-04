@extends('dashboard.dashboard')

@section('title', 'Product Return Report')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mt-2 mb-0">Product Return Report</h4>
                </div>
                <div class="gap-2 d-flex">
                    @can('product.return')
                        <a href="{{ route('product-returns.index') }}" class="btn btn-outline-primary">
                            <i class="bx bx-undo me-1"></i>New Product Return
                        </a>
                    @endcan
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            <div class="mb-4 border-0 shadow-sm card no-print">
                <div class="card-header">
                    <h5 class="mb-0">Filter by Return Date</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('product-returns.report') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="from_date" class="form-label">From date</label>
                            <input type="date" name="from_date" id="from_date" class="form-control"
                                value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-4">
                            <label for="to_date" class="form-label">To date</label>
                            <input type="date" name="to_date" id="to_date" class="form-control"
                                value="{{ $toDate }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-filter-alt me-1"></i>Apply Filter
                            </button>
                            <a href="{{ route('product-returns.report') }}" class="btn btn-outline-secondary">Today</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Return Transactions</div>
                            <h4 class="mb-0">{{ number_format($returnCount) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Returned Quantity</div>
                            <h4 class="mb-0">{{ number_format($totalQuantity) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Return Value</div>
                            <h4 class="mb-0">৳{{ number_format($totalReturnAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Refund Paid</div>
                            <h4 class="mb-0">৳{{ number_format($totalRefundAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-0 shadow-sm card">
                <div class="card-header">
                    <h5 class="mb-0">
                        Return Transactions
                        <span class="text-muted small">({{ $fromDate }} to {{ $toDate }})</span>
                    </h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Return No.</th>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Return Value</th>
                                <th class="text-end">Refund</th>
                                <th>Processed By</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $return)
                                <tr>
                                    <td>{{ $return->return_no }}</td>
                                    <td>{{ $return->return_date->format('d M Y') }}</td>
                                    <td>{{ $return->invoice_no }}</td>
                                    <td>{{ $return->product_name }}</td>
                                    <td>
                                        {{ $return->invoice?->customer_name ?: 'Walk-in Customer' }}
                                        <div class="small text-muted">{{ $return->invoice?->customer_phone }}</div>
                                    </td>
                                    <td class="text-center">{{ $return->quantity }}</td>
                                    <td class="text-end">৳{{ number_format($return->return_amount, 2) }}</td>
                                    <td class="text-end">৳{{ number_format($return->refund_amount, 2) }}</td>
                                    <td>{{ $return->returnedBy?->name ?? 'N/A' }}</td>
                                    <td>{{ $return->reason ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-4 text-center text-muted">
                                        No product returns found for this date range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-3">{{ $rows->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
