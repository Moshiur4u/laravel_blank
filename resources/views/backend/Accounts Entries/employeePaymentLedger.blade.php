@extends('dashboard.dashboard')

@section('title', 'Employee Payment Ledger - ' . $employee->name)

@section('content')
    <div class="page-wrapper">
        <div class="page-content print-report">
            @include('backend.partials.organizationHeader', [
                'reportTitle' => 'Employee Payment Ledger',
                'reportGeneratedAt' => now()->format('d M Y, h:i A'),
            ])
            {{-- print ও export control কাগজের ledger-এ দেখানো হবে না। --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
                @can('employee_payment_voucher.entry')
                <a href="{{ route('employee-payment.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Employee Payments
                </a>
                @endcan
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('employee-payment.ledger.export', $employee->id) }}"
                        class="btn btn-success">
                        <i class="bx bx-spreadsheet me-1"></i> Export Excel
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i> Print Ledger
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md">
                            <h3 class="text-primary fw-bold mb-1 d-print-none">Employee Payment Ledger</h3>
                            <div><strong>Employee:</strong> {{ $employee->name }}</div>
                            <div><strong>Email:</strong> {{ $employee->email }}</div>
                            <div>
                                <strong>Current Salary:</strong>
                                {{ $employee->salary !== null ? '৳' . number_format((float) $employee->salary, 2) : 'Not set' }}
                            </div>
                        </div>
                        <div class="col-md-auto text-md-end">
                            <div class="text-muted">Total Employee Payments</div>
                            <div class="fs-3 fw-bold text-primary">৳{{ number_format((float) $totalAmount, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach ($expenseTypes as $type => $label)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body py-3">
                                <div class="text-muted small">{{ $label }}</div>
                                <div class="fw-bold">
                                    ৳{{ number_format((float) ($categoryTotals[$type] ?? 0), 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i> All Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher No.</th>
                                    <th>Date</th>
                                    <th>Expense Type</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th class="text-end">Amount</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vouchers as $voucher)
                                    <tr>
                                        <td class="fw-semibold">{{ $voucher->voucher_no }}</td>
                                        <td>{{ $voucher->payment_date->format('d M Y') }}</td>
                                        <td>{{ $expenseTypes[$voucher->expense_type] ?? $voucher->expense_type }}</td>
                                        <td>{{ $paymentMethods[$voucher->payment_method] ?? $voucher->payment_method }}</td>
                                        <td>{{ $voucher->reference ?: '—' }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format((float) $voucher->amount, 2) }}</td>
                                        <td>{{ $voucher->notes ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No payment vouchers recorded for this employee.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($vouchers->isNotEmpty())
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">All-time Total</td>
                                        <td class="text-end fw-bold">৳{{ number_format((float) $totalAmount, 2) }}</td>
                                        <td></td>
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

            body {
                background: #fff !important;
            }
        }
    </style>
@endsection
