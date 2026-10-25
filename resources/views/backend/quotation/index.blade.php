@extends('dashboard.dashboard')
@section('title', 'All Quotations')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumb -->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Quotation</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">All Quotations</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('quotation.create') }}" class="btn btn-primary px-3 shadow-sm">
                        <i class="bx bx-plus-circle me-1"></i> Get Quotation
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
                    <div class="text-white">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm radius-10">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-file text-primary me-2"></i>Quotation Records</h5>
                        <span class="badge bg-light-primary text-primary px-3 py-2">Total: {{ $quotations->count() }}</span>
                    </div>

                    <div class="table-responsive">
                        <table id="myTable" class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Ref No</th>
                                    <th>Date</th>
                                    <th>Customer Name</th>
                                    <th>Phone</th>
                                    <th class="text-end">Total Amount</th>
                                    <th>Created By</th>
                                    <th class="text-center" style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($quotations as $index => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('quotation.show', $item->id) }}" class="fw-bold text-primary">
                                                {{ $item->quotation_no }}
                                            </a>
                                        </td>
                                        <td>{{ $item->quotation_date->format('d M Y') }}</td>
                                        <td class="fw-semibold text-dark">{{ $item->customer_name }}</td>
                                        <td>{{ $item->customer_phone }}</td>
                                        <td class="text-end fw-bold text-dark">৳{{ number_format((float)$item->total_amount, 2) }}</td>
                                        <td>{{ $item->user->name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <a href="{{ route('quotation.show', $item->id) }}" class="btn btn-sm btn-outline-primary" title="View / Print Quotation">
                                                    <i class="bx bx-printer"></i>
                                                </a>
                                                <form action="{{ route('quotation.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this quotation?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Quotation">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bx bx-file-blank fs-1 d-block mb-2 text-secondary"></i>
                                            No quotation records found. Click <strong>"Get Quotation"</strong> to create one.
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
