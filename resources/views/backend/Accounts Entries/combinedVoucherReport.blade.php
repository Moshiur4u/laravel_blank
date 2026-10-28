@extends('dashboard.dashboard')

@section('title', 'Combined Voucher Report')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                <div>
                    <div class="breadcrumb-title pe-3">Reports</div>
                    <h4 class="mb-0 mt-2">Combined Voucher Report</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dashboardbody') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Dashboard
                    </a>
                    <a href="{{ route('voucher-report.combined.export', request()->except('page')) }}" class="btn btn-success">
                        <i class="bx bx-spreadsheet me-1"></i>Export Excel
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print Report
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-filter-alt me-1"></i>Filter Report</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('voucher-report.combined') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="period" class="form-label">Report period</label>
                            <select name="period" id="period" class="form-select">
                                <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="daily-date-field">
                            <label for="date" class="form-label">Voucher date</label>
                            <input type="date" name="date" id="date" class="form-control"
                                value="{{ $period === 'daily' ? $startDate : today()->toDateString() }}">
                        </div>
                        <div class="col-md-4" id="monthly-date-field">
                            <label for="month" class="form-label">Voucher month</label>
                            <input type="month" name="month" id="month" class="form-control"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Apply
                            </button>
                            <a href="{{ route('voucher-report.combined') }}" class="btn btn-outline-secondary">Today</a>
                        </div>
                    </form>
                    @if ($errors->any())
                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                            @foreach ($errors->all() as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success mt-3 mb-0" role="status">{{ session('success') }}</div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm print-report">
                <div class="card-body p-4 p-md-5">
                    @include('backend.partials.organizationHeader', [
                        'reportTitle' => 'Combined Voucher Report',
                        'reportSubtitle' => 'Period: ' . ($period === 'daily'
                            ? \Illuminate\Support\Carbon::parse($startDate)->format('d M Y')
                            : \Illuminate\Support\Carbon::parse($startDate)->format('F Y')),
                        'reportGeneratedAt' => now()->format('d M Y, h:i A'),
                    ])
                    <div class="row align-items-start mb-4 d-print-none">
                        <div class="col-7">
                            <h2 class="text-primary fw-bold mb-1 d-print-none">Combined Voucher Report</h2>
                            <div class="text-muted">
                                Period:
                                <strong>
                                    {{ $period === 'daily'
                                        ? \Illuminate\Support\Carbon::parse($startDate)->format('d M Y')
                                        : \Illuminate\Support\Carbon::parse($startDate)->format('F Y') }}
                                </strong>
                            </div>
                        </div>
                        <div class="col-5 text-end d-print-none">
                            <h5 class="fw-bold mb-1">Report Generated</h5>
                            <div>{{ now()->format('d M Y, h:i A') }}</div>
                            <div class="text-muted">{{ ucfirst($period) }} voucher entries</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 print-summary">
                        <div class="col-md-3 summary-card">
                            <div class="card border-0 bg-light h-100 voucher-count-card">
                                <div class="card-body">
                                    <div class="text-muted mb-2">Voucher Count</div>
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <div class="small text-muted">Debit</div>
                                            <div class="fs-5 fw-bold text-danger">{{ number_format($debitVoucherCount) }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">Credit</div>
                                            <div class="fs-5 fw-bold text-success">{{ number_format($creditVoucherCount) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 summary-card">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Employee Voucher</div>
                                    <div class="fs-5 fw-bold">৳{{ number_format($employeeTotal, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 summary-card">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Debit</div>
                                    <div class="fs-5 fw-bold text-danger">৳{{ number_format($debitTotal, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 summary-card">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="text-muted">Total Credit</div>
                                    <div class="fs-5 fw-bold text-success">৳{{ number_format($creditTotal, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $canManageVouchers = auth()->user()->canAny(['voucher.edit', 'voucher.delete', 'reports.all_access']);
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher No.</th>
                                    <th>Voucher Type</th>
                                    <th>Voucher Head</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th>Employee</th>
                                    <th>Created By</th>
                                    <th>Reference / Description</th>
                                    @if ($canManageVouchers)
                                        <th class="no-print">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vouchers as $voucher)
                                    @php
                                        $isEmployeeVoucher = $voucher->voucher_category === 'employee';
                                        $isLinkedVoucher = in_array($voucher->voucherable_type, [
                                            \App\Models\Supplierledger::class,
                                            \App\Models\CustomerLedger::class,
                                        ], true);
                                    @endphp
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($voucher->report_date)->format('d M Y') }}</td>
                                        <td class="fw-semibold">{{ $voucher->voucher_code ?: '—' }}</td>
                                        <td>
                                            @if ($voucher->voucher_category === 'employee')
                                                Employee
                                            @else
                                                {{ $voucherTypes[$voucher->voucher_category] ?? $voucher->voucher_category }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($voucher->voucher_category === 'employee')
                                                {{ $expenseTypes[$voucher->voucher_head] ?? $voucher->voucher_head }}
                                            @else
                                                {{ $voucher->voucher_head }}
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">
                                            @if ($voucher->voucher_category !== 'credit')
                                                ৳{{ number_format((float) $voucher->amount, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">
                                            @if ($voucher->voucher_category === 'credit')
                                                ৳{{ number_format((float) $voucher->amount, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $voucher->employee_name ?: '—' }}</td>
                                        <td>{{ $voucher->created_by_name }}</td>
                                        <td>{{ $voucher->reference ?: $voucher->description ?: '—' }}</td>
                                        @if ($canManageVouchers)
                                        <td class="no-print">
                                            @canany(['voucher.edit', 'reports.all_access'])
                                            <details>
                                                <summary class="btn btn-sm btn-outline-primary">Edit</summary>
                                                <form method="POST"
                                                    action="{{ route('voucher-report.combined.update', [$voucher->voucher_category, $voucher->voucher_id]) }}"
                                                    class="mt-2" style="min-width: 240px">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="period" value="{{ $period }}">
                                                    <input type="hidden" name="date" value="{{ $period === 'daily' ? $startDate : '' }}">
                                                    <input type="hidden" name="month" value="{{ $period === 'monthly' ? $selectedMonth : '' }}">
                                                    <label class="form-label small mb-1">Date</label>
                                                    <input type="date" name="{{ $isEmployeeVoucher ? 'payment_date' : 'voucher_date' }}"
                                                        class="form-control form-control-sm mb-2"
                                                        value="{{ \Illuminate\Support\Carbon::parse($voucher->report_date)->toDateString() }}"
                                                        @if ($isEmployeeVoucher) max="{{ today()->toDateString() }}" @endif required>
                                                    <label class="form-label small mb-1">Amount</label>
                                                    <input type="number" name="amount" class="form-control form-control-sm mb-2"
                                                        min="0.01" max="9999999999.99" step="0.01"
                                                        value="{{ number_format((float) $voucher->amount, 2, '.', '') }}" required>
                                                    @if ($isEmployeeVoucher)
                                                        <label class="form-label small mb-1">Expense Type</label>
                                                        <select name="expense_type" class="form-select form-select-sm mb-2" required>
                                                            @foreach ($expenseTypes as $type => $label)
                                                                <option value="{{ $type }}" @selected($voucher->voucher_head === $type)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="form-label small mb-1">Reference</label>
                                                        <input type="text" name="reference" maxlength="100"
                                                            class="form-control form-control-sm mb-2" value="{{ $voucher->reference }}">
                                                        <label class="form-label small mb-1">Notes</label>
                                                        <textarea name="notes" maxlength="2000" rows="2"
                                                            class="form-control form-control-sm mb-2">{{ $voucher->description }}</textarea>
                                                    @elseif (! $isLinkedVoucher)
                                                        <label class="form-label small mb-1">Voucher Head</label>
                                                        <input type="text" name="expense_income_type" maxlength="100"
                                                            class="form-control form-control-sm mb-2" value="{{ $voucher->voucher_head }}">
                                                        <label class="form-label small mb-1">Reference</label>
                                                        <input type="text" name="reference" maxlength="100"
                                                            class="form-control form-control-sm mb-2" value="{{ $voucher->reference }}">
                                                        <label class="form-label small mb-1">Description</label>
                                                        <textarea name="description" maxlength="2000" rows="2"
                                                            class="form-control form-control-sm mb-2">{{ $voucher->description }}</textarea>
                                                    @endif
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                </form>
                                            </details>
                                            @endcanany
                                            @canany(['voucher.delete', 'reports.all_access'])
                                            <form method="POST"
                                                action="{{ route('voucher-report.combined.destroy', [$voucher->voucher_category, $voucher->voucher_id]) }}"
                                                class="mt-2" onsubmit="return confirm('Delete this voucher? This change will update related ledger balances.');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="period" value="{{ $period }}">
                                                <input type="hidden" name="date" value="{{ $period === 'daily' ? $startDate : '' }}">
                                                <input type="hidden" name="month" value="{{ $period === 'monthly' ? $selectedMonth : '' }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                            @endcanany
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageVouchers ? 10 : 9 }}" class="text-center text-muted py-4">No vouchers found for this period.</td>
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

    <style>
        #monthly-date-field {
            display: {{ $period === 'monthly' ? 'block' : 'none' }};
        }

        #daily-date-field {
            display: {{ $period === 'daily' ? 'block' : 'none' }};
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
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
            .print-report {
                border: 0 !important;
                box-shadow: none !important;
            }

            .print-report .card-body {
                padding: 0 !important;
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

    <script>
        document.getElementById('period').addEventListener('change', function () {
            const isMonthly = this.value === 'monthly';
            document.getElementById('daily-date-field').style.display = isMonthly ? 'none' : 'block';
            document.getElementById('monthly-date-field').style.display = isMonthly ? 'block' : 'none';
        });
    </script>
@endsection
