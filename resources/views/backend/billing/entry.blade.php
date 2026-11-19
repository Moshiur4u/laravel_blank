@extends('dashboard.dashboard')

@section('title', 'BillingEntry')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-1">BillingEntry</h4>
                    <div class="text-muted">{{ $organization->name }} — monthly billing</div>
                </div>
                @can('billing.record')
                    <div class="d-flex gap-2">
                        <a href="{{ route('billing.records') }}" class="btn btn-outline-primary">
                            <i class="bx bx-history me-1"></i>BillingRecord
                        </a>
                        <a href="{{ route('billing.transactions') }}" class="btn btn-outline-info">
                            <i class="bx bx-filter-alt me-1"></i>Billing Transactions
                        </a>
                    </div>
                @endcan
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-xl-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Generate Monthly Bill</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('billing.generate') }}" method="POST" class="row g-3">
                                @csrf
                                <div class="col-md-4">
                                    <label for="billing_month" class="form-label">Billing month</label>
                                    <input type="month" id="billing_month" name="billing_month"
                                        class="form-control @error('billing_month') is-invalid @enderror"
                                        value="{{ old('billing_month', now()->format('Y-m')) }}" required>
                                    @error('billing_month')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="amount" class="form-label">Bill amount</label>
                                    <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="due_date" class="form-label">Due date</label>
                                    <input type="date" id="due_date" name="due_date"
                                        class="form-control @error('due_date') is-invalid @enderror"
                                        value="{{ old('due_date', now()->addDays(15)->toDateString()) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Details</label>
                                    <textarea id="description" name="description" rows="2" maxlength="2000"
                                        class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-plus-circle me-1"></i>Generate Bill
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Marquee Alert Settings</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('billing.settings.update') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="alert_message" class="form-label">Custom message</label>
                                    <textarea id="alert_message" name="alert_message" rows="3" maxlength="1000"
                                        class="form-control @error('alert_message') is-invalid @enderror">{{ old('alert_message', $settings ? ($settings->alert_message ?? '') : 'Dear Subscriber, Your License Will Expire Soon!') }}</textarea>
                                    @error('alert_message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="show_marquee_alert"
                                        name="show_marquee_alert" value="1"
                                        @checked(old('show_marquee_alert', $settings?->show_marquee_alert ?? true))>
                                    <label class="form-check-label" for="show_marquee_alert">ShowMarqueeAlert</label>
                                </div>
                                <button type="submit" class="btn btn-outline-primary">Save Marquee Settings</button>
                            </form>
                        </div>
                    </div>

                    @if (auth()->user()->hasRole('admin') && $entries->contains(fn ($entry) => $entry->outstandingAmount() > 0))
                        <div class="card border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Access_Off</h5>
                                <p class="card-text">Deactivate every non-admin account while a bill remains due.</p>
                                <form action="{{ route('billing.access-off') }}" method="POST"
                                    onsubmit="return confirm('Deactivate all non-admin users?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">Access_Off</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Bills</h5>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Due date</th>
                                <th>Details</th>
                                <th class="text-end">Bill</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                                <th>Record payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td>{{ $entry->billing_month->format('F Y') }}</td>
                                    <td>{{ $entry->due_date->format('d M Y') }}</td>
                                    <td>{{ $entry->description ?: '—' }}</td>
                                    <td class="text-end">৳{{ number_format((float) $entry->amount, 2) }}</td>
                                    <td class="text-end">৳{{ number_format($entry->paidAmount(), 2) }}</td>
                                    <td class="text-end">
                                        @if ($entry->outstandingAmount() > 0)
                                            <span class="text-danger">৳{{ number_format($entry->outstandingAmount(), 2) }}</span>
                                        @else
                                            <span class="text-success">Paid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($entry->outstandingAmount() > 0)
                                            <form action="{{ route('billing.payments.store', $entry) }}" method="POST"
                                                class="d-flex flex-wrap gap-2">
                                                @csrf
                                                <input type="number" name="amount" min="0.01" step="0.01"
                                                    max="{{ number_format($entry->outstandingAmount(), 2, '.', '') }}"
                                                    value="{{ number_format($entry->outstandingAmount(), 2, '.', '') }}"
                                                    class="form-control form-control-sm" style="max-width: 120px"
                                                    aria-label="Payment amount" required>
                                                <input type="date" name="received_at" value="{{ now()->toDateString() }}"
                                                    class="form-control form-control-sm" style="max-width: 145px"
                                                    aria-label="Payment date" required>
                                                <select name="payment_method" class="form-select form-select-sm"
                                                    style="max-width: 135px" aria-label="Payment method">
                                                    <option value="">Method</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Bank transfer">Bank transfer</option>
                                                    <option value="Cheque">Cheque</option>
                                                    <option value="Mobile wallet">Mobile wallet</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <input type="text" name="reference" maxlength="150"
                                                    class="form-control form-control-sm" style="max-width: 150px"
                                                    placeholder="Reference">
                                                <button type="submit" class="btn btn-sm btn-success">BillPaid</button>
                                            </form>
                                        @else
                                            <span class="text-success">Fully paid</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-muted">No monthly bills have been generated.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
