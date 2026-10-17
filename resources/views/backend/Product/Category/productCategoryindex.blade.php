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
                    @can('product.category.create') {{-- category তৈরির নতুন permission। --}}
                    <a href="{{ route('category.create') }}" class="px-3 shadow-sm btn btn-primary">
                        <i class="bx bx-plus-circle me-1"></i> Add New Category
                    </a>
                    @endcan
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Summary KPI Cards -->
            <div class="mb-4 row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Categories</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $ProductCategories->count() }}</h4>
                                    <p class="mb-0 font-13 text-muted">Registered in database</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-category"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Categories With Products</p>
                                    @php
                                        $categoriesWithProducts = $ProductCategories
                                            ->filter(function ($category) {
                                                return $category->Products && $category->Products->count() > 0;
                                            })
                                            ->count();
                                    @endphp
                                    <h4 class="my-1 text-success fw-bold">{{ $categoriesWithProducts }}</h4>
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
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Category Products</p>
                                    @php
                                        $totalCategoryProducts = $ProductCategories->sum(function ($category) {
                                            return $category->Products ? $category->Products->count() : 0;
                                        });
                                    @endphp
                                    <h4 class="my-1 text-info fw-bold">{{ $totalCategoryProducts }}</h4>
                                    <p class="mb-0 font-13 text-muted">Linked inventory items</p>
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

            <!-- Category List Table Card -->
            <div class="border-0 shadow-sm card radius-10">
                <div
                    class="py-3 bg-transparent card-header border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-customize text-primary me-2"></i>Category Directory
                        </h5>
                        <p class="mb-0 text-muted small">Manage all registered product categories in the system</p>
                    </div>
                    @can('product.category.create') {{-- category তৈরির নতুন permission। --}}
                    <a href="{{ route('category.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>New Category
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table mb-0 align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 70px;">SL</th>
                                    <th>Category Name</th>
                                    <th class="text-center">Products Count</th>
                                    <th>Created Date</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ProductCategories as $key => $category)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center me-3"
                                                    style="width: 40px; height: 40px; font-weight: 700; font-size: 15px;">
                                                    {{ strtoupper(substr($category->category_name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">{{ $category->category_name }}
                                                    </h6>
                                                    <span class="text-muted small">ID:
                                                        #CT-{{ str_pad($category->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $prodCount = $category->Products ? $category->Products->count() : 0;
                                            @endphp
                                            @if ($prodCount > 0)
                                                <span
                                                    class="px-3 py-2 badge bg-light-success text-success rounded-pill font-13">
                                                    <i class="bx bx-package me-1"></i>{{ $prodCount }}
                                                    {{ Str::plural('Product', $prodCount) }}
                                                </span>
                                            @else
                                                <span
                                                    class="px-3 py-2 badge bg-light-secondary text-secondary rounded-pill font-13">
                                                    0 Products
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-muted small">
                                                <i
                                                    class="bx bx-calendar me-1"></i>{{ $category->created_at ? $category->created_at->format('d M, Y') : 'N/A' }}
                                            </div>
                                            <div class="text-muted small">
                                                <i
                                                    class="bx bx-time-five me-1"></i>{{ $category->created_at ? $category->created_at->format('h:i A') : '' }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="gap-2 d-inline-flex">
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewCategoryModal{{ $category->id }}"
                                                    title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </button>

                                                @can('product.category.edit') {{-- category সম্পাদনার নতুন permission। --}}
                                                <a href="{{ route('category.edit', $category->id) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Edit Category">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>
                                                @endcan

                                                @can('product.category.delete') {{-- category মুছতে নতুন permission। --}}
                                                <a href="{{ route('category.destroy', $category->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-swal-delete
                                                    data-delete-message="Category: {{ $category->category_name }}"
                                                    title="Delete Category">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                                @endcan
                                            </div>

                                            <div class="modal fade" id="viewCategoryModal{{ $category->id }}"
                                                tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header border-bottom">
                                                            <h5 class="modal-title fw-bold text-primary">
                                                                <i class="bx bx-customize me-2"></i>Category Details
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="p-4 modal-body">
                                                            <div class="mb-3 text-center">
                                                                <div class="mb-2 rounded-circle bg-light-primary text-primary d-inline-flex align-items-center justify-content-center"
                                                                    style="width: 70px; height: 70px; font-weight: 700; font-size: 24px;">
                                                                    {{ strtoupper(substr($category->category_name, 0, 2)) }}
                                                                </div>
                                                                <h4 class="mb-1 fw-bold">{{ $category->category_name }}
                                                                </h4>
                                                                <span class="badge bg-primary">Category ID:
                                                                    #CT-{{ str_pad($category->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                            </div>

                                                            <div
                                                                class="my-3 list-group list-group-flush border-top border-bottom">
                                                                <div
                                                                    class="px-0 py-2 list-group-item d-flex justify-content-between align-items-center">
                                                                    <span class="text-muted"><i
                                                                            class="bx bx-tag me-1"></i> Category
                                                                        Name:</span>
                                                                    <span
                                                                        class="fw-semibold text-dark">{{ $category->category_name }}</span>
                                                                </div>
                                                                <div
                                                                    class="px-0 py-2 list-group-item d-flex justify-content-between align-items-center">
                                                                    <span class="text-muted"><i
                                                                            class="bx bx-box me-1"></i> Total
                                                                        Products:</span>
                                                                    <span
                                                                        class="fw-semibold text-primary">{{ $prodCount }}
                                                                        Items</span>
                                                                </div>
                                                                <div
                                                                    class="px-0 py-2 list-group-item d-flex justify-content-between align-items-center">
                                                                    <span class="text-muted"><i
                                                                            class="bx bx-calendar me-1"></i>
                                                                        Created:</span>
                                                                    <span
                                                                        class="fw-semibold text-dark">{{ $category->created_at ? $category->created_at->format('d M, Y') : 'N/A' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top">
                                                            <button type="button" class="btn btn-light"
                                                                data-bs-dismiss="modal">Close</button>
                                                            @can('product.category.edit') {{-- category সম্পাদনার নতুন permission। --}}
                                                            <a href="{{ route('category.edit', $category->id) }}"
                                                                class="btn btn-primary">Edit Category</a>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-5 text-center">
                                            <div class="text-muted">
                                                <i class="mb-2 bx bx-package fs-1 d-block"></i>
                                                <h5 class="mb-2">No categories found</h5>
                                                <p class="mb-0">Create a new category to start organizing your product
                                                    catalog.</p>
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
