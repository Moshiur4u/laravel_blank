@extends('dashboard.dashboard')

@section('title', 'Daily Debit/Credit Voucher Report')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="print-report">
                <div class="flex-wrap gap-2 mb-3 page-breadcrumb d-flex align-items-center justify-content-between no-print">
                    <div>
                        <div class="breadcrumb-title pe-3">Reports</div>
                        <h4 class="mt-2 mb-0">Daily Debit/Credit Voucher Report</h4>
                    </div>
                    <div class="gap-2 d-flex">
                        @canany(['voucher', 'voucher.report.miscellaneous', 'reports.all_access'])
                            {{-- পুরোনো All Vouchers-এর বদলে miscellaneous report permission। --}}
                            <a href="{{ route('voucher-entries.index') }}" class="btn btn-outline-primary">
                                <i class="bx bx-plus me-1"></i>Create Voucher
                            </a>
                        @endcanany
                        <a href="{{ route('voucher-entries.report.export', request()->except('page')) }}"
                            class="btn btn-success">
                            <i class="bx bx-spreadsheet me-1"></i>Export Excel
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bx bx-printer me-1"></i>Print Report
                        </button>
                    </div>
                </div>

                <div class="mb-4 border-0 shadow-sm card report-filters no-print">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-filter-alt me-1"></i>Filter Report</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('voucher-entries.report') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4 col-lg-3">
                                <label for="start_date" class="form-label">Voucher date from</label>
                                <input type="date" name="start_date" id="start_date" class="form-control"
                                    value="{{ old('start_date', $startDate) }}">
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label for="end_date" class="form-label">Voucher date to</label>
                                <input type="date" name="end_date" id="end_date" class="form-control"
                                    value="{{ old('end_date', $endDate) }}">
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label for="created_by" class="form-label">Created by</label>
                                <select name="created_by" id="created_by" class="form-select">
                                    <option value="">All creators</option>
                                    @foreach ($creators as $creator)
                                        <option value="{{ $creator->id }}"
                                            {{ (string) old('created_by', $filters['created_by'] ?? '') === (string) $creator->id ? 'selected' : '' }}>
                                            {{ $creator->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="gap-2 col-md-12 col-lg-3 d-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-search me-1"></i>Apply
                                </button>
                                <a href="{{ route('voucher-entries.report') }}" class="btn btn-outline-secondary">Today</a>
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

                @include('backend.partials.organizationHeader', [
                    'reportTitle' => 'Daily Debit/Credit Voucher Report',
                    'reportSubtitle' =>
                        \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') .
                        ' – ' .
                        \Illuminate\Support\Carbon::parse($endDate)->format('d M Y'),
                    'reportGeneratedAt' => now()->format('d M Y, h:i A'),
                ])
                <div class="flex-wrap gap-2 mb-4 d-flex align-items-start justify-content-between d-print-none">
                    <div>
                        <h2 class="mb-1 text-primary fw-bold d-print-none">Daily Debit/Credit Voucher Report</h2>
                        <p class="mb-0 text-muted">
                            {{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }}
                            – {{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}
                        </p>
                    </div>
                    <div class="small text-muted d-print-none">
                        <span class="fw-bold text-body">Report Generated:</span>
                        {{ now()->format('d M Y, h:i A') }}
                    </div>
                </div>

                <div class="mb-4 row g-2 print-summary print-summary-cards d-print-none"> {{-- summary card শুধু screen-এ দেখায়। --}}
                    <div class="col-md-4 summary-card">
                        <div class="border-0 shadow-sm card h-100">
                            <div class="gap-2 py-3 card-body d-flex align-items-center justify-content-between">
                                <div class="text-muted fw-medium">Voucher Count</div>
                                <div class="fs-5 fw-bold text-primary">{{ number_format($totalCount) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 summary-card">
                        <div class="border-0 shadow-sm card h-100">
                            <div class="gap-2 py-3 card-body d-flex align-items-center justify-content-between">
                                <div class="text-muted fw-medium">Total Debit</div>
                                <div class="fs-5 fw-bold text-danger text-nowrap">৳{{ number_format($debitTotal, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 summary-card">
                        <div class="border-0 shadow-sm card h-100">
                            <div class="gap-2 py-3 card-body d-flex align-items-center justify-content-between">
                                <div class="text-muted fw-medium">Total Credit</div>
                                <div class="fs-5 fw-bold text-success text-nowrap">৳{{ number_format($creditTotal, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-none d-print-block print-summary-text"> {{-- প্রিন্টে debit/credit summary text দেখায়। --}}
                    <p class="mb-1"> {{-- voucher count আলাদা লাইনে দেখায়। --}}
                        <strong>Voucher Count:</strong> {{-- মোট voucher count-এর label দেখায়। --}}
                        {{ number_format($totalCount) }} {{-- voucher count format করে দেখায়। --}}
                    </p> {{-- voucher count-এর লাইন শেষ করে। --}}
                    <p class="mb-1"> {{-- মোট debit আলাদা লাইনে দেখায়। --}}
                        <strong>Total Debit:</strong> {{-- debit amount-এর label দেখায়। --}}
                        ৳{{ number_format($debitTotal, 2) }} {{-- debit amount format করে দেখায়। --}}
                    </p> {{-- মোট debit-এর লাইন শেষ করে। --}}
                    <p class="mb-3"> {{-- মোট credit আলাদা লাইনে দেখায়। --}}
                        <strong>Total Credit:</strong> {{-- credit amount-এর label দেখায়। --}}
                        ৳{{ number_format($creditTotal, 2) }} {{-- credit amount format করে দেখায়। --}}
                    </p> {{-- মোট credit-এর লাইন শেষ করে। --}}
                </div> {{-- print summary block শেষ করে। --}}

                <div class="mb-4 border-0 shadow-sm card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-calendar me-1"></i>Daily Summary</h5>
                        <small class="text-muted">
                            {{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }}
                            – {{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}
                        </small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Created By</th>
                                        <th>Type</th>
                                        <th class="text-end">Voucher Count</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dailySummaries as $summary)
                                        <tr>
                                            <td>{{ $summary->voucher_date->format('d M Y') }}</td>
                                            <td>{{ $summary->created_by_name }}</td>
                                            <td>
                                                <span
                                                    class="badge {{ $summary->voucher_type === 'debit' ? 'bg-danger' : 'bg-success' }}">
                                                    {{ \App\Models\VoucherEntry::TYPES[$summary->voucher_type] }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format((int) $summary->voucher_count) }}</td>
                                            <td class="text-end fw-semibold">
                                                ৳{{ number_format((float) $summary->total_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-4 text-center text-muted">No vouchers found for
                                                these filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="border-0 shadow-sm card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i>Voucher Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Voucher Code</th>
                                        <th>Type</th>
                                        <th>Expense/Income Type</th>
                                        <th>Date</th>
                                        <th>Created By</th>
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($vouchers as $voucher)
                                        <tr>
                                            <td class="fw-semibold">{{ $voucher->voucher_code }}</td>
                                            <td>
                                                <span
                                                    class="badge {{ $voucher->voucher_type === 'debit' ? 'bg-danger' : 'bg-success' }}">
                                                    {{ \App\Models\VoucherEntry::TYPES[$voucher->voucher_type] }}
                                                </span>
                                            </td>
                                            <td>{{ $voucher->expense_income_type }}</td>
                                            <td>{{ $voucher->voucher_date->format('d M Y') }}</td>
                                            <td>{{ $voucher->created_by_name }}</td>
                                            <td>{{ $voucher->reference ?: '—' }}</td>
                                            <td>{{ $voucher->description ?: '—' }}</td>
                                            <td class="text-end fw-semibold">
                                                ৳{{ number_format((float) $voucher->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="py-4 text-center text-muted">No vouchers found for
                                                these filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $vouchers->links('pagination::bootstrap-5') }}
                        <div class="d-none d-print-block print-pagination-info"> {{-- প্রিন্টে voucher pagination-এর entry count দেখায়। --}}
                            Showing {{ $vouchers->firstItem() ?? 0 }} to {{ $vouchers->lastItem() ?? 0 }} of {{ number_format($vouchers->total()) }} entries {{-- voucher-এর বর্তমান range ও মোট সংখ্যা দেখায়। --}}
                        </div> {{-- voucher entry count block শেষ করে। --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {

            .no-print,
            .sidebar-wrapper,
            header,
            .topbar,
            .top-header,
            .page-footer,
            .footer,
            .pagination {
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
                flex: 0 0 33.333333% !important;
                max-width: 33.333333% !important;
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
    @include('backend.partials.reportPrintStyles') {{-- সব report-এর একই portrait print layout প্রয়োগ করে। --}}
@endsection
