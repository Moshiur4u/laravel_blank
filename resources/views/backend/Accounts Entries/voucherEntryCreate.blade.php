@extends('dashboard.dashboard')

@section('title', 'Debit/Credit Voucher Entries')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="breadcrumb-title pe-3">Accounts</div>
                    <h4 class="mb-0 mt-2">Debit/Credit Voucher Entries</h4>
                </div>
                <a href="{{ route('voucher-entries.report') }}" class="btn btn-outline-primary">
                    <i class="bx bx-bar-chart-alt-2 me-1"></i>Voucher Report
                </a>
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
                            <div class="text-muted">Today's Vouchers</div>
                            <div class="fs-4 fw-bold text-primary">{{ number_format($todayCount) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted">Today's Voucher Amount</div>
                            <div class="fs-4 fw-bold text-success">৳{{ number_format($todayAmount, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bx bx-receipt me-1"></i>Create Voucher</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('voucher-entries.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label for="voucher_type" class="form-label fw-semibold">Voucher Type <span class="text-danger">*</span></label>
                                <select name="voucher_type" id="voucher_type"
                                    class="form-select @error('voucher_type') is-invalid @enderror" required>
                                    <option value="">-- Select Debit or Credit --</option>
                                    @foreach ($voucherTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('voucher_type') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('voucher_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="expense_income_type" class="form-label fw-semibold">Expense/Income Type <span class="text-danger">*</span></label>
                                <input type="text" name="expense_income_type" id="expense_income_type"
                                    class="form-control @error('expense_income_type') is-invalid @enderror"
                                    maxlength="100" value="{{ old('expense_income_type') }}"
                                    placeholder="Type expense or income category" required>
                                @error('expense_income_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="voucher_date" class="form-label fw-semibold">Voucher Date <span class="text-danger">*</span></label>
                                <input type="date" name="voucher_date" id="voucher_date"
                                    class="form-control @error('voucher_date') is-invalid @enderror"
                                    value="{{ old('voucher_date', today()->toDateString()) }}" required>
                                @error('voucher_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="amount" class="form-label fw-semibold">Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    min="0.01" max="9999999999.99" step="0.01"
                                    value="{{ old('amount') }}" placeholder="Enter amount" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <label for="reference" class="form-label fw-semibold">Reference <span class="text-muted">(Optional)</span></label>
                                <input type="text" name="reference" id="reference"
                                    class="form-control @error('reference') is-invalid @enderror"
                                    maxlength="100" value="{{ old('reference') }}" placeholder="Reference number">
                                @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">Description <span class="text-muted">(Optional)</span></label>
                                <textarea name="description" id="description" rows="2"
                                    class="form-control @error('description') is-invalid @enderror"
                                    maxlength="2000" placeholder="Voucher details">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Save Voucher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i>Recent Vouchers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher Code</th>
                                    <th>Type</th>
                                    <th>Expense/Income Type</th>
                                    <th>Date</th>
                                    <th>Created By</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vouchers as $voucher)
                                    <tr>
                                        <td class="fw-semibold">{{ $voucher->voucher_code }}</td>
                                        <td>
                                            <span class="badge {{ $voucher->voucher_type === 'debit' ? 'bg-danger' : 'bg-success' }}">
                                                {{ $voucherTypes[$voucher->voucher_type] }}
                                            </span>
                                        </td>
                                        <td>{{ $voucher->expense_income_type }}</td>
                                        <td>{{ $voucher->voucher_date->format('d M Y') }}</td>
                                        <td>{{ $voucher->created_by_name }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format((float) $voucher->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No vouchers created yet.</td>
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
@endsection
