@extends('dashboard.dashboard')
@section('title')
    Category List
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
                            <li class="breadcrumb-item active" aria-current="page">Category List</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('category.create') }}" class="btn btn-primary px-3 shadow-sm">
                        <i class="bx bx-plus-circle me-1"></i> Add New Category
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Summary KPI Cards -->
            @php
                $totalCats = $ProductCategories->count();
                $catsWithProducts = $ProductCategories->filter(function($c) {
                    return $c->Products && $c->Products->count() > 0;
                })->count();
                $totalCatProducts = $ProductCategories->sum(function($c) {
                    return $c->Products ? $c->Products->count() : 0;
                });
            @endphp
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-danger shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Categories</p>
                                    <h4 class="my-1 text-danger fw-bold">{{ $totalCats }}</h4>
                                    <p class="mb-0 font-13 text-muted">Configured product groups</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                                    <i class="bx bx-category-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Active Categories</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $catsWithProducts }}</h4>
                                    <p class="mb-0 font-13 text-muted">With assigned products</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-check-double"></i>
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
                                    <p class="mb-0 text-secondary fw-semibold">Total Linked Items</p>
                                    <h4 class="my-1 text-success fw-bold">{{ $totalCatProducts }}</h4>
                                    <p class="mb-0 font-13 text-muted">Products in categories</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-package"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Summary Cards -->

            <!-- Category Directory Table Card -->
            <div class="card radius-10 shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-category text-danger me-2"></i>Product Categories Directory
                        </h5>
                        <p class="text-muted small mb-0">Manage all classification groups for inventory products</p>
                    </div>
                    <a href="{{ route('category.create') }}" class="btn btn-sm btn-outline-danger">
                        <i class="bx bx-plus me-1"></i>New Category
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 70px;">SL</th>
                                    <th>Category Information</th>
                                    <th class="text-center">Associated Products</th>
                                    <th>Created Date</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ProductCategories as $key => $ProductCategory)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light-danger text-danger d-flex align-items-center justify-content-center me-3"
                                                    style="width: 42px; height: 42px; font-weight: 700; font-size: 15px;">
                                                    {{ strtoupper(substr($ProductCategory->category_name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">{{ $ProductCategory->category_name }}</h6>
                                                    <span class="text-muted small">ID: #CAT-{{ str_pad($ProductCategory->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $prodCount = $ProductCategory->Products ? $ProductCategory->Products->count() : 0;
                                            @endphp
                                            @if ($prodCount > 0)
                                                <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill font-13">
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
                                                <i class="bx bx-calendar me-1"></i>{{ $ProductCategory->created_at ? $ProductCategory->created_at->format('d M, Y') : 'N/A' }}
                                            </div>
                                            <div class="text-muted small">
                                                <i class="bx bx-time-five me-1"></i>{{ $ProductCategory->created_at ? $ProductCategory->created_at->format('h:i A') : '' }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <!-- Quick View Details Modal Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal" data-bs-target="#viewCatModal{{ $ProductCategory->id }}"
                                                    title="View Category Details">
                                                    <i class="bx bx-show"></i>
                                                </button>

                                                <!-- Edit Category -->
                                                <a href="{{ route('category.edit', $ProductCategory->id) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Category">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>

                                                <!-- Delete Category -->
                                                <a href="{{ route('category.destroy', $ProductCategory->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete category: {{ $ProductCategory->category_name }}?')"
                                                    title="Delete Category">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                            </div>

                                            <!-- Category Details Modal -->
                                            <div class="modal fade" id="viewCatModal{{ $ProductCategory->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header border-bottom">
                                                            <div class="d-flex align-items-center">
                                                                <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger me-2">
                                                                    <i class="bx bx-category"></i>
                                                                </div>
                                                                <div>
                                                                    <h5 class="modal-title fw-bold text-dark mb-0">Category Details</h5>
                                                                    <span class="text-muted small">ID: #CAT-{{ str_pad($ProductCategory->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body p-4">
                                                            <div class="text-center mb-3">
                                                                <div class="rounded-circle bg-light-danger text-danger d-inline-flex align-items-center justify-content-center mb-2"
                                                                    style="width: 70px; height: 70px; font-weight: 700; font-size: 24px;">
                                                                    {{ strtoupper(substr($ProductCategory->category_name, 0, 2)) }}
                                                                </div>
                                                                <h4 class="fw-bold mb-1">{{ $ProductCategory->category_name }}</h4>
                                                                <span class="badge bg-danger">ID: #CAT-{{ str_pad($ProductCategory->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                            </div>

                                                            <div class="list-group list-group-flush border-top border-bottom my-3">
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-tag me-1"></i> Category Name:</span>
                                                                    <span class="fw-semibold text-dark">{{ $ProductCategory->category_name }}</span>
                                                                </div>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-package me-1"></i> Linked Products:</span>
                                                                    <span class="fw-semibold text-danger">{{ $prodCount }} Items</span>
                                                                </div>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-calendar me-1"></i> Registered At:</span>
                                                                    <span class="fw-semibold text-dark">{{ $ProductCategory->created_at ? $ProductCategory->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                                                </div>
                                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                                    <span class="text-muted"><i class="bx bx-refresh me-1"></i> Last Updated:</span>
                                                                    <span class="fw-semibold text-dark">{{ $ProductCategory->updated_at ? $ProductCategory->updated_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                                                </div>
                                                            </div>

                                                            @if ($ProductCategory->Products && $ProductCategory->Products->count() > 0)
                                                                <h6 class="fw-bold text-secondary mb-2 small text-uppercase">
                                                                    Sample Products in this Category:
                                                                </h6>
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    @foreach ($ProductCategory->Products->take(6) as $p)
                                                                        <span class="badge bg-light-primary text-primary border py-1 px-2 font-12">
                                                                            <i class="bx bx-box me-1"></i>{{ $p->productName }} (৳{{ number_format($p->price, 2) }})
                                                                        </span>
                                                                    @endforeach
                                                                    @if ($ProductCategory->Products->count() > 6)
                                                                        <span class="badge bg-light-secondary text-secondary py-1 px-2 font-12">
                                                                            +{{ $ProductCategory->Products->count() - 6 }} more
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="modal-footer border-top">
                                                            <a href="{{ route('category.edit', $ProductCategory->id) }}" class="btn btn-primary btn-sm">
                                                                <i class="bx bx-edit me-1"></i>Edit Category
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
                                            No categories found. Click "Add New Category" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Category Directory Table Card -->

        </div>
    </div>
@endsection
