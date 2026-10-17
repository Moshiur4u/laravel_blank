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
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">Customer List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $customer->name }} Ledger</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('customer.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <h4 class="mb-1 text-primary fw-bold">{{ $customer->name }}</h4>
                                    @if ($customer->customer_type === 'permanent' && $customer->employee)
                                        <p class="mb-1"><i class="bx bx-user me-1 text-muted"></i>Employee: {{ $customer->employee->name }}</p>
                                    @endif
                                    <p class="mb-1"><i class="bx bx-phone me-1 text-muted"></i>{{ $customer->phone ?? 'N/A' }}</p>
                                    <p class="mb-1"><i class="bx bx-envelope me-1 text-muted"></i>{{ $customer->email ?? 'N/A' }}</p>
                                    <p class="mb-0"><i class="bx bx-map me-1 text-muted"></i>{{ $customer->address ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-7">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="card bg-light-primary border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-primary mb-1">Total Sale</h6>
                                                    <h4 class="mb-0 fw-bold text-primary">৳{{ number_format($totalSales, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-success border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-success mb-1">Payment</h6>
                                                    <h4 class="mb-0 fw-bold text-success">৳{{ number_format($totalPayment, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-danger border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-danger mb-1">Due</h6>
                                                    <h4 class="mb-0 fw-bold text-danger">৳{{ number_format($totalDue, 2) }}</h4>
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

            <div class="row mt-4">
                @can('customer.ledger.payment')
                <div class="col-lg-4">
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
                                    <input type="date" name="sale_date" class="form-control" value="{{ date('Y-m-d') }}" required>
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
                                    <input type="text" name="product_name" class="form-control" placeholder="Product Name">
                                </div>

                                <div class="row sale-entry-field">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Quantity</label>
                                            <input type="number" name="quantity" class="form-control" value="1" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Unit Price</label>
                                            <input type="number" step="0.01" name="unit_price" class="form-control" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 sale-entry-field">
                                    <label class="form-label fw-semibold">Payment Type</label>
                                    <select name="payment_type" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="due">Due</option>
                                        <option value="full_payment">Full Payment</option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Payment Amount <span class="payment-required d-none text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0" name="payment_amount" class="form-control" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6 sale-entry-field">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Due Amount</label>
                                            <input type="number" step="0.01" name="due_amount" class="form-control" value="0">
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
                                    <input type="text" name="paid_by" class="form-control" placeholder="Person who paid">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Invoice No.</label>
                                    <input type="text" name="invoice_no" class="form-control" placeholder="INV-001">
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
                </div>
                @endcan

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-receipt text-primary me-2"></i>Customer Ledger</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($customer->ledgers as $ledger)
                                            <tr>
                                                <td>{{ $ledger->sale_date }}</td>
                                                <td>{{ $ledger->invoice_no }}</td>
                                                <td>
                                                    @if ($ledger->entry_type === 'payment')
                                                        Payment Received{{ $ledger->paid_by ? ' from ' . $ledger->paid_by : '' }}
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
                                                        <span class="badge bg-success-subtle text-success">Full Payment</span>
                                                    @elseif ($ledger->payment_type === 'cash')
                                                        <span class="badge bg-primary-subtle text-primary">Cash</span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-warning">Due</span>
                                                    @endif
                                                </td>
                                                <td>{{ $ledger->payment_method ? ucfirst($ledger->payment_method) : 'N/A' }}</td>
                                                <td>{{ $ledger->employee ? $ledger->employee->name : 'N/A' }}</td>
                                                <td>{{ $ledger->receiver ? $ledger->receiver->name : 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center py-4 text-muted">No ledger records yet.</td>
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
        document.addEventListener('DOMContentLoaded', function () {
            const entryType = document.getElementById('entry_type');
            const paymentAmount = document.querySelector('[name="payment_amount"]');
            const receivedBy = document.getElementById('received_by');
            const saleFields = document.querySelectorAll('.sale-entry-field');
            const paymentFields = document.querySelectorAll('.payment-entry-field');

            function updateEntryFields() {
                const isPayment = entryType.value === 'payment';
                saleFields.forEach((field) => field.classList.toggle('d-none', isPayment));
                paymentFields.forEach((field) => field.classList.toggle('d-none', !isPayment));
                paymentAmount.required = isPayment;
                paymentAmount.min = isPayment ? '0.01' : '0';
                receivedBy.required = isPayment;
            }

            entryType.addEventListener('change', updateEntryFields);
            updateEntryFields();
        });
    </script>
@endsection
