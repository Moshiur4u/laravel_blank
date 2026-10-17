@extends('dashboard.dashboard')

@section('title', 'Daily Employee Voucher Report')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mb-0 mt-2">Daily Employee Voucher Report</h4>
                </div>
                <div class="d-flex gap-2">
                    @can('employee_payment_voucher.entry')
                    <a href="{{ route('employee-payment.index') }}" class="btn btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>Create Voucher
                    </a>
                    @endcan
                    <a href="{{ route('employee-payment.report.export', request()->except('page')) }}" class="btn btn-success">
                        <i class="bx bx-spreadsheet me-1"></i>Export Excel
                    </a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-filter-alt me-1"></i>Filter Report</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('employee-payment.report') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="start_date" class="form-label">Created from</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ old('start_date', $startDate) }}">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="end_date" class="form-label">Created to</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ old('end_date', $endDate) }}">
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="employee_id" class="form-label">Employee name</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">All employees</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                        {{ (string) old('employee_id', $validated['employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="created_by" class="form-label">Created by</label>
                            <select name="created_by" id="created_by" class="form-select">
                                <option value="">All creators</option>
                                @foreach ($creators as $creator)
                                    <option value="{{ $creator->id }}"
                                        {{ (string) old('created_by', $validated['created_by'] ?? '') === (string) $creator->id ? 'selected' : '' }}>
                                        {{ $creator->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Apply
                            </button>
                            <a href="{{ route('employee-payment.report') }}" class="btn btn-outline-secondary">Today</a>
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

            <div class="print-report">
                @include('backend.partials.organizationHeader', [
                    'reportTitle' => 'Daily Employee Voucher Report',
                    'reportSubtitle' => 'Period: ' . \Illuminate\Support\Carbon::parse($startDate)->format('d M Y')
                        . ($endDate !== $startDate ? ' – ' . \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') : ''),
                    'reportGeneratedAt' => now()->format('d M Y, h:i A'),
                ])
                <div class="row align-items-start mb-4 print-report-heading d-print-none">
                    <div class="col-7">
                        <h2 class="text-primary fw-bold mb-1 d-print-none">Daily Employee Voucher Report</h2>
                        <div class="text-muted">
                            Period:
                            <strong>
                                {{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }}
                                @if ($endDate !== $startDate)
                                    — {{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}
                                @endif
                            </strong>
                        </div>
                    </div>
                    <div class="col-5 text-end d-print-none">
                        <h5 class="fw-bold mb-1">Report Generated</h5>
                        <div>{{ now()->format('d M Y, h:i A') }}</div>
                        <div class="text-muted">Employee voucher entries</div>
                    </div>
                </div>

            <div class="row g-3 mb-4 print-summary">
                <div class="col-md-6 summary-card">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <div class="text-muted">Vouchers created in selected period</div>
                            <div class="fs-4 fw-bold text-primary">{{ number_format($totalCount) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 summary-card">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <div class="text-muted">Total voucher amount</div>
                            <div class="fs-4 fw-bold text-success">৳{{ number_format((float) $totalAmount, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 print-report-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-calendar me-1"></i>Daily Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Created date</th>
                                    <th>Employee</th>
                                    <th>Created by</th>
                                    <th class="text-end">Voucher count</th>
                                    <th class="text-end">Total amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dailySummaries as $summary)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($summary->voucher_date)->format('d M Y') }}</td>
                                        <td>{{ $summary->employee_name }}</td>
                                        <td>{{ $summary->creator_name ?: 'Unknown / legacy voucher' }}</td>
                                        <td class="text-end">{{ number_format((int) $summary->voucher_count) }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format((float) $summary->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No vouchers found for these filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm print-report-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i>Voucher Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher No.</th>
                                    <th>Created at</th>
                                    <th>Created by</th>
                                    <th>Employee</th>
                                    <th>Expense type</th>
                                    <th>Payment method</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vouchers as $voucher)
                                    <tr>
                                        <td class="fw-semibold">{{ $voucher->voucher_no }}</td>
                                        <td>{{ $voucher->created_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ $voucher->createdBy?->name ?: 'Unknown / legacy voucher' }}</td>
                                        <td>{{ $voucher->employee_name }}</td>
                                        <td>{{ \App\Models\EmployeePaymentVoucher::EXPENSE_TYPES[$voucher->expense_type] ?? $voucher->expense_type }}</td>
                                        <td>{{ \App\Models\EmployeePaymentVoucher::PAYMENT_METHODS[$voucher->payment_method] ?? $voucher->payment_method }}</td>
                                        <td>{{ $voucher->reference ?: '—' }}</td>
                                        <td>{{ $voucher->notes ?: '—' }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format((float) $voucher->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No vouchers found for these filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $vouchers->links('pagination::bootstrap-5') }}
                </div>
            </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            header,
            .topbar,
            .sidebar-wrapper,
            .page-footer,
            .footer,
            .no-print,
            .pagination {
                display: none !important;
            }

            .page-wrapper,
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .card,
            .print-report-card {
                box-shadow: none !important;
                border: 0 !important;
            }

            .print-report-heading {
                display: flex !important;
                break-inside: avoid;
            }

            .print-summary {
                display: flex !important;
                flex-wrap: nowrap !important;
                margin-bottom: 1rem !important;
                break-inside: avoid;
            }

            .print-summary .summary-card {
                display: block !important;
                flex: 0 0 50% !important;
                max-width: 50% !important;
                visibility: visible !important;
            }

            .print-summary .summary-card .card {
                display: block !important;
                min-height: 0 !important;
                border: 1px solid #dee2e6 !important;
                background-color: #f8f9fa !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .print-report-card .card-header {
                background-color: #f8f9fa !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .print-report table {
                width: 100% !important;
                font-size: 9pt;
            }

            .print-report th,
            .print-report td {
                padding: 0.35rem !important;
            }
        }
    </style>
@endsection