@extends('dashboard.dashboard')
@section('title')
    Brand List
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <!-- Breadcrumb -->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Product Manager</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="javascript:;">Product</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Brand List</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('brand.create') }}" class="btn btn-primary px-3 shadow-sm">
                        <i class="bx bx-plus-circle me-1"></i> Add New Brand
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Summary KPI Cards -->
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Brands</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $Brans->count() }}</h4>
                                    <p class="mb-0 font-13 text-muted">Registered in database</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-purchase-tag-alt"></i>
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
                                    <p class="mb-0 text-secondary fw-semibold">Brands With Products</p>
                                    @php
                                        $brandsWithProducts = $Brans->filter(function($b) {
                                            return $b->Products && $b->Products->count() > 0;
                                        })->count();
                                    @endphp
                                    <h4 class="my-1 text-success fw-bold">{{ $brandsWithProducts }}</h4>
                                    <p class="mb-0 font-13 text-muted">Active in catalog</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-check-shield"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Associated Items</p>
                                    @php
                                        $totalBrandProducts = $Brans->sum(function($b) {
                                            return $b->Products ? $b->Products->count() : 0;
                                        });
                                    @endphp
                                    <h4 class="my-1 text-info fw-bold">{{ $totalBrandProducts }}</h4>
                                    <p class="mb-0 font-13 text-muted">Total linked products</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto">
                                    <i class="bx bx-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Summary Cards -->

            <!-- Brand List Table Card -->
            <div class="card radius-10 shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-customize text-primary me-2"></i>Brand Directory
                        </h5>
                        <p class="text-muted small mb-0">Manage all registered product brands in the system</p>
                    </div>
                    <a href="{{ route('brand.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>New Brand
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 70px;">SL</th>
                                    <th>Brand Name</th>
                                    <th class="text-center">Products Count</th>
                                    <th>Created Date</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($Brans as $key => $brand)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center me-3"
                                                    style="width: 40px; height: 40px; font-weight: 700; font-size: 15px;">
                                                    {{ strtoupper(substr($brand->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">{{ $brand->name }}</h6>
                                                    <span class="text-muted small">ID: #BR-{{ str_pad($brand->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $prodCount = $brand->Products ? $brand->Products->count() : 0;
                                            @endphp
                                            @if ($prodCount > 0)
                                                <span class="badge bg-light-success text-success px-3 py-2 rounded-pill font-13">
                                                    <i class="bx bx-package me-1"></i>{{ $prodCount }} {{ Str::plural('Product', $prodCount) }}
                                                </span>
                                            @else
                                                <span class="badge bg-light-secondary text-secondary px-3 py-2 rounded-pill font-13">
                                                    0 Products
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-muted small">
                                                <i class="bx bx-calendar me-1"></i>{{ $brand->created_at ? $brand->created_at->format('d M, Y') : 'N/A' }}
                                            </div>
                                            <div class="text-muted small">
                                                <i class="bx bx-time-five me-1"></i>{{ $brand->created_at ? $brand->created_at->format('h:i A') : '' }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <!-- Quick View Details Modal Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal" data-bs-target="#viewBrandModal{{ $brand->id }}"
                                                    title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </button>

                                                <!-- Edit Button -->
                                                <a href="{{ route('brand.edit', $brand->id) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Brand">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>

                                                <!-- Delete Button -->
                                                <a href="{{ route('brand.destroy', $brand->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete brand: {{ $brand->name }}?')"
                                                    title="Delete Brand">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                            </div>

                                            <!-- Brand Details Modal -->
                                            <div class="modal fade" id="viewBrandModal{{ $brand->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header border-bottom">
                                                            <h5 class="modal-title fw-bold text-primary">
                                                                <i class="bx bx-customize me-2"></i>Brand Details
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <div class="text-center mb-3">
                                                                <div class="rounded-circle bg-light-primary text-primary d-inline-flex align-items-center justify-content-center mb-2"
                                                                    style="width: 70px; height: 70px; font-weight: 700; font-size: 24px;">
                                                                    {{ strtoupper(substr($brand->name, 0, 2)) }}
                                                                </div>
                                                                <h4 class="fw-bold mb-1">{{ $brand->name }}</h4>
                                                                <span class="badge bg-primary">Brand ID: #BR-{{ str_pad($brand->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                            </div>

                                                            <div class="list-group list-group-flush border-top border-bottom my-3">
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-tag me-1"></i> Brand Name:</span>
                                                                    <span class="fw-semibold text-dark">{{ $brand->name }}</span>
                                                                </div>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-box me-1"></i> Total Products:</span>
                                                                    <span class="fw-semibold text-primary">{{ $prodCount }} Items</span>
                                                                </div>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-calendar me-1"></i> Registered At:</span>
                                                                    <span class="fw-semibold text-dark">{{ $brand->created_at ? $brand->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                                                </div>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-refresh me-1"></i> Last Updated:</span>
                                                                    <span class="fw-semibold text-dark">{{ $brand->updated_at ? $brand->updated_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top">
                                                            <a href="{{ route('brand.edit', $brand->id) }}" class="btn btn-primary btn-sm">
                                                                <i class="bx bx-edit me-1"></i>Edit Brand
                                                            </a>
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Details Modal -->
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bx bx-info-circle fs-3 d-block mb-2"></i>
                                            No brands found in the system. Click "Add New Brand" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Brand List Table Card -->

        </div>
    </div>
@endsection
