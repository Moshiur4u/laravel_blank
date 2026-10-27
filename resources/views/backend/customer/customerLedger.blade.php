@extends('dashboard.dashboard')
@section('title')
    Customer Ledger - {{ $customer->name }}
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            @include('backend.partials.organizationHeader')
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Customer</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i
                                        class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">Customer List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $customer->name }} Ledger</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('customer.index') }}" class="btn btn-primary"><i
                            class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            {{-- @if ($organization?->logo || $organization?->name)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3">
                            @if ($organization?->logo)
                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                    alt="{{ $organization->name ?: 'Organization logo' }}"
                                    style="max-width: 90px; max-height: 60px; object-fit: contain;">
                            @endif
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h5>
                                <div class="small text-muted">
                                    @if ($organization?->phone) Phone: {{ $organization->phone }} @endif
                                    @if ($organization?->email) | Email: {{ $organization->email }} @endif
                                    @if ($organization?->address) | {{ $organization->address }} @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif --}}

            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="text-center col-md-1">
                                    @php
                                        $customerColors = [
                                            '#0d6efd',
                                            '#6f42c1',
                                            '#d63384',
                                            '#dc3545',
                                            '#fd7e14',
                                            '#198754',
                                            '#0dcaf0',
                                            '#6610f2',
                                        ];
                                        $customerColorIndex = $customer->id % count($customerColors);
                                    @endphp
                                    <div
                                        style="width: 80px; height: 80px; border-radius: 50%; background: {{ $customerColors[$customerColorIndex] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 28px; text-transform: uppercase; margin: 0 auto;">
                                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <h4 class="mb-1 text-primary fw-bold">{{ $customer->name }}</h4>
                                    @if ($customer->customer_type === 'permanent' && $customer->employee)
                                        <p class="mb-1"><i class="bx bx-user me-1 text-muted"></i>Employee:
                                            {{ $customer->employee->name }}</p>
                                    @endif
                                    <p class="mb-1"><i
                                            class="bx bx-phone me-1 text-muted"></i>{{ $customer->phone ?? 'N/A' }}</p>
                                    <p class="mb-1"><i
                                            class="bx bx-envelope me-1 text-muted"></i>{{ $customer->email ?? 'N/A' }}</p>
                                    <p class="mb-0"><i
                                            class="bx bx-map me-1 text-muted"></i>{{ $customer->address ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="card bg-light-primary border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-primary mb-1">Total Sale</h6>
                                                    <h4 class="mb-0 fw-bold text-primary">
                                                        ৳{{ number_format($totalSales, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-success border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-success mb-1">Payment</h6>
                                                    <h4 class="mb-0 fw-bold text-success">
                                                        ৳{{ number_format($totalPayment, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-danger border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-danger mb-1">Due</h6>
                                                    <h4 class="mb-0 fw-bold text-danger">৳{{ number_format($totalDue, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Edit mode-এ supplier ledger-এর মতো editable entry form দেখানো হয়। --}}
            @if (isset($ledger))
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bx bx-edit-alt me-1"></i>Edit Ledger Entry</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('customer.ledger.update', $ledger->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Date</label>
                                            <input type="date" name="sale_date" class="form-control"
                                                value="{{ old('sale_date', $ledger->sale_date->format('Y-m-d')) }}"
                                                required>
                                            @error('sale_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Invoice No.</label>
                                            <input type="text" name="invoice_no" class="form-control"
                                                value="{{ old('invoice_no', $ledger->invoice_no) }}">
                                            @error('invoice_no')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        @if ($ledger->entry_type === 'sale')
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Product Name</label>
                                                <input type="text" name="product_name" class="form-control"
                                                    value="{{ old('product_name', $ledger->product_name) }}" required>
                                                @error('product_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label fw-semibold">Qty</label>
                                                <input type="number" name="quantity" class="form-control" min="1"
                                                    value="{{ old('quantity', $ledger->quantity) }}" required>
                                                @error('quantity')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fw-semibold">Unit Price</label>
                                                <input type="number" name="unit_price" class="form-control" min="0"
                                                    step="0.01" value="{{ old('unit_price', $ledger->unit_price) }}"
                                                    required>
                                                @error('unit_price')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Payment Type</label>
                                                <input type="text" class="form-control"
                                                    value="{{ ucfirst(str_replace('_', ' ', $ledger->payment_type)) }}"
                                                    readonly>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Employee</label>
                                                <select name="employee_id" class="form-select">
                                                    <option value="">Select employee</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{ $employee->id }}" @selected((string) old('employee_id', $ledger->employee_id) === (string) $employee->id)>
                                                            {{ $employee->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Payment Amount</label>
                                                <input type="number" name="payment_amount" class="form-control"
                                                    min="0.01" step="0.01"
                                                    value="{{ old('payment_amount', $ledger->payment_amount) }}" required>
                                                @error('payment_amount')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">Paid By</label>
                                                <input type="text" name="paid_by" class="form-control"
                                                    value="{{ old('paid_by', $ledger->paid_by) }}">
                                                @error('paid_by')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Received By</label>
                                            <select name="received_by" class="form-select">
                                                <option value="">Select receiver</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}" @selected((string) old('received_by', $ledger->received_by) === (string) $employee->id)>
                                                        {{ $employee->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('received_by')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Notes</label>
                                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $ledger->notes) }}</textarea>
                                            @error('notes')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-12 d-flex gap-2">
                                            <button type="submit" class="btn btn-primary"><i
                                                    class="bx bx-check me-1"></i>Update Entry</button>
                                            <a href="{{ route('customer.ledger', $customer->id) }}"
                                                class="btn btn-secondary"><i class="bx bx-x me-1"></i>Cancel</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Controller redirect-এর session message দেখানো হয়। --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mt-3" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row mt-4">
                @can('Customer.ledger.payment')
                    {{-- seeder-এর নতুন casing অনুযায়ী ledger payment permission। --}}
                    {{-- <div class="col-lg-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bx bx-plus-medical me-1"></i>Add Ledger Entry</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('customer.ledger.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Sale Date</label>
                                        <input type="date" name="sale_date" class="form-control"
                                            value="{{ date('Y-m-d') }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Entry Type</label>
                                        <select name="entry_type" id="entry_type" class="form-select" required>
                                            <option value="sale">Product Sale</option>
                                            <option value="payment">Receive Payment</option>
                                        </select>
                                    </div>

                                    <div class="mb-3 sale-entry-field">
                                        <label class="form-label fw-semibold">Product Name</label>
                                        <input type="text" name="product_name" class="form-control"
                                            placeholder="Product Name">
                                    </div>

                                    <div class="row sale-entry-field">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Quantity</label>
                                                <input type="number" name="quantity" class="form-control" value="1"
                                                    min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Unit Price</label>
                                                <input type="number" step="0.01" name="unit_price" class="form-control"
                                                    value="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3 sale-entry-field">
                                        <label class="form-label fw-semibold">Payment Type</label>
                                        <select name="payment_type" id="payment_type" class="form-select" required>
                                            <option value="cash">Cash</option>
                                            <option value="due">Due</option>
                                            <option value="full_payment">Full Payment</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Payment Amount <span
                                                        class="payment-required d-none text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0" name="payment_amount"
                                                    class="form-control" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6 sale-entry-field">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Due Amount</label>
                                                <input type="number" step="0.01" name="due_amount" class="form-control"
                                                    value="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3 sale-entry-field">
                                        <label class="form-label fw-semibold">Employee / Receiver</label>
                                        <select name="employee_id" class="form-select">
                                            <option value="">Select employee</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Received By</label>
                                        <select name="received_by" id="received_by" class="form-select">
                                            <option value="">Select receiver</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3 payment-entry-field d-none">
                                        <label class="form-label fw-semibold">Paid By</label>
                                        <input type="text" name="paid_by" class="form-control"
                                            placeholder="Person who paid">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Invoice No.</label>
                                        <input type="text" name="invoice_no" class="form-control" placeholder="INV-001">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Payment Entry Note</label>
                                        <small class="form-text text-muted">Standalone payment can be used for opening due or
                                            unallocated customer balance.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Notes</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Sale note..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bx bx-save me-1"></i>Save Ledger Entry
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div> --}}
                @endcan
                <div class="col-lg-12">
                    <div class="border-0 shadow-sm card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i>Ledger History -
                                <span class="text-primary">{{ $customer->name }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped table-bordered display align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Date</th>
                                            <th>Invoice</th>
                                            <th>Entry / Product</th>
                                            <th>Qty</th>
                                            <th>Amount</th>
                                            <th>Payment</th>
                                            <th>Due</th>
                                            <th>Payment Type</th>
                                            <th>Method</th>
                                            <th>Employee</th>
                                            <th>Received By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($customer->ledgers as $ledgerIndex => $ledger)
                                            <tr>
                                                <td>{{ $ledgerIndex + 1 }}</td>
                                                <td>{{ $ledger->sale_date }}</td>
                                                <td>{{ $ledger->invoice_no }}</td>
                                                <td>
                                                    @if ($ledger->entry_type === 'payment')
                                                        Payment
                                                        Received{{ $ledger->paid_by ? ' from ' . $ledger->paid_by : '' }}
                                                    @else
                                                        {{ $ledger->product_name }}
                                                    @endif
                                                </td>
                                                <td>{{ $ledger->entry_type === 'payment' ? '—' : $ledger->quantity }}</td>
                                                <td>৳{{ number_format($ledger->total_amount, 2) }}</td>
                                                <td>৳{{ number_format($ledger->payment_amount, 2) }}</td>
                                                <td>৳{{ number_format($ledger->due_amount, 2) }}</td>
                                                <td>
                                                    @if ($ledger->payment_type === 'full_payment')
                                                        <span class="badge bg-success-subtle text-success">Full
                                                            Payment</span>
                                                    @elseif ($ledger->payment_type === 'cash')
                                                        <span class="badge bg-primary-subtle text-primary">Cash</span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-warning">Due</span>
                                                    @endif
                                                </td>
                                                <td>{{ $ledger->payment_method ? ucfirst($ledger->payment_method) : 'N/A' }}
                                                </td>
                                                <td>{{ $ledger->employee ? $ledger->employee->name : 'N/A' }}</td>
                                                <td>{{ $ledger->receiver ? $ledger->receiver->name : 'N/A' }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @if ($ledger->payments->isNotEmpty())
                                                            <details class="w-100">
                                                                <summary class="btn btn-sm btn-outline-secondary">Payment
                                                                    History</summary>
                                                                <div class="mt-2">
                                                                    @foreach ($ledger->payments as $payment)
                                                                        <div class="small border-bottom py-1">
                                                                            {{ $payment->payment_date->format('d M Y') }}:
                                                                            ৳{{ number_format((float) $payment->amount, 2) }}
                                                                            ({{ $payment->receiver->name ?? 'N/A' }})
                                                                            @if ($payment->notes)
                                                                                <span class="text-muted">—
                                                                                    {{ $payment->notes }}</span>
                                                                            @endif
                                                                        </div>
                                                                        @can('Customer.ledger.payment.edit')
                                                                            <details class="mb-2">
                                                                                <summary class="text-primary small">Edit receipt</summary>
                                                                                <form
                                                                                    action="{{ route('customer.ledger.payments.update', $payment->id) }}"
                                                                                    method="POST" class="p-2 mt-1 border rounded bg-light">
                                                                                    @csrf
                                                                                    @method('PUT')
                                                                                    <label class="form-label small"
                                                                                        for="customer_payment_date_{{ $payment->id }}">Payment
                                                                                        Date</label>
                                                                                    <input type="date" name="payment_date"
                                                                                        id="customer_payment_date_{{ $payment->id }}"
                                                                                        class="form-control form-control-sm mb-2"
                                                                                        value="{{ $payment->payment_date->toDateString() }}"
                                                                                        max="{{ now()->toDateString() }}" required>
                                                                                    <label class="form-label small"
                                                                                        for="customer_payment_amount_{{ $payment->id }}">Amount
                                                                                        (৳)</label>
                                                                                    <input type="number" name="amount"
                                                                                        id="customer_payment_amount_{{ $payment->id }}"
                                                                                        class="form-control form-control-sm mb-2"
                                                                                        min="0.01"
                                                                                        max="{{ number_format((float) $ledger->due_amount + (float) $payment->amount, 2, '.', '') }}"
                                                                                        step="0.01"
                                                                                        value="{{ number_format((float) $payment->amount, 2, '.', '') }}"
                                                                                        required>
                                                                                    <label class="form-label small"
                                                                                        for="customer_payment_receiver_{{ $payment->id }}">Received
                                                                                        By</label>
                                                                                    <select name="received_by"
                                                                                        id="customer_payment_receiver_{{ $payment->id }}"
                                                                                        class="form-select form-select-sm mb-2">
                                                                                        <option value="">Select receiver</option>
                                                                                        @foreach ($employees as $employee)
                                                                                            <option value="{{ $employee->id }}"
                                                                                                @selected((int) $payment->received_by === (int) $employee->id)>
                                                                                                {{ $employee->name }}
                                                                                            </option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                    <label class="form-label small"
                                                                                        for="customer_payment_notes_{{ $payment->id }}">Notes</label>
                                                                                    <textarea name="notes" id="customer_payment_notes_{{ $payment->id }}"
                                                                                        class="form-control form-control-sm mb-2" rows="2">{{ $payment->notes }}</textarea>
                                                                                    <button type="submit"
                                                                                        class="btn btn-sm btn-primary w-100">Update Receipt</button>
                                                                                </form>
                                                                            </details>
                                                                        @endcan
                                                                    @endforeach
                                                                </div>
                                                            </details>
                                                        @endif
                                                        @if ($ledger->entry_type === 'sale' && (float) $ledger->due_amount > 0)
                                                            @can('Customer.ledger.payment')
                                                                <details class="w-100">
                                                                    <summary class="btn btn-sm btn-outline-success">Receive
                                                                        Payment</summary>
                                                                    <form
                                                                        action="{{ route('customer.ledger.payments.store', $ledger->id) }}"
                                                                        method="POST" class="mt-2">
                                                                        @csrf
                                                                        <label class="form-label small">Payment Date</label>
                                                                        <input type="date" name="payment_date"
                                                                            class="form-control form-control-sm mb-2"
                                                                            value="{{ date('Y-m-d') }}"
                                                                            max="{{ date('Y-m-d') }}" required>
                                                                        <label class="form-label small">Amount (Due:
                                                                            ৳{{ number_format($ledger->due_amount, 2) }})</label>
                                                                        <input type="number" name="amount"
                                                                            class="form-control form-control-sm mb-2"
                                                                            min="0.01"
                                                                            max="{{ number_format((float) $ledger->due_amount, 2, '.', '') }}"
                                                                            step="0.01" required>
                                                                        <label class="form-label small">Received By</label>
                                                                        <select name="received_by"
                                                                            class="form-select form-select-sm mb-2">
                                                                            <option value="">Select receiver</option>
                                                                            @foreach ($employees as $employee)
                                                                                <option value="{{ $employee->id }}">
                                                                                    {{ $employee->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Payment note"></textarea>
                                                                        <button type="submit"
                                                                            class="btn btn-sm btn-success w-100">Save
                                                                            Payment</button>
                                                                    </form>
                                                                </details>
                                                            @endcan
                                                        @endif
                                                        <a href="{{ route('customer.ledger.invoice', $ledger->id) }}"
                                                            class="btn btn-sm btn-outline-success"
                                                            title="Print invoice or receipt">
                                                            <i class="bx bx-printer"></i>
                                                        </a>
                                                        @can('Customer.ledger.edit')
                                                            <a href="{{ route('customer.ledger.edit', $ledger->id) }}"
                                                                class="btn btn-sm btn-primary" title="Edit ledger entry">
                                                                <i class="bx bx-edit"></i>
                                                            </a>
                                                        @endcan
                                                        @can('Customer.ledger.delete')
                                                            <form action="{{ route('customer.ledger.destroy', $ledger->id) }}"
                                                                method="POST"
                                                                onsubmit="return confirm('Delete this customer ledger entry and its payment history?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    title="Delete ledger entry">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="13" class="text-center py-4 text-muted">No ledger records
                                                    yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const entryType = document.getElementById('entry_type');
            if (!entryType) {
                return;
            }
            const entryForm = entryType.closest('form');
            const paymentAmount = entryForm.querySelector('[name="payment_amount"]');
            const receivedBy = document.getElementById('received_by');
            const paymentType = document.getElementById('payment_type');
            const saleFields = document.querySelectorAll('.sale-entry-field');
            const paymentFields = document.querySelectorAll('.payment-entry-field');
            if (!paymentAmount || !receivedBy || !paymentType) {
                return;
            }

            function updateEntryFields() {
                const isPayment = entryType.value === 'payment';
                saleFields.forEach((field) => field.classList.toggle('d-none', isPayment));
                paymentFields.forEach((field) => field.classList.toggle('d-none', !isPayment));
                paymentAmount.required = isPayment;
                paymentAmount.min = isPayment ? '0.01' : '0';
                receivedBy.required = isPayment;
                paymentType.required = !isPayment;
            }

            entryType.addEventListener('change', updateEntryFields);
            updateEntryFields();
        });
    </script>
@endsection
