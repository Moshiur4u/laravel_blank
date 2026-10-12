@extends('dashboard.dashboard')

@section('title', 'Employee Payment Voucher')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- কর্মীর বেতন, অগ্রিম ও অন্যান্য খরচের voucher এখানে তৈরি ও দেখা যায়। --}}
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Accounts</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Employee Payment Voucher</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Total Employee Expenses</div>
                            <div class="fs-4 fw-bold text-primary">৳{{ number_format((float) $totalAmount, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Voucher Records</div>
                            <div class="fs-4 fw-bold">{{ $vouchers->total() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bx bx-receipt me-1"></i> Create Employee Payment Voucher</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('employee-payment.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label for="employee_id" class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id"
                                    class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            data-salary="{{ $employee->salary ?? '' }}"
                                            {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <a id="employee-ledger-link"
                                    data-ledger-url="{{ route('employee-payment.ledger', ['employee' => '__EMPLOYEE__']) }}"
                                    class="btn btn-sm btn-outline-primary mt-2 d-none" href="#">
                                    <i class="bx bx-book-open me-1"></i> View Employee Ledger
                                </a>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="expense_type" class="form-label fw-semibold">Expense Type <span class="text-danger">*</span></label>
                                <select name="expense_type" id="expense_type"
                                    class="form-select @error('expense_type') is-invalid @enderror" required>
                                    <option value="">-- Select Expense Type --</option>
                                    @foreach ($expenseTypes as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('expense_type') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('expense_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="payment_date" class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="payment_date"
                                    class="form-control @error('payment_date') is-invalid @enderror"
                                    value="{{ old('payment_date', now()->toDateString()) }}"
                                    max="{{ now()->toDateString() }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="amount" class="form-label fw-semibold">Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    min="0.01" max="9999999999.99" step="0.01"
                                    value="{{ old('amount') }}" placeholder="Enter amount" required>
                                <small id="salary-limit-help" class="form-text text-muted">
                                    Salary expense cannot exceed the selected employee's current salary.
                                </small>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="payment_method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="payment_method"
                                    class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="">-- Select Method --</option>
                                    @foreach ($paymentMethods as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('payment_method') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="reference" class="form-label fw-semibold">Reference No. <span class="text-muted">(Optional)</span></label>
                                <input type="text" name="reference" id="reference"
                                    class="form-control @error('reference') is-invalid @enderror"
                                    maxlength="100" value="{{ old('reference') }}"
                                    placeholder="Transaction or document reference">
                                @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold">
                                    Notes <span class="text-muted">(Required for Other Employee Expense)</span>
                                </label>
                                <textarea name="notes" id="notes" rows="2"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    maxlength="2000" placeholder="Additional payment details">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Voucher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i> Employee Payment Voucher History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher No.</th>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Expense Type</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th class="text-end">Amount</th>
                                    <th>Notes</th>
                                    <th>Ledger</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vouchers as $voucher)
                                    <tr>
                                        <td class="fw-semibold">{{ $voucher->voucher_no }}</td>
                                        <td>{{ $voucher->payment_date->format('d M Y') }}</td>
                                        <td>{{ $voucher->employee_name }}</td>
                                        <td>{{ $expenseTypes[$voucher->expense_type] ?? $voucher->expense_type }}</td>
                                        <td>{{ $paymentMethods[$voucher->payment_method] ?? $voucher->payment_method }}</td>
                                        <td>{{ $voucher->reference ?: '—' }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format((float) $voucher->amount, 2) }}</td>
                                        <td>{{ $voucher->notes ?: '—' }}</td>
                                        <td>
                                            @if ($voucher->employee_id)
                                                <a href="{{ route('employee-payment.ledger', $voucher->employee_id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-book-open me-1"></i>View Ledger
                                                </a>
                                            @else
                                                <span class="text-muted">Employee unavailable</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No employee payment vouchers yet.</td>
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

    <script>
        // Other expense বাছলে notes field বাধ্যতামূলক হয়; server-side validation-ও একই নিয়ম মানে।
        document.addEventListener('DOMContentLoaded', function () {
            const expenseType = document.getElementById('expense_type');
            const notes = document.getElementById('notes');
            const employee = document.getElementById('employee_id');
            const amount = document.getElementById('amount');
            const salaryLimitHelp = document.getElementById('salary-limit-help');
            const employeeLedgerLink = document.getElementById('employee-ledger-link');

            function updateNotesRequirement() {
                notes.required = expenseType.value === 'other';
            }

            function updateSalaryLimit() {
                const isSalary = expenseType.value === 'salary';
                const selectedEmployee = employee.options[employee.selectedIndex];
                const salary = selectedEmployee ? selectedEmployee.dataset.salary : '';

                if (isSalary && salary !== '') {
                    amount.max = salary;
                    salaryLimitHelp.textContent = `Maximum salary amount: ৳${Number(salary).toFixed(2)}`;
                } else if (isSalary) {
                    amount.max = '0';
                    salaryLimitHelp.textContent = 'This employee has no salary set. Set the employee salary before creating a salary voucher.';
                } else {
                    amount.max = '9999999999.99';
                    salaryLimitHelp.textContent = "Salary expense cannot exceed the selected employee's current salary.";
                }
            }

            function updateEmployeeLedgerLink() {
                if (!employee.value) {
                    employeeLedgerLink.classList.add('d-none');
                    return;
                }

                employeeLedgerLink.href = employeeLedgerLink.dataset.ledgerUrl
                    .replace('__EMPLOYEE__', encodeURIComponent(employee.value));
                employeeLedgerLink.classList.remove('d-none');
            }

            expenseType.addEventListener('change', updateNotesRequirement);
            expenseType.addEventListener('change', updateSalaryLimit);
            employee.addEventListener('change', updateSalaryLimit);
            employee.addEventListener('change', updateEmployeeLedgerLink);
            updateNotesRequirement();
            updateSalaryLimit();
            updateEmployeeLedgerLink();
        });
    </script>
@endsection