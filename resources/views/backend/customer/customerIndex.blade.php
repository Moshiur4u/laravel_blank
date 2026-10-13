@extends('dashboard.dashboard')
@section('title')
    Customer List
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Customer</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Customer List</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('customer.create') }}" class="btn btn-primary px-3 shadow-sm">
                        <i class="bx bx-plus-circle me-1"></i> Add Customer
                    </a>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Customers</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $customers->count() }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-user"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Sales</p>
                                    <h4 class="my-1 text-success fw-bold">৳{{ number_format($totalSales, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-wallet"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-danger shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Due</p>
                                    <h4 class="my-1 text-danger fw-bold">৳{{ number_format($totalDue, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                                    <i class="bx bx-credit-card"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card radius-10 shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold"><i class="bx bx-user-pin text-primary me-2"></i>Customer Directory</h5>
                        <p class="text-muted small mb-0">Temporary and permanent customer records with payment ledger</p>
                    </div>
                    <a href="{{ route('customer.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>New Customer
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 70px;">SL</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Phone</th>
                                    <th>Payment Status</th>
                                    <th class="text-center" style="width: 170px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $key => $customer)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: 700; font-size: 15px;">
                                                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">{{ $customer->name }}</h6>
                                                    <span class="text-muted small">{{ $customer->customer_code ?? 'No code' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($customer->customer_type === 'permanent')
                                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Permanent</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">Temporary</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($customer->employee)
                                                {{ $customer->employee->name }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $customer->phone ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $ledgerDue = (float) $customer->opening_due
                                                    + $customer->ledgers->sum('total_amount')
                                                    - $customer->ledgers->sum('payment_amount');
                                            @endphp
                                            @if ($ledgerDue > 0)
                                                <span class="badge bg-light-danger text-danger px-3 py-2 rounded-pill">Due: ৳{{ number_format($ledgerDue, 2) }}</span>
                                            @else
                                                <span class="badge bg-light-success text-success px-3 py-2 rounded-pill">Paid</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <a href="{{ route('customer.ledger', $customer->id) }}" class="btn btn-sm btn-outline-info" title="Ledger">
                                                    <i class="bx bx-book-open"></i>
                                                </a>
                                                <a href="{{ route('customer.edit', $customer->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>
                                                <a href="{{ route('customer.destroy', $customer->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete"
                                                    data-swal-delete
                                                    data-delete-message="Customer: {{ $customer->name }}">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bx bx-user-x fs-1 d-block mb-2"></i>
                                                <h5 class="mb-2">No customers found</h5>
                                                <p class="mb-0">Add a temporary or permanent customer to begin tracking sales and payment ledger.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
