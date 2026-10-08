@extends('dashboard.dashboard')
@section('title')
    Supplier List & Details
@endsection

@section('content')
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            <!-- start-content -->

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Suppliers</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Supplier List & Ledger Details</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('supplier.create') }}" class="btn btn-primary"><i class="bx bx-plus-medical"></i>
                            Add New Supplier</a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            {{-- Summary Cards --}}
            @php
                $allTotal = 0;
                $allPaid = 0;
                $allDue = 0;
                foreach ($suppliers as $s) {
                    $allTotal += $s->supplierledgers->sum('total');
                    $allPaid += $s->supplierledgers->sum('payment');
                    $allDue += $s->supplierledgers->sum('due');
                }
            @endphp
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Suppliers</p>
                                    <h4 class="my-1 text-info">{{ $suppliers->count() }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto">
                                    <i class="bx bxs-group"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Purchase</p>
                                    <h4 class="my-1 text-primary">৳{{ number_format($allTotal, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Paid</p>
                                    <h4 class="my-1 text-success">৳{{ number_format($allPaid, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-danger mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Due</p>
                                    <h4 class="my-1 text-danger">৳{{ number_format($allDue, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                                    <i class="bx bx-wallet"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- সাকসেস মেসেজ দেখানো হবে --}}
            @if (session('success'))
                <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="font-35 text-white"><i class="bx bxs-check-circle"></i></div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-white">{{ session('success') }}</h6>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 text-uppercase fw-bold"><i class="bx bx-list-ul me-1"></i> Supplier Details & Ledger Summary</h6>
                    <span class="badge bg-primary">{{ $suppliers->count() }} Suppliers</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5">
                            <table id="myTable" class="table display table-striped align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Supplier Details</th>
                                        <th>Contact</th>
                                        <th>Address</th>
                                        <th>Total Purchase</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($suppliers as $key => $supplier)
                                        @php
                                            $total = $supplier->supplierledgers->sum('total');
                                            $payment = $supplier->supplierledgers->sum('payment');
                                            $due = $supplier->supplierledgers->sum('due');
                                            $ledgerCount = $supplier->supplierledgers->count();
                                        @endphp
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if ($supplier->logo)
                                                        <img src="{{ asset('uploads/suppliers/' . $supplier->logo) }}"
                                                            alt="{{ $supplier->name }}" class="product-img-2"
                                                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;" />
                                                    @else
                                                        @php
                                                            $colors = [
                                                                '#0d6efd',
                                                                '#6f42c1',
                                                                '#d63384',
                                                                '#dc3545',
                                                                '#fd7e14',
                                                                '#198754',
                                                                '#0dcaf0',
                                                                '#6610f2',
                                                            ];
                                                            $colorIndex = $supplier->id % count($colors);
                                                        @endphp
                                                        <div
                                                            style="width: 45px; height: 45px; border-radius: 50%; background: {{ $colors[$colorIndex] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 16px; text-transform: uppercase;">
                                                            {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">
                                                            <a href="{{ route('supplier.ledger', $supplier->id) }}" class="text-dark">
                                                                {{ $supplier->name }}
                                                            </a>
                                                        </h6>
                                                        <small class="text-muted"><i class="bx bx-receipt"></i> {{ $ledgerCount }} Transactions</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><i class="bx bx-envelope text-primary me-1"></i>{{ $supplier->email }}</div>
                                                <div><i class="bx bx-phone text-success me-1"></i>{{ $supplier->phone }}</div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><i class="bx bx-map me-1"></i>{{ $supplier->address }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-primary">৳{{ number_format($total, 2) }}</strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">৳{{ number_format($payment, 2) }}</strong>
                                            </td>
                                            <td>
                                                @if ($due > 0)
                                                    <span class="badge bg-danger fs-6">৳{{ number_format($due, 2) }}</span>
                                                @else
                                                    <span class="badge bg-success fs-6">৳0.00</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    {{-- লিঙ্ক টু সাপ্লায়ার লেজার অ্যান্ড ডিটেইলস --}}
                                                    <a href="{{ route('supplier.ledger', $supplier->id) }}"
                                                        class="btn btn-sm btn-info text-white" title="Supplier Details & Ledger">
                                                        <i class="bx bx-file"></i> Ledger
                                                    </a>
                                                    <a href="{{ route('supplier.edit', $supplier->id) }}"
                                                        class="btn btn-sm btn-primary" title="Edit Supplier">
                                                        <i class="bx bx-edit"></i> Edit
                                                    </a>
                                                    <a href="{{ route('supplier.destroy', $supplier->id) }}"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this supplier?')"
                                                        title="Delete Supplier">
                                                        <i class="bx bx-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end-content -->
        </div>
    </div>
    <!--end page wrapper -->
@endsection
