@extends('dashboard.dashboard')

@section('title', 'Billing Transactions')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-1">Organization Billing Transactions</h4>
                    <div class="text-muted">Billing details and receipts, filtered by billing month</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('billing.records') }}" class="btn btn-outline-primary">
                        <i class="bx bx-history me-1"></i>BillingRecord
                    </a>
                    @can('billing.entry')
                        <a href="{{ route('billing.entry') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-receipt me-1"></i>BillingEntry
                        </a>
                    @endcan
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-3 card">
                <div class="card-body">
                    <form action="{{ route('billing.transactions') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-sm-4 col-lg-3">
                            <label for="from_month" class="form-label">From billing month</label>
                            <input type="month" id="from_month" name="from_month"
                                value="{{ $fromMonth }}" class="form-control">
                        </div>
                        <div class="col-sm-4 col-lg-3">
                            <label for="to_month" class="form-label">To billing month</label>
                            <input type="month" id="to_month" name="to_month"
                                value="{{ $toMonth }}" class="form-control">
                        </div>
                        <div class="col-sm-4 col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Filter
                            </button>
                            <a href="{{ route('billing.transactions') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-3 row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted">Bills</div>
                            <h4 class="mb-0">{{ $billCount }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted">Total billed</div>
                            <h4 class="mb-0">৳{{ number_format($totalBillAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted">Total received</div>
                            <h4 class="mb-0 text-success">৳{{ number_format($totalPaidAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted">Outstanding</div>
                            <h4 class="mb-0 text-danger">৳{{ number_format($totalDueAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Billing month</th>
                                <th>Due date</th>
                                <th>Billing details</th>
                                <th class="text-end">Bill amount</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Due</th>
                                <th>Status</th>
                                <th>Payment transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td class="text-nowrap">{{ $entry->billing_month->format('F Y') }}</td>
                                    <td class="text-nowrap">{{ $entry->due_date->format('d M Y') }}</td>
                                    <td>{{ $entry->description ?: 'Monthly organization bill' }}</td>
                                    <td class="text-end text-nowrap">৳{{ number_format((float) $entry->amount, 2) }}</td>
                                    <td class="text-end text-nowrap">৳{{ number_format($entry->paidAmount(), 2) }}</td>
                                    <td class="text-end text-nowrap">
                                        ৳{{ number_format($entry->outstandingAmount(), 2) }}
                                    </td>
                                    <td>
                                        @if ($entry->outstandingAmount() > 0)
                                            <span class="badge bg-warning text-dark">Due</span>
                                        @else
                                            <span class="badge bg-success">Paid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse ($entry->records as $record)
                                            <div class="mb-1 text-nowrap">
                                                {{ $record->received_at->format('d M Y') }}:
                                                ৳{{ number_format((float) $record->amount, 2) }}
                                                @if ($record->payment_method)
                                                    · {{ $record->payment_method }}
                                                @endif
                                                @if ($record->reference)
                                                    · Ref {{ $record->reference }}
                                                @endif
                                                @if ($record->reversed_at)
                                                    <span class="badge bg-secondary">Reverted</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-muted">No payment recorded</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 text-center text-muted">
                                        No billing details found for the selected months.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
