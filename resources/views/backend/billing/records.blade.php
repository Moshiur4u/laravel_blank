@extends('dashboard.dashboard')

@section('title', 'BillingRecord')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-1">BillingRecord</h4>
                    <div class="text-muted">{{ $organization->name }} — due and payment history by month</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('billing.transactions') }}" class="btn btn-outline-info">
                        <i class="bx bx-filter-alt me-1"></i>Billing Transactions
                    </a>
                    @can('billing.entry')
                        <a href="{{ route('billing.entry') }}" class="btn btn-outline-primary">
                            <i class="bx bx-receipt me-1"></i>BillingEntry
                        </a>
                    @endcan
                </div>
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

            @forelse ($entries as $month => $monthlyEntries)
                <section class="mb-4 card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }}</h5>
                    </div>
                    <div class="card-body">
                        @foreach ($monthlyEntries as $entry)
                            <div class="pb-3 mb-3 border-bottom">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong>Bill due {{ $entry->due_date->format('d M Y') }}</strong>
                                        <div class="small text-muted">{{ $entry->description ?: 'Monthly organization bill' }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div>Amount: ৳{{ number_format((float) $entry->amount, 2) }}</div>
                                        <div>Paid: ৳{{ number_format($entry->paidAmount(), 2) }}</div>
                                        <div>
                                            @if ($entry->outstandingAmount() > 0)
                                                <span class="text-danger">Due: ৳{{ number_format($entry->outstandingAmount(), 2) }}</span>
                                            @else
                                                <span class="text-success">Paid in full</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @forelse ($entry->records as $record)
                                    <div class="mt-3 p-3 rounded bg-light">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <div>
                                                <strong>Receipt: ৳{{ number_format((float) $record->amount, 2) }}</strong>
                                                <span class="ms-2">{{ $record->received_at->format('d M Y') }}</span>
                                                @if ($record->payment_method)
                                                    <span class="ms-2">{{ $record->payment_method }}</span>
                                                @endif
                                                @if ($record->reference)
                                                    <span class="ms-2">Ref: {{ $record->reference }}</span>
                                                @endif
                                                @if ($record->note)
                                                    <div class="small mt-1">{{ $record->note }}</div>
                                                @endif
                                                <div class="small text-muted">
                                                    Received by {{ $record->receiver?->name ?: 'Deleted user' }}
                                                    @if ($record->updated_by)
                                                        · Edited by {{ $record->updater?->name ?: 'Deleted user' }}
                                                    @endif
                                                    @if ($record->reversed_at)
                                                        · Reverted {{ $record->reversed_at->format('d M Y H:i') }}
                                                        by {{ $record->reverser?->name ?: 'Deleted user' }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if (! $record->reversed_at)
                                            @can('billing.record')
                                                <form action="{{ route('billing.records.update', $record) }}" method="POST"
                                                    class="mt-3 row g-2 align-items-end">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="col-sm-2">
                                                        <label class="form-label small">Amount</label>
                                                        <input type="number" name="amount" min="0.01" step="0.01"
                                                            max="{{ number_format((float) $entry->amount, 2, '.', '') }}"
                                                            value="{{ number_format((float) $record->amount, 2, '.', '') }}"
                                                            class="form-control form-control-sm" required>
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <label class="form-label small">Received date</label>
                                                        <input type="date" name="received_at"
                                                            value="{{ $record->received_at->toDateString() }}"
                                                            class="form-control form-control-sm" required>
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <label class="form-label small">Method</label>
                                                        <input type="text" name="payment_method" maxlength="50"
                                                            value="{{ $record->payment_method }}"
                                                            class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <label class="form-label small">Reference</label>
                                                        <input type="text" name="reference" maxlength="150"
                                                            value="{{ $record->reference }}"
                                                            class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <label class="form-label small">Note</label>
                                                        <input type="text" name="note" maxlength="2000"
                                                            value="{{ $record->note }}" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-sm-2 d-flex gap-2">
                                                        <button type="submit" class="btn btn-sm btn-primary">Edit</button>
                                                        <button type="submit" form="revert-payment-{{ $record->id }}"
                                                            class="btn btn-sm btn-outline-danger">Revert</button>
                                                    </div>
                                                </form>
                                                <form id="revert-payment-{{ $record->id }}"
                                                    action="{{ route('billing.records.revert', $record) }}" method="POST"
                                                    onsubmit="return confirm('Revert this payment and restore the bill balance?');">
                                                    @csrf
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                @empty
                                    <div class="mt-3 text-muted">No payment has been recorded for this bill.</div>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="alert alert-info">No billing history is available yet.</div>
            @endforelse
        </div>
    </div>
@endsection
