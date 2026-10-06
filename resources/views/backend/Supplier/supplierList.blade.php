@extends('dashboard.dashboard')
@section('title')
    Supplier List
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
                            <li class="breadcrumb-item active" aria-current="page">Supplier List</li>
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
            <h6 class="mb-0 text-uppercase">All Supplier Information</h6>
            <hr>

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
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5">
                            <table id="myTable" class="table display table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Logo</th>
                                        <th>Supplier Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- এখানে আমরা ফর ইচ লুপ এর মাধ্যমে সাপ্লায়ার দের তথ্য দেখাবো -->
                                    @foreach ($suppliers as $key => $supplier)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <!-- এখানে আমরা ইফ কন্ডিশন লজিক ব্যবহার করে ব্লেডে লোগো দেখাবো -->
                                                @if ($supplier->logo)
                                                    <img src="{{ asset('uploads/suppliers/' . $supplier->logo) }}"
                                                        alt="{{ $supplier->name }}" class="product-img-2"
                                                        style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;" />
                                                @else
                                                    {{-- লোগো না থাকলে সাপ্লায়ার নামের প্রথম অক্ষর দিয়ে অ্যাভাটার দেখাবে --}}
                                                    @php
                                                        $colors = ['#0d6efd','#6f42c1','#d63384','#dc3545','#fd7e14','#198754','#0dcaf0','#6610f2'];
                                                        $colorIndex = $supplier->id % count($colors);
                                                    @endphp
                                                    <div style="width: 45px; height: 45px; border-radius: 50%; background: {{ $colors[$colorIndex] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 16px; text-transform: uppercase;">
                                                        {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ $supplier->name }}</td>
                                            <td>{{ $supplier->email }}</td>
                                            <td>{{ $supplier->phone }}</td>
                                            <td>{{ $supplier->address }}</td>
                                            <td>
                                                <!-- এখানে আমরা বাটন ব্যবহার করে সাপ্লায়ার এডিট এবং ডিলিট করবো -->
                                                <a href="{{ route('supplier.edit', $supplier->id) }}"
                                                    class="btn btn-success btn-small"><i class="bx bx-edit"></i>
                                                    Ledger</a>
                                                <a href="{{ route('supplier.edit', $supplier->id) }}"
                                                    class="btn btn-primary btn-small"><i class="bx bx-edit"></i>
                                                    Edit</a>

                                                <a href="{{ route('supplier.destroy', $supplier->id) }}"
                                                    class="btn btn-danger btn-icon"
                                                    onclick="return confirm('Are you sure you want to delete this supplier?')">
                                                    <i class="bx bx-trash"></i> Delete
                                                </a>
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
